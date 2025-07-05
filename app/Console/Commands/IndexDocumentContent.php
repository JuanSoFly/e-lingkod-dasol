<?php

namespace App\Console\Commands;

use App\Models\EmployeeDocument;
use App\Services\DocumentSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IndexDocumentContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:index 
                            {--batch-size=10 : Number of documents to process in each batch}
                            {--document-id= : Index a specific document by ID}
                            {--reindex : Re-index all documents, including those already indexed}
                            {--document-type= : Index only documents of specified type}
                            {--from-date= : Index documents uploaded from this date (Y-m-d format)}
                            {--to-date= : Index documents uploaded to this date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index document content for full-text search capabilities';

    private DocumentSearchService $documentSearchService;

    public function __construct(DocumentSearchService $documentSearchService)
    {
        parent::__construct();
        $this->documentSearchService = $documentSearchService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting document content indexing...');

        try {
            $batchSize = (int) $this->option('batch-size');
            $documentId = $this->option('document-id');
            $reindex = $this->option('reindex');
            $documentType = $this->option('document-type');
            $fromDate = $this->option('from-date');
            $toDate = $this->option('to-date');

            // Index specific document
            if ($documentId) {
                return $this->indexSpecificDocument($documentId);
            }

            // Build query for batch processing
            $query = EmployeeDocument::query();

            // Apply filters
            if ($documentType) {
                $query->where('document_type', $documentType);
            }

            if ($fromDate) {
                $query->where('uploaded_at', '>=', $fromDate);
            }

            if ($toDate) {
                $query->where('uploaded_at', '<=', $toDate);
            }

            // Only process unindexed documents unless reindex is specified
            if (!$reindex) {
                $query->whereNull('content_indexed_at');
            }

            $totalDocuments = $query->count();

            if ($totalDocuments === 0) {
                $this->info('No documents found to index.');
                return 0;
            }

            $this->info("Found {$totalDocuments} documents to index.");

            // Process in batches
            $processed = 0;
            $successful = 0;
            $failed = 0;
            $errors = [];

            $progressBar = $this->output->createProgressBar($totalDocuments);
            $progressBar->start();

            $query->chunk($batchSize, function ($documents) use (
                &$processed, &$successful, &$failed, &$errors, $progressBar
            ) {
                foreach ($documents as $document) {
                    try {
                        $result = $this->documentSearchService->indexDocumentContent($document);
                        
                        if ($result) {
                            $successful++;
                            // Update the database with indexing information
                            $document->update([
                                'content_indexed_at' => now(),
                                'content_hash' => $this->generateContentHash($document)
                            ]);
                        } else {
                            $failed++;
                            $errors[] = "Document {$document->id}: Content extraction failed";
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Document {$document->id}: " . $e->getMessage();
                        
                        Log::error('Document indexing error', [
                            'document_id' => $document->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }

                    $processed++;
                    $progressBar->advance();

                    // Memory management for large batches
                    if ($processed % 100 === 0) {
                        gc_collect_cycles();
                    }
                }
            });

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->displayResults($processed, $successful, $failed, $errors);

            Log::info('Document indexing completed', [
                'total_processed' => $processed,
                'successful' => $successful,
                'failed' => $failed,
                'batch_size' => $batchSize
            ]);

            return $failed > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('An error occurred during document indexing: ' . $e->getMessage());
            
            Log::error('Document indexing command error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Index a specific document
     */
    private function indexSpecificDocument(string $documentId): int
    {
        $document = EmployeeDocument::find($documentId);

        if (!$document) {
            $this->error("Document with ID {$documentId} not found.");
            return 1;
        }

        $this->info("Indexing document: {$document->file_name}");

        try {
            $result = $this->documentSearchService->indexDocumentContent($document);

            if ($result) {
                $document->update([
                    'content_indexed_at' => now(),
                    'content_hash' => $this->generateContentHash($document)
                ]);

                $this->info("Document indexed successfully.");
                return 0;
            } else {
                $this->error("Failed to extract content from document.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error indexing document: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Display indexing results
     */
    private function displayResults(int $processed, int $successful, int $failed, array $errors): void
    {
        $this->info("Document indexing completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $processed],
                ['Successfully Indexed', $successful],
                ['Failed', $failed],
                ['Success Rate', $processed > 0 ? round(($successful / $processed) * 100, 2) . '%' : '0%']
            ]
        );

        if (!empty($errors)) {
            $this->warn("Errors encountered:");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->line("  • {$error}");
            }

            if (count($errors) > 10) {
                $remaining = count($errors) - 10;
                $this->line("  ... and {$remaining} more errors (check logs for details)");
            }
        }
    }

    /**
     * Generate content hash for tracking changes
     */
    private function generateContentHash(EmployeeDocument $document): string
    {
        return hash('sha256', $document->file_path . $document->file_size . $document->uploaded_at);
    }
}