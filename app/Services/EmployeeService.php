<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Models\Office;
use App\Models\OfficeAssignment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * Get employees list with role-based filtering
     */
    public function getEmployeesList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = Employee::with(['user', 'office', 'officeAssignments' => function ($query) {
            $query->where('is_active', true)->with('office');
        }]);

        // Apply role-based filtering
        if ($user->hasRole('Employee')) {
            // Employees can only see their own profile
            $query->where('id', $user->employee?->id);
        } elseif ($user->hasRole('HR Admin')) {
            // HR Admin sees all active employees
            $query->active();
        } elseif ($user->hasRole('Super Admin')) {
            // Super Admin sees all employees (including inactive)
        } else {
            // Check for OPCR roles with office restrictions
            $officeIds = $this->getUserOfficeIds($user);
            if (!empty($officeIds)) {
                $query->whereHas('officeAssignments', function ($query) use ($officeIds) {
                    $query->whereIn('office_id', $officeIds)->where('is_active', true);
                });
            } else {
                // Unknown role - see nothing
                $query->whereRaw('1 = 0');
            }
        }

        // Apply additional filters (only for admin roles or users with office access)
        if ($user->hasAnyRole(['HR Admin', 'Super Admin']) || !empty($this->getUserOfficeIds($user))) {
            if (!empty($filters['office_id'])) {
                $query->whereHas('officeAssignments', function ($query) use ($filters) {
                    $query->where('office_id', $filters['office_id'])->where('is_active', true);
                });
            }

            if (!empty($filters['department_id'])) {
                $query->where('department_id', $filters['department_id']);
            }

            if (!empty($filters['position_id'])) {
                $query->where('position_id', $filters['position_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('employment_status', $filters['status']);
            }

            if (!empty($filters['opcr_role'])) {
                $query->whereHas('officeAssignments', function ($query) use ($filters) {
                    $query->where('role', $filters['opcr_role'])->where('is_active', true);
                });
            }

            if (!empty($filters['search'])) {
                $query->where(function (Builder $q) use ($filters) {
                    $q->where('first_name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('last_name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('employee_number', 'like', '%' . $filters['search'] . '%')
                      ->orWhereHas('officeAssignments', function ($subQuery) use ($filters) {
                          $subQuery->whereHas('office', function ($officeQuery) use ($filters) {
                              $officeQuery->where('name', 'like', '%' . $filters['search'] . '%');
                          });
                      });
                });
            }
        }

        return $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);
    }
    
    /**
     * Get single employee with authorization check
     */
    public function getEmployee(int $employeeId): Employee
    {
        $user = auth()->user();

        $query = Employee::with(['user', 'documents', 'office', 'officeAssignments' => function ($query) {
            $query->where('is_active', true)->with('office');
        }]);

        if ($user->hasRole('Employee')) {
            // Employees can only access their own profile
            if ($user->employee?->id !== $employeeId) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access to employee data.');
            }
            $query->where('id', $employeeId);
        } elseif ($user->hasAnyRole(['HR Admin', 'Super Admin'])) {
            // Admin roles can access any employee
            $query->where('id', $employeeId);
        } else {
            // Check OPCR role access
            $officeIds = $this->getUserOfficeIds($user);
            if (!empty($officeIds)) {
                $query->whereHas('officeAssignments', function ($query) use ($officeIds, $employeeId) {
                    $query->where('employee_id', $employeeId)
                          ->whereIn('office_id', $officeIds)
                          ->where('is_active', true);
                });
            } else {
                throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access.');
            }
        }

        return $query->firstOrFail();
    }

    /**
     * Get user's accessible office IDs
     */
    private function getUserOfficeIds(User $user): array
    {
        return $user->officeAssignments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->pluck('office_id')
            ->toArray();
    }

    /**
     * Get employees by OPCR role
     */
    public function getEmployeesByOPCRRole(string $role, ?int $officeId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Employee::whereHas('officeAssignments', function ($query) use ($role, $officeId) {
            $query->where('role', $role)
                  ->where('is_active', true)
                  ->where(function ($subQuery) {
                      $subQuery->whereNull('ended_date')
                              ->orWhere('ended_date', '>=', now());
                  });

            if ($officeId) {
                $query->where('office_id', $officeId);
            }
        })->with(['user', 'officeAssignments' => function ($query) use ($role) {
            $query->where('role', $role)->where('is_active', true)->with('office');
        }]);

        return $query->get();
    }

    /**
     * Get department heads
     */
    public function getDepartmentHeads(?int $officeId = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getEmployeesByOPCRRole(OfficeAssignment::ROLE_DEPARTMENT_HEAD, $officeId);
    }

    /**
     * Get assessors
     */
    public function getAssessors(?int $officeId = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getEmployeesByOPCRRole(OfficeAssignment::ROLE_ASSESSOR, $officeId);
    }

    /**
     * Get final approvers
     */
    public function getFinalApprovers(?int $officeId = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getEmployeesByOPCRRole(OfficeAssignment::ROLE_FINAL_APPROVER, $officeId);
    }

    /**
     * Assign employee to office with role
     */
    public function assignToOffice(Employee $employee, int $officeId, string $role, array $metadata = []): OfficeAssignment
    {
        // Deactivate existing assignments for this role if needed
        OfficeAssignment::where('employee_id', $employee->id)
            ->where('office_id', $officeId)
            ->where('role', $role)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_date' => now(),
            ]);

        return OfficeAssignment::create([
            'user_id' => $employee->user_id,
            'employee_id' => $employee->id,
            'office_id' => $officeId,
            'role' => $role,
            'assigned_date' => now()->toDateString(),
            'is_active' => true,
            'remarks' => $metadata['remarks'] ?? null,
            'assigned_by' => auth()->id(),
        ]);
    }

    /**
     * Remove employee from office role
     */
    public function removeFromOffice(Employee $employee, int $officeId, string $role): bool
    {
        return OfficeAssignment::where('employee_id', $employee->id)
            ->where('office_id', $officeId)
            ->where('role', $role)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_date' => now()->toDateString(),
            ]);
    }

    /**
     * Get employee's OPCR roles
     */
    public function getEmployeeOPCRRoles(Employee $employee): array
    {
        return $employee->officeAssignments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->with('office')
            ->get()
            ->map(function ($assignment) {
                return [
                    'office_id' => $assignment->office_id,
                    'office_name' => $assignment->office->name,
                    'role' => $assignment->role,
                    'assigned_date' => $assignment->assigned_date,
                    'permissions' => $assignment->role_permissions,
                ];
            })
            ->toArray();
    }

    /**
     * Get employees with office assignments for OPCR
     */
    public function getEmployeesWithOfficeAssignments(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Employee::with(['user', 'officeAssignments' => function ($query) {
            $query->where('is_active', true)
                  ->where(function ($subQuery) {
                      $subQuery->whereNull('ended_date')
                              ->orWhere('ended_date', '>=', now());
                  })
                  ->with('office');
        }]);

        if (!empty($filters['office_id'])) {
            $query->whereHas('officeAssignments', function ($query) use ($filters) {
                $query->where('office_id', $filters['office_id'])->where('is_active', true);
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('officeAssignments', function ($query) use ($filters) {
                $query->where('role', $filters['role'])->where('is_active', true);
            });
        }

        if (!empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        return $query->get();
    }

    /**
     * Check if employee has specific OPCR role
     */
    public function hasOPCRRole(Employee $employee, string $role, ?int $officeId = null): bool
    {
        $query = $employee->officeAssignments()
            ->where('role', $role)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            });

        if ($officeId) {
            $query->where('office_id', $officeId);
        }

        return $query->exists();
    }

    /**
     * Get employee office for OPCR purposes
     */
    public function getEmployeeOPCROffice(Employee $employee): ?Office
    {
        $assignment = $employee->officeAssignments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->whereHas('office', function ($query) {
                $query->where('is_active', true);
            })
            ->with('office')
            ->first();

        return $assignment?->office;
    }
}
