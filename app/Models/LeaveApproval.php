<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_application_id',
        'approver_id',
        'action',
        'remarks',
        'action_date',
    ];

    protected $casts = [
        'action_date' => 'date',
    ];

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}