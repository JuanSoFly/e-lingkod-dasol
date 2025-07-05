<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class DocumentApprovalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'document_type',
        'title',
        'description',
        'requester_id',
        'employee_id',
        'status',
        'priority',
        'current_step',
        'workflow_config',
        'deadline',
        'submitted_at',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'workflow_config' => 'array',
        'deadline' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DocumentApprovalStep::class, 'request_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentApprovalAttachment::class, 'request_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentApprovalComment::class, 'request_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(DocumentApprovalStep::class, 'current_step', 'step_number');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', Carbon::now())
                    ->whereIn('status', ['submitted', 'under_review']);
    }

    public function scopeByDocumentType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeForApprover($query, $userId)
    {
        return $query->whereHas('steps', function ($q) use ($userId) {
            $q->where('approver_id', $userId)
              ->where('status', 'pending');
        });
    }

    /**
     * Scope for user-based filtering
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('Employee')) {
            return $query->where('employee_id', $user->employee?->id);
        }
        
        if ($user->hasAnyRole(['HR Admin', 'Super Admin'])) {
            return $query; // No filtering for admin roles
        }
        
        return $query->whereRaw('1 = 0'); // Unknown role
    }

    /**
     * Scope for visible requests only
     */
    public function scopeVisible($query)
    {
        return $query->forUser(auth()->user());
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'returned' => 'Returned for Revision',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    public function getPriorityLabelAttribute()
    {
        $labels = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];

        return $labels[$this->priority] ?? 'Unknown';
    }

    public function getIsOverdueAttribute()
    {
        return $this->deadline && $this->deadline->isPast() && 
               in_array($this->status, ['submitted', 'under_review']);
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->deadline) return null;
        
        $now = Carbon::now();
        return $this->deadline->diffInDays($now, false);
    }

    public function getProgressPercentageAttribute()
    {
        $totalSteps = $this->steps->count();
        if ($totalSteps === 0) return 0;

        $completedSteps = $this->steps->where('status', 'approved')->count();
        return round(($completedSteps / $totalSteps) * 100);
    }

    // Business Logic Methods
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->attachments()->exists();
    }

    public function canBeWithdrawn(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']) && 
               $this->requester_id === auth()->id();
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'under_review';
    }

    public function getCurrentApprover(): ?User
    {
        $currentStep = $this->steps()
            ->where('step_number', $this->current_step)
            ->where('status', 'pending')
            ->first();

        return $currentStep ? $currentStep->approver : null;
    }

    public function getNextStep(): ?DocumentApprovalStep
    {
        return $this->steps()
            ->where('step_number', $this->current_step + 1)
            ->first();
    }

    public function hasAllApprovals(): bool
    {
        return $this->steps()
            ->where('status', '!=', 'approved')
            ->count() === 0;
    }

    public function generateReferenceNumber(): string
    {
        $prefix = strtoupper(substr($this->document_type, 0, 3));
        $year = date('Y');
        $month = date('m');
        $sequence = str_pad($this->id, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}{$month}-{$sequence}";
    }

    // Boot method to auto-generate reference number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->reference_number)) {
                // Generate a temporary reference number
                $prefix = strtoupper(substr($request->document_type, 0, 3));
                $year = date('Y');
                $month = date('m');
                $sequence = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                
                $request->reference_number = "{$prefix}-{$year}{$month}-{$sequence}";
            }
        });
    }
}