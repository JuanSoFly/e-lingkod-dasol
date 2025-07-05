<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_type',
        'snapshot_date',
        'data',
        'period_type',
        'total_employees',
        'turnover_rate',
        'average_performance',
        'compliance_rate',
        'training_completion_rate',
        'status',
        'notes'
    ];

    protected $casts = [
        'data' => 'array',
        'snapshot_date' => 'date',
        'turnover_rate' => 'decimal:2',
        'average_performance' => 'decimal:2',
        'compliance_rate' => 'decimal:2',
        'training_completion_rate' => 'decimal:2'
    ];

    /**
     * Scope to filter by snapshot type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('snapshot_type', $type);
    }

    /**
     * Scope to filter by period type
     */
    public function scopeOfPeriod($query, $period)
    {
        return $query->where('period_type', $period);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    /**
     * Scope for active snapshots
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the latest snapshot for a type
     */
    public function scopeLatest($query, $type)
    {
        return $query->where('snapshot_type', $type)
                    ->where('status', 'active')
                    ->orderBy('snapshot_date', 'desc');
    }

    /**
     * Format data attribute for display
     */
    protected function data(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => json_decode($value, true),
            set: fn (array $value) => json_encode($value),
        );
    }

    /**
     * Get formatted snapshot date
     */
    public function getFormattedDateAttribute()
    {
        return $this->snapshot_date->format('M d, Y');
    }

    /**
     * Get trend direction based on previous snapshot
     */
    public function getTrendDirectionAttribute()
    {
        $previous = self::where('snapshot_type', $this->snapshot_type)
            ->where('snapshot_date', '<', $this->snapshot_date)
            ->where('status', 'active')
            ->orderBy('snapshot_date', 'desc')
            ->first();

        if (!$previous) {
            return 'neutral';
        }

        // Compare key metrics
        $currentValue = $this->getComparisonValue();
        $previousValue = $previous->getComparisonValue();

        if ($currentValue > $previousValue) {
            return 'up';
        } elseif ($currentValue < $previousValue) {
            return 'down';
        } else {
            return 'neutral';
        }
    }

    /**
     * Get comparison value based on snapshot type
     */
    private function getComparisonValue()
    {
        switch ($this->snapshot_type) {
            case 'workforce':
                return $this->total_employees;
            case 'turnover':
                return $this->turnover_rate;
            case 'performance':
                return $this->average_performance;
            case 'compliance':
                return $this->compliance_rate;
            case 'training':
                return $this->training_completion_rate;
            default:
                return 0;
        }
    }

    /**
     * Get percentage change from previous snapshot
     */
    public function getPercentageChangeAttribute()
    {
        $previous = self::where('snapshot_type', $this->snapshot_type)
            ->where('snapshot_date', '<', $this->snapshot_date)
            ->where('status', 'active')
            ->orderBy('snapshot_date', 'desc')
            ->first();

        if (!$previous) {
            return null;
        }

        $currentValue = $this->getComparisonValue();
        $previousValue = $previous->getComparisonValue();

        if ($previousValue == 0) {
            return null;
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, 2);
    }

    /**
     * Create a snapshot for given type and date
     */
    public static function createSnapshot($type, $date, $data, $periodType = 'daily')
    {
        $snapshot = self::create([
            'snapshot_type' => $type,
            'snapshot_date' => $date,
            'data' => $data,
            'period_type' => $periodType,
            'total_employees' => $data['total_employees'] ?? null,
            'turnover_rate' => $data['turnover_rate'] ?? null,
            'average_performance' => $data['average_performance'] ?? null,
            'compliance_rate' => $data['compliance_rate'] ?? null,
            'training_completion_rate' => $data['training_completion_rate'] ?? null,
            'status' => 'active'
        ]);

        return $snapshot;
    }

    /**
     * Get snapshots for chart data
     */
    public static function getChartData($type, $period = 'monthly', $limit = 12)
    {
        return self::where('snapshot_type', $type)
            ->where('period_type', $period)
            ->where('status', 'active')
            ->orderBy('snapshot_date', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Archive old snapshots
     */
    public static function archiveOldSnapshots($type, $keepDays = 365)
    {
        $cutoffDate = now()->subDays($keepDays);
        
        return self::where('snapshot_type', $type)
            ->where('snapshot_date', '<', $cutoffDate)
            ->where('status', 'active')
            ->update(['status' => 'archived']);
    }
}