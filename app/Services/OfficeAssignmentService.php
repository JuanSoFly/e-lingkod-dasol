<?php

namespace App\Services;

use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\Activity;

class OfficeAssignmentService
{
    /**
     * Assign user to office with specific role
     */
    public function assignUserToOffice(User $user, Office $office, string $role, array $data = []): OfficeAssignment
    {
        return DB::transaction(function () use ($user, $office, $role, $data) {
            // Deactivate existing assignments for this user/office combination
            $this->deactivateExistingAssignments($user, $office);

            $assignment = OfficeAssignment::create([
                'user_id' => $user->id,
                'employee_id' => $data['employee_id'] ?? null,
                'office_id' => $office->id,
                'role' => $role,
                'assigned_date' => $data['assigned_date'] ?? now()->toDateString(),
                'remarks' => $data['remarks'] ?? null,
                'assigned_by' => Auth::id(),
                'is_active' => true,
            ]);

            // Update user's default office if this is their primary assignment
            if ($data['is_primary'] ?? false) {
                $user->update(['office_id' => $office->id]);
            }

            // Update employee if linked
            if (!empty($data['employee_id'])) {
                $employee = Employee::find($data['employee_id']);
                if ($employee && $role === 'Department Head') {
                    $employee->update(['is_department_head' => true]);
                }
            }

            Activity::log('User assigned to office', [
                'user_id' => $user->id,
                'office_id' => $office->id,
                'role' => $role,
                'assigned_by' => Auth::id(),
            ]);

            return $assignment;
        });
    }

    /**
     * Bulk assign users to office
     */
    public function bulkAssignUsersToOffice(Office $office, array $assignments): Collection
    {
        return DB::transaction(function () use ($office, $assignments) {
            $createdAssignments = collect();

            foreach ($assignments as $assignmentData) {
                $user = User::findOrFail($assignmentData['user_id']);
                $assignment = $this->assignUserToOffice($user, $office, $assignmentData['role'], $assignmentData);
                $createdAssignments->push($assignment);
            }

            return $createdAssignments;
        });
    }

    /**
     * Update user office assignment
     */
    public function updateAssignment(OfficeAssignment $assignment, array $data): OfficeAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $oldRole = $assignment->role;

            $assignment->update([
                'role' => $data['role'] ?? $assignment->role,
                'remarks' => $data['remarks'] ?? $assignment->remarks,
            ]);

            // Handle role change to/from Department Head
            if ($oldRole !== $assignment->role) {
                $this->handleRoleChange($assignment, $oldRole, $assignment->role);
            }

            Activity::log('Office assignment updated', [
                'assignment_id' => $assignment->id,
                'old_role' => $oldRole,
                'new_role' => $assignment->role,
                'updated_by' => Auth::id(),
            ]);

            return $assignment;
        });
    }

    /**
     * Deactivate office assignment
     */
    public function deactivateAssignment(OfficeAssignment $assignment, string $reason = null): bool
    {
        return DB::transaction(function () use ($assignment, $reason) {
            $assignment->update([
                'is_active' => false,
                'ended_date' => now()->toDateString(),
                'remarks' => ($assignment->remarks ?? '') . "\n\nDeactivated: " . ($reason ?? 'No reason provided'),
            ]);

            // Handle Department Head role deactivation
            if ($assignment->role === 'Department Head' && $assignment->employee_id) {
                $employee = Employee::find($assignment->employee_id);
                if ($employee) {
                    $employee->update(['is_department_head' => false]);
                }
            }

            Activity::log('Office assignment deactivated', [
                'assignment_id' => $assignment->id,
                'reason' => $reason,
                'deactivated_by' => Auth::id(),
            ]);

            return true;
        });
    }

    /**
     * Transfer user between offices
     */
    public function transferUser(User $user, Office $newOffice, string $newRole, array $data = []): OfficeAssignment
    {
        return DB::transaction(function () use ($user, $newOffice, $newRole, $data) {
            // Deactivate all current assignments
            OfficeAssignment::where('user_id', $user->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ended_date' => now()->toDateString(),
                ]);

            // Create new assignment
            return $this->assignUserToOffice($user, $newOffice, $newRole, array_merge($data, [
                'is_primary' => true,
            ]));
        });
    }

    /**
     * Get user's active office assignments
     */
    public function getUserAssignments(User $user): Collection
    {
        return OfficeAssignment::with(['office', 'employee'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('assigned_date', 'desc')
            ->get();
    }

    /**
     * Get office assignments by role
     */
    public function getOfficeAssignmentsByRole(Office $office, string $role): Collection
    {
        return OfficeAssignment::with(['user', 'employee'])
            ->where('office_id', $office->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('assigned_date', 'desc')
            ->get();
    }

    /**
     * Get all active assignments for an office
     */
    public function getOfficeActiveAssignments(Office $office): Collection
    {
        return OfficeAssignment::with(['user', 'employee'])
            ->where('office_id', $office->id)
            ->where('is_active', true)
            ->orderBy('role')
            ->orderBy('assigned_date', 'desc')
            ->get();
    }

    /**
     * Check if user has specific role in office
     */
    public function hasRoleInOffice(User $user, Office $office, string $role): bool
    {
        return OfficeAssignment::where('user_id', $user->id)
            ->where('office_id', $office->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get user's highest priority role in office
     */
    public function getUserPrimaryRole(User $user, Office $office): ?string
    {
        $rolePriorities = [
            'Final Approver' => 1,
            'Assessor' => 2,
            'Department Head' => 3,
            'Staff' => 4,
        ];

        $assignment = OfficeAssignment::where('user_id', $user->id)
            ->where('office_id', $office->id)
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($assignment) use ($rolePriorities) {
                return $rolePriorities[$assignment->role] ?? 999;
            })
            ->first();

        return $assignment?->role;
    }

    /**
     * Get department heads for office
     */
    public function getDepartmentHeads(Office $office): Collection
    {
        return $this->getOfficeAssignmentsByRole($office, 'Department Head');
    }

    /**
     * Get assessors for office or all offices
     */
    public function getAssessors(?Office $office = null): Collection
    {
        $query = OfficeAssignment::with(['user', 'office'])
            ->where('role', 'Assessor')
            ->where('is_active', true);

        if ($office) {
            $query->where('office_id', $office->id);
        }

        return $query->orderBy('assigned_date', 'desc')->get();
    }

    /**
     * Get final approvers for office or all offices
     */
    public function getFinalApprovers(?Office $office = null): Collection
    {
        $query = OfficeAssignment::with(['user', 'office'])
            ->where('role', 'Final Approver')
            ->where('is_active', true);

        if ($office) {
            $query->where('office_id', $office->id);
        }

        return $query->orderBy('assigned_date', 'desc')->get();
    }

    /**
     * Get users eligible for specific role
     */
    public function getUsersEligibleForRole(Office $office, string $role): Collection
    {
        $query = User::whereDoesntHave('officeAssignments', function ($query) use ($office, $role) {
            $query->where('office_id', $office->id)
                ->where('role', $role)
                ->where('is_active', true);
        });

        // Additional eligibility checks based on role
        switch ($role) {
            case 'Department Head':
                // Must be an employee
                $query->whereHas('employee');
                break;

            case 'Assessor':
            case 'Final Approver':
                // Must have appropriate permissions
                $query->where(function ($q) use ($role) {
                    $q->whereHas('permissions', function ($permQuery) use ($role) {
                        $permission = $role === 'Assessor' ? 'opcr.assess' : 'opcr.approve';
                        $permQuery->where('name', $permission);
                    })->orWhereHas('roles', function ($roleQuery) use ($role) {
                        $roleName = $role === 'Assessor' ? 'Assessor' : 'Final Approver';
                        $roleQuery->where('name', $roleName);
                    });
                });
                break;
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get assignment statistics for office
     */
    public function getOfficeAssignmentStatistics(Office $office): array
    {
        $assignments = OfficeAssignment::where('office_id', $office->id)
            ->where('is_active', true)
            ->get();

        $byRole = $assignments->groupBy('role')->map(function ($group) {
            return $group->count();
        });

        return [
            'total_assignments' => $assignments->count(),
            'by_role' => $byRole->toArray(),
            'has_department_head' => $byRole->has('Department Head') && $byRole->get('Department Head') > 0,
            'has_assessor' => $byRole->has('Assessor') && $byRole->get('Assessor') > 0,
            'has_final_approver' => $byRole->has('Final Approver') && $byRole->get('Final Approver') > 0,
        ];
    }

    /**
     * Validate assignment data
     */
    public function validateAssignmentData(array $data): array
    {
        $errors = [];

        if (empty($data['user_id'])) {
            $errors['user_id'] = 'User is required';
        }

        if (empty($data['office_id'])) {
            $errors['office_id'] = 'Office is required';
        }

        if (empty($data['role'])) {
            $errors['role'] = 'Role is required';
        } elseif (!in_array($data['role'], ['Department Head', 'Assessor', 'Final Approver', 'Staff'])) {
            $errors['role'] = 'Invalid role';
        }

        // Check if user already has this role in the office
        if (!empty($data['user_id']) && !empty($data['office_id']) && !empty($data['role'])) {
            $existing = OfficeAssignment::where('user_id', $data['user_id'])
                ->where('office_id', $data['office_id'])
                ->where('role', $data['role'])
                ->where('is_active', true)
                ->exists();

            if ($existing) {
                $errors['duplicate'] = 'User already has this role in the office';
            }
        }

        return $errors;
    }

    /**
     * Deactivate existing assignments for user/office combination
     */
    private function deactivateExistingAssignments(User $user, Office $office): void
    {
        OfficeAssignment::where('user_id', $user->id)
            ->where('office_id', $office->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_date' => now()->toDateString(),
            ]);
    }

    /**
     * Handle role changes for employee assignments
     */
    private function handleRoleChange(OfficeAssignment $assignment, string $oldRole, string $newRole): void
    {
        if (!$assignment->employee_id) {
            return;
        }

        $employee = Employee::find($assignment->employee_id);
        if (!$employee) {
            return;
        }

        // Handle Department Head role changes
        if ($oldRole === 'Department Head' && $newRole !== 'Department Head') {
            $employee->update(['is_department_head' => false]);
        } elseif ($oldRole !== 'Department Head' && $newRole === 'Department Head') {
            $employee->update(['is_department_head' => true]);
        }
    }

    /**
     * Export assignments for reporting
     */
    public function exportAssignmentsForReporting(?Office $office = null): array
    {
        $query = OfficeAssignment::with(['user:id,name,email', 'employee:id,employee_number,first_name,last_name', 'office:id,name,code']);

        if ($office) {
            $query->where('office_id', $office->id);
        }

        return $query->get()->map(function ($assignment) {
            return [
                'user_name' => $assignment->user->name,
                'user_email' => $assignment->user->email,
                'employee_number' => $assignment->employee?->employee_number,
                'employee_name' => $assignment->employee ? "{$assignment->employee->first_name} {$assignment->employee->last_name}" : null,
                'office_code' => $assignment->office->code,
                'office_name' => $assignment->office->name,
                'role' => $assignment->role,
                'assigned_date' => $assignment->assigned_date,
                'is_active' => $assignment->is_active ? 'Yes' : 'No',
                'remarks' => $assignment->remarks,
            ];
        })->toArray();
    }
}