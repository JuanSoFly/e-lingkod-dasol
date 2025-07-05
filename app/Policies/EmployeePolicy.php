<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    public function view(User $user, Employee $employee): bool
    {
        // HR/Admin can view any employee
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return true;
        }
        
        // Employees can only view their own profile
        if ($user->hasPermissionTo('employee.view-own')) {
            return $user->employee && $user->employee->id === $employee->id;
        }
        
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    public function update(User $user, Employee $employee): bool
    {
        // HR/Admin can update any employee
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return true;
        }
        
        // Employees can update their own profile (limited fields)
        if ($user->hasPermissionTo('profile.edit-own')) {
            return $user->employee && $user->employee->id === $employee->id;
        }
        
        return false;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    public function manage(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }
}