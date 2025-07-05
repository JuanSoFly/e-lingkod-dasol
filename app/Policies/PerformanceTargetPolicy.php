<?php

namespace App\Policies;

use App\Models\PerformanceTarget;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerformanceTargetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PerformanceTarget $performanceTarget): bool
    {
        return $user->employee?->id === $performanceTarget->employee_id && $performanceTarget->period->status == 'active';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PerformanceTarget $performanceTarget): bool
    {
        return $user->employee?->id === $performanceTarget->employee_id && $performanceTarget->period->status == 'active';
    }

    /**
     * Determine whether the user can submit a self-rating for the model.
     */
    public function rate(User $user, PerformanceTarget $performanceTarget): bool
    {
        return $user->employee?->id === $performanceTarget->employee_id && $performanceTarget->period->status == 'active';
    }

    /**
     * Determine whether the user can submit a supervisor rating for the model.
     */
    public function evaluate(User $user, PerformanceTarget $performanceTarget): bool
    {
        // A more complex check would be needed here, e.g., checking if the user is the employee's supervisor.
        // For now, we rely on the 'performance.evaluate' permission.
        return $user->can('performance.evaluate') && $performanceTarget->period->status == 'active';
    }
}