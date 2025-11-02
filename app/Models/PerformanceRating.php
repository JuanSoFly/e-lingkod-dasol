<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class PerformanceRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'success_indicator_id',
        'self_rating',
        'supervisor_rating',
        'final_rating',
        'remarks',
        'office_id',
        'rating_quantity',
        'rating_efficiency',
        'rating_timeliness',
        'average_qet_rating',
        'adjectival_rating',
        'accomplished_quantity',
        'accomplished_efficiency',
        'accomplished_timeliness',
        'assessed_by',
        'assessed_at',
        'approved_by',
        'approved_at',
        'is_legacy_ipcr',
        'assessor_remarks',
        'approver_remarks',
        'evidence_documents',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'self_rating' => 'decimal:2',
        'supervisor_rating' => 'decimal:2',
        'final_rating' => 'decimal:2',
        'average_qet_rating' => 'decimal:2',
        'accomplished_quantity' => 'decimal:2',
        'rating_quantity' => 'integer',
        'rating_efficiency' => 'integer',
        'rating_timeliness' => 'integer',
        'assessed_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_legacy_ipcr' => 'boolean',
        'evidence_documents' => 'array',
        'assessed_by' => 'integer',
        'approved_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(PerformanceTarget::class, 'target_id');
    }

    /**
     * Get the performance target (alias for target)
     */
    public function performanceTarget(): BelongsTo
    {
        return $this->belongsTo(PerformanceTarget::class, 'target_id');
    }

    /**
     * Get the success indicator that owns this performance rating
     */
    public function successIndicator(): BelongsTo
    {
        return $this->belongsTo(SuccessIndicator::class, 'success_indicator_id');
    }

    /**
     * Get the success indicator through the target relationship (fallback)
     */
    public function getSuccessIndicatorThroughTargetAttribute()
    {
        return $this->target?->successIndicator;
    }

    /**
     * Get the assessed by user
     */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Get the rated by user (alias for assessedBy)
     */
    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Get the performance period through the target
     */
    public function performancePeriod(): HasOneThrough
    {
        return $this->hasOneThrough(
            PerformancePeriod::class,
            PerformanceTarget::class,
            'id', // Foreign key on performance_ratings
            'id', // Foreign key on performance_periods
            'target_id', // Local key on performance_ratings
            'period_id' // Local key on performance_targets
        );
    }

    /**
     * Get the approved by user
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the created by user
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updated by user
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
