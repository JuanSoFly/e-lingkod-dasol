<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpcrProgressUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'ipcr_item_id',
        'reported_by',
        'progress_date',
        'status',
        'accomplishments',
        'challenges',
        'next_steps',
        'metadata',
    ];

    protected $casts = [
        'progress_date' => 'date',
        'metadata' => 'array',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(IpcrItem::class, 'ipcr_item_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
