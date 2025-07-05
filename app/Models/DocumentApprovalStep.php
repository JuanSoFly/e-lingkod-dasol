<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'step_number',
        'step_name',
        'approver_id',
        'approver_role',
        'status',
        'comments',
        'metadata',
        'assigned_at',
        'approved_at',
        'rejected_at',
        'deadline',
    ];

    protected $casts = [
        'metadata' => 'array',
        'assigned_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deadline' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function request(): BelongsTo
    {
        return $this->belongsTo(DocumentApprovalRequest::class, 'request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentApprovalComment::class, 'step_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['approved', 'rejected']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
                    ->where('status', 'pending');
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'skipped' => 'Skipped',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    public function getIsOverdueAttribute()
    {
        return $this->deadline && $this->deadline->isPast() && $this->status === 'pending';
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->deadline) return null;
        
        $now = now();
        return $this->deadline->diffInDays($now, false);
    }

    // Business Logic Methods
    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && $this->approver_id === auth()->id();
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'pending' && $this->approver_id === auth()->id();
    }

    public function approve($comments = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->status = 'approved';
        $this->comments = $comments;
        $this->approved_at = now();
        
        return $this->save();
    }

    public function reject($comments = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->status = 'rejected';
        $this->comments = $comments;
        $this->rejected_at = now();
        
        return $this->save();
    }

    public function skip($reason = null): bool
    {
        $this->status = 'skipped';
        $this->comments = $reason;
        
        return $this->save();
    }
}