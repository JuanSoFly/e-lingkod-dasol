<?php

namespace App\Services;

use App\Models\DocumentLink;
use App\Models\DocumentVersion;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentManagementService
{
    /**
     * Create a new document version
     */
    public function createDocumentVersion(
        EmployeeDocument $document,
        UploadedFile $file,
        User $uploader,
        string $changeReason = null,
        string $versionNotes = null,
        bool $autoApprove = false
    ): DocumentVersion {
        return DB::transaction(function () use ($document, $file, $uploader, $changeReason, $versionNotes, $autoApprove) {
            // Store the file
            $filePath = $file->store('documents/versions', 'public');
            $fileHash = hash_file('sha256', $file->getPathname());
            $checksum = hash_file('md5', $file->getPathname());

            // Create version data
            $versionData = [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_hash' => $fileHash,
                'checksum' => $checksum,
                'change_reason' => $changeReason,
                'version_notes' => $versionNotes,
                'approval_status' => $autoApprove ? 'approved' : 'pending',
            ];

            if ($autoApprove) {
                $versionData['approved_by'] = $uploader->id;
                $versionData['approved_at'] = now();
            }

            $version = $document->createVersion($versionData, $uploader);

            Log::info('Document version created', [
                'document_id' => $document->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'uploader' => $uploader->id,
                'auto_approved' => $autoApprove
            ]);

            return $version;
        });
    }

    /**
     * Approve a document version
     */
    public function approveDocumentVersion(
        DocumentVersion $version,
        User $approver,
        string $notes = null
    ): bool {
        return DB::transaction(function () use ($version, $approver, $notes) {
            $approved = $version->approve($approver, $notes);

            if ($approved) {
                // Set as current version
                $version->setAsCurrent();

                // Update the main document
                $version->originalDocument->update([
                    'file_name' => $version->file_name,
                    'file_path' => $version->file_path,
                    'file_size' => $version->file_size,
                    'mime_type' => $version->mime_type,
                ]);

                Log::info('Document version approved', [
                    'version_id' => $version->id,
                    'approver' => $approver->id,
                    'notes' => $notes
                ]);
            }

            return $approved;
        });
    }

    /**
     * Rollback to a previous version
     */
    public function rollbackToVersion(
        DocumentVersion $version,
        User $user,
        string $reason = null
    ): bool {
        if (!$version->canRollback()) {
            Log::warning('Attempted rollback to non-rollbackable version', [
                'version_id' => $version->id,
                'user' => $user->id
            ]);
            return false;
        }

        $success = $version->rollback($user, $reason);

        if ($success) {
            Log::info('Document rolled back to previous version', [
                'version_id' => $version->id,
                'user' => $user->id,
                'reason' => $reason
            ]);
        }

        return $success;
    }

    /**
     * Create automatic links for a document
     */
    public function createAutomaticLinks(EmployeeDocument $document, User $creator): array
    {
        $links = DocumentLink::createAutomaticLinks($document, $creator);

        Log::info('Automatic links created for document', [
            'document_id' => $document->id,
            'links_created' => count($links),
            'creator' => $creator->id
        ]);

        return $links;
    }

    /**
     * Create a manual link between records
     */
    public function createManualLink(
        Model $source,
        Model $target,
        string $linkType,
        User $creator,
        array $options = []
    ): ?DocumentLink {
        $options['is_automatic'] = false;
        $options['confidence_score'] = 1.0; // Manual links have full confidence

        $link = DocumentLink::createLink($source, $target, $linkType, $creator, $options);

        if ($link) {
            Log::info('Manual document link created', [
                'link_id' => $link->id,
                'source_type' => get_class($source),
                'source_id' => $source->id,
                'target_type' => get_class($target),
                'target_id' => $target->id,
                'link_type' => $linkType,
                'creator' => $creator->id
            ]);

            // Create bidirectional link if needed
            if ($link->is_bidirectional) {
                $link->createBidirectionalLink();
            }
        }

        return $link;
    }

    /**
     * Validate document links for consistency
     */
    public function validateDocumentLinks(Employee $employee): array
    {
        $results = [
            'total_links' => 0,
            'valid_links' => 0,
            'broken_links' => 0,
            'pending_links' => 0,
            'issues' => []
        ];

        // Get all links for employee's documents
        $documentIds = $employee->documents()->pluck('id');
        $links = DocumentLink::where(function ($query) use ($documentIds) {
            $query->where('source_type', EmployeeDocument::class)
                  ->whereIn('source_id', $documentIds);
        })->orWhere(function ($query) use ($documentIds) {
            $query->where('target_type', EmployeeDocument::class)
                  ->whereIn('target_id', $documentIds);
        })->get();

        $results['total_links'] = $links->count();

        foreach ($links as $link) {
            if ($link->status === 'pending_validation') {
                $results['pending_links']++;
                continue;
            }

            $isValid = $link->checkConsistency();
            
            if ($isValid) {
                $results['valid_links']++;
            } else {
                $results['broken_links']++;
                $results['issues'][] = [
                    'link_id' => $link->id,
                    'link_type' => $link->link_type,
                    'issue' => 'Link consistency check failed'
                ];
            }
        }

        Log::info('Document links validated for employee', [
            'employee_id' => $employee->id,
            'results' => $results
        ]);

        return $results;
    }

    /**
     * Get document management statistics
     */
    public function getDocumentStatistics(): array
    {
        return [
            'total_documents' => EmployeeDocument::count(),
            'total_versions' => DocumentVersion::count(),
            'pending_versions' => DocumentVersion::pending()->count(),
            'approved_versions' => DocumentVersion::approved()->count(),
            'total_links' => DocumentLink::count(),
            'active_links' => DocumentLink::active()->count(),
            'automatic_links' => DocumentLink::automatic()->count(),
            'manual_links' => DocumentLink::manual()->count(),
            'high_confidence_links' => DocumentLink::highConfidence()->count(),
            'needs_validation' => DocumentLink::needsValidation()->count(),
        ];
    }

    /**
     * Clean up old document versions
     */
    public function cleanupOldVersions(int $keepVersions = 10): array
    {
        $documents = EmployeeDocument::all();
        $totalCleaned = 0;

        foreach ($documents as $document) {
            $cleaned = DocumentVersion::cleanupOldVersions($document->id, $keepVersions);
            $totalCleaned += $cleaned;
        }

        Log::info('Document version cleanup completed', [
            'documents_processed' => $documents->count(),
            'versions_cleaned' => $totalCleaned,
            'keep_versions' => $keepVersions
        ]);

        return [
            'documents_processed' => $documents->count(),
            'versions_cleaned' => $totalCleaned
        ];
    }

    /**
     * Generate document compliance report
     */
    public function generateComplianceReport(): array
    {
        $employees = Employee::with(['documents', 'leaveApplications', 'performanceReviews', 'education', 'workExperiences'])->get();
        $report = [
            'total_employees' => $employees->count(),
            'employees_with_documents' => 0,
            'total_documents' => 0,
            'linked_documents' => 0,
            'compliance_rates' => [],
            'low_compliance_employees' => []
        ];

        foreach ($employees as $employee) {
            $compliance = $employee->getDocumentComplianceSummary();
            
            if ($compliance['total_documents'] > 0) {
                $report['employees_with_documents']++;
                $report['total_documents'] += $compliance['total_documents'];
                $report['linked_documents'] += $compliance['linked_documents'];
                $report['compliance_rates'][] = $compliance['compliance_rate'];

                if ($compliance['compliance_rate'] < 50) {
                    $report['low_compliance_employees'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                        'compliance_rate' => $compliance['compliance_rate'],
                        'total_documents' => $compliance['total_documents'],
                        'linked_documents' => $compliance['linked_documents']
                    ];
                }
            }
        }

        $report['average_compliance_rate'] = count($report['compliance_rates']) > 0 
            ? array_sum($report['compliance_rates']) / count($report['compliance_rates']) 
            : 0;

        $report['overall_compliance_rate'] = $report['total_documents'] > 0 
            ? ($report['linked_documents'] / $report['total_documents']) * 100 
            : 0;

        return $report;
    }

    /**
     * Process automatic linking for all documents
     */
    public function processAutomaticLinking(User $creator): array
    {
        $documents = EmployeeDocument::whereDoesntHave('sourceLinks')->get();
        $results = [
            'processed' => 0,
            'links_created' => 0,
            'errors' => []
        ];

        foreach ($documents as $document) {
            try {
                $links = $this->createAutomaticLinks($document, $creator);
                $results['processed']++;
                $results['links_created'] += count($links);
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ];
                Log::error('Error processing automatic linking', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Compare two document versions
     */
    public function compareVersions(DocumentVersion $version1, DocumentVersion $version2): array
    {
        $comparison = $version1->compareWith($version2);

        Log::info('Document versions compared', [
            'version1_id' => $version1->id,
            'version2_id' => $version2->id,
            'differences_found' => count($comparison)
        ]);

        return $comparison;
    }

    /**
     * Calculate storage usage by document versions
     */
    public function calculateStorageUsage(): array
    {
        $usage = [
            'total_files' => DocumentVersion::count(),
            'total_size_bytes' => DocumentVersion::sum('file_size'),
            'by_document' => [],
            'by_mime_type' => []
        ];

        // Calculate usage by document
        $documents = EmployeeDocument::withCount('versions')
                                   ->with('versions:original_document_id,file_size')
                                   ->get();

        foreach ($documents as $document) {
            $documentUsage = $document->versions->sum('file_size');
            $usage['by_document'][] = [
                'document_id' => $document->id,
                'document_name' => $document->file_name,
                'version_count' => $document->versions_count,
                'total_size_bytes' => $documentUsage,
                'total_size_human' => $this->formatBytes($documentUsage)
            ];
        }

        // Calculate usage by MIME type
        $mimeTypes = DocumentVersion::select('mime_type')
                                  ->selectRaw('COUNT(*) as count')
                                  ->selectRaw('SUM(file_size) as total_size')
                                  ->groupBy('mime_type')
                                  ->get();

        foreach ($mimeTypes as $mimeType) {
            $usage['by_mime_type'][] = [
                'mime_type' => $mimeType->mime_type,
                'count' => $mimeType->count,
                'total_size_bytes' => $mimeType->total_size,
                'total_size_human' => $this->formatBytes($mimeType->total_size)
            ];
        }

        $usage['total_size_human'] = $this->formatBytes($usage['total_size_bytes']);

        return $usage;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}