<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PerformanceTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'objective',
        'target',
        'weight',
        'success_indicator',
        'opcr_workflow_id',
        'mfo_id',
        'success_indicator_id',
        'mfo_code',
        'si_code',
        'office_id',
        'target_quantity',
        'target_efficiency',
        'target_timeliness',
        'is_legacy_ipcr',
        'accomplished_quantity',
        'accomplished_efficiency',
        'accomplished_timeliness',
        'performance_percentage',
        'is_target_met',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_quantity' => 'decimal:2',
        'accomplished_quantity' => 'decimal:2',
        'performance_percentage' => 'decimal:2',
        'is_legacy_ipcr' => 'boolean',
        'is_target_met' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class, 'period_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(PerformanceRating::class, 'target_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PerformanceRating::class, 'target_id');
    }

    public function mfo(): BelongsTo
    {
        return $this->belongsTo(MajorFinalOutput::class, 'mfo_id');
    }

    public function successIndicator(): BelongsTo
    {
        return $this->belongsTo(SuccessIndicator::class, 'success_indicator_id');
    }

    public function opcrWorkflow(): BelongsTo
    {
        return $this->belongsTo(OPCRWorkflow::class, 'opcr_workflow_id');
    }

    public function ipcrMappings(): HasMany
    {
        return $this->hasMany(OpcrIpcrMapping::class, 'performance_target_id');
    }
}
