<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightDistributionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'rule_name',
        'applies_to_role',
        'allocation_method',
        'default_weight',
        'minimum_weight',
        'maximum_weight',
        'priority',
        'effective_start_date',
        'effective_end_date',
        'is_active',
        'conditions',
        'metadata',
    ];

    protected $casts = [
        'default_weight' => 'decimal:2',
        'minimum_weight' => 'decimal:2',
        'maximum_weight' => 'decimal:2',
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'metadata' => 'array',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_start_date')
                  ->orWhere('effective_start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_end_date')
                  ->orWhere('effective_end_date', '>=', now());
            });
    }
}
