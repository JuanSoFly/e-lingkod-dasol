<?php

namespace App\Jobs;

use App\Models\EmployeeDocument;
use App\Services\DocumentSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexDocumentContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    private EmployeeDocument $document;

    /**
     * Create a new job instance.
     */
    public function __construct(EmployeeDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentSearchService $documentSearchService): void
    {
        try {
            Log::info('Starting document content indexing job', [
                'document_id' => $this->document->id,
                'file_name' => $this->document->file_name,
                'file_size' => $this->document->file_size
            ]);

            // Check if document has already been indexed recently
            if ($this->document->content_indexed_at && 
                $this->document->content_indexed_at->diffInHours(now()) < 24) {
                Log::info('Document already indexed recently, skipping', [
                    'document_id' => $this->document->id,
                    'last_indexed' => $this->document->content_indexed_at
                ]);
                return;
            }

            // Perform content extraction and indexing
            $result = $documentSearchService->indexDocumentContent($this->document);

            if ($result) {
                // Update the document with indexing information
                $this->document->update([
                    'content_indexed_at' => now(),
                    'content_hash' => $this->generateContentHash(),
                    'search_metadata' => $this->generateSearchMetadata()
                ]);

                Log::info('Document content indexed successfully', [
                    'document_id' => $this->document->id,
                    'indexed_at' => now()
                ]);
            } else {
                Log::warning('Document content indexing failed - no content extracted', [
                    'document_id' => $this->document->id,
                    'file_name' => $this->document->file_name,
                    'mime_type' => $this->document->mime_type
                ]);

                // Mark as processed even if no content was extracted
                $this->document->update([
                    'content_indexed_at' => now(),
                    'content_hash' => $this->generateContentHash(),
                    'search_metadata' => ['extraction_failed' => true, 'reason' => 'No content extracted']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Document content indexing job failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark the attempt
            $this->document->update([
                'search_metadata' => array_merge(
                    $this->document->search_metadata ?? [],
                    [
                        'last_indexing_error' => $e->getMessage(),
                        'last_error_at' => now()->toISOString(),
                        'failed_attempts' => ($this->document->search_metadata['failed_attempts'] ?? 0) + 1
                    ]
                )
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Document content indexing job permanently failed', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update document with permanent failure status
        $this->document->update([
            'search_metadata' => array_merge(
                $this->document->search_metadata ?? [],
                [
                    'indexing_failed_permanently' => true,
                    'final_error' => $exception->getMessage(),
                    'failed_at' => now()->toISOString(),
                    'total_attempts' => $this->attempts()
                ]
            )
        ]);
    }

    /**
     * Generate content hash for tracking changes
     */
    private function generateContentHash(): string
    {
        return hash('sha256', 
            $this->document->file_path . 
            $this->document->file_size . 
            $this->document->uploaded_at->timestamp
        );
    }

    /**
     * Generate search metadata
     */
    private function generateSearchMetadata(): array
    {
        return [
            'indexed_at' => now()->toISOString(),
            'file_type' => pathinfo($this->document->file_name, PATHINFO_EXTENSION),
            'mime_type' => $this->document->mime_type,
            'file_size' => $this->document->file_size,
            'employee_department' => $this->document->employee->department ?? null,
            'document_type' => $this->document->document_type,
            'indexing_version' => '1.0'
        ];
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'document-indexing',
            'document:' . $this->document->id,
            'employee:' . $this->document->employee_id
        ];
    }
}