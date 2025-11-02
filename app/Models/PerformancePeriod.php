<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformancePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'semester',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function targets(): HasMany
    {
        return $this->hasMany(PerformanceTarget::class, 'period_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'period_id');
    }

    /**
     * Get OPCR workflows for this period
     */
    public function opcrWorkflows(): HasMany
    {
        return $this->hasMany(OPCRWorkflow::class, 'period_id');
    }
}