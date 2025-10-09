<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeeSelfServicePolicy
{
    /**
     * Employee can view their own profile only
     */
    public function viewOwnEmployee(User $user, Employee $employee): bool
    {
        return $user->employee && $user->employee->id === $employee->id;
    }
    
    /**
     * Employee can edit their own profile
     */
    public function editOwnProfile(User $user): bool
    {
        return $user->hasPermissionTo('profile.edit-own');
    }
    
    /**
     * Employee can manage their own notifications
     */
    public function manageOwnNotifications(User $user): bool
    {
        return $user->hasPermissionTo('notifications.manage-own');
    }
}