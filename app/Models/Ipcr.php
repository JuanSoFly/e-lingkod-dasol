<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\OpcrIpcrMapping;
use App\Models\MidPeriodAdjustment;
use App\Models\PerformanceLink;
use App\Models\PmtValidation;
use App\Models\CalibrationSessionItem;
use App\Models\FinalRating;
use App\Models\WorkflowAttachment;
use App\Models\IpcrProgressUpdate;
use App\Models\IpcrCoachingSession;
use App\Models\IpcrDevelopmentAction;

class Ipcr extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'office_id',
        'period_id',
        'opcr_workflow_id',
        'supervisor_id',
        'head_of_office_id',
        'pmt_validator_id',
        'final_approver_id',
        'created_by',
        'updated_by',
        'status',
        'total_weight',
        'overall_score',
        'adjectival_rating',
        'is_auto_generated',
        'submitted_at',
        'supervisor_reviewed_at',
        'head_reviewed_at',
        'pmt_validated_at',
        'finalized_at',
        'locked_at',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'total_weight' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'is_auto_generated' => 'boolean',
        'submitted_at' => 'datetime',
        'supervisor_reviewed_at' => 'datetime',
        'head_reviewed_at' => 'datetime',
        'pmt_validated_at' => 'datetime',
        'finalized_at' => 'datetime',
        'locked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class, 'period_id');
    }

    public function opcrWorkflow(): BelongsTo
    {
        return $this->belongsTo(OPCRWorkflow::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function headOfOffice(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_of_office_id');
    }

    public function pmtValidator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pmt_validator_id');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'final_approver_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(IpcrItem::class);
    }

    public function ipcrItem()
    {
        return $this->items()->first();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(IpcrRating::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(IpcrWorkflowLog::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(OpcrIpcrMapping::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(MidPeriodAdjustment::class);
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(IpcrProgressUpdate::class);
    }

    public function coachingSessions(): HasMany
    {
        return $this->hasMany(IpcrCoachingSession::class);
    }

    public function developmentActions(): HasMany
    {
        return $this->hasMany(IpcrDevelopmentAction::class);
    }

    public function performanceLinks(): HasMany
    {
        return $this->hasMany(PerformanceLink::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(WorkflowAttachment::class, 'attachable');
    }

    public function pmtValidations(): HasMany
    {
        return $this->hasMany(PmtValidation::class);
    }

    public function calibrationItems(): HasMany
    {
        return $this->hasMany(CalibrationSessionItem::class);
    }

    public function finalRatings(): HasMany
    {
        return $this->hasMany(FinalRating::class);
    }

    public function finalRating(): HasOne
    {
        return $this->hasOne(FinalRating::class);
    }

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function getIsLockedAttribute(): bool
    {
        return !is_null($this->locked_at);
    }
}
