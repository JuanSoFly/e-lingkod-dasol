<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCardEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_card_id',
        'leave_application_id',
        'leave_type_code',
        'date',
        'days',
        'vl_balance_after',
        'sl_balance_after',
        'remarks',
        'entry_type',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'days' => 'decimal:1',
        'vl_balance_after' => 'decimal:2',
        'sl_balance_after' => 'decimal:2',
    ];

    public function leaveCard(): BelongsTo
    {
        return $this->belongsTo(LeaveCard::class);
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}