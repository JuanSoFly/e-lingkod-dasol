<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Services\DocumentSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;

class DocumentSearchController extends Controller
{
    use AuthorizesRequests;

    private DocumentSearchService $documentSearchService;

    public function __construct(DocumentSearchService $documentSearchService)
    {
        $this->documentSearchService = $documentSearchService;
        $this->middleware('auth');
    }

    /**
     * Perform document search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'search_term' => 'nullable|string|max:255',
                'document_types' => 'nullable|array',
                'document_types.*' => 'string|in:id_card,passport,birth_certificate,diploma,license,contract,other',
                'employee_ids' => 'nullable|array',
                'employee_ids.*' => 'integer|exists:employees,id',
                'departments' => 'nullable|array',
                'departments.*' => 'string|max:100',
                'employment_status' => 'nullable|array',
                'employment_status.*' => 'string|in:active,inactive,terminated,resigned',
                'uploaded_from' => 'nullable|date',
                'uploaded_to' => 'nullable|date|after_or_equal:uploaded_from',
                'min_file_size' => 'nullable|integer|min:0',
                'max_file_size' => 'nullable|integer|min:0|gte:min_file_size',
                'mime_types' => 'nullable|array',
                'mime_types.*' => 'string|max:100',
                'uploaded_by' => 'nullable|array',
                'uploaded_by.*' => 'integer|exists:users,id',
                'sort_by' => 'nullable|string|in:file_name,document_type,uploaded_at,file_size,employee_name,department,relevance',
                'sort_direction' => 'nullable|string|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
                'include_content' => 'nullable|boolean',
            ]);

            $results = $this->documentSearchService->searchDocuments($validated, auth()->user());

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Search completed successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Document search API error', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching documents'
            ], 500);
        }
    }

    /**
     * Get search suggestions for autocomplete
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'term' => 'required|string|min:2|max:100',
                'limit' => 'nullable|integer|min:1|max:50'
            ]);

            $suggestions = $this->documentSearchService->getSearchSuggestions(
                $validated['term'],
                auth()->user(),
                $validated['limit'] ?? 10
            );

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'message' => 'Suggestions retrieved successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Document search suggestions error', [
                'user_id' => auth()->id(),
                'term' => $request->input('term'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting suggestions'
            ], 500);
        }
    }

    /**
     * Get available filter options
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function filterOptions(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $options = [
                'document_types' => $this->documentSearchService->getAvailableDocumentTypes(),
                'departments' => $this->documentSearchService->getAvailableDepartments($user),
                'employment_statuses' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive', 
                    'terminated' => 'Terminated',
                    'resigned' => 'Resigned'
                ],
                'mime_types' => [
                    'application/pdf' => 'PDF Documents',
                    'image/jpeg' => 'JPEG Images',
                    'image/jpg' => 'JPG Images',
                    'image/png' => 'PNG Images'
                ],
                'sort_options' => [
                    'uploaded_at' => 'Upload Date',
                    'file_name' => 'File Name',
                    'document_type' => 'Document Type',
                    'file_size' => 'File Size',
                    'employee_name' => 'Employee Name',
                    'department' => 'Department',
                    'relevance' => 'Relevance'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $options,
                'message' => 'Filter options retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Document search filter options error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting filter options'
            ], 500);
        }
    }

    /**
     * Index document content for search
     *
     * @param Request $request
     * @param EmployeeDocument $document
     * @return JsonResponse
     */
    public function indexDocument(Request $request, EmployeeDocument $document): JsonResponse
    {
        try {
            $this->authorize('view', $document);

            $success = $this->documentSearchService->indexDocumentContent($document);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document content indexed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Document content could not be indexed'
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Document indexing error', [
                'user_id' => auth()->id(),
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while indexing the document'
            ], 500);
        }
    }

    /**
     * Bulk index multiple documents
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkIndex(Request $request): JsonResponse
    {
        try {
            // Only allow HR Admin and Super Admin to perform bulk operations
            if (!in_array(auth()->user()->role, ['hr_admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions for bulk operations'
                ], 403);
            }

            $validated = $request->validate([
                'document_ids' => 'required|array|min:1|max:100',
                'document_ids.*' => 'integer|exists:employee_documents,id'
            ]);

            // Verify user has access to all specified documents
            $authorizedDocuments = [];
            foreach ($validated['document_ids'] as $documentId) {
                $document = EmployeeDocument::find($documentId);
                if ($document && auth()->user()->can('view', $document)) {
                    $authorizedDocuments[] = $documentId;
                }
            }

            if (empty($authorizedDocuments)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents found or insufficient permissions'
                ], 422);
            }

            $results = $this->documentSearchService->bulkIndexDocuments($authorizedDocuments);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => "Bulk indexing completed. {$results['indexed']} documents indexed, {$results['failed']} failed."
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Bulk document indexing error', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk indexing'
            ], 500);
        }
    }

    /**
     * Get document content extract
     *
     * @param Request $request
     * @param EmployeeDocument $document
     * @return JsonResponse
     */
    public function getDocumentContent(Request $request, EmployeeDocument $document): JsonResponse
    {
        try {
            $this->authorize('view', $document);

            $content = $this->documentSearchService->extractDocumentContent($document);

            return response()->json([
                'success' => true,
                'data' => [
                    'document_id' => $document->id,
                    'content' => $content,
                    'content_length' => $content ? strlen($content) : 0,
                    'has_content' => !empty($content)
                ],
                'message' => 'Document content retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Document content retrieval error', [
                'user_id' => auth()->id(),
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving document content'
            ], 500);
        }
    }

    /**
     * Clear search cache
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            // Only allow HR Admin and Super Admin to clear cache
            if (!in_array(auth()->user()->role, ['hr_admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to clear cache'
                ], 403);
            }

            $this->documentSearchService->clearSearchCache();

            Log::info('Document search cache cleared', [
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Search cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Clear search cache error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while clearing cache'
            ], 500);
        }
    }

    /**
     * Get search statistics and analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            // Only allow HR Admin and Super Admin to view analytics
            if (!in_array(auth()->user()->role, ['hr_admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view analytics'
                ], 403);
            }

            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'department' => 'nullable|string|max:100',
            ]);

            // Basic analytics for document search system
            $query = EmployeeDocument::query()
                ->with(['employee:id,department,employment_status'])
                ->select([
                    'id', 'employee_id', 'document_type', 'file_size', 
                    'mime_type', 'uploaded_at', 'content_indexed_at'
                ]);

            if (!empty($validated['date_from'])) {
                $query->where('uploaded_at', '>=', $validated['date_from']);
            }

            if (!empty($validated['date_to'])) {
                $query->where('uploaded_at', '<=', $validated['date_to']);
            }

            if (!empty($validated['department'])) {
                $query->whereHas('employee', function($q) use ($validated) {
                    $q->where('department', $validated['department']);
                });
            }

            $documents = $query->get();

            $analytics = [
                'total_documents' => $documents->count(),
                'indexed_documents' => $documents->whereNotNull('content_indexed_at')->count(),
                'indexing_coverage' => $documents->count() > 0 
                    ? round(($documents->whereNotNull('content_indexed_at')->count() / $documents->count()) * 100, 2)
                    : 0,
                'document_types' => $documents->groupBy('document_type')->map->count(),
                'departments' => $documents->groupBy('employee.department')->map->count(),
                'file_types' => $documents->groupBy('mime_type')->map->count(),
                'total_storage' => $documents->sum('file_size'),
                'average_file_size' => $documents->avg('file_size'),
                'upload_trends' => $documents->groupBy(function($doc) {
                    return $doc->uploaded_at->format('Y-m');
                })->map->count()->take(12),
                'search_performance' => [
                    'total_searchable_content' => $documents->whereNotNull('content_indexed_at')->count(),
                    'content_extraction_success_rate' => $documents->count() > 0 
                        ? round(($documents->whereNotNull('content_indexed_at')->count() / $documents->count()) * 100, 2)
                        : 0,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Analytics retrieved successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Document search analytics error', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving analytics'
            ], 500);
        }
    }
}