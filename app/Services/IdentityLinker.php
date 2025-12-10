<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class IdentityLinker
{
    /**
     * Ensure user/employee linkage is synchronized for a given employee record.
     */
    public function syncForEmployee(Employee $employee): void
    {
        $user = $employee->user;

        if (!$user && $employee->email) {
            $user = $this->findUserByEmail($employee->email);
        }

        if ($user) {
            $this->link($user, $employee);
        }
    }

    /**
     * Ensure user/employee linkage is synchronized for a given user record.
     */
    public function syncForUser(User $user): void
    {
        $employee = $user->employee;

        if (!$employee && $user->email) {
            $employee = $this->findEmployeeByEmail($user->email);
        }

        if ($employee) {
            $this->link($user, $employee);
        }
    }

    /**
     * Link a user and employee bidirectionally without firing additional model events.
     */
    public function link(User $user, Employee $employee): void
    {
        $changed = false;

        if ($user->employee_id !== $employee->id) {
            $user->forceFill(['employee_id' => $employee->id])->saveQuietly();
            $changed = true;
        }

        if (Arr::has($employee->getAttributes(), 'user_id') && $employee->user_id !== $user->id) {
            $employee->forceFill(['user_id' => $user->id])->saveQuietly();
            $changed = true;
        }

        if ($changed) {
            Log::info('IdentityLinker synchronized user and employee relationship', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
            ]);
        }
    }

    /**
     * Detach a user from their employee record when the user is deleted or unlinked.
     */
    public function detachUser(User $user): void
    {
        $employee = $user->employee;

        if ($employee && $employee->user_id === $user->id) {
            $employee->forceFill(['user_id' => null])->saveQuietly();
        }

        if ($user->employee_id) {
            $user->forceFill(['employee_id' => null])->saveQuietly();
        }
    }

    /**
     * Detach an employee from their user record (used when employees are archived or deleted).
     */
    public function detachEmployee(Employee $employee): void
    {
        if ($employee->user_id) {
            $user = User::find($employee->user_id);
            if ($user && $user->employee_id === $employee->id) {
                $user->forceFill(['employee_id' => null])->saveQuietly();
            }
        }

        if ($employee->user_id !== null) {
            $employee->forceFill(['user_id' => null])->saveQuietly();
        }
    }

    private function findUserByEmail(?string $email): ?User
    {
        if (empty($email)) {
            return null;
        }

        return User::where('email', $email)->first();
    }

    private function findEmployeeByEmail(?string $email): ?Employee
    {
        if (empty($email)) {
            return null;
        }

        return Employee::where('email', $email)->first();
    }
}

