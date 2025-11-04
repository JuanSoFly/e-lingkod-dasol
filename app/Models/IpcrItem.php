<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpcrItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ipcr_id',
        'performance_target_id',
        'success_indicator_id',
        'title',
        'description',
        'weight',
        'measure',
        'target_quantity',
        'target_efficiency',
        'target_timeliness',
        'accomplished_quantity',
        'accomplished_efficiency',
        'accomplished_timeliness',
        'self_rating',
        'self_rating_details',
        'supervisor_rating',
        'supervisor_rating_details',
        'head_rating',
        'head_rating_details',
        'pmt_rating',
        'pmt_rating_details',
        'final_rating',
        'final_rating_details',
        'remarks',
        'sequence',
        'metadata',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'target_quantity' => 'decimal:2',
        'accomplished_quantity' => 'decimal:2',
        'self_rating' => 'decimal:2',
        'supervisor_rating' => 'decimal:2',
        'head_rating' => 'decimal:2',
        'pmt_rating' => 'decimal:2',
        'final_rating' => 'decimal:2',
        'self_rating_details' => 'array',
        'supervisor_rating_details' => 'array',
        'head_rating_details' => 'array',
        'pmt_rating_details' => 'array',
        'final_rating_details' => 'array',
        'metadata' => 'array',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function performanceTarget(): BelongsTo
    {
        return $this->belongsTo(PerformanceTarget::class);
    }

    public function successIndicator(): BelongsTo
    {
        return $this->belongsTo(SuccessIndicator::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(IpcrRating::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(MidPeriodAdjustment::class);
    }

    public function performanceLinks(): HasMany
    {
        return $this->hasMany(PerformanceLink::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(WorkflowAttachment::class, 'attachable');
    }
}
