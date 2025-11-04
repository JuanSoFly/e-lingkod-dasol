<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PerformanceLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'ipcr_item_id',
        'linked_type',
        'linked_id',
        'link_category',
        'description',
        'created_by',
        'metadata',
    ];

    protected $casts = [
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

    public function linked(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
