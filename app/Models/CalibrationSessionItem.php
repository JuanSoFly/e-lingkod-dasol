<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationSessionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'calibration_session_id',
        'ipcr_id',
        'employee_id',
        'initial_rating',
        'recommended_rating',
        'final_rating',
        'discussion_points',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'initial_rating' => 'decimal:2',
        'recommended_rating' => 'decimal:2',
        'final_rating' => 'decimal:2',
        'discussion_points' => 'array',
        'metadata' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CalibrationSession::class, 'calibration_session_id');
    }

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
