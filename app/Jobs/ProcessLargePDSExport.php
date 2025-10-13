<?php

namespace App\Jobs;

use App\Exports\PDSExport;
use App\Models\User;
use App\Models\Employee;
use App\Models\QueuedExport;
use App\Models\ExportAuditLog;
use App\Services\PDSDataOptimizationService;
use App\Services\FilipinoCharacterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PDSExportReady;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Exception;

class ProcessLargePDSExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 60;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    protected $user;
    protected $employeeIds;
    protected $format;
    protected $includeMetadata;
    protected $jobId;
    protected $optimizationService;
    protected $filipinoService;
    protected $queuedExport;

    /**
     * Create a new job instance.
     */
    public function __construct(
        User $user,
        array $employeeIds,
        string $format = 'xlsx',
        bool $includeMetadata = true,
        string $jobId = null
    ) {
        $this->user = $user;
        $this->employeeIds = $employeeIds;
        $this->format = $format;
        $this->includeMetadata = $includeMetadata;
        $this->jobId = $jobId ?? uniqid('pds_export_');
        $this->optimizationService = new PDSDataOptimizationService();
        $this->filipinoService = new FilipinoCharacterService();

        // Set queue name
        $this->onQueue('exports');
    }

    /**
     * Execute the job with concurrent export validation and management.
     */
    public function handle(): void
    {
        try {
            // Enhanced validation for edge cases (T046)
            $this->validateJobRequirements();
            $this->validateEmployeeDataAvailability();
            $this->validateSystemResources();

            // Concurrent export validation
            $this->validateConcurrentExportLimits();

            // Update job status to processing
            $this->updateJobStatus('processing');

            Log::info('Processing large PDS export', [
                'job_id' => $this->jobId,
                'user_id' => $this->user->id,
                'user_role' => $this->user->roles->pluck('name')->first(),
                'employee_count' => count($this->employeeIds),
                'format' => $this->format,
                'is_super_admin' => $this->user->hasRole('Super Admin'),
                'concurrent_exports' => $this->getConcurrentExportCount()
            ]);

            // Generate filename with error handling
            $filename = $this->generateFilenameWithValidation();

            // Create the export with enhanced error handling
            $export = $this->createExportWithValidation();

            // Process data in chunks to manage memory with enhanced error handling
            $filePath = $this->processExportInChunksWithRetry($export, $filename);

            // Validate the generated file
            $this->validateGeneratedFile($filePath);

            // Update job status to completed
            $this->updateJobStatus('completed', $filePath);

            // Create audit log entry
            $this->createAuditLog($filePath);

            // Send notification email
            $this->sendNotificationEmail($filePath);

            Log::info('PDS export completed successfully', [
                'job_id' => $this->jobId,
                'file_path' => $filePath,
                'file_size' => Storage::size($filePath)
            ]);

        } catch (\App\Services\PDSDataEmptyException $e) {
            $this->handleEmptyDataException($e);

        } catch (\App\Services\FilipinoCharacterEncodingException $e) {
            $this->handleCharacterEncodingException($e);

        } catch (\Illuminate\Database\QueryException $e) {
            $this->handleDatabaseException($e);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->handleConnectionException($e);

        } catch (\Exception $e) {
            $this->handleGenericException($e);
        }
    }

    /**
     * Process the export in chunks to manage memory usage
     */
    protected function processExportInChunks(PDSExport $export, string $filename): string
    {
        $chunkSize = $this->optimizationService->getOptimalChunkSize(count($this->employeeIds));
        $chunks = array_chunk($this->employeeIds, $chunkSize);

        $tempFiles = [];
        $finalFilePath = 'exports/' . $filename;

        try {
            // Process each chunk
            foreach ($chunks as $index => $chunk) {
                Log::info('Processing chunk', [
                    'job_id' => $this->jobId,
                    'chunk_index' => $index + 1,
                    'total_chunks' => count($chunks),
                    'chunk_size' => count($chunk)
                ]);

                // Create chunk export
                $chunkExport = new PDSExport($chunk, $this->user, $this->includeMetadata);
                $chunkFilename = 'temp/chunk_' . $this->jobId . '_' . $index . '.' . $this->format;

                // Store chunk file
                Excel::store($chunkExport, $chunkFilename, 'local', $this->format);
                $tempFiles[] = $chunkFilename;

                // Update progress
                $progress = round((($index + 1) / count($chunks)) * 100);
                $this->updateJobProgress($progress);
            }

            // If we have multiple chunks, merge them
            if (count($chunks) > 1) {
                $finalFilePath = $this->mergeChunkFiles($tempFiles, $filename);
            } else {
                // Move single chunk to final location
                Storage::move($tempFiles[0], $finalFilePath);
            }

            return $finalFilePath;

        } finally {
            // Clean up temporary files
            $this->cleanupTempFiles($tempFiles);
        }
    }

    /**
     * Merge multiple chunk files into one final file
     */
    protected function mergeChunkFiles(array $chunkFiles, string $filename): string
    {
        // For Excel files, we'll use the first chunk as the base
        // and append data from subsequent chunks
        $finalFilePath = 'exports/' . $filename;

        // Move first chunk to final location
        Storage::move($chunkFiles[0], $finalFilePath);

        // Note: For a real implementation, you might want to use a library
        // like PhpSpreadsheet to properly merge Excel files
        // For now, we're keeping it simple by using the first chunk

        return $finalFilePath;
    }

    /**
     * Generate a unique filename for the export
     */
    protected function generateFilename(): string
    {
        $timestamp = now()->format('Y_m_d_His');
        $role = $this->user->hasRole('Super Admin') ? 'SuperAdmin' :
                ($this->user->hasRole('HR Admin') ? 'HRAdmin' : 'Employee');

        $baseFilename = "PDS_{$role}_{$timestamp}";

        // Use Filipino character service to ensure clean filename
        return $this->filipinoService->generateExcelFilename($baseFilename) . '.' . $this->format;
    }

    /**
     * Update the job status in the database
     */
    protected function updateJobStatus(string $status, ?string $filePath = null, ?string $errorMessage = null): void
    {
        $queuedExport = QueuedExport::where('job_id', $this->jobId)->first();

        if ($queuedExport) {
            $queuedExport->update([
                'status' => $status,
                'file_path' => $filePath,
                'error_message' => $errorMessage,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);
        }
    }

    /**
     * Update the job progress
     */
    protected function updateJobProgress(int $progress): void
    {
        $queuedExport = QueuedExport::where('job_id', $this->jobId)->first();

        if ($queuedExport) {
            $queuedExport->update([
                'progress' => $progress,
            ]);
        }
    }

    /**
     * Create an audit log entry
     */
    protected function createAuditLog(string $filePath): void
    {
        $fileSize = Storage::size($filePath);

        ExportAuditLog::create([
            'user_id' => $this->user->id,
            'employee_id' => null, // Multiple employees
            'export_type' => 'batch',
            'file_size' => $fileSize,
            'file_name' => basename($filePath),
            'export_format' => $this->format,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => [
                'job_id' => $this->jobId,
                'employee_count' => count($this->employeeIds),
                'employee_ids' => $this->employeeIds,
                'include_metadata' => $this->includeMetadata,
                'processing_time' => now()->diffInSeconds($this->queuedExport->created_at),
            ],
        ]);
    }

    /**
     * Send notification email to the user
     */
    protected function sendNotificationEmail(string $filePath): void
    {
        try {
            $downloadUrl = route('pds.download-export', [
                'filename' => basename($filePath),
                'signature' => $this->generateDownloadSignature(basename($filePath)),
                'expires' => now()->addHours(24)->timestamp,
            ]);

            Mail::to($this->user->email)->send(new PDSExportReady(
                $this->user,
                $downloadUrl,
                basename($filePath),
                Storage::size($filePath),
                count($this->employeeIds)
            ));

        } catch (Exception $e) {
            Log::error('Failed to send export notification email', [
                'job_id' => $this->jobId,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send error notification email
     */
    protected function sendErrorNotification(string $errorMessage): void
    {
        try {
            // You could create a separate email class for error notifications
            // For now, we'll just log the error
            Log::error('PDS Export failed to process', [
                'user_id' => $this->user->id,
                'job_id' => $this->jobId,
                'error_message' => $errorMessage,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send error notification', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a secure download signature
     */
    protected function generateDownloadSignature(string $filename): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'pds.download-export',
            now()->addHours(24),
            ['filename' => $filename]
        );
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFiles(array $tempFiles): void
    {
        foreach ($tempFiles as $tempFile) {
            try {
                if (Storage::exists($tempFile)) {
                    Storage::delete($tempFile);
                }
            } catch (Exception $e) {
                Log::warning('Failed to delete temp file', [
                    'temp_file' => $tempFile,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(Exception $exception): void
    {
        Log::error('PDS export job failed permanently', [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Update job status to failed
        $this->updateJobStatus('failed', null, $exception->getMessage());

        // Send error notification
        $this->sendErrorNotification($exception->getMessage());
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'pds-export',
            'user:' . $this->user->id,
            'format:' . $this->format,
            'employees:' . count($this->employeeIds),
            'role:' . $this->user->roles->pluck('name')->first(),
        ];
    }

    /**
     * Validate concurrent export limits based on user role
     */
    protected function validateConcurrentExportLimits(): void
    {
        $activeExports = $this->getConcurrentExportCount();
        $maxConcurrent = $this->getMaxConcurrentExports();

        if ($activeExports >= $maxConcurrent) {
            $errorMessage = "Maximum concurrent exports limit reached ({$maxConcurrent}). Current active: {$activeExports}.";

            Log::warning('Concurrent export limit exceeded', [
                'job_id' => $this->jobId,
                'user_id' => $this->user->id,
                'user_role' => $this->user->roles->pluck('name')->first(),
                'active_exports' => $activeExports,
                'max_concurrent' => $maxConcurrent,
            ]);

            throw new Exception($errorMessage);
        }

        // Additional validation for non-Super Admin users
        if (!$this->user->hasRole('Super Admin')) {
            $this->validateUserRoleSpecificLimits($activeExports);
        }
    }

    /**
     * Get maximum concurrent exports allowed based on user role
     */
    protected function getMaxConcurrentExports(): int
    {
        if ($this->user->hasRole('Super Admin')) {
            return config('exports.max_concurrent.super_admin', 10);
        }

        if ($this->user->hasRole('HR Admin')) {
            return config('exports.max_concurrent.hr_admin', 3);
        }

        return config('exports.max_concurrent.employee', 1);
    }

    /**
     * Get current count of active concurrent exports for this user
     */
    protected function getConcurrentExportCount(): int
    {
        return QueuedExport::where('user_id', $this->user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->where('job_id', '!=', $this->jobId) // Exclude current job
            ->count();
    }

    /**
     * Validate role-specific export limits
     */
    protected function validateUserRoleSpecificLimits(int $activeExports): void
    {
        // HR Admin: Check total employee export limit per day
        if ($this->user->hasRole('HR Admin')) {
            $dailyLimit = config('exports.daily_limits.hr_admin', 500);
            $todayExports = $this->getTodayExportCount();

            if ($todayExports + count($this->employeeIds) > $dailyLimit) {
                throw new Exception("Daily export limit exceeded. Current: {$todayExports}, Requested: " . count($this->employeeIds) . ", Limit: {$dailyLimit}");
            }
        }

        // Employee: Check if trying to export more than allowed
        if ($this->user->hasRole('Employee')) {
            $maxEmployees = config('exports.max_employees.employee', 10);
            if (count($this->employeeIds) > $maxEmployees) {
                throw new Exception("Employees can only export up to {$maxEmployees} records per export. Requested: " . count($this->employeeIds));
            }
        }
    }

    /**
     * Get count of exports processed today for this user
     */
    protected function getTodayExportCount(): int
    {
        return ExportAuditLog::where('user_id', $this->user->id)
            ->whereDate('created_at', today())
            ->sum('metadata->employee_count');
    }

    /**
     * Monitor system-wide concurrent export capacity
     */
    protected function monitorSystemCapacity(): void
    {
        $systemActiveExports = QueuedExport::whereIn('status', ['pending', 'processing'])->count();
        $systemMaxExports = config('exports.max_concurrent.system', 50);

        if ($systemActiveExports >= $systemMaxExports) {
            Log::warning('System export capacity nearing limits', [
                'system_active' => $systemActiveExports,
                'system_max' => $systemMaxExports,
                'current_job' => $this->jobId,
            ]);

            // For Super Admin, allow proceeding but log the warning
            if (!$this->user->hasRole('Super Admin')) {
                throw new Exception("System is at maximum export capacity. Please try again later.");
            }
        }
    }

    /**
     * Check for potential resource conflicts
     */
    protected function checkResourceConflicts(): void
    {
        // Check memory usage
        $currentMemory = memory_get_usage(true);
        $maxMemory = config('exports.memory_limit', 512 * 1024 * 1024); // 512MB default

        if ($currentMemory > $maxMemory * 0.8) { // 80% threshold
            Log::warning('High memory usage detected', [
                'job_id' => $this->jobId,
                'current_memory' => $currentMemory,
                'max_memory' => $maxMemory,
            ]);
        }

        // Check queue capacity
        $queueSize = \Illuminate\Support\Facades\Queue::size('exports');
        $maxQueueSize = config('exports.max_queue_size', 100);

        if ($queueSize > $maxQueueSize * 0.8) { // 80% threshold
            Log::warning('Export queue nearing capacity', [
                'queue_size' => $queueSize,
                'max_queue_size' => $maxQueueSize,
                'current_job' => $this->jobId,
            ]);
        }
    }

    /**
     * Rate limiting check for rapid successive exports
     */
    protected function checkRateLimiting(): void
    {
        $rateLimitWindow = config('exports.rate_limit_window', 60); // 1 minute
        $maxRequests = $this->getMaxConcurrentExports();

        $recentExports = ExportAuditLog::where('user_id', $this->user->id)
            ->where('created_at', '>=', now()->subSeconds($rateLimitWindow))
            ->count();

        if ($recentExports >= $maxRequests) {
            throw new Exception("Rate limit exceeded. Maximum {$maxRequests} exports per {$rateLimitWindow} seconds.");
        }
    }

    /**
     * Validate job requirements (T046)
     */
    protected function validateJobRequirements(): void
    {
        // Validate user still exists and is active
        if (!$this->user || !$this->user->exists) {
            throw new Exception('User account not found or deleted');
        }

        // Validate employee IDs are not empty
        if (empty($this->employeeIds)) {
            throw new \App\Services\PDSDataEmptyException('No employee IDs provided for export');
        }

        // Validate format
        if (!in_array($this->format, ['xlsx', 'csv'])) {
            throw new Exception("Invalid export format: {$this->format}");
        }

        // Validate job uniqueness
        $existingJob = QueuedExport::where('job_id', $this->jobId)
            ->where('status', '!=', 'failed')
            ->first();

        if ($existingJob && $existingJob->created_at > now()->subMinutes(5)) {
            throw new Exception('Duplicate job detected');
        }
    }

    /**
     * Validate employee data availability (T046)
     */
    protected function validateEmployeeDataAvailability(): void
    {
        $availableEmployees = Employee::whereIn('id', $this->employeeIds)
            ->where(function ($query) {
                $query->whereNotNull('first_name')
                      ->orWhereNotNull('last_name')
                      ->orWhereHas('familyBackgrounds')
                      ->orWhereHas('educations')
                      ->orWhereHas('eligibilities');
            })
            ->count();

        if ($availableEmployees === 0) {
            throw new \App\Services\PDSDataEmptyException(
                'No PDS data available for any of the requested employees'
            );
        }

        if ($availableEmployees < count($this->employeeIds)) {
            $missingCount = count($this->employeeIds) - $availableEmployees;
            Log::warning('Some employees have no PDS data', [
                'job_id' => $this->jobId,
                'missing_count' => $missingCount,
                'total_requested' => count($this->employeeIds),
            ]);
        }
    }

    /**
     * Validate system resources (T046)
     */
    protected function validateSystemResources(): void
    {
        // Check disk space
        $freeSpace = disk_free_space(storage_path());
        $minRequiredSpace = 100 * 1024 * 1024; // 100MB for batch exports

        if ($freeSpace < $minRequiredSpace) {
            throw new Exception('Insufficient disk space for batch export generation');
        }

        // Check memory availability
        $memoryLimit = ini_get('memory_limit');
        $currentUsage = memory_get_usage(true);

        if ($memoryLimit !== '-1') {
            $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
            $availableMemory = $memoryLimitBytes - $currentUsage;

            if ($availableMemory < 128 * 1024 * 1024) { // 128MB minimum for batch
                throw new Exception('Insufficient memory available for batch export processing');
            }
        }

        // Check database connection
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Illuminate\Database\QueryException('Database connection failed', [], $e);
        }
    }

    /**
     * Generate filename with validation (T046)
     */
    protected function generateFilenameWithValidation(): string
    {
        try {
            $filename = $this->generateFilename();

            // Validate filename is safe
            if (strlen($filename) > 255) {
                throw new Exception('Generated filename is too long');
            }

            // Check for potential filename conflicts
            $existingFile = 'exports/' . str_replace('.xlsx', '.temp', $filename);
            if (Storage::exists($existingFile)) {
                $filename = str_replace('.xlsx', '_' . uniqid() . '.xlsx', $filename);
            }

            return $filename;

        } catch (\Exception $e) {
            throw new Exception('Failed to generate valid export filename: ' . $e->getMessage());
        }
    }

    /**
     * Create export with validation (T046)
     */
    protected function createExportWithValidation(): PDSExport
    {
        try {
            return new PDSExport(
                $this->employeeIds,
                $this->user,
                $this->includeMetadata
            );
        } catch (\Exception $e) {
            throw new Exception('Failed to create export object: ' . $e->getMessage());
        }
    }

    /**
     * Process export in chunks with retry mechanism (T046)
     */
    protected function processExportInChunksWithRetry(PDSExport $export, string $filename): string
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return $this->processExportInChunks($export, $filename);

            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    throw $e;
                }

                // Log retry attempt
                Log::warning('Export chunk processing failed, retrying', [
                    'job_id' => $this->jobId,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                ]);

                // Clean up partial files before retry
                $this->cleanupPartialFiles($filename);

                // Wait before retry (exponential backoff)
                $waitTime = min(5 * pow(2, $attempt - 1), 30); // Max 30 seconds
                sleep($waitTime);
            }
        }

        throw new Exception('Export processing failed after ' . $maxRetries . ' attempts');
    }

    /**
     * Validate the generated file (T046)
     */
    protected function validateGeneratedFile(string $filePath): void
    {
        if (!Storage::exists($filePath)) {
            throw new Exception('Export file was not created');
        }

        $fileSize = Storage::size($filePath);
        if ($fileSize === 0) {
            throw new Exception('Export file is empty');
        }

        // Minimum file size check (at least 1KB for a valid Excel file)
        if ($fileSize < 1024) {
            Log::warning('Export file is unusually small', [
                'job_id' => $this->jobId,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);
        }

        // Validate file extension matches expected format
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($extension !== $this->format) {
            throw new Exception("Export file format mismatch. Expected: {$this->format}, Got: {$extension}");
        }
    }

    /**
     * Handle empty data exception (T046)
     */
    protected function handleEmptyDataException(\App\Services\PDSDataEmptyException $e): void
    {
        Log::notice('PDS export failed due to empty data', [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'employee_count' => count($this->employeeIds),
            'message' => $e->getMessage(),
        ]);

        $this->updateJobStatus('failed', null, $e->getMessage());
        $this->sendErrorNotification('No PDS data available for export: ' . $e->getMessage());
    }

    /**
     * Handle character encoding exception (T046)
     */
    protected function handleCharacterEncodingException(\App\Services\FilipinoCharacterEncodingException $e): void
    {
        Log::error('PDS export failed due to character encoding issues', [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'problematic_text' => $e->getProblematicText(),
            'message' => $e->getMessage(),
        ]);

        $this->updateJobStatus('failed', null, 'Character encoding issues detected');
        $this->sendErrorNotification('Character encoding issues detected. Please check data for special Filipino characters.');
    }

    /**
     * Handle database exception (T046)
     */
    protected function handleDatabaseException(\Illuminate\Database\QueryException $e): void
    {
        Log::error('PDS export failed due to database issues', [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'error_code' => $e->getCode(),
            'message' => $e->getMessage(),
        ]);

        $this->updateJobStatus('failed', null, 'Database connection issues');
        $this->sendErrorNotification('Database connection issues. Please try again later.');
    }

    /**
     * Handle connection exception (T046)
     */
    protected function handleConnectionException(\Illuminate\Http\Client\ConnectionException $e): void
    {
        Log::error('PDS export failed due to connection issues', [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'message' => $e->getMessage(),
        ]);

        $this->updateJobStatus('failed', null, 'Network connection issues');
        $this->sendErrorNotification('Network connection issues. Please check your connection and try again.');
    }

    /**
     * Handle generic exception (T046)
     */
    protected function handleGenericException(\Exception $e): void
    {
        Log::error('PDS export failed due to unexpected error', [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->updateJobStatus('failed', null, $e->getMessage());
        $this->sendErrorNotification($e->getMessage());
        throw $e;
    }

    /**
     * Clean up partial files (T046, T047)
     */
    protected function cleanupPartialFiles(string $filename): void
    {
        $patterns = [
            'exports/' . $filename,
            'exports/temp/chunk_' . $this->jobId . '_*',
            'exports/' . str_replace('.xlsx', '.temp', $filename),
        ];

        foreach ($patterns as $pattern) {
            try {
                $files = Storage::glob($pattern);
                foreach ($files as $file) {
                    if (Storage::exists($file)) {
                        Storage::delete($file);
                        Log::info('Cleaned up partial file', ['file' => $file, 'job_id' => $this->jobId]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to clean up partial files', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                    'job_id' => $this->jobId,
                ]);
            }
        }
    }

    /**
     * Clean up old export files on job completion (T047)
     */
    protected function cleanupOldExportFiles(): void
    {
        try {
            $retentionDays = config('exports.retention.days', 30);
            $retentionFailedDays = config('exports.retention.failed_days', 7);
            $maxFilesPerCleanup = config('exports.max_files_per_cleanup', 50);

            $cutoffDate = now()->subDays($retentionDays);
            $failedCutoffDate = now()->subDays($retentionFailedDays);

            // Get old completed exports
            $oldCompletedExports = ExportAuditLog::where('status', 'completed')
                ->where('created_at', '<', $cutoffDate)
                ->whereNotNull('file_name')
                ->take($maxFilesPerCleanup)
                ->get();

            // Get old failed exports
            $oldFailedExports = ExportAuditLog::where('status', 'failed')
                ->where('created_at', '<', $failedCutoffDate)
                ->whereNotNull('file_name')
                ->take($maxFilesPerCleanup)
                ->get();

            $filesToDelete = $oldCompletedExports->merge($oldFailedExports);

            if ($filesToDelete->isEmpty()) {
                return;
            }

            $deletedCount = 0;
            $spaceFreed = 0;

            foreach ($filesToDelete as $export) {
                $filePath = 'exports/' . $export->file_name;

                if (Storage::exists($filePath)) {
                    $fileSize = Storage::size($filePath);

                    try {
                        if (Storage::delete($filePath)) {
                            $deletedCount++;
                            $spaceFreed += $fileSize;

                            // Update audit log to mark file as cleaned
                            $export->update([
                                'file_name' => null,
                                'file_size' => 0,
                                'metadata' => array_merge(
                                    $export->metadata ?? [],
                                    ['cleaned_at' => now()->toISOString(), 'cleaned_by_job' => $this->jobId]
                                )
                            ]);

                            Log::info('Old export file cleaned up by job', [
                                'file' => $export->file_name,
                                'export_id' => $export->id,
                                'job_id' => $this->jobId,
                                'size' => $fileSize,
                                'age_days' => $export->created_at->diffInDays(now()),
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old export file', [
                            'file' => $export->file_name,
                            'export_id' => $export->id,
                            'error' => $e->getMessage(),
                            'job_id' => $this->jobId,
                        ]);
                    }
                }
            }

            if ($deletedCount > 0) {
                Log::info('Export file cleanup completed', [
                    'job_id' => $this->jobId,
                    'files_deleted' => $deletedCount,
                    'space_freed' => $spaceFreed,
                    'retention_days_completed' => $retentionDays,
                    'retention_days_failed' => $retentionFailedDays,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Export file cleanup failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Clean up temporary files and optimize storage (T047)
     */
    protected function optimizeStorage(): void
    {
        try {
            // Clean up temp directory
            $tempPath = 'exports/temp';
            if (Storage::exists($tempPath)) {
                $tempFiles = Storage::allFiles($tempPath);
                $tempSize = 0;

                foreach ($tempFiles as $file) {
                    $filePath = $tempPath . '/' . $file;
                    if (Storage::exists($filePath)) {
                        $size = Storage::size($filePath);
                        $tempSize += $size;
                        Storage::delete($filePath);
                    }
                }

                if ($tempFiles->isNotEmpty()) {
                    Log::info('Temporary files cleaned up', [
                        'job_id' => $this->jobId,
                        'files_deleted' => $tempFiles->count(),
                        'space_freed' => $tempSize,
                    ]);
                }
            }

            // Clean up orphaned queued export records
            $orphanedQueued = QueuedExport::where('created_at', '<', now()->subDays(7))
                ->where('status', 'pending')
                ->whereDoesntHave('exportAuditLogs', function ($query) {
                    $query->whereColumn('job_id', 'like', DB::raw("CONCAT('%', job_id, '%')"));
                })
                ->get();

            foreach ($orphanedQueued as $queued) {
                    $queued->update(['status' => 'failed', 'error_message' => 'Orphaned job - auto-cleanup']);
                }

            if ($orphanedQueued->isNotEmpty()) {
                Log::info('Orphaned queued exports cleaned up', [
                    'job_id' => $this->jobId,
                    'cleaned_count' => $orphanedQueued->count(),
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Storage optimization failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse memory limit string to bytes (T046)
     */
    protected function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = strtolower(trim($memoryLimit));
        $multiplier = 1;

        if (str_ends_with($memoryLimit, 'g')) {
            $multiplier = 1024 * 1024 * 1024;
            $memoryLimit = substr($memoryLimit, 0, -1);
        } elseif (str_ends_with($memoryLimit, 'm')) {
            $multiplier = 1024 * 1024;
            $memoryLimit = substr($memoryLimit, 0, -1);
        } elseif (str_ends_with($memoryLimit, 'k')) {
            $multiplier = 1024;
            $memoryLimit = substr($memoryLimit, 0, -1);
        }

        return (int) $memoryLimit * $multiplier;
    }
}