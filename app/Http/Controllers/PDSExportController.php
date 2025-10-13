<?php

namespace App\Http\Controllers;

use App\Exports\PDSExport;
use App\Jobs\ProcessLargePDSExport;
use App\Models\Employee;
use App\Models\QueuedExport;
use App\Models\ExportAuditLog;
use App\Services\PDSDataOptimizationService;
use App\Services\FilipinoCharacterService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PDSExportController extends Controller
{
    use AuthorizesRequests;

    protected $optimizationService;
    protected $filipinoService;

    public function __construct()
    {
        $this->optimizationService = new PDSDataOptimizationService();
        $this->filipinoService = new FilipinoCharacterService();

        // Apply middleware for authentication and authorization
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Show the export interface
     */
    public function index()
    {
        $this->authorize('viewExportInterface', Employee::class);

        // Get employees user can export based on their role
        $employees = $this->getExportableEmployees();

        return view('pds.export.index', compact('employees'));
    }

    /**
     * Export PDS data for a single employee
     */
    public function exportSingle(Request $request, Employee $employee)
    {
        $this->authorize('export', [$employee, true]); // true = actual export attempt

        try {
            // Apply the same validation as exportSelfPDS method
            $this->validateEmployeeData($employee);
            $this->validateSystemRequirements();

            $filename = $this->generateSingleExportFilename($employee);
            $format = $request->get('format', 'xlsx');
            $includeMetadata = $request->get('include_metadata', true);

            // Validate export parameters
            $this->validateExportParameters($format, $includeMetadata);

            // Create audit log entry
            $this->createInitialAuditLog('single', [$employee->id], $format);

            // For single employee exports, process immediately (no need for queue)
            $export = new PDSExport([$employee->id], Auth::user(), $includeMetadata);

            // Update audit log with file information
            $this->updateAuditLogAfterExport($filename);

            Log::info('Admin PDS export successful', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->roles->pluck('name')->first(),
                'employee_id' => $employee->id,
                'filename' => $filename,
            ]);

            return Excel::download($export, $filename);

        } catch (\App\Services\PDSDataEmptyException $e) {
            Log::notice('Admin PDS export failed - Empty PDS data', [
                'user_id' => Auth::id(),
                'employee_id' => $employee->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('warning', 'No PDS data available for this employee. The employee record may be incomplete.');

        } catch (\App\Services\FilipinoCharacterEncodingException $e) {
            Log::error('Filipino character encoding error in admin PDS export', [
                'user_id' => Auth::id(),
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Character encoding issue detected in this employee\'s data. Please contact HR to review the information.');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database connection error during admin PDS export', [
                'user_id' => Auth::id(),
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return back()->with('error', 'Database connection issue. Please try again in a few moments or contact IT support.');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Network connection error during admin PDS export', [
                'user_id' => Auth::id(),
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Network connection issue. Please check your internet connection and try again.');

        } catch (\Exception $e) {
            Log::error('Unexpected error in admin PDS export', [
                'user_id' => Auth::id(),
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Export failed due to an unexpected error. Please try again later or contact system administrator.');
        }
    }

    /**
     * Export PDS data for employee self-service
     * This method ensures employees can only export their own PDS
     */
    public function exportSelfPDS(Request $request)
    {
        $user = Auth::user();

        // Get the employee record associated with the authenticated user
        $employee = $user->employee;

        if (!$employee) {
            Log::warning('Self-PDS export failed - No employee record linked', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
            return back()->with('error', 'No employee record found for your account. Please contact HR.');
        }

        // Double-check authorization for self-export
        $this->authorize('export', [$employee, true]); // true = actual export attempt

        // Log export attempt for debugging
        Log::info('Employee self-PDS export attempt', [
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        try {
            // Apply the same validation as exportSingle method
            $this->validateEmployeeData($employee);
            $this->validateSystemRequirements();

            $filename = $this->generateSingleExportFilename($employee);
            $format = $request->get('format', 'xlsx');
            $includeMetadata = $request->get('include_metadata', true);

            // Validate export parameters
            $this->validateExportParameters($format, $includeMetadata);

            // Create audit log entry with self-service indicator
            $this->createInitialAuditLog('single', [$employee->id], $format, 'self_service');

            // For single employee exports, process immediately (no need for queue)
            $export = new PDSExport([$employee->id], $user, $includeMetadata);

            // Update audit log with file information
            $this->updateAuditLogAfterExport($filename);

            Log::info('Employee self-PDS export successful', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'filename' => $filename,
            ]);

            return Excel::download($export, $filename);

        } catch (\App\Services\PDSDataEmptyException $e) {
            Log::notice('Employee self-PDS export failed - Empty PDS data', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('warning', 'Your PDS data appears to be incomplete. Please ensure your personal information, family background, and educational details are filled out before exporting.');

        } catch (\App\Services\FilipinoCharacterEncodingException $e) {
            Log::error('Filipino character encoding error in employee self-PDS export', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Character encoding issue detected in your data. Please contact HR to review your information.');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database connection error during employee self-PDS export', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return back()->with('error', 'Database connection issue. Please try again in a few moments or contact IT support.');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Network connection error during employee self-PDS export', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Network connection issue. Please check your internet connection and try again.');

        } catch (\Exception $e) {
            Log::error('Unexpected error in employee self-PDS export', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Export failed due to an unexpected error. Please try again later or contact system administrator.');
        }
    }

    /**
     * Export PDS data for multiple employees (batch export)
     */
    public function exportBatch(Request $request)
    {
        $this->authorize('batchExport', Employee::class);

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'format' => 'in:xlsx,csv',
            'include_metadata' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employeeIds = $request->get('employee_ids');
        $format = $request->get('format', 'xlsx');
        $includeMetadata = $request->get('include_metadata', true);

        // Additional authorization check for batch export
        $this->authorize('batchExport', [Employee::class, $employeeIds]);

        try {
            // Create audit log entry
            $this->createInitialAuditLog('batch', $employeeIds, $format);

            // Check if export should be queued
            if ($this->shouldQueueExport($employeeIds)) {
                return $this->queueExport($employeeIds, $format, $includeMetadata);
            }

            // Process export immediately
            $filename = $this->generateBatchExportFilename($employeeIds, $format);
            $export = new PDSExport($employeeIds, Auth::user(), $includeMetadata);

            // Update audit log with file information
            $this->updateAuditLogAfterExport($filename);

            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Batch PDS export failed', [
                'user_id' => Auth::id(),
                'employee_ids' => $employeeIds,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the status of a queued export
     * Enhanced for Super Admin system audit capabilities
     */
    public function getExportStatus(string $jobId)
    {
        $this->authorize('viewExportStatus', Employee::class);

        $user = Auth::user();
        $queuedExport = QueuedExport::where('job_id', $jobId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Build comprehensive status response
        $response = [
            'job_id' => $jobId,
            'status' => $queuedExport->status,
            'progress' => $queuedExport->progress ?? 0,
            'created_at' => $queuedExport->created_at->toISOString(),
            'estimated_completion' => $queuedExport->estimated_completion?->toISOString(),
        ];

        // Add metadata for Super Admin system audit
        if ($user->hasRole('Super Admin')) {
            $response['system_audit_info'] = [
                'access_level' => 'super_admin',
                'user_role' => 'Super Admin',
                'concurrent_exports' => QueuedExport::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->count(),
                'total_queued_exports' => QueuedExport::whereIn('status', ['pending', 'processing'])->count(),
            ];
        }

        // Add completion details
        if ($queuedExport->status === 'completed') {
            $response['download_url'] = $this->generateSecureDownloadUrl($queuedExport->file_path);
            $response['file_size'] = Storage::size($queuedExport->file_path);
            $response['completed_at'] = $queuedExport->completed_at->toISOString();

            // Add file metadata for Super Admin
            if ($user->hasRole('Super Admin')) {
                $response['file_metadata'] = [
                    'file_path' => $queuedExport->file_path,
                    'file_extension' => pathinfo($queuedExport->file_path, PATHINFO_EXTENSION),
                    'download_expires_at' => now()->addHours(24)->toISOString(),
                ];
            }
        }

        // Add error information
        if ($queuedExport->status === 'failed') {
            $response['error_message'] = $queuedExport->error_message;
            $response['failed_at'] = $queuedExport->updated_at->toISOString();

            // Add error troubleshooting for Super Admin
            if ($user->hasRole('Super Admin')) {
                $response['troubleshooting_info'] = [
                    'can_retry' => true,
                    'retry_url' => route('pds.export.retry', $jobId),
                    'error_logs_available' => true,
                ];
            }
        }

        // Add performance metrics for Super Admin
        if ($user->hasRole('Super Admin')) {
            $response['performance_metrics'] = $this->optimizationService->getExportMetrics($jobId);
        }

        return response()->json($response);
    }

    /**
     * Download an exported file using signed URL
     * Enhanced for Super Admin system audit capabilities
     */
    public function downloadExport(Request $request, string $filename)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid or expired download link');
        }

        $filePath = 'exports/' . $filename;

        if (!Storage::exists($filePath)) {
            abort(404, 'Export file not found');
        }

        $user = Auth::user();
        $this->authorize('downloadExport', Employee::class);

        // Get file information for audit logging
        $fileSize = Storage::size($filePath);
        $fileMimeType = Storage::mimeType($filePath);

        // Enhanced logging for Super Admin system audit
        $logData = [
            'user_id' => $user->id,
            'user_role' => $user->roles->pluck('name')->first(),
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_mime_type' => $fileMimeType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Add system audit metadata for Super Admin
        if ($user->hasRole('Super Admin')) {
            $logData['system_audit'] = [
                'access_level' => 'super_admin',
                'download_purpose' => 'system_audit',
                'signed_url_valid' => $request->hasValidSignature(),
                'url_expires_at' => $request->query('expires'),
            ];
        }

        Log::info('PDS export file downloaded', $logData);

        // Create audit log entry for download
        ExportAuditLog::create([
            'user_id' => $user->id,
            'export_type' => 'download',
            'export_format' => pathinfo($filename, PATHINFO_EXTENSION),
            'file_name' => $filename,
            'file_size' => $fileSize,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_role' => $user->roles->pluck('name')->first(),
            'metadata' => [
                'download_purpose' => $user->hasRole('Super Admin') ? 'system_audit' : 'user_download',
                'access_level' => $user->hasRole('Super Admin') ? 'super_admin' : 'standard',
            ],
        ]);

        return Storage::download($filePath);
    }

    /**
     * Get export history for the current user
     */
    public function getExportHistory(Request $request)
    {
        $this->authorize('viewExportHistory', Employee::class);

        $history = ExportAuditLog::where('user_id', Auth::id())
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($history);
    }

    /**
     * Check if export should be queued based on data size
     */
    protected function shouldQueueExport(array $employeeIds): bool
    {
        $threshold = config('excel.queue_threshold', 10); // Queue for more than 10 employees
        $complexityScore = $this->optimizationService->calculateComplexityScore($employeeIds);

        return count($employeeIds) > $threshold || $complexityScore > 100;
    }

    /**
     * Queue an export job
     */
    protected function queueExport(array $employeeIds, string $format, bool $includeMetadata): JsonResponse
    {
        $jobId = uniqid('pds_export_');
        $estimatedTime = $this->optimizationService->estimateProcessingTime($employeeIds);

        // Create queued export record
        $queuedExport = QueuedExport::create([
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'employee_ids' => json_encode($employeeIds),
            'status' => 'pending',
            'estimated_completion' => now()->addSeconds($estimatedTime),
            'metadata' => [
                'format' => $format,
                'include_metadata' => $includeMetadata,
                'employee_count' => count($employeeIds),
            ],
        ]);

        // Dispatch the job
        ProcessLargePDSExport::dispatch(
            Auth::user(),
            $employeeIds,
            $format,
            $includeMetadata,
            $jobId
        );

        return response()->json([
            'success' => true,
            'message' => 'Export queued for processing. You will be notified when it\'s ready.',
            'job_id' => $jobId,
            'estimated_time' => $this->formatEstimatedTime($estimatedTime),
        ], 202);
    }

    /**
     * Get employees that the current user can export
     */
    protected function getExportableEmployees()
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return Employee::orderBy('last_name')->get();
        }

        if ($user->hasRole('HR Admin')) {
            return Employee::where('employment_status', 'Active')
                ->orderBy('last_name')
                ->get();
        }

        if ($user->hasRole('Employee')) {
            return Employee::where('user_id', $user->id)->get();
        }

        return collect([]);
    }

    /**
     * Generate filename for single employee export
     */
    protected function generateSingleExportFilename(Employee $employee): string
    {
        $timestamp = now()->format('Y_m_d_His');
        $name = $this->filipinoService->cleanForExcel(
            Str::slug($employee->last_name . '_' . $employee->first_name, '_')
        );

        return "PDS_{$name}_{$timestamp}.xlsx";
    }

    /**
     * Generate filename for batch export
     */
    protected function generateBatchExportFilename(array $employeeIds, string $format): string
    {
        $timestamp = now()->format('Y_m_d_His');
        $role = Auth::user()->hasRole('Super Admin') ? 'SuperAdmin' :
                (Auth::user()->hasRole('HR Admin') ? 'HRAdmin' : 'Employee');
        $count = count($employeeIds);

        return "PDS_{$role}_Batch_{$count}_Employees_{$timestamp}.{$format}";
    }

    /**
     * Create initial audit log entry
     */
    protected function createInitialAuditLog(string $exportType, array $employeeIds, string $format, string $source = 'admin'): void
    {
        $user = Auth::user();

        ExportAuditLog::create([
            'user_id' => Auth::id(),
            'employee_id' => $exportType === 'single' ? $employeeIds[0] : null,
            'export_type' => $exportType,
            'export_format' => $format,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_role' => $user->roles->pluck('name')->first(),
            'metadata' => [
                'employee_ids' => $employeeIds,
                'employee_count' => count($employeeIds),
                'source' => $source, // 'admin' or 'self_service'
            ],
        ]);
    }

    /**
     * Update audit log after successful export
     */
    protected function updateAuditLogAfterExport(string $filename): void
    {
        $auditLog = ExportAuditLog::where('user_id', Auth::id())
            ->whereNull('file_name')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($auditLog) {
            $filePath = 'exports/' . $filename;
            $auditLog->update([
                'file_name' => $filename,
                'file_size' => Storage::exists($filePath) ? Storage::size($filePath) : 0,
            ]);
        }
    }

    /**
     * Generate secure download URL
     */
    protected function generateSecureDownloadUrl(string $filePath): string
    {
        $filename = basename($filePath);

        return URL::temporarySignedRoute(
            'pds.download-export',
            now()->addHours(24),
            ['filename' => $filename]
        );
    }

    /**
     * Format estimated processing time for human readability
     */
    protected function formatEstimatedTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ?
                "{$minutes} minutes {$remainingSeconds} seconds" :
                "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ?
            "{$hours} hours {$remainingMinutes} minutes" :
            "{$hours} hours";
    }

    /**
     * Validate employee data for edge cases (T046)
     */
    protected function validateEmployeeData(Employee $employee): void
    {
        // Check if employee record exists and is accessible
        if (!$employee || !$employee->exists) {
            throw new \App\Services\PDSDataEmptyException('Employee record not found or inaccessible');
        }

        // Check for empty PDS data
        $hasPDSData = $this->employeeHasPDSData($employee);
        if (!$hasPDSData) {
            throw new \App\Services\PDSDataEmptyException("No PDS data available for employee: {$employee->full_name}");
        }

        // Check for data integrity issues
        $this->validatePDSDataIntegrity($employee);
    }

    /**
     * Validate system requirements for export (T046)
     */
    protected function validateSystemRequirements(): void
    {
        // Check disk space
        $this->validateDiskSpace();

        // Check database connection
        $this->validateDatabaseConnection();

        // Check memory availability
        $this->validateMemoryAvailability();
    }

    /**
     * Validate export parameters (T046)
     */
    protected function validateExportParameters(string $format, bool $includeMetadata): void
    {
        $validator = Validator::make([
            'format' => $format,
            'include_metadata' => $includeMetadata,
        ], [
            'format' => 'required|in:xlsx,csv',
            'include_metadata' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Process immediate export with enhanced error handling (T046)
     */
    protected function processImmediateExport(array $employeeIds, string $filename, string $format, bool $includeMetadata, string $exportType): BinaryFileResponse
    {
        try {
            // Validate file can be created
            $this->validateFileCreation($filename);

            // Create export with error handling
            $export = new PDSExport($employeeIds, Auth::user(), $includeMetadata);

            // Process export with timeout protection
            $this->processExportWithTimeout($export, $filename, $format);

            // Update audit log with file information
            $this->updateAuditLogAfterExport($filename);

            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            // Clean up any partial files
            $this->cleanupPartialExport($filename);

            throw $e;
        }
    }

    /**
     * Check if employee has any PDS data (T046)
     */
    protected function employeeHasPDSData(Employee $employee): bool
    {
        // Check basic employee information
        if (!empty($employee->first_name) || !empty($employee->last_name)) {
            return true;
        }

        // Check related PDS data
        $relatedModels = [
            'familyBackground',
            'education',
            'pdsEligibilities',
            'trainings',
            'voluntaryWork',
            'otherInformation',
            'references'
        ];

        foreach ($relatedModels as $relation) {
            if ($employee->$relation && $employee->$relation->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate PDS data integrity (T046)
     */
    protected function validatePDSDataIntegrity(Employee $employee): void
    {
        // Check for corrupted Filipino characters
        if ($employee->first_name && $this->filipinoService->hasInvalidCharacters($employee->first_name)) {
            throw new \App\Services\FilipinoCharacterEncodingException('Invalid Filipino characters detected in employee first name');
        }

        if ($employee->last_name && $this->filipinoService->hasInvalidCharacters($employee->last_name)) {
            throw new \App\Services\FilipinoCharacterEncodingException('Invalid Filipino characters detected in employee last name');
        }

        // Check for data consistency
        if ($employee->birth_date && $employee->birth_date > now()) {
            throw new \App\Services\PDSDataEmptyException('Invalid birth date detected');
        }
    }

    /**
     * Validate available disk space (T046)
     */
    protected function validateDiskSpace(): void
    {
        $freeSpace = disk_free_space(storage_path());
        $minRequiredSpace = 50 * 1024 * 1024; // 50MB minimum

        if ($freeSpace < $minRequiredSpace) {
            throw new \Exception('Insufficient disk space for export generation');
        }
    }

    /**
     * Validate database connection (T046)
     */
    protected function validateDatabaseConnection(): void
    {
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Illuminate\Database\QueryException('Database connection failed', [], $e);
        }
    }

    /**
     * Validate memory availability (T046)
     */
    protected function validateMemoryAvailability(): void
    {
        $memoryLimit = ini_get('memory_limit');
        $currentUsage = memory_get_usage(true);

        if ($memoryLimit !== '-1') {
            $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
            $availableMemory = $memoryLimitBytes - $currentUsage;

            if ($availableMemory < 64 * 1024 * 1024) { // 64MB minimum
                throw new \Exception('Insufficient memory available for export processing');
            }
        }
    }

    /**
     * Validate file can be created (T046)
     */
    protected function validateFileCreation(string $filename): void
    {
        $filePath = storage_path('app/exports/' . $filename);
        $directory = dirname($filePath);

        // Check if directory exists and is writable
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception('Cannot create export directory');
            }
        }

        if (!is_writable($directory)) {
            throw new \Exception('Export directory is not writable');
        }
    }

    /**
     * Process export with timeout protection (T046)
     */
    protected function processExportWithTimeout(PDSExport $export, string $filename, string $format): void
    {
        $timeout = config('excel.exports.timeout', 300); // 5 minutes default
        $startTime = time();

        try {
            // Set timeout for this operation
            set_time_limit($timeout);

            Excel::store($export, 'exports/' . $filename, 'local', $format);

            // Check if we exceeded timeout
            $elapsed = time() - $startTime;
            if ($elapsed > $timeout * 0.8) { // 80% threshold
                Log::warning('Export processing approaching timeout', [
                    'filename' => $filename,
                    'elapsed' => $elapsed,
                    'timeout' => $timeout,
                ]);
            }

        } finally {
            // Restore original timeout
            set_time_limit(ini_get('max_execution_time'));
        }
    }

    /**
     * Clean up partial export files (T046)
     */
    protected function cleanupPartialExport(string $filename): void
    {
        $filePath = 'exports/' . $filename;

        try {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
                Log::info('Cleaned up partial export file', ['filename' => $filename]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clean up partial export file', [
                'filename' => $filename,
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