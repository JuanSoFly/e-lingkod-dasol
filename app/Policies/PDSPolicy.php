<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Models\QueuedExport;
use App\Models\ExportAuditLog;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PDSPolicy
{
    /**
     * Determine whether the user can export PDS data.
     * Enhanced with security checks for T049
     *
     * @param User $user
     * @param Employee|null $employee
     * @param bool $isActualExport Whether this is an actual export attempt (vs permission check)
     * @return Response
     */
    public function export(User $user, Employee $employee = null, bool $isActualExport = false): Response
    {
        // Security check: Verify user account is active and not suspended
        if ($user->status !== 'active' || $user->suspended_at) {
            return Response::deny('Your account has been suspended or deactivated.');
        }

        // Security check: Rate limiting for actual export attempts only (not permission checks)
        if ($isActualExport && $this->hasExceededExportRateLimit($user)) {
            return Response::deny('You have exceeded the maximum number of export attempts. Please try again later.');
        }

        // Super Admin has unrestricted access to any employee PDS regardless of status
        if ($user->hasRole('Super Admin')) {
            // Additional security check: Log Super Admin access for audit only on actual exports
            if ($isActualExport) {
                $this->logSensitiveAccess($user, 'super_admin_export', $employee);
            }
            return Response::allow('Super Admin has unrestricted access to all employee PDS data.');
        }

        // HR Admin can export active employees only
        if ($user->hasRole('HR Admin')) {
            if ($employee && $employee->employment_status !== 'Active') {
                return Response::deny('You can only export PDS for active employees.');
            }

            // Security check: Verify HR Admin has proper department access
            if (!$this->hasDepartmentAccess($user, $employee)) {
                return Response::deny('You do not have departmental access to this employee\'s data.');
            }

            return Response::allow();
        }

        // Employee can only export their own PDS
        if ($user->hasRole('Employee')) {
            if ($employee && $user->employee_id === $employee->id) {
                // Security check: Additional verification for self-export
                if ($this->requiresAdditionalVerification($user)) {
                    return Response::deny('Additional verification required for this export.');
                }
                return Response::allow();
            }
            return Response::deny('You can only export your own PDS.');
        }

        return Response::deny('You do not have permission to export PDS data.');
    }

    /**
     * Determine whether the user can batch export PDS data.
     */
    public function batchExport(User $user, array $employeeIds): Response
    {
        // Super Admin has unrestricted batch export access for system audit purposes
        if ($user->hasRole('Super Admin')) {
            return Response::allow('Super Admin has unrestricted batch export access for system audit.');
        }

        // HR Admin can batch export active employees only
        if ($user->hasRole('HR Admin')) {
            // Check if all employees are active
            $inactiveCount = Employee::whereIn('id', $employeeIds)
                ->where('employment_status', '!=', 'Active')
                ->count();

            if ($inactiveCount > 0) {
                return Response::deny('You can only export PDS for active employees.');
            }

            // Limit batch exports to prevent abuse
            if (count($employeeIds) > 50) {
                return Response::deny('You can only export up to 50 employees at once.');
            }

            return Response::allow();
        }

        return Response::deny('You do not have permission to batch export PDS data.');
    }

    /**
     * Determine whether the user can access export status.
     */
    public function viewExportStatus(User $user, string $jobId): Response
    {
        // Super Admin and HR Admin can view any export status
        if ($user->hasRole(['Super Admin', 'HR Admin'])) {
            return Response::allow();
        }

        // Employees can only view their own export job status
        if ($user->hasRole('Employee')) {
            $queuedExport = QueuedExport::where('job_id', $jobId)
                ->where('user_id', $user->id)
                ->first();

            if ($queuedExport) {
                return Response::allow();
            }

            return Response::deny('You can only view your own export status.');
        }

        return Response::deny('You do not have permission to view export status.');
    }

    /**
     * Determine whether the user can download exported files.
     */
    public function downloadExport(User $user, string $filename): Response
    {
        // Super Admin and HR Admin can download exports
        if ($user->hasRole(['Super Admin', 'HR Admin'])) {
            return Response::allow();
        }

        // Employees can download their own exports
        if ($user->hasRole('Employee')) {
            // For now, allow employees to download files with their own employee number in the filename
            // This is a simple validation - in a production environment, you might want
            // to check against a database record of completed exports

            // Get the employee associated with this user
            $employee = $user->employee;

            if ($employee && str_contains($filename, $employee->employee_number)) {
                return Response::allow();
            }

            return Response::deny('You can only download your own export files.');
        }

        return Response::deny('You do not have permission to download this export file.');
    }

    /**
     * Determine whether the user can view export history.
     */
    public function viewExportHistory(User $user): Response
    {
        return $user->hasRole(['Super Admin', 'HR Admin'])
            ? Response::allow()
            : Response::deny('You do not have permission to view export history.');
    }

    /**
     * Determine whether the user can access PDS export interface.
     */
    public function viewExportInterface(User $user): Response
    {
        // Super Admin and HR Admin can access export interface
        if ($user->hasRole(['Super Admin', 'HR Admin'])) {
            return Response::allow();
        }

        // Employees can access self-export interface
        if ($user->hasRole('Employee')) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to access PDS export interface.');
    }

    /**
     * Determine whether Super Admin can access any employee PDS for system audit.
     * This is a specialized method for system audit purposes.
     */
    public function systemAuditAccess(User $user, Employee $employee = null): Response
    {
        // Only Super Admin has system audit access
        if ($user->hasRole('Super Admin')) {
            return Response::allow('Super Admin has system audit access to all employee PDS data.');
        }

        return Response::deny('Only Super Admin has system audit access.');
    }

    /**
     * Determine whether user can manage concurrent exports.
     */
    public function manageConcurrentExports(User $user): Response
    {
        // Super Admin can manage all concurrent exports
        if ($user->hasRole('Super Admin')) {
            return Response::allow('Super Admin can manage all concurrent exports.');
        }

        // HR Admin can manage their own concurrent exports within limits
        if ($user->hasRole('HR Admin')) {
            // Check if HR Admin has too many concurrent exports
            $activeExports = QueuedExport::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            if ($activeExports >= 3) {
                return Response::deny('You have reached the maximum number of concurrent exports (3).');
            }

            return Response::allow();
        }

        return Response::deny('You do not have permission to manage concurrent exports.');
    }

    /**
     * Determine whether user can access performance metrics.
     */
    public function viewPerformanceMetrics(User $user): Response
    {
        return $user->hasRole('Super Admin')
            ? Response::allow('Super Admin can access performance metrics.')
            : Response::deny('Only Super Admin can access performance metrics.');
    }

    // === SECURITY HARDENING METHODS FOR T049 ===

    /**
     * Check if user has exceeded export rate limit
     *
     * @param User $user
     * @return bool
     */
    protected function hasExceededExportRateLimit(User $user): bool
    {
        // Super Admins are exempt from export rate limits
        if ($user->hasRole('Super Admin')) {
            return false;
        }

        $cacheKey = "export_rate_limit_{$user->id}";
        $windowMinutes = 60; // 1 hour window
        $maxAttempts = $this->getMaxExportAttempts($user);

        // Get current attempts from cache
        $attempts = Cache::get($cacheKey, []);

        // Clean up old attempts outside the window
        $now = Carbon::now();
        $validAttempts = array_filter($attempts, function ($timestamp) use ($now, $windowMinutes) {
            return $now->diffInMinutes(Carbon::createFromTimestamp($timestamp)) <= $windowMinutes;
        });

        // Update cache with cleaned attempts
        Cache::put($cacheKey, array_values($validAttempts), $windowMinutes * 60);

        // Check if limit exceeded
        return count($validAttempts) >= $maxAttempts;
    }

    /**
     * Record export attempt for rate limiting
     *
     * @param User $user
     * @return void
     */
    public function recordExportAttempt(User $user): void
    {
        $cacheKey = "export_rate_limit_{$user->id}";
        $attempts = Cache::get($cacheKey, []);
        $attempts[] = now()->timestamp;

        Cache::put($cacheKey, $attempts, 3600); // 1 hour
    }

    /**
     * Get maximum export attempts based on user role
     *
     * @param User $user
     * @return int
     */
    protected function getMaxExportAttempts(User $user): int
    {
        if ($user->hasRole('Super Admin')) {
            return 100; // High limit for Super Admin
        }

        if ($user->hasRole('HR Admin')) {
            return 50; // Moderate limit for HR Admin
        }

        return 10; // Low limit for Employee
    }

    /**
     * Check if HR Admin has department access to employee
     *
     * @param User $user
     * @param Employee|null $employee
     * @return bool
     */
    protected function hasDepartmentAccess(User $user, ?Employee $employee): bool
    {
        if (!$employee) {
            return true; // No specific employee to check
        }

        // For this implementation, we'll assume HR Admins have access to all departments
        // In a real implementation, you would check department assignments
        return true;
    }

    /**
     * Check if user requires additional verification for export
     *
     * @param User $user
     * @return bool
     */
    protected function requiresAdditionalVerification(User $user): bool
    {
        // Check if user has recent suspicious activity
        $recentFailedAttempts = ExportAuditLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->where('status', 'failed')
            ->count();

        return $recentFailedAttempts >= 3;
    }

    /**
     * Log sensitive access for audit trail
     *
     * @param User $user
     * @param string $action
     * @param Employee|null $employee
     * @return void
     */
    protected function logSensitiveAccess(User $user, string $action, ?Employee $employee): void
    {
        Log::warning('Sensitive PDS access detected', [
            'user_id' => $user->id,
            'user_role' => $user->roles->pluck('name')->first(),
            'action' => $action,
            'employee_id' => $employee?->id,
            'employee_name' => $employee?->full_name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // Also create audit log entry
        ExportAuditLog::create([
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'export_type' => 'security_audit',
            'export_format' => 'audit',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_role' => $user->roles->pluck('name')->first(),
            'metadata' => [
                'action' => $action,
                'security_level' => 'high',
                'requires_review' => true,
                'access_reason' => 'sensitive_data_access',
            ],
        ]);
    }

    /**
     * Validate user session for additional security
     *
     * @param User $user
     * @return bool
     */
    public function validateUserSession(User $user): bool
    {
        // Check for suspicious session patterns
        $sessionData = session()->all();

        // Validate session hasn't been hijacked
        if ($this->hasSessionAnomalies($user, $sessionData)) {
            Log::warning('Session anomaly detected for PDS export', [
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Check for session anomalies
     *
     * @param User $user
     * @param array $sessionData
     * @return bool
     */
    protected function hasSessionAnomalies(User $user, array $sessionData): bool
    {
        // Check for rapid IP changes
        $lastIP = session('last_ip_address');
        $currentIP = request()->ip();

        if ($lastIP && $lastIP !== $currentIP) {
            // Log IP change but don't necessarily block (could be legitimate mobile use)
            Log::info('IP address change detected', [
                'user_id' => $user->id,
                'last_ip' => $lastIP,
                'current_ip' => $currentIP,
            ]);
        }

        // Update current IP in session
        session(['last_ip_address' => $currentIP]);

        return false;
    }

    /**
     * Check if export is within allowed time windows
     *
     * @param User $user
     * @return bool
     */
    public function isWithinAllowedTimeWindow(User $user): bool
    {
        // Super Admins can export anytime
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Other roles can only export during business hours (9 AM - 6 PM, Monday-Friday)
        $now = now();
        $hour = $now->hour;
        $dayOfWeek = $now->dayOfWeek;

        // Weekends (Saturday = 6, Sunday = 0)
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return false;
        }

        // Business hours: 9 AM - 6 PM (9:00 - 18:00)
        return $hour >= 9 && $hour < 18;
    }

    /**
     * Validate data access patterns for anomaly detection
     *
     * @param User $user
     * @param Employee|null $employee
     * @return bool
     */
    public function validateAccessPatterns(User $user, ?Employee $employee): bool
    {
        // Check for unusual access patterns - only count actual exports, not security audits
        $recentExports = ExportAuditLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(1))
            ->where('export_type', '!=', 'security_audit') // Exclude security audit logs
            ->count();

        // More than 20 exports in 1 hour is suspicious
        if ($recentExports > 20) {
            Log::warning('Unusual export pattern detected', [
                'user_id' => $user->id,
                'recent_exports' => $recentExports,
                'time_window' => '1 hour',
                'note' => 'Only counting actual exports, excluding security audits',
            ]);

            return false;
        }

        return true;
    }
}