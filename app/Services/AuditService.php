<?php

namespace App\Services;

use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;

class AuditService
{
    /**
     * Log employee data access
     */
    public static function logEmployeeAccess(int $employeeId, string $action = 'viewed', array $additional = []): void
    {
        $user = auth()->user();

        $properties = array_merge([
            'employee_id' => $employeeId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $additional);

        // Generate meaningful description based on employee context
        $employeeDescription = static::getEmployeeDescription($employeeId);

        // Log to activity log
        activity('employee_access')
            ->causedBy($user)
            ->withProperties($properties)
            ->log("User {$user->email} {$action} {$employeeDescription}");

        // Also log to Laravel log for monitoring
        Log::info('Employee data access', $properties);
    }
    
    /**
     * Log document request access
     */
    public static function logDocumentAccess(int $requestId, string $action = 'viewed', array $additional = []): void
    {
        $user = auth()->user();
        
        $properties = array_merge([
            'document_request_id' => $requestId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $additional);
        
        activity('document_access')
            ->causedBy($user)
            ->withProperties($properties)
            ->log("User {$user->email} {$action} document request ID {$requestId}");
            
        Log::info('Document request access', $properties);
    }
    
    /**
     * Log leave data access
     */
    public static function logLeaveAccess(int $leaveId, string $action = 'viewed', array $additional = []): void
    {
        $user = auth()->user();
        
        $properties = array_merge([
            'leave_application_id' => $leaveId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $additional);
        
        activity('leave_access')
            ->causedBy($user)
            ->withProperties($properties)
            ->log("User {$user->email} {$action} leave application ID {$leaveId}");
            
        Log::info('Leave application access', $properties);
    }
    
    /**
     * Log privacy violation attempts
     */
    public static function logPrivacyViolation(string $attemptedAction, array $details = []): void
    {
        $user = auth()->user();
        
        $properties = array_merge([
            'violation_type' => 'unauthorized_access_attempt',
            'attempted_action' => $attemptedAction,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_role' => $user->roles->pluck('name')->toArray(),
        ], $details);
        
        activity('privacy_violation')
            ->causedBy($user)
            ->withProperties($properties)
            ->log("PRIVACY VIOLATION: User {$user->email} attempted unauthorized action: {$attemptedAction}");
            
        // Critical alert
        Log::alert('Privacy violation attempt detected', $properties);
    }
    
    /**
     * Log authentication events
     */
    public static function logAuthentication(string $event, array $additional = []): void
    {
        $properties = array_merge([
            'event' => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ], $additional);

        if (auth()->check()) {
            $user = auth()->user();
            activity('authentication')
                ->causedBy($user)
                ->withProperties($properties)
                ->log("Authentication event: {$event} for user {$user->email}");
        } else {
            activity('authentication')
                ->withProperties($properties)
                ->log("Authentication event: {$event}");
        }

        Log::info('Authentication event', $properties);
    }

    /**
     * Get meaningful employee description for audit logs
     */
    private static function getEmployeeDescription(int $employeeId): string
    {
        // Handle index/general access (employee_id = 0)
        if ($employeeId === 0) {
            return 'employee list';
        }

        // Try to find the employee and get their name
        $employee = Employee::find($employeeId);

        if ($employee) {
            return "employee data for {$employee->full_name}";
        }

        // Employee not found but ID was provided
        return "employee data for employee ID {$employeeId} (not found)";
    }
}