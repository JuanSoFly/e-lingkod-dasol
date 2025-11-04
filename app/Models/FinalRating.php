<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'employee_id',
        'validated_by',
        'final_score',
        'adjectival_rating',
        'performance_level',
        'is_locked',
        'locked_at',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
