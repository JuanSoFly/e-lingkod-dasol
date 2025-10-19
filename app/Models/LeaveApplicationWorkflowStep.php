<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplicationWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_application_id',
        'leave_workflow_step_id',
        'step_order',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
        'escalated_at',
        'escalated_to',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function leaveWorkflowStep(): BelongsTo
    {
        return $this->belongsTo(LeaveWorkflowStep::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    /**
     * Scope to get pending steps
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved steps
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected steps
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get escalated steps
     */
    public function scopeEscalated($query)
    {
        return $query->where('status', 'escalated');
    }

    /**
     * Check if step is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if step is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if step is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if step is escalated
     */
    public function isEscalated(): bool
    {
        return $this->status === 'escalated';
    }

    /**
     * Approve this step
     */
    public function approve(User $approver, ?string $remarks = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    /**
     * Reject this step
     */
    public function reject(User $approver, string $remarks): bool
    {
        return $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    /**
     * Escalate this step
     */
    public function escalate(?User $escalatedTo = null): bool
    {
        return $this->update([
            'status' => 'escalated',
            'escalated_at' => now(),
            'escalated_to' => $escalatedTo?->id,
        ]);
    }
}