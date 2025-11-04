<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpcrDevelopmentAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'focus_area',
        'action_item',
        'target_date',
        'status',
        'support_needed',
        'created_by',
        'updated_by',
        'metadata',
    ];

    protected $casts = [
        'target_date' => 'date',
        'metadata' => 'array',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
