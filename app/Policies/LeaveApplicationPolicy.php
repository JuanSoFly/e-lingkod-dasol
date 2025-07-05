<?php

namespace App\Policies;

use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveApplicationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\LeaveApplication  $leaveApplication
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, LeaveApplication $leaveApplication)
    {
        // Allow user to view their own application
        if ($user->employee?->id === $leaveApplication->employee_id) {
            return true;
        }

        // Allow users with approval permission to view any application
        return $user->can('leave.approve');
    }
}