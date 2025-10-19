<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'conditions',
        'approval_steps',
    ];

    protected $casts = [
        'conditions' => 'array',
        'approval_steps' => 'array',
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(LeaveWorkflowStep::class)->orderBy('step_order');
    }

    /**
     * Find applicable workflow for a leave application
     */
    public static function findApplicableWorkflow(LeaveApplication $application): ?self
    {
        return static::where('is_active', true)
            ->get()
            ->first(function ($workflow) use ($application) {
                return $workflow->matchesApplication($application);
            });
    }

    /**
     * Check if workflow matches application conditions
     */
    public function matchesApplication(LeaveApplication $application): bool
    {
        $conditions = $this->conditions;

        // Check leave type conditions
        if (isset($conditions['leave_types']) && !empty($conditions['leave_types'])) {
            if (!in_array($application->leave_type_id, $conditions['leave_types'])) {
                return false;
            }
        }

        // Check duration conditions
        if (isset($conditions['min_days']) && $application->days_requested < $conditions['min_days']) {
            return false;
        }

        if (isset($conditions['max_days']) && $application->days_requested > $conditions['max_days']) {
            return false;
        }

        // Check employee type conditions
        if (isset($conditions['employment_types']) && !empty($conditions['employment_types'])) {
            if (!in_array($application->employee->employment_type_id, $conditions['employment_types'])) {
                return false;
            }
        }

        // Check department conditions
        if (isset($conditions['departments']) && !empty($conditions['departments'])) {
            if (!in_array($application->employee->department_id, $conditions['departments'])) {
                return false;
            }
        }

        return true;
    }
}