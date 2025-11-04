<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpcrIpcrMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'opcr_workflow_id',
        'performance_target_id',
        'ipcr_id',
        'ipcr_item_id',
        'allocation_strategy',
        'weight_percentage',
        'cascade_level',
        'metadata',
    ];

    protected $casts = [
        'weight_percentage' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function opcrWorkflow(): BelongsTo
    {
        return $this->belongsTo(OPCRWorkflow::class);
    }

    public function performanceTarget(): BelongsTo
    {
        return $this->belongsTo(PerformanceTarget::class);
    }

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function ipcrItem(): BelongsTo
    {
        return $this->belongsTo(IpcrItem::class);
    }
}
