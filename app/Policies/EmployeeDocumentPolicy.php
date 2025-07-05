<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmployeeDocument $document): bool
    {
        // User can view if they are the owner of the document
        if ($user->employee_id === $document->employee_id) {
            return true;
        }

        // User can view if they have general permission to view employee files
        return $user->can('employee.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Employee $employee): bool
    {
        // Only users who can edit an employee can add documents
        return $user->can('employee.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmployeeDocument $document): bool
    {
        // Only users with permission to delete employee records can delete documents
        return $user->can('employee.delete');
    }
}