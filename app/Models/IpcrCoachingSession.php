<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpcrCoachingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'coach_user_id',
        'participant_user_id',
        'session_date',
        'session_type',
        'focus_area',
        'discussion_notes',
        'agreements',
        'follow_up_date',
        'metadata',
    ];

    protected $casts = [
        'session_date' => 'date',
        'follow_up_date' => 'date',
        'metadata' => 'array',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_user_id');
    }
}
