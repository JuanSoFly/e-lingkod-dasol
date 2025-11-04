<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateEmployeeRelationship
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only validate if user is authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip validation for Super Admin
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Auto-sync User roles based on office assignments (CRITICAL FIX)
        $this->syncUserRolesFromOfficeAssignments($user);

        // For Employee role, ensure user has linked employee profile
        if ($user->hasRole('Employee')) {
            if (!$user->employee) {
                Log::warning('Employee role user without linked employee profile', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'ip_address' => $request->ip(),
                    'timestamp' => now()->toDateTimeString()
                ]);

                // Redirect to employee portal with error message
                return redirect()->route('dashboard')
                    ->with('error', 'Your user account is not properly linked to an employee profile. Please contact HR administrator.');
            }
        }

        return $next($request);
    }

    /**
     * Determine if the middleware should be applied to the request
     */
    public function only($roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Check if middleware should be applied
     */
    private function shouldApply($user): bool
    {
        if (!$user) {
            return false;
        }

        if (empty($this->roles)) {
            return true;
        }

        return $user->hasAnyRole($this->roles);
    }

    /**
     * Sync User roles from office assignments (AUTOMATIC ROLE SYNCHRONIZATION)
     * This runs on EVERY web request to ensure User roles stay in sync with office assignments
     */
    private function syncUserRolesFromOfficeAssignments($user): void
    {
        // Get active office assignments for this user
        $assignments = \App\Models\OfficeAssignment::where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>', now()); // Fixed: > instead of >= to include today
            })
            ->get();

        // Check for Department Head assignment
        $hasDepartmentHeadAssignment = $assignments->contains('role', \App\Models\OfficeAssignment::ROLE_DEPARTMENT_HEAD);

        // Sync Department Head role (CRITICAL: This runs every request)
        if ($hasDepartmentHeadAssignment && !$user->hasRole('Department Head')) {
            $user->assignRole('Department Head');

            // Remove Employee role to avoid conflicts
            if ($user->hasRole('Employee')) {
                $user->removeRole('Employee');
            }

            Log::info('AUTO-SYNCED Department Head role from office assignment', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'synced_at' => now()->toDateTimeString(),
                'sync_location' => 'ValidateEmployeeRelationship middleware',
            ]);
        } elseif (!$hasDepartmentHeadAssignment && $user->hasRole('Department Head')) {
            // Check if user should keep Department Head role due to other reasons
            if (!$user->hasAnyRole(['HR Admin', 'Super Admin'])) {
                $user->removeRole('Department Head');

                // Add Employee role back if no other special roles
                if (!$user->hasAnyRole(['Assessor', 'Final Approver'])) {
                    $user->assignRole('Employee');
                }

                Log::info('AUTO-REMOVED Department Head role (no active assignment)', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'synced_at' => now()->toDateTimeString(),
                    'sync_location' => 'ValidateEmployeeRelationship middleware',
                ]);
            }
        }

        // Sync other roles as needed
        $hasAssessorAssignment = $assignments->contains('role', \App\Models\OfficeAssignment::ROLE_ASSESSOR);
        if ($hasAssessorAssignment && !$user->hasRole('Assessor')) {
            $user->assignRole('Assessor');
        } elseif (!$hasAssessorAssignment && $user->hasRole('Assessor')) {
            $user->removeRole('Assessor');
        }

        $hasFinalApproverAssignment = $assignments->contains('role', \App\Models\OfficeAssignment::ROLE_FINAL_APPROVER);
        if ($hasFinalApproverAssignment && !$user->hasRole('Final Approver')) {
            $user->assignRole('Final Approver');
        } elseif (!$hasFinalApproverAssignment && $user->hasRole('Final Approver')) {
            $user->removeRole('Final Approver');
        }
    }
}
