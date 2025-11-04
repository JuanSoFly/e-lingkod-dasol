<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpcrWorkflowLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'user_id',
        'employee_id',
        'from_state',
        'to_state',
        'action',
        'performed_role',
        'remarks',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
