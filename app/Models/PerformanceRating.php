<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'self_rating',
        'supervisor_rating',
        'final_rating',
        'remarks',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(PerformanceTarget::class, 'target_id');
    }
}