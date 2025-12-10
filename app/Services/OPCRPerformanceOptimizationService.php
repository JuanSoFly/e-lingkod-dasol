<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OPCRPerformanceOptimizationService
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 15;
    private const LONG_CACHE_DURATION = 60;

    /**
     * Get optimized OPCR statistics with caching
     */
    public function getCachedOPCRStatistics(?int $periodId = null, ?int $officeId = null, array $restrictedOfficeIds = []): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $scopeKey = empty($restrictedOfficeIds) ? 'all' : implode('-', $restrictedOfficeIds);
        $cacheKey = "opcr_stats_{$periodId}_{$officeId}_{$scopeKey}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $officeId, $restrictedOfficeIds) {
            return $this->calculateOPCRStatistics($periodId, $officeId, $restrictedOfficeIds);
        });
    }

    /**
     * Get paginated OPCR workflows with optimized queries
     */
    public function getOptimizedWorkflows(array $filters = [], int $perPage = 15)
    {
        $query = OPCRWorkflow::with([
            'office:id,name',
            'period:id,name,start_date,end_date',
            'committedBy.employee:id,first_name,last_name',
            'targets' => function ($query) {
                $query->select('id', 'opcr_workflow_id', 'mfo_id', 'success_indicator_id', 'target_quality')
                      ->with(['mfo:id,code,description', 'successIndicator:id,description']);
            },
            'targets.ratings' => function ($query) {
                $query->select('id', 'target_id', 'final_rating', 'rating_quality', 'rating_efficiency', 'rating_timeliness');
            }
        ])->withCount('targets');

        // Apply filters efficiently
        if (!empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (!empty($filters['office_id'])) {
            $query->where('office_id', $filters['office_id']);
        }

        // Apply office access restrictions
        if (!empty($filters['accessible_office_ids'])) {
            $query->whereIn('office_id', $filters['accessible_office_ids']);
        }

        if (!empty($filters['workflow_state'])) {
            $query->where('workflow_state', $filters['workflow_state']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('office', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('committedBy.employee', function ($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Use efficient ordering
        $orderBy = $filters['sort_by'] ?? 'updated_at';
        $orderDirection = $filters['sort_order'] ?? 'desc';

        if (in_array($orderBy, ['created_at', 'updated_at', 'title', 'workflow_state'])) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get dashboard metrics with optimized aggregation queries
     */
    public function getOptimizedDashboardMetrics(?int $periodId = null, ?int $officeId = null, array $restrictedOfficeIds = []): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $scopeKey = empty($restrictedOfficeIds) ? 'all' : implode('-', $restrictedOfficeIds);
        $cacheKey = "opcr_dashboard_metrics_{$periodId}_{$officeId}_{$scopeKey}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $officeId, $restrictedOfficeIds) {
            // Use raw SQL for better performance on large datasets
            $query = DB::table('opcr_workflows')
                ->select([
                    'workflow_state',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(overall_rating) as avg_rating'),
                ])
                ->where('period_id', $periodId);

            if ($officeId) {
                $query->where('office_id', $officeId);
            } elseif (!empty($restrictedOfficeIds)) {
                $query->whereIn('office_id', $restrictedOfficeIds);
            }

            $stateData = $query->groupBy('workflow_state')->get()->keyBy('workflow_state');

            // Get target completion rates using aggregated query
            $targetCompletionQuery = DB::table('performance_targets as pt')
                ->join('opcr_workflows as ow', 'pt.opcr_workflow_id', '=', 'ow.id')
                ->select([
                    DB::raw('COUNT(pt.id) as total_targets'),
                    DB::raw('SUM(CASE WHEN pt.accomplished_quality IS NOT NULL AND pt.accomplished_quality > 0 THEN 1 ELSE 0 END) as completed_targets'),
                ])
                ->where('ow.period_id', $periodId);

            if ($officeId) {
                $targetCompletionQuery->where('ow.office_id', $officeId);
            } elseif (!empty($restrictedOfficeIds)) {
                $targetCompletionQuery->whereIn('ow.office_id', $restrictedOfficeIds);
            }

            $targetData = $targetCompletionQuery->first();

            return [
                'total_workflows' => $stateData->sum('count'),
                'workflow_states' => $stateData->toArray(),
                'average_rating' => $stateData->whereNotNull('avg_rating')->avg('avg_rating') ?? 0,
                'target_completion_rate' => $targetData->total_targets > 0
                    ? ($targetData->completed_targets / $targetData->total_targets) * 100
                    : 0,
                'total_targets' => $targetData->total_targets ?? 0,
                'completed_targets' => $targetData->completed_targets ?? 0,
            ];
        });
    }

    /**
     * Batch update workflow states efficiently
     */
    public function batchUpdateWorkflowStates(array $workflowIds, string $newState, array $updateData = []): int
    {
        $updateData['workflow_state'] = $newState;
        $updateData['updated_at'] = now();

        return DB::table('opcr_workflows')
            ->whereIn('id', $workflowIds)
            ->update($updateData);
    }

    /**
     * Get top performing offices with optimized query
     */
    public function getTopPerformingOffices(?int $periodId = null, int $limit = 10, array $restrictedOfficeIds = []): Collection
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $scopeKey = empty($restrictedOfficeIds) ? 'all' : implode('-', $restrictedOfficeIds);
        $cacheKey = "top_offices_{$periodId}_{$limit}_{$scopeKey}";

        return Cache::remember($cacheKey, self::LONG_CACHE_DURATION, function () use ($periodId, $limit, $restrictedOfficeIds) {
            return DB::table('opcr_workflows as ow')
                ->join('offices as o', 'ow.office_id', '=', 'o.id')
                ->select([
                    'o.id',
                    'o.name',
                    DB::raw('COUNT(ow.id) as workflow_count'),
                    DB::raw('AVG(ow.overall_rating) as avg_rating'),
                    DB::raw('SUM(CASE WHEN ow.workflow_state = "approved" THEN 1 ELSE 0 END) as completed_count'),
                ])
                ->where('ow.period_id', $periodId)
                ->whereNotNull('ow.overall_rating')
                ->when(!empty($restrictedOfficeIds), function ($query) use ($restrictedOfficeIds) {
                    $query->whereIn('ow.office_id', $restrictedOfficeIds);
                })
                ->groupBy('o.id', 'o.name')
                ->orderBy('avg_rating', 'desc')
                ->orderBy('completed_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Precompute and cache frequently accessed data
     */
    public function warmupCache(?int $periodId = null): void
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();

        // Warm up dashboard metrics for all offices
        $officeIds = DB::table('offices')->where('is_active', true)->pluck('id');

        foreach ($officeIds as $officeId) {
            $this->getOptimizedDashboardMetrics($periodId, $officeId);
        }

        // Warm up general metrics
        $this->getOptimizedDashboardMetrics($periodId);
        $this->getCachedOPCRStatistics($periodId);
        $this->getTopPerformingOffices($periodId);
    }

    /**
     * Clear OPCR-related cache
     */
    public function clearCache(?int $periodId = null, ?int $officeId = null): void
    {
        $patterns = [
            "opcr_stats_*",
            "opcr_dashboard_metrics_*",
            "top_offices_*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Clear specific caches if provided
        if ($periodId) {
            Cache::forget("opcr_stats_{$periodId}_{$officeId}");
            Cache::forget("opcr_dashboard_metrics_{$periodId}_{$officeId}");
        }
    }

    /**
     * Get performance trends over time with optimized aggregation
     */
    public function getPerformanceTrends(int $periodsCount = 6): array
    {
        $cacheKey = "opcr_performance_trends_{$periodsCount}";

        return Cache::remember($cacheKey, self::LONG_CACHE_DURATION, function () use ($periodsCount) {
            return DB::table('performance_periods as pp')
                ->leftJoin('opcr_workflows as ow', function ($join) {
                    $join->on('ow.period_id', '=', 'pp.id')
                         ->where('ow.workflow_state', 'approved');
                })
                ->select([
                    'pp.id',
                    'pp.name',
                    'pp.start_date',
                    'pp.end_date',
                    DB::raw('COUNT(ow.id) as completed_workflows'),
                    DB::raw('AVG(ow.overall_rating) as avg_rating'),
                ])
                ->where('pp.start_date', '>=', now()->subMonths($periodsCount))
                ->groupBy('pp.id', 'pp.name', 'pp.start_date', 'pp.end_date')
                ->orderBy('pp.start_date')
                ->get()
                ->map(function ($period) {
                    return [
                        'period' => $period->name,
                        'completed_workflows' => $period->completed_workflows ?? 0,
                        'average_rating' => round($period->avg_rating ?? 0, 2),
                        'start_date' => $period->start_date,
                        'end_date' => $period->end_date,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Calculate OPCR statistics using optimized queries
     */
    private function calculateOPCRStatistics(int $periodId, ?int $officeId, array $restrictedOfficeIds = []): array
    {
        $baseQuery = DB::table('opcr_workflows')
            ->where('period_id', $periodId);

        if ($officeId) {
            $baseQuery->where('office_id', $officeId);
        } elseif (!empty($restrictedOfficeIds)) {
            $baseQuery->whereIn('office_id', $restrictedOfficeIds);
        }

        $workflows = $baseQuery->get();

        return [
            'total_workflows' => $workflows->count(),
            'draft_workflows' => $workflows->where('workflow_state', 'draft')->count(),
            'committed_workflows' => $workflows->where('workflow_state', 'committed')->count(),
            'in_progress_workflows' => $workflows->where('workflow_state', 'in_progress')->count(),
            'evaluation_workflows' => $workflows->where('workflow_state', 'evaluation')->count(),
            'final_approval_workflows' => $workflows->where('workflow_state', 'final_approval')->count(),
            'completed_workflows' => $workflows->where('workflow_state', 'approved')->count(),
            'average_rating' => $workflows->whereNotNull('overall_rating')->avg('overall_rating') ?? 0,
        ];
    }

    /**
     * Get current active period ID
     */
    private function getCurrentPeriodId(): int
    {
        return Cache::remember('current_period_id', self::LONG_CACHE_DURATION, function () {
            return DB::table('performance_periods')
                ->where('is_active', true)
                ->value('id') ?? DB::table('performance_periods')
                    ->orderBy('start_date', 'desc')
                    ->value('id');
        });
    }
}
