<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalibrationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_period_id',
        'office_id',
        'session_title',
        'agenda',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'participants',
        'rating_statistics',
        'action_items',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'participants' => 'array',
        'rating_statistics' => 'array',
        'action_items' => 'array',
        'metadata' => 'array',
    ];

    public function performancePeriod(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CalibrationSessionItem::class);
    }
}
