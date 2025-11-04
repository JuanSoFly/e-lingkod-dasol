<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmtValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'office_id',
        'validator_id',
        'validator_user_id',
        'validation_stage',
        'status',
        'recommended_rating',
        'rating_breakdown',
        'remarks',
        'validated_at',
        'metadata',
    ];

    protected $casts = [
        'recommended_rating' => 'decimal:2',
        'rating_breakdown' => 'array',
        'validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'validator_id');
    }

    public function validatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_user_id');
    }
}
