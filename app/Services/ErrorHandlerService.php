<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\MessageBag;

class ErrorHandlerService
{
    /**
     * Handle cases where user doesn't have linked employee profile
     */
    public static function handleMissingEmployeeProfile($user = null, $redirectRoute = 'dashboard'): RedirectResponse
    {
        $user = $user ?? auth()->user();

        Log::warning('User accessed employee-specific feature without employee profile', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'route' => request()->route()->getName(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        return redirect()
            ->route($redirectRoute)
            ->with('error', self::getEmployeeProfileMissingMessage($user));
    }

    /**
     * Handle cases where employee doesn't have linked user account
     */
    public static function handleMissingUserAccount($employee, $redirectRoute = 'dashboard'): RedirectResponse
    {
        Log::warning('Access attempt for employee without user account', [
            'employee_id' => $employee?->id,
            'employee_name' => $employee?->full_name,
            'route' => request()->route()->getName(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        return redirect()
            ->route($redirectRoute)
            ->with('error', 'The requested employee does not have an associated user account.');
    }

    /**
     * Handle generic employee relationship errors with JSON response
     */
    public static function jsonMissingEmployeeProfile($user = null, $statusCode = 403): JsonResponse
    {
        $user = $user ?? auth()->user();

        Log::warning('API access without employee profile', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'endpoint' => request()->path(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        return response()->json([
            'success' => false,
            'message' => self::getEmployeeProfileMissingMessage($user),
            'error_code' => 'EMPLOYEE_PROFILE_MISSING'
        ], $statusCode);
    }

    /**
     * Handle case where user is not authorized for specific employee
     */
    public static function handleUnauthorizedEmployeeAccess($employee, $user = null): RedirectResponse
    {
        $user = $user ?? auth()->user();

        Log::warning('Unauthorized employee access attempt', [
            'user_id' => $user?->id,
            'target_employee_id' => $employee?->id,
            'route' => request()->route()->getName(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        return redirect()
            ->route('dashboard')
            ->with('error', 'You are not authorized to access the requested employee information.');
    }

    /**
     * Return appropriate message based on user role
     */
    private static function getEmployeeProfileMissingMessage($user): string
    {
        if (!$user) {
            return 'Authentication required. Please log in to access this feature.';
        }

        if ($user->hasRole('Employee')) {
            return 'Your user account is not properly linked to an employee profile. Please contact HR administrator to resolve this issue.';
        }

        if ($user->hasRole('HR Admin')) {
            return 'Your HR Admin account requires an associated employee profile for this feature. Please contact system administrator.';
        }

        return 'Your account requires an associated employee profile to access this feature. Please contact your administrator.';
    }

    /**
     * Log user-employee relationship issues for debugging
     */
    public static function logRelationshipIssue($issue, $context = []): void
    {
        Log::error('User-Employee Relationship Issue: ' . $issue, array_merge([
            'timestamp' => now()->toDateTimeString(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ], $context));
    }

    /**
     * Check for common user-employee relationship issues
     */
    public static function diagnoseRelationshipIssues($user = null): array
    {
        $user = $user ?? auth()->user();
        $issues = [];

        if (!$user) {
            $issues[] = 'User not authenticated';
            return $issues;
        }

        if ($user->hasRole('Employee') && !$user->employee) {
            $issues[] = 'Employee role without linked employee profile';
        }

        if ($user->hasRole('HR Admin') && !$user->employee) {
            $issues[] = 'HR Admin role without linked employee profile';
        }

        // Check for email synchronization issues
        if ($user->employee && $user->email !== $user->employee->email) {
            $issues[] = 'Email mismatch between user and employee records';
        }

        return $issues;
    }
}