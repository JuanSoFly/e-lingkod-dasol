<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidPeriodAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'ipcr_item_id',
        'requested_by',
        'requested_employee_id',
        'requested_at',
        'approved_by',
        'approved_at',
        'status',
        'adjustment_type',
        'original_values',
        'proposed_values',
        'justification',
        'decision_notes',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'original_values' => 'array',
        'proposed_values' => 'array',
        'metadata' => 'array',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function ipcrItem(): BelongsTo
    {
        return $this->belongsTo(IpcrItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requesterEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
