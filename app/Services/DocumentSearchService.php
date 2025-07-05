<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class DocumentSearchService
{
    /**
     * Cache duration in minutes for search results
     */
    private const CACHE_DURATION = 15;

    /**
     * Cache duration for document content indexing
     */
    private const INDEX_CACHE_DURATION = 60;

    /**
     * Maximum file size for text extraction (in bytes)
     */
    private const MAX_EXTRACTION_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Supported document types for search
     */
    private const DOCUMENT_TYPES = [
        'id_card' => 'ID Card',
        'passport' => 'Passport',
        'birth_certificate' => 'Birth Certificate',
        'diploma' => 'Diploma',
        'license' => 'License',
        'contract' => 'Contract',
        'other' => 'Other'
    ];

    /**
     * Perform comprehensive document search
     *
     * @param array $criteria Search criteria
     * @param User $user Current user for permission filtering
     * @return array Search results with metadata
     */
    public function searchDocuments(array $criteria, User $user): array
    {
        try {
            // Generate cache key based on criteria and user permissions
            $cacheKey = $this->generateCacheKey($criteria, $user);
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($criteria, $user) {
                $query = $this->buildSearchQuery($criteria, $user);
                
                // Execute search with pagination
                $results = $query->paginate($criteria['per_page'] ?? 20);
                
                // Enhance results with content matching and relevance scoring
                $enhancedResults = $this->enhanceSearchResults($results, $criteria);
                
                return [
                    'documents' => $enhancedResults['data'],
                    'pagination' => [
                        'current_page' => $results->currentPage(),
                        'last_page' => $results->lastPage(),
                        'per_page' => $results->perPage(),
                        'total' => $results->total(),
                    ],
                    'statistics' => $this->generateSearchStatistics($enhancedResults['data']),
                    'filters_applied' => $this->getAppliedFilters($criteria),
                    'search_time' => microtime(true) - LARAVEL_START,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Document search error', [
                'criteria' => $criteria,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Build the search query based on criteria and permissions
     */
    private function buildSearchQuery(array $criteria, User $user): Builder
    {
        $query = EmployeeDocument::query()
            ->with(['employee:id,employee_number,first_name,last_name,department,employment_status', 'uploader:id,name'])
            ->select([
                'employee_documents.*',
                DB::raw('CONCAT(employees.first_name, " ", employees.last_name) as employee_name'),
                DB::raw('employees.department as employee_department'),
                DB::raw('employees.employment_status as employee_status')
            ])
            ->join('employees', 'employee_documents.employee_id', '=', 'employees.id');

        // Apply permission-based filtering
        $query = $this->applyPermissionFilters($query, $user);

        // Apply text search
        if (!empty($criteria['search_term'])) {
            $query = $this->applyTextSearch($query, $criteria['search_term']);
        }

        // Apply document type filter
        if (!empty($criteria['document_types'])) {
            $query->whereIn('employee_documents.document_type', $criteria['document_types']);
        }

        // Apply employee filter
        if (!empty($criteria['employee_ids'])) {
            $query->whereIn('employee_documents.employee_id', $criteria['employee_ids']);
        }

        // Apply department filter
        if (!empty($criteria['departments'])) {
            $query->whereIn('employees.department', $criteria['departments']);
        }

        // Apply employment status filter
        if (!empty($criteria['employment_status'])) {
            $query->whereIn('employees.employment_status', $criteria['employment_status']);
        }

        // Apply date range filters
        if (!empty($criteria['uploaded_from'])) {
            $query->where('employee_documents.uploaded_at', '>=', $criteria['uploaded_from']);
        }

        if (!empty($criteria['uploaded_to'])) {
            $query->where('employee_documents.uploaded_at', '<=', $criteria['uploaded_to']);
        }

        // Apply file size filters
        if (!empty($criteria['min_file_size'])) {
            $query->where('employee_documents.file_size', '>=', $criteria['min_file_size']);
        }

        if (!empty($criteria['max_file_size'])) {
            $query->where('employee_documents.file_size', '<=', $criteria['max_file_size']);
        }

        // Apply MIME type filter
        if (!empty($criteria['mime_types'])) {
            $query->whereIn('employee_documents.mime_type', $criteria['mime_types']);
        }

        // Apply uploader filter
        if (!empty($criteria['uploaded_by'])) {
            $query->whereIn('employee_documents.uploaded_by', $criteria['uploaded_by']);
        }

        // Apply sorting
        $sortBy = $criteria['sort_by'] ?? 'uploaded_at';
        $sortDirection = $criteria['sort_direction'] ?? 'desc';
        
        if ($sortBy === 'relevance' && !empty($criteria['search_term'])) {
            // Custom relevance sorting will be applied in enhanceSearchResults
            $query->orderBy('employee_documents.uploaded_at', 'desc');
        } else {
            $query->orderBy($this->mapSortField($sortBy), $sortDirection);
        }

        return $query;
    }

    /**
     * Apply permission-based filtering
     */
    private function applyPermissionFilters(Builder $query, User $user): Builder
    {
        $userRole = $user->role ?? 'employee';
        
        switch ($userRole) {
            case 'super_admin':
            case 'hr_admin':
                // Full access to all documents
                break;
                
            case 'department_head':
                // Access to documents of employees in their department
                if ($user->employee && $user->employee->department) {
                    $query->where('employees.department', $user->employee->department);
                } else {
                    // If no department, restrict to own documents only
                    $query->where('employee_documents.employee_id', $user->employee->id ?? 0);
                }
                break;
                
            case 'employee':
            default:
                // Only access to own documents
                $query->where('employee_documents.employee_id', $user->employee->id ?? 0);
                break;
        }

        return $query;
    }

    /**
     * Apply text search with full-text and content matching
     */
    private function applyTextSearch(Builder $query, string $searchTerm): Builder
    {
        // Parse search term for advanced operators
        $parsedSearch = $this->parseSearchTerm($searchTerm);
        
        $query->where(function ($q) use ($parsedSearch) {
            // Search in file names
            foreach ($parsedSearch['include_terms'] as $term) {
                $q->orWhere('employee_documents.file_name', 'LIKE', "%{$term}%");
            }
            
            // Search in employee names
            foreach ($parsedSearch['include_terms'] as $term) {
                $q->orWhere(DB::raw('CONCAT(employees.first_name, " ", employees.last_name)'), 'LIKE', "%{$term}%");
            }
            
            // Search in employee numbers
            foreach ($parsedSearch['include_terms'] as $term) {
                $q->orWhere('employees.employee_number', 'LIKE', "%{$term}%");
            }
            
            // Search in document types
            foreach ($parsedSearch['include_terms'] as $term) {
                $q->orWhere('employee_documents.document_type', 'LIKE', "%{$term}%");
            }
            
            // Search in departments
            foreach ($parsedSearch['include_terms'] as $term) {
                $q->orWhere('employees.department', 'LIKE', "%{$term}%");
            }

            // MySQL Full-text search on indexed fields
            if (!empty($parsedSearch['fulltext_terms'])) {
                $fulltextTerm = implode(' ', $parsedSearch['fulltext_terms']);
                $q->orWhereRaw('MATCH(employee_documents.file_name) AGAINST(? IN BOOLEAN MODE)', [$fulltextTerm]);
            }
        });

        // Apply exclude terms
        foreach ($parsedSearch['exclude_terms'] as $excludeTerm) {
            $query->where('employee_documents.file_name', 'NOT LIKE', "%{$excludeTerm}%")
                  ->where(DB::raw('CONCAT(employees.first_name, " ", employees.last_name)'), 'NOT LIKE', "%{$excludeTerm}%");
        }

        return $query;
    }

    /**
     * Parse search term for boolean operators and phrase matching
     */
    private function parseSearchTerm(string $searchTerm): array
    {
        $includTerms = [];
        $excludeTerms = [];
        $phraseTerms = [];
        $fulltextTerms = [];

        // Handle quoted phrases
        if (preg_match_all('/"([^"]+)"/', $searchTerm, $matches)) {
            $phraseTerms = $matches[1];
            $searchTerm = preg_replace('/"[^"]+"/', '', $searchTerm);
        }

        // Handle exclude terms (preceded by -)
        if (preg_match_all('/\-(\w+)/', $searchTerm, $matches)) {
            $excludeTerms = $matches[1];
            $searchTerm = preg_replace('/\-\w+/', '', $searchTerm);
        }

        // Handle boolean operators and remaining terms
        $words = preg_split('/\s+/', trim($searchTerm), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, function($word) {
            return !in_array(strtolower($word), ['and', 'or', 'not']);
        });

        $includTerms = array_merge($words, $phraseTerms);
        $fulltextTerms = $words;

        return [
            'include_terms' => $includTerms,
            'exclude_terms' => $excludeTerms,
            'phrase_terms' => $phraseTerms,
            'fulltext_terms' => $fulltextTerms,
        ];
    }

    /**
     * Enhance search results with content matching and relevance scoring
     */
    private function enhanceSearchResults($results, array $criteria): array
    {
        $enhancedData = [];
        
        foreach ($results->items() as $document) {
            $enhanced = $document->toArray();
            
            // Calculate relevance score
            $enhanced['relevance_score'] = $this->calculateRelevanceScore($document, $criteria);
            
            // Extract document content if needed
            if (!empty($criteria['include_content'])) {
                $enhanced['extracted_content'] = $this->extractDocumentContent($document);
            }
            
            // Add content highlights
            if (!empty($criteria['search_term'])) {
                $enhanced['highlights'] = $this->generateHighlights($document, $criteria['search_term']);
            }
            
            // Add document metadata
            $enhanced['metadata'] = $this->getDocumentMetadata($document);
            
            $enhancedData[] = $enhanced;
        }

        // Sort by relevance if requested
        if (($criteria['sort_by'] ?? '') === 'relevance' && !empty($criteria['search_term'])) {
            usort($enhancedData, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
        }

        return ['data' => $enhancedData];
    }

    /**
     * Calculate relevance score for search results
     */
    private function calculateRelevanceScore(EmployeeDocument $document, array $criteria): float
    {
        if (empty($criteria['search_term'])) {
            return 1.0;
        }

        $score = 0.0;
        $searchTerm = strtolower($criteria['search_term']);
        
        // File name relevance (highest weight)
        if (stripos($document->file_name, $searchTerm) !== false) {
            $score += 5.0;
            if (stripos($document->file_name, $searchTerm) === 0) {
                $score += 2.0; // Bonus for starting with search term
            }
        }

        // Employee name relevance
        $employeeName = strtolower($document->employee->first_name . ' ' . $document->employee->last_name);
        if (stripos($employeeName, $searchTerm) !== false) {
            $score += 3.0;
        }

        // Employee number relevance
        if (stripos($document->employee->employee_number, $searchTerm) !== false) {
            $score += 2.0;
        }

        // Document type relevance
        if (stripos($document->document_type, $searchTerm) !== false) {
            $score += 1.5;
        }

        // Department relevance
        if (stripos($document->employee->department, $searchTerm) !== false) {
            $score += 1.0;
        }

        // Recency bonus (newer documents get higher scores)
        $daysSinceUpload = Carbon::parse($document->uploaded_at)->diffInDays(now());
        $recencyBonus = max(0, 1 - ($daysSinceUpload / 365)); // Decreases over a year
        $score += $recencyBonus;

        return round($score, 2);
    }

    /**
     * Extract text content from documents using OCR and text extraction
     */
    public function extractDocumentContent(EmployeeDocument $document): ?string
    {
        try {
            // Check cache first
            $cacheKey = "document_content_{$document->id}";
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // Check file size limit
            if ($document->file_size > self::MAX_EXTRACTION_SIZE) {
                Log::info('Document too large for content extraction', [
                    'document_id' => $document->id,
                    'file_size' => $document->file_size
                ]);
                return null;
            }

            $content = null;
            $filePath = Storage::path($document->file_path);
            
            if (!file_exists($filePath)) {
                Log::warning('Document file not found for content extraction', [
                    'document_id' => $document->id,
                    'file_path' => $document->file_path
                ]);
                return null;
            }

            // Extract content based on MIME type
            switch ($document->mime_type) {
                case 'application/pdf':
                    $content = $this->extractPdfContent($filePath);
                    break;
                    
                case 'image/jpeg':
                case 'image/jpg':
                case 'image/png':
                    $content = $this->extractImageTextOCR($filePath);
                    break;
                    
                default:
                    Log::info('Unsupported MIME type for content extraction', [
                        'document_id' => $document->id,
                        'mime_type' => $document->mime_type
                    ]);
                    break;
            }

            // Cache the result (even if null)
            Cache::put($cacheKey, $content, self::INDEX_CACHE_DURATION);
            
            return $content;
            
        } catch (\Exception $e) {
            Log::error('Content extraction error', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extract text content from PDF files
     */
    private function extractPdfContent(string $filePath): ?string
    {
        // Try using pdftotext command if available
        $output = '';
        $returnCode = 0;
        
        if (function_exists('exec')) {
            $escapedPath = escapeshellarg($filePath);
            exec("pdftotext {$escapedPath} -", $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                return implode("\n", $output);
            }
        }

        // Fallback: Try using Smalot\PdfParser if available
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
            } catch (\Exception $e) {
                Log::warning('PDF parsing failed', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return null;
    }

    /**
     * Extract text from images using OCR
     */
    private function extractImageTextOCR(string $filePath): ?string
    {
        // Try using Tesseract OCR if available
        if (function_exists('exec')) {
            $output = '';
            $returnCode = 0;
            $escapedPath = escapeshellarg($filePath);
            
            exec("tesseract {$escapedPath} stdout 2>/dev/null", $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                return implode("\n", $output);
            }
        }

        // Log that OCR is not available
        Log::info('OCR not available for image text extraction', [
            'file_path' => $filePath
        ]);
        
        return null;
    }

    /**
     * Generate search result highlights
     */
    private function generateHighlights(EmployeeDocument $document, string $searchTerm): array
    {
        $highlights = [];
        $searchTerm = strtolower($searchTerm);
        
        // Highlight in file name
        if (stripos($document->file_name, $searchTerm) !== false) {
            $highlights['file_name'] = $this->highlightText($document->file_name, $searchTerm);
        }
        
        // Highlight in employee name
        $employeeName = $document->employee->first_name . ' ' . $document->employee->last_name;
        if (stripos($employeeName, $searchTerm) !== false) {
            $highlights['employee_name'] = $this->highlightText($employeeName, $searchTerm);
        }
        
        return $highlights;
    }

    /**
     * Highlight search terms in text
     */
    private function highlightText(string $text, string $searchTerm): string
    {
        return preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<mark>$1</mark>', $text);
    }

    /**
     * Get document metadata
     */
    private function getDocumentMetadata(EmployeeDocument $document): array
    {
        return [
            'document_type_label' => self::DOCUMENT_TYPES[$document->document_type] ?? $document->document_type,
            'file_size_human' => $this->formatFileSize($document->file_size),
            'uploaded_human' => Carbon::parse($document->uploaded_at)->diffForHumans(),
            'extension' => pathinfo($document->file_name, PATHINFO_EXTENSION),
            'is_image' => in_array($document->mime_type, ['image/jpeg', 'image/jpg', 'image/png']),
            'is_pdf' => $document->mime_type === 'application/pdf',
        ];
    }

    /**
     * Generate search statistics
     */
    private function generateSearchStatistics(array $documents): array
    {
        $stats = [
            'total_results' => count($documents),
            'document_types' => [],
            'departments' => [],
            'file_types' => [],
            'upload_date_range' => [],
            'average_file_size' => 0,
        ];

        if (empty($documents)) {
            return $stats;
        }

        $fileSizes = [];
        $uploadDates = [];

        foreach ($documents as $doc) {
            // Document types
            $docType = $doc['document_type'];
            $stats['document_types'][$docType] = ($stats['document_types'][$docType] ?? 0) + 1;

            // Departments
            $dept = $doc['employee']['department'] ?? 'Unknown';
            $stats['departments'][$dept] = ($stats['departments'][$dept] ?? 0) + 1;

            // File types (by extension)
            $extension = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
            $stats['file_types'][$extension] = ($stats['file_types'][$extension] ?? 0) + 1;

            // File sizes and dates for calculations
            if ($doc['file_size']) {
                $fileSizes[] = $doc['file_size'];
            }
            if ($doc['uploaded_at']) {
                $uploadDates[] = $doc['uploaded_at'];
            }
        }

        // Calculate averages and ranges
        if (!empty($fileSizes)) {
            $stats['average_file_size'] = $this->formatFileSize(array_sum($fileSizes) / count($fileSizes));
        }

        if (!empty($uploadDates)) {
            sort($uploadDates);
            $stats['upload_date_range'] = [
                'earliest' => Carbon::parse($uploadDates[0])->format('Y-m-d'),
                'latest' => Carbon::parse(end($uploadDates))->format('Y-m-d'),
            ];
        }

        return $stats;
    }

    /**
     * Get applied filters from criteria
     */
    private function getAppliedFilters(array $criteria): array
    {
        $filters = [];

        if (!empty($criteria['search_term'])) {
            $filters['search_term'] = $criteria['search_term'];
        }

        if (!empty($criteria['document_types'])) {
            $filters['document_types'] = array_map(function($type) {
                return self::DOCUMENT_TYPES[$type] ?? $type;
            }, $criteria['document_types']);
        }

        if (!empty($criteria['departments'])) {
            $filters['departments'] = $criteria['departments'];
        }

        if (!empty($criteria['employment_status'])) {
            $filters['employment_status'] = $criteria['employment_status'];
        }

        if (!empty($criteria['uploaded_from']) || !empty($criteria['uploaded_to'])) {
            $filters['date_range'] = [
                'from' => $criteria['uploaded_from'] ?? null,
                'to' => $criteria['uploaded_to'] ?? null,
            ];
        }

        return $filters;
    }

    /**
     * Get search suggestions based on partial input
     */
    public function getSearchSuggestions(string $partialTerm, User $user, int $limit = 10): array
    {
        $cacheKey = "search_suggestions_" . md5($partialTerm . $user->id);
        
        return Cache::remember($cacheKey, 10, function () use ($partialTerm, $user, $limit) {
            $suggestions = [];
            
            // Get document type suggestions
            foreach (self::DOCUMENT_TYPES as $key => $label) {
                if (stripos($label, $partialTerm) !== false) {
                    $suggestions[] = [
                        'type' => 'document_type',
                        'value' => $key,
                        'label' => $label,
                        'category' => 'Document Types'
                    ];
                }
            }

            // Get employee name suggestions
            $query = Employee::select(['id', 'first_name', 'last_name', 'employee_number'])
                ->where(function($q) use ($partialTerm) {
                    $q->where(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$partialTerm}%")
                      ->orWhere('employee_number', 'LIKE', "%{$partialTerm}%");
                })
                ->limit($limit);

            // Apply permission filtering
            $query = $this->applyPermissionFilters($query, $user);
            
            foreach ($query->get() as $employee) {
                $suggestions[] = [
                    'type' => 'employee',
                    'value' => $employee->id,
                    'label' => $employee->first_name . ' ' . $employee->last_name . ' (' . $employee->employee_number . ')',
                    'category' => 'Employees'
                ];
            }

            return array_slice($suggestions, 0, $limit);
        });
    }

    /**
     * Index document content for better search performance
     */
    public function indexDocumentContent(EmployeeDocument $document): bool
    {
        try {
            $content = $this->extractDocumentContent($document);
            
            if ($content) {
                // Store indexed content in cache or database
                Cache::put("document_content_{$document->id}", $content, self::INDEX_CACHE_DURATION);
                
                Log::info('Document content indexed', [
                    'document_id' => $document->id,
                    'content_length' => strlen($content)
                ]);
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Document indexing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Bulk index multiple documents
     */
    public function bulkIndexDocuments(array $documentIds): array
    {
        $results = ['indexed' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($documentIds as $documentId) {
            try {
                $document = EmployeeDocument::find($documentId);
                if ($document && $this->indexDocumentContent($document)) {
                    $results['indexed']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Document {$documentId}: " . $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Generate cache key for search results
     */
    private function generateCacheKey(array $criteria, User $user): string
    {
        $keyData = [
            'criteria' => $criteria,
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_department' => $user->employee->department ?? null,
        ];
        
        return 'document_search_' . md5(serialize($keyData));
    }

    /**
     * Map sort field names to database columns
     */
    private function mapSortField(string $sortBy): string
    {
        $mapping = [
            'file_name' => 'employee_documents.file_name',
            'document_type' => 'employee_documents.document_type',
            'uploaded_at' => 'employee_documents.uploaded_at',
            'file_size' => 'employee_documents.file_size',
            'employee_name' => DB::raw('CONCAT(employees.first_name, " ", employees.last_name)'),
            'department' => 'employees.department',
        ];

        return $mapping[$sortBy] ?? 'employee_documents.uploaded_at';
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(?int $bytes): string
    {
        if (!$bytes) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Clear document search cache
     */
    public function clearSearchCache(): void
    {
        $pattern = 'document_search_*';
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
        
        Log::info('Document search cache cleared');
    }

    /**
     * Get available document types for filtering
     */
    public function getAvailableDocumentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }

    /**
     * Get available departments for filtering
     */
    public function getAvailableDepartments(User $user): array
    {
        $query = Employee::select('department')
            ->whereNotNull('department')
            ->distinct();

        // Apply permission filtering
        $userRole = $user->role ?? 'employee';
        if ($userRole === 'department_head' && $user->employee && $user->employee->department) {
            $query->where('department', $user->employee->department);
        } elseif ($userRole === 'employee') {
            $query->where('id', $user->employee->id ?? 0);
        }

        return $query->pluck('department')->toArray();
    }
}