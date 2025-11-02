<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_policy_id',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'remarks',
        'applied_date',
        'approved_by',
        'approved_date',
        'dept_head_informed',
        'dept_head_informed_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applied_date' => 'date',
        'approved_date' => 'date',
        'dept_head_informed' => 'boolean',
        'dept_head_informed_date' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class);
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(LeaveApplicationWorkflowStep::class)->orderBy('step_order');
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    /**
     * Get the applicable leave policy for this application
     */
    public function getApplicablePolicy()
    {
        // First try to use the stored policy relationship if exists
        if ($this->leave_policy_id) {
            return $this->leavePolicy;
        }

        // Otherwise, find the applicable policy based on employee and leave type
        return $this->employee
            ->getApplicableLeavePolicies()
            ->where('leave_type_id', $this->leave_type_id)
            ->first();
    }

    /**
     * Validate this application against its policy
     */
    public function validateAgainstPolicy(): array
    {
        $policy = $this->getApplicablePolicy();
        
        if (!$policy) {
            return ['No applicable leave policy found for this leave type and employee.'];
        }

        return $policy->validateApplication($this->employee, [
            'days_requested' => $this->days_requested,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);
    }

    /**
     * Document linking relationships
     */
    public function sourceLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'source');
    }

    public function targetLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'target');
    }

    /**
     * Get linked documents
     */
    public function getLinkedDocuments(bool $activeOnly = true): \Illuminate\Support\Collection
    {
        $query = $this->targetLinks()->whereIn('link_type', [
            'leave_supporting_doc', 
            'medical_certificate'
        ]);
        
        if ($activeOnly) {
            $query->active();
        }

        return $query->with('source')->get()->pluck('source');
    }

    /**
     * Get supporting documents for this leave application
     */
    public function getSupportingDocuments(): \Illuminate\Support\Collection
    {
        return $this->getLinkedDocuments()->filter(function ($document) {
            return $document instanceof EmployeeDocument;
        });
    }
}