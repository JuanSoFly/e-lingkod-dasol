<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EmployeeService
{
    /**
     * Get employees list with role-based filtering
     */
    public function getEmployeesList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = auth()->user();
        
        $query = Employee::with(['user']);
        
        // Apply role-based filtering
        if ($user->hasRole('Employee')) {
            // Employees can only see their own profile
            $query->where('id', $user->employee?->id);
        } elseif ($user->hasRole('HR Admin')) {
            // HR Admin sees all active employees
            $query->where('employment_status', 'active');
        } elseif ($user->hasRole('Super Admin')) {
            // Super Admin sees all employees (including inactive)
        } else {
            // Unknown role - see nothing
            $query->whereRaw('1 = 0');
        }
        
        // Apply additional filters (only for admin roles)
        if ($user->hasAnyRole(['HR Admin', 'Super Admin'])) {
            if (!empty($filters['department_id'])) {
                $query->where('department_id', $filters['department_id']);
            }
            
            if (!empty($filters['position_id'])) {
                $query->where('position_id', $filters['position_id']);
            }
            
            if (!empty($filters['status'])) {
                $query->where('employment_status', $filters['status']);
            }
            
            if (!empty($filters['search'])) {
                $query->where(function (Builder $q) use ($filters) {
                    $q->where('first_name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('last_name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('employee_number', 'like', '%' . $filters['search'] . '%');
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
        
        $query = Employee::with(['user', 'documents']);
        
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
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access.');
        }
        
        return $query->firstOrFail();
    }
}