<?php

namespace App\Services;

use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OfficeAssignmentService
{
    /**
     * Assign user to office with specific role
     */
    public function assignUserToOffice(User $user, Office $office, string $role, array $data = []): OfficeAssignment
    {
        return DB::transaction(function () use ($user, $office, $role, $data) {
            // If assigning as Department Head, deactivate existing Department Head first
            if ($role === 'Department Head') {
                $this->deactivateExistingDepartmentHead($office);
            }

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

            // Sync User roles based on assignment
            $this->syncUserRolesFromAssignment($user, $role, true);

            // Activity logged

            return $assignment;
        });
    }

    /**
     * Assign Department Head with automatic transition of existing Department Head
     */
    public function assignDepartmentHead(User $user, Office $office, array $data = []): array
    {
        return DB::transaction(function () use ($user, $office, $data) {
            $results = [
                'new_assignment' => null,
                'deactivated_assignments' => [],
                'updated_employees' => [],
                'synced_roles' => [],
            ];

            // Step 1: Find and deactivate existing Department Head assignments
            $existingDepartmentHeads = OfficeAssignment::where('office_id', $office->id)
                ->where('role', 'Department Head')
                ->where('is_active', true)
                ->get();

            foreach ($existingDepartmentHeads as $existingAssignment) {
                // Check if there's already an inactive assignment with the same end date
                $existingInactive = DB::table('office_assignments')
                    ->where('employee_id', $existingAssignment->employee_id)
                    ->where('office_id', $existingAssignment->office_id)
                    ->where('is_active', 0)
                    ->where('ended_date', now()->toDateString())
                    ->where('id', '!=', $existingAssignment->id)
                    ->first();

                $endDate = now()->toDateString();
                if ($existingInactive) {
                    // Use a different end date to avoid unique constraint violation
                    $endDate = now()->addDay()->toDateString();
                }

                // Deactivate the assignment using raw query to avoid unique constraint issues
                DB::table('office_assignments')
                    ->where('id', $existingAssignment->id)
                    ->update([
                        'is_active' => false,
                        'ended_date' => $endDate,
                        'remarks' => ($existingAssignment->remarks ?? '') . "\n\nAutomatically deactivated when assigning new Department Head: " . $user->name,
                        'updated_at' => now(),
                    ]);

                // Update employee record if linked
                if ($existingAssignment->employee_id) {
                    $employee = Employee::find($existingAssignment->employee_id);
                    if ($employee) {
                        $employee->update(['is_department_head' => false]);
                        $results['updated_employees'][] = [
                            'employee_id' => $employee->id,
                            'name' => $employee->first_name . ' ' . $employee->last_name,
                            'action' => 'removed_department_head_status'
                        ];
                    }
                }

                // Remove Department Head role from user
                $this->syncUserRolesFromAssignment($existingAssignment->user, 'Department Head', false);

                $results['deactivated_assignments'][] = [
                    'assignment_id' => $existingAssignment->id,
                    'user_name' => $existingAssignment->user->name,
                    'role' => $existingAssignment->role,
                ];

                // // Activity logged
                //         'previous_assignment_id' => $existingAssignment->id,
                //         'previous_user_id' => $existingAssignment->user_id,
                //         'office_id' => $office->id,
                //         'new_department_head_id' => $user->id,
                //         'deactivated_by' => Auth::id(),
                //     ]);
            }

            // Step 2: Deactivate existing assignments for the new Department Head user/office combination
            $this->deactivateExistingAssignments($user, $office);

            // Step 3: Create new Department Head assignment
            $newAssignment = OfficeAssignment::create([
                'user_id' => $user->id,
                'employee_id' => $data['employee_id'] ?? null,
                'office_id' => $office->id,
                'role' => 'Department Head',
                'position' => 'Department Head',
                'assigned_date' => $data['assigned_date'] ?? now()->toDateString(),
                'started_date' => $data['started_date'] ?? now()->toDateString(),
                'remarks' => ($data['remarks'] ?? '') . "\n\nAutomatically assigned as Department Head",
                'assigned_by' => Auth::id(),
                'is_active' => true,
            ]);

            // Step 4: Update employee record if linked
            if (!empty($data['employee_id'])) {
                $employee = Employee::find($data['employee_id']);
                if ($employee) {
                    $employee->update(['is_department_head' => true]);
                    $results['updated_employees'][] = [
                        'employee_id' => $employee->id,
                        'name' => $employee->first_name . ' ' . $employee->last_name,
                        'action' => 'assigned_department_head_status'
                    ];
                }
            }

            // Step 5: Sync Department Head role to user
            $this->syncUserRolesFromAssignment($user, 'Department Head', true);
            $results['synced_roles'][] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role' => 'Department Head',
                'action' => 'assigned'
            ];

            // Step 6: Update user's default office if this is their primary assignment
            if ($data['is_primary'] ?? true) {
                $user->update(['office_id' => $office->id]);
            }

            $results['new_assignment'] = $newAssignment;

            // // Activity logged
            //         'new_assignment_id' => $newAssignment->id,
            //         'user_id' => $user->id,
            //         'office_id' => $office->id,
            //         'deactivated_count' => count($existingDepartmentHeads),
            //         'assigned_by' => Auth::id(),
            //     ]);

            return $results;
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

                // Sync User roles based on new assignment role
                $this->syncUserRolesFromAssignment($assignment->user, $assignment->role, $assignment->is_active);
            }

            // Activity logged
            //     'assignment_id' => $assignment->id,
            //     'old_role' => $oldRole,
            //     'new_role' => $assignment->role,
            //     'updated_by' => Auth::id(),
            // ]);

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

            // Sync User roles based on deactivation
            $this->syncUserRolesFromAssignment($assignment->user, $assignment->role, false);

            // Activity logged
            //     'assignment_id' => $assignment->id,
            //     'reason' => $reason,
            //     'deactivated_by' => Auth::id(),
            // ]);

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
        $existingAssignments = OfficeAssignment::where('user_id', $user->id)
            ->where('office_id', $office->id)
            ->where('is_active', true)
            ->get();

        foreach ($existingAssignments as $assignment) {
            // Check if there's already an inactive assignment with the same end date
            $existingInactive = DB::table('office_assignments')
                ->where('employee_id', $assignment->employee_id)
                ->where('office_id', $assignment->office_id)
                ->where('is_active', 0)
                ->where('ended_date', now()->toDateString())
                ->where('id', '!=', $assignment->id)
                ->first();

            $endDate = now()->toDateString();
            if ($existingInactive) {
                // Use a different end date to avoid unique constraint violation
                $endDate = now()->addDay()->toDateString();
            }

            DB::table('office_assignments')
                ->where('id', $assignment->id)
                ->update([
                    'is_active' => false,
                    'ended_date' => $endDate,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Deactivate existing Department Head assignment for office
     */
    private function deactivateExistingDepartmentHead(Office $office): void
    {
        $existingDepartmentHeads = OfficeAssignment::where('office_id', $office->id)
            ->where('role', 'Department Head')
            ->where('is_active', true)
            ->get();

        foreach ($existingDepartmentHeads as $assignment) {
            // Update employee record if linked
            if ($assignment->employee_id) {
                $employee = Employee::find($assignment->employee_id);
                if ($employee) {
                    $employee->update(['is_department_head' => false]);
                }
            }

            // Remove Department Head role from user
            $this->syncUserRolesFromAssignment($assignment->user, 'Department Head', false);

            // Check if there's already an inactive assignment with the same end date
            $existingInactive = DB::table('office_assignments')
                ->where('employee_id', $assignment->employee_id)
                ->where('office_id', $assignment->office_id)
                ->where('is_active', 0)
                ->where('ended_date', now()->toDateString())
                ->where('id', '!=', $assignment->id)
                ->first();

            $endDate = now()->toDateString();
            if ($existingInactive) {
                // Use a different end date to avoid unique constraint violation
                $endDate = now()->addDay()->toDateString();
            }

            // Deactivate the assignment using raw query to avoid unique constraint issues
            DB::table('office_assignments')
                ->where('id', $assignment->id)
                ->update([
                    'is_active' => false,
                    'ended_date' => $endDate,
                    'remarks' => ($assignment->remarks ?? '') . "\n\nAutomatically deactivated when assigning new Department Head",
                    'updated_at' => now(),
                ]);
        }
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
     * Sync User roles based on office assignment changes
     */
    private function syncUserRolesFromAssignment(User $user, string $role, bool $isActive): void
    {
        // Only sync specific roles to User permissions
        switch ($role) {
            case 'Department Head':
                if ($isActive) {
                    // Add Department Head role if they don't have it
                    if (!$user->hasRole('Department Head')) {
                        $user->assignRole('Department Head');
                        $this->logRoleSyncActivity($user, 'Department Head', 'assigned');
                    }

                    // Remove Employee role if they have it (to avoid conflicts)
                    if ($user->hasRole('Employee')) {
                        $user->removeRole('Employee');
                        $this->logRoleSyncActivity($user, 'Employee', 'removed');
                    }
                } else {
                    // Remove Department Head role if assignment is deactivated
                    if ($user->hasRole('Department Head')) {
                        $user->removeRole('Department Head');
                        $this->logRoleSyncActivity($user, 'Department Head', 'removed');

                        // Add Employee role back if they don't have any other special roles
                        if (!$user->hasAnyRole(['HR Admin', 'Super Admin', 'Assessor', 'Final Approver'])) {
                            $user->assignRole('Employee');
                            $this->logRoleSyncActivity($user, 'Employee', 'assigned');
                        }
                    }
                }
                break;

            case 'Assessor':
                if ($isActive && !$user->hasRole('Assessor')) {
                    $user->assignRole('Assessor');
                    $this->logRoleSyncActivity($user, 'Assessor', 'assigned');
                } elseif (!$isActive && $user->hasRole('Assessor')) {
                    $user->removeRole('Assessor');
                    $this->logRoleSyncActivity($user, 'Assessor', 'removed');
                }
                break;

            case 'Final Approver':
                if ($isActive && !$user->hasRole('Final Approver')) {
                    $user->assignRole('Final Approver');
                    $this->logRoleSyncActivity($user, 'Final Approver', 'assigned');
                } elseif (!$isActive && $user->hasRole('Final Approver')) {
                    $user->removeRole('Final Approver');
                    $this->logRoleSyncActivity($user, 'Final Approver', 'removed');
                }
                break;
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

    /**
     * Synchronize employee office assignments when employee office changes
     */
    public function synchronizeEmployeeOfficeAssignments(Employee $employee, int $newOfficeId): array
    {
        return DB::transaction(function () use ($employee, $newOfficeId) {
            $results = [
                'deactivated' => [],
                'created' => [],
                'updated' => [],
                'errors' => []
            ];

            // Get current active assignments for this employee
            $activeAssignments = OfficeAssignment::where('employee_id', $employee->id)
                ->where('is_active', true)
                ->get();

            // Deactivate assignments in different offices
            foreach ($activeAssignments as $assignment) {
                if ($assignment->office_id !== $newOfficeId) {
                    $assignment->update([
                        'is_active' => false,
                        'end_date' => now(),
                        'updated_by' => auth()->id()
                    ]);
                    $results['deactivated'][] = $assignment->id;
                }
            }

            // Check if employee already has an active assignment in the target office
            $existingAssignment = OfficeAssignment::where('employee_id', $employee->id)
                ->where('office_id', $newOfficeId)
                ->where('is_active', true)
                ->first();

            if (!$existingAssignment) {
                // Create new assignment in the target office
                $office = Office::findOrFail($newOfficeId);

                // Determine role based on employee's properties
                $role = 'Member'; // Default role
                if ($employee->is_department_head) {
                    $role = 'Department Head';
                }

                $newAssignment = OfficeAssignment::create([
                    'employee_id' => $employee->id,
                    'office_id' => $newOfficeId,
                    'role' => $role,
                    'is_active' => true,
                    'start_date' => now(),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id()
                ]);

                $results['created'][] = $newAssignment->id;
            } else {
                $results['updated'][] = $existingAssignment->id;
            }

            return $results;
        });
    }

    /**
     * Get employees by office with consistent assignment checking
     */
    public function getOfficeEmployees(Office $office, bool $consistentOnly = true): Collection
    {
        $query = Employee::where('office_id', $office->id)
            ->with(['activeOfficeAssignments']);

        if ($consistentOnly) {
            // Only get employees who have active assignments in this office
            $query->whereHas('activeOfficeAssignments', function ($q) use ($office) {
                $q->where('office_id', $office->id);
            });
        }

        return $query->get();
    }

    /**
     * Check and fix inconsistent office assignments for all employees
     */
    public function fixInconsistentAssignments(): array
    {
        $syncService = app(OfficeAssignmentSynchronizationService::class);
        return $syncService->synchronizeAll();
    }

    /**
     * Validate employee office assignment consistency
     */
    public function validateEmployeeConsistency(Employee $employee): array
    {
        $issues = [];

        // Check if employee has active assignments in different office
        $assignmentsInDifferentOffice = $employee->activeOfficeAssignments
            ->filter(function ($assignment) use ($employee) {
                return $assignment->office_id !== $employee->office_id;
            });

        if ($assignmentsInDifferentOffice->count() > 0) {
            $issues[] = 'Has active assignments in different office';
            foreach ($assignmentsInDifferentOffice as $assignment) {
                $issues[] = "Active in {$assignment->office->name} (ID: {$assignment->id})";
            }
        }

        // Check if employee has no active assignments
        if ($employee->activeOfficeAssignments->count() === 0) {
            $issues[] = 'No active office assignments';
        }

        // Check if employee's department head status matches assignments
        $activeDepartmentHeadAssignment = $employee->activeOfficeAssignments
            ->where('role', 'Department Head')
            ->where('is_active', true)
            ->first();

        if ($employee->is_department_head && !$activeDepartmentHeadAssignment) {
            $issues[] = 'Marked as department head but no active Department Head assignment';
        }

        if (!$employee->is_department_head && $activeDepartmentHeadAssignment) {
            $issues[] = 'Has Department Head assignment but not marked as department head';
        }

        return [
            'is_consistent' => empty($issues),
            'issues' => $issues
        ];
    }

    private function logRoleSyncActivity(User $user, string $role, string $action): void
    {
        $causer = Auth::user();

        activity('office_assignment_role_sync')
            ->causedBy($causer ?? $user)
            ->withProperties([
                'action' => $action,
                'role' => $role,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'synced_from' => 'office_assignment',
                'performed_by' => $causer?->id,
                'performed_by_email' => $causer?->email,
                'office_id' => $user->office_id,
                'timestamp' => now()->toIso8601String(),
            ])
            ->log("Role {$action}: {$role} for {$user->email}");
    }
}
