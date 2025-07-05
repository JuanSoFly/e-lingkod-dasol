<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PerformanceTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'objective',
        'target',
        'weight',
        'success_indicator',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class, 'period_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(PerformanceRating::class, 'target_id');
    }
}