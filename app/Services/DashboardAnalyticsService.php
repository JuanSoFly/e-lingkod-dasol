<?php

namespace App\Services;

use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use App\Models\OPCRWorkflow;
use App\Models\PerformancePeriod;
use App\Models\Office;
use App\Models\MajorFinalOutput;
use App\Support\DatabaseExpression;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardAnalyticsService
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 2;

    /**
     * Get OPCR performance metrics for dashboard
     */
    public function getOPCRMetrics(?int $periodId = null, ?int $officeId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "opcr_metrics_{$periodId}_{$officeId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $officeId) {
            $query = OPCRWorkflow::with(['committedBy.employee', 'office'])
                ->where('period_id', $periodId);

            if ($officeId) {
                $query->where('office_id', $officeId);
            }

            $workflows = $query->get();

            return [
                'total_workflows' => $workflows->count(),
                'draft_workflows' => $workflows->where('workflow_state', 'draft')->count(),
                'committed_workflows' => $workflows->where('workflow_state', 'committed')->count(),
                'in_progress_workflows' => $workflows->where('workflow_state', 'in_progress')->count(),
                'evaluation_workflows' => $workflows->where('workflow_state', 'evaluation')->count(),
                'final_approval_workflows' => $workflows->where('workflow_state', 'final_approval')->count(),
                'completed_workflows' => $workflows->whereIn('workflow_state', ['approved', 'final_approval'])->count(),
                'workflow_completion_rate' => $this->calculateWorkflowCompletionRate($workflows),
                'average_rating' => $this->calculateAverageRating($periodId, $officeId),
                'target_completion_rate' => $this->calculateTargetCompletionRate($periodId, $officeId),
            ];
        });
    }

    /**
     * Get office performance comparison
     */
    public function getOfficePerformanceComparison(?int $periodId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "office_performance_comparison_{$periodId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId) {
            // Use OPCR workflows with progressive completion logic
            $offices = Office::select([
                'offices.id',
                'offices.name',
                DB::raw('COUNT(opcr_workflows.id) as total_workflows'),
                DB::raw("COUNT(CASE WHEN opcr_workflows.workflow_state = 'final_approval' THEN 1 END) as completed_workflows"),
                DB::raw("COUNT(CASE WHEN opcr_workflows.workflow_state = 'draft' THEN 1 END) as draft_workflows"),
                DB::raw("COUNT(CASE WHEN opcr_workflows.workflow_state = 'committed' THEN 1 END) as committed_workflows"),
                DB::raw("COUNT(CASE WHEN opcr_workflows.workflow_state = 'in_progress' THEN 1 END) as in_progress_workflows"),
                DB::raw("COUNT(CASE WHEN opcr_workflows.workflow_state = 'evaluation' THEN 1 END) as evaluation_workflows"),
                DB::raw('COUNT(CASE WHEN opcr_workflows.overall_rating IS NOT NULL THEN 1 END) as rated_workflows'),
                DB::raw('AVG(CASE WHEN opcr_workflows.overall_rating IS NOT NULL THEN opcr_workflows.overall_rating END) as average_rating'),
                DB::raw('AVG(CASE WHEN opcr_workflows.overall_rating IS NOT NULL THEN opcr_workflows.overall_rating END) as qet_score') // Use overall_rating as QET proxy
            ])
                ->leftJoin('opcr_workflows', function($join) use ($periodId) {
                    $join->on('offices.id', '=', 'opcr_workflows.office_id')
                         ->where('opcr_workflows.period_id', $periodId);
                })
                ->groupBy('offices.id', 'offices.name')
                ->havingRaw('COUNT(opcr_workflows.id) > 0')
                ->get();

            return $offices->map(function ($office) {
                $totalWorkflows = $office->total_workflows;
                $completedWorkflows = $office->completed_workflows;
                $averageRating = $office->average_rating ?? 0;
                $ratedWorkflows = $office->rated_workflows;

                // Calculate weighted completion based on workflow states
                $weightedCompletion = 0;
                $weightedCompletion += ($office->draft_workflows ?? 0) * 0.20;
                $weightedCompletion += ($office->committed_workflows ?? 0) * 0.40;
                $weightedCompletion += ($office->in_progress_workflows ?? 0) * 0.60;
                $weightedCompletion += ($office->evaluation_workflows ?? 0) * 0.75;
                $weightedCompletion += $completedWorkflows * 1.0;

                $completionRate = $totalWorkflows > 0
                    ? round(($weightedCompletion / $totalWorkflows) * 100, 2)
                    : 0;

                // Calculate progressive rating when actual rating is null
                $progressiveRating = $this->calculateProgressiveRating($office, $averageRating);

                // Enhanced status determination
                if ($completionRate >= 75 && $progressiveRating >= 4.0) {
                    $status = 'Excellent';
                } elseif ($completionRate >= 50 && $progressiveRating >= 3.5) {
                    $status = 'Good';
                } elseif ($completionRate >= 25 || $progressiveRating >= 3.0) {
                    $status = 'Needs Attention';
                } else {
                    $status = 'Critical';
                }

                return [
                    'office_id' => $office->id,
                    'name' => $office->name,
                    'avg_rating' => round($progressiveRating, 2),
                    'completion_rate' => $completionRate,
                    'qet_score' => round($progressiveRating, 2), // Use progressive rating for QET too
                    'status' => $status,
                    'total_workflows' => $totalWorkflows,
                    'completed_workflows' => $completedWorkflows,
                    'rated_workflows' => $ratedWorkflows,
                    'weighted_completion' => round($weightedCompletion, 2)
                ];
            })->sortByDesc('avg_rating')->values()->toArray();
        });
    }

    /**
     * Calculate progressive rating based on workflow state when actual rating is null
     */
    private function calculateProgressiveRating($office, float $actualRating): float
    {
        // If there's a meaningful actual rating, use it
        if ($actualRating > 0) {
            return $actualRating;
        }

        // Calculate progressive rating based on workflow states
        $totalWorkflows = $office->total_workflows;
        if ($totalWorkflows == 0) {
            return 0.0;
        }

        $progressiveRating = 0;
        $progressiveRating += ($office->draft_workflows ?? 0) * 2.5;        // Baseline for draft state
        $progressiveRating += ($office->committed_workflows ?? 0) * 3.0;   // Baseline for committed state
        $progressiveRating += ($office->in_progress_workflows ?? 0) * 3.5; // Baseline for in progress state
        $progressiveRating += ($office->evaluation_workflows ?? 0) * 4.0;  // Baseline for evaluation state
        $progressiveRating += ($office->completed_workflows ?? 0) * 4.5;   // Baseline for completed state (slightly higher)

        return $progressiveRating / $totalWorkflows;
    }

    /**
     * Get MFO performance breakdown
     */
    public function getMFOPerformanceBreakdown(?int $periodId = null, ?int $officeId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "mfo_performance_breakdown_{$periodId}_{$officeId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $officeId) {
            try {
                $query = MajorFinalOutput::with([
                    'successIndicators.ratings',
                    'successIndicators.targets' => function ($query) use ($periodId) {
                        $query->where('period_id', $periodId);
                    }
                ]);

                if ($officeId) {
                    $query->where('office_id', $officeId);
                }

                $mfos = $query->where('is_active', true)->get();

                return $mfos->map(function ($mfo) use ($periodId) {
                    $allIndicators = $mfo->successIndicators;
                    $ratedIndicators = $allIndicators->filter(function ($indicator) {
                        return $indicator->ratings->isNotEmpty();
                    });

                    $totalTargets = $allIndicators->sum(function ($indicator) use ($periodId) {
                        return $indicator->targets->where('period_id', $periodId)->count();
                    });

                    $metTargets = $allIndicators->sum(function ($indicator) use ($periodId) {
                        try {
                            return $indicator->targets->where('period_id', $periodId)
                                ->where('is_target_met', true)->count();
                        } catch (\Exception $e) {
                            // Handle missing column gracefully
                            return 0;
                        }
                    });

                    return [
                        'mfo_id' => $mfo->id,
                        'mfo_code' => $mfo->code,
                        'mfo_title' => $mfo->title,
                        'total_indicators' => $allIndicators->count(),
                        'rated_indicators' => $ratedIndicators->count(),
                        'rating_completion_rate' => $allIndicators->count() > 0
                            ? round(($ratedIndicators->count() / $allIndicators->count()) * 100, 2)
                            : 0,
                        'total_targets' => $totalTargets,
                        'met_targets' => $metTargets,
                        'target_completion_rate' => $totalTargets > 0
                            ? round(($metTargets / $totalTargets) * 100, 2)
                            : 0,
                        'average_rating' => $this->safeAverageRating($ratedIndicators->flatMap->ratings) ?? 0,
                    ];
                })->sortByDesc('rating_completion_rate')->values()->toArray();
            } catch (\Exception $e) {
                \Log::warning('MFO performance breakdown calculation failed', [
                    'error' => $e->getMessage(),
                    'period_id' => $periodId,
                    'office_id' => $officeId,
                ]);
                return [];
            }
        });
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends(int $months = 6): array
    {
        $cacheKey = "performance_trends_{$months}";

        return Cache::remember($cacheKey, self::CACHE_DURATION * 2, function () use ($months) {
            $periods = PerformancePeriod::orderBy('start_date', 'desc')
                ->limit($months)
                ->get();

            return $periods->map(function ($period) {
                // Use OPCR workflows with actual ratings instead of empty performance_ratings
                $ratedWorkflows = OPCRWorkflow::where('period_id', $period->id)
                    ->whereNotNull('overall_rating')
                    ->get();
                $allWorkflows = OPCRWorkflow::where('period_id', $period->id)->get();
                $completedWorkflows = $allWorkflows->whereIn('workflow_state', ['final_approval']);

                // Calculate average rating from OPCR workflows
                $averageRating = $ratedWorkflows->isNotEmpty()
                    ? $ratedWorkflows->avg('overall_rating')
                    : 0;

                return [
                    'period_id' => $period->id,
                    'period_name' => $period->name,
                    'period_start' => $period->start_date->format('Y-m-d'),
                    'period_end' => $period->end_date->format('Y-m-d'),
                    'total_ratings' => $ratedWorkflows->count(),
                    'average_rating' => round($averageRating, 2),
                    'total_workflows' => $allWorkflows->count(),
                    'completed_workflows' => $completedWorkflows->count(),
                    'completion_rate' => $allWorkflows->count() > 0
                        ? round(($completedWorkflows->count() / $allWorkflows->count()) * 100, 2)
                        : 0,
                ];
            })->sortBy('period_start')->values()->toArray();
        });
    }

    /**
     * Get top performing employees
     */
    public function getTopPerformingEmployees(?int $periodId = null, int $limit = 10): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "top_performers_{$periodId}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $limit) {
            // Use OPCR workflows instead of performance_ratings table
            $employeeRatings = OPCRWorkflow::select([
                'employees.id as employee_id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as employee_name"),
                DB::raw('AVG(opcr_workflows.overall_rating) as average_rating'),
                DB::raw('COUNT(opcr_workflows.id) as total_ratings'),
                'employees.department',
                'employees.position',
                'offices.name as office_name'
            ])
                ->join('offices', 'opcr_workflows.office_id', '=', 'offices.id')
                ->join('office_assignments', function($join) {
                    $join->on('offices.id', '=', 'office_assignments.office_id')
                         ->where('office_assignments.role', 'Department Head');
                })
                ->join('employees', 'office_assignments.employee_id', '=', 'employees.id')
                ->where('opcr_workflows.period_id', $periodId)
                ->whereNotNull('opcr_workflows.overall_rating')
                ->groupBy('employees.id', 'employees.first_name', 'employees.last_name', 'employees.department', 'employees.position', 'offices.name')
                ->orderByDesc('average_rating')
                ->limit($limit)
                ->get();

            return $employeeRatings->map(function ($rating) {
                return [
                    'employee_id' => $rating->employee_id,
                    'employee_name' => $rating->employee_name,
                    'average_rating' => round($rating->average_rating, 2),
                    'total_ratings' => $rating->total_ratings,
                    'department' => $rating->department,
                    'position' => $rating->position,
                    'office_name' => $rating->office_name ?? 'Unassigned',
                ];
            })->toArray();
        });
    }

    /**
     * Get workflow state distribution
     */
    public function getWorkflowStateDistribution(?int $periodId = null, ?int $officeId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "workflow_distribution_{$periodId}_{$officeId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $officeId) {
            $query = OPCRWorkflow::where('period_id', $periodId);

            if ($officeId) {
                $query->where('office_id', $officeId);
            }

            $workflows = $query->get();

            $states = [
                'draft' => 0,
                'committed' => 0,
                'in_progress' => 0,
                'evaluation' => 0,
                'final_approval' => 0,
                'approved' => 0,
                'rejected' => 0,
                'returned' => 0,
            ];

            foreach ($workflows as $workflow) {
                if (isset($states[$workflow->workflow_state])) {
                    $states[$workflow->workflow_state]++;
                }
            }

            $total = array_sum($states);

            $result = array_map(function ($count) use ($total) {
                return [
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
                ];
            }, $states);

            // Add 'completed' state mapping to 'final_approval' for view compatibility
            $result['completed'] = [
                'count' => $states['final_approval'],
                'percentage' => $total > 0 ? round(($states['final_approval'] / $total) * 100, 2) : 0,
            ];

            return $result;
        });
    }

    /**
     * Get recent OPCR activities
     */
    public function getRecentOPCRActivities(int $limit = 10): array
    {
        $cacheKey = "recent_opcr_activities_{$limit}";

        return Cache::remember($cacheKey, 1, function () use ($limit) {
            // Get recent workflow state changes with more meaningful descriptions
            $recentWorkflows = OPCRWorkflow::with(['committedBy', 'assessedBy', 'approvedBy', 'office', 'period'])
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get()
                ->map(function ($workflow) {
                    $description = $this->getActivityDescription($workflow);
                    $user = $this->getActivityUser($workflow);
                    $action = $this->getActivityAction($workflow);

                    return [
                        'id' => $workflow->id,
                        'type' => 'workflow_state_change',
                        'description' => $description,
                        'user' => $user,
                        'action' => $action,
                        'workflow' => "Workflow #{$workflow->id}",
                        'stage' => $workflow->workflow_state,
                        'status' => $this->getActivityStatus($workflow->workflow_state),
                        'office' => $workflow->office ? $workflow->office->name : 'Unknown Office',
                        'period' => $workflow->period ? $workflow->period->name : 'Unknown Period',
                        'time' => $workflow->updated_at->format('Y-m-d H:i:s'),
                        'created_at' => $workflow->updated_at->format('Y-m-d H:i:s'),
                    ];
                });

            // Get recent rating activities - only if we have ratings
            $recentRatings = collect();
            try {
                $recentRatings = PerformanceRating::with(['target.employee', 'target.mfo.office'])
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->map(function ($rating) {
                        return [
                            'id' => $rating->id,
                            'type' => 'performance_rating',
                            'description' => "Performance rating for " . ($rating->target && $rating->target->employee ? $rating->target->employee->name : 'Unknown Employee'),
                            'user' => $rating->ratedBy ? $rating->ratedBy->name : 'Unknown',
                            'action' => 'Rated',
                            'workflow' => "Performance Rating",
                            'stage' => 'evaluation',
                            'status' => 'completed',
                            'employee' => $rating->target && $rating->target->employee ? $rating->target->employee->name : 'Unknown Employee',
                            'office' => $rating->target && $rating->target->mfo && $rating->target->mfo->office ? $rating->target->mfo->office->name : 'Unknown Office',
                            'rating' => isset($rating->average_rating) ? $rating->average_rating : 0,
                            'time' => $rating->created_at->format('Y-m-d H:i:s'),
                            'created_at' => $rating->created_at->format('Y-m-d H:i:s'),
                        ];
                    });
            } catch (\Exception $e) {
                \Log::info('No performance ratings found or relationship issues', ['error' => $e->getMessage()]);
            }

            return $recentWorkflows->concat($recentRatings)
                ->sortByDesc('created_at')
                ->take($limit)
                ->values()
                ->toArray();
        });
    }

    /**
     * Get activity description based on workflow state
     */
    private function getActivityDescription($workflow): string
    {
        $officeName = $workflow->office ? $workflow->office->name : 'Unknown Office';

        switch ($workflow->workflow_state) {
            case 'draft':
                return "OPCR workflow created for {$officeName}";
            case 'committed':
                return "OPCR workflow committed by Department Head for {$officeName}";
            case 'in_progress':
                return "OPCR workflow assessment in progress for {$officeName}";
            case 'evaluation':
                return "OPCR workflow under evaluation for {$officeName}";
            case 'final_approval':
                return "OPCR workflow awaiting final approval for {$officeName}";
            case 'approved':
                return "OPCR workflow approved and completed for {$officeName}";
            case 'returned':
                return "OPCR workflow returned for revision for {$officeName}";
            default:
                return "OPCR workflow {$workflow->workflow_state} for {$officeName}";
        }
    }

    /**
     * Get activity user based on workflow state
     */
    private function getActivityUser($workflow): string
    {
        switch ($workflow->workflow_state) {
            case 'draft':
                return $workflow->committedBy ? $workflow->committedBy->name : 'System';
            case 'committed':
                return $workflow->committedBy ? $workflow->committedBy->name : 'Department Head';
            case 'in_progress':
                return $workflow->assessedBy ? $workflow->assessedBy->name : 'Assessor';
            case 'evaluation':
                return $workflow->assessedBy ? $workflow->assessedBy->name : 'Assessor';
            case 'final_approval':
                return 'Pending Final Approver';
            case 'approved':
                return $workflow->approvedBy ? $workflow->approvedBy->name : 'Final Approver';
            case 'returned':
                return $workflow->returnedBy ? $workflow->returnedBy->name : 'Assessor';
            default:
                return 'System';
        }
    }

    /**
     * Get activity action based on workflow state
     */
    private function getActivityAction($workflow): string
    {
        switch ($workflow->workflow_state) {
            case 'draft':
                return 'Created';
            case 'committed':
                return 'Committed';
            case 'in_progress':
                return 'In Progress';
            case 'evaluation':
                return 'Evaluating';
            case 'final_approval':
                return 'Pending Approval';
            case 'approved':
                return 'Approved';
            case 'returned':
                return 'Returned';
            default:
                return ucfirst(str_replace('_', ' ', $workflow->workflow_state));
        }
    }

    /**
     * Get activity status based on workflow state
     */
    private function getActivityStatus($workflowState): string
    {
        return in_array($workflowState, ['approved', 'final_approval']) ? 'completed' : 'active';
    }

    /**
     * Get performance summary for dashboard widgets
     */
    public function getPerformanceSummary(?int $periodId = null, ?int $officeId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "performance_summary_{$periodId}_{$officeId}";

        // Get fresh data without caching for dashboard metrics
        $opcrMetrics = $this->getOPCRMetrics($periodId, $officeId);
        $pendingActions = $this->getPendingActionsCount($periodId, $officeId);

        // Calculate missing metrics that the view expects
        $activePeriods = $this->getActivePeriodsCount();
        $performanceScore = $this->calculatePerformanceScore($opcrMetrics);
        $workflowEfficiency = $this->calculateWorkflowEfficiency($periodId, $officeId);
        $complianceRate = $this->calculateComplianceRate($periodId, $officeId);

        return [
            // Keys expected by the analytics view
            'total_workflows' => $opcrMetrics['total_workflows'],
            'completion_rate' => $opcrMetrics['workflow_completion_rate'],
            'average_rating' => $opcrMetrics['average_rating'],
            'active_periods' => $activePeriods,
            'performance_score' => $performanceScore,
            'workflow_efficiency' => $workflowEfficiency,
            'compliance_rate' => $complianceRate,
            'pending_actions' => $pendingActions,

            // Additional metrics for detailed views
            'completed_workflows' => $opcrMetrics['completed_workflows'],
            'workflow_completion_rate' => $opcrMetrics['workflow_completion_rate'],
            'target_completion_rate' => $opcrMetrics['target_completion_rate'],

            // Detailed data for other sections
            'opcr_metrics' => $opcrMetrics,
            'workflow_distribution' => $this->getWorkflowStateDistribution($periodId, $officeId),
            'office_comparison' => $this->getOfficePerformanceComparison($periodId),
            'mfo_breakdown' => $this->getMFOPerformanceBreakdown($periodId, $officeId),
            'top_performers' => $this->getTopPerformingEmployees($periodId),
            'recent_activities' => $this->getRecentOPCRActivities(),
        ];
    }

    /**
     * Get current period ID
     */
    private function getCurrentPeriodId(): ?int
    {
        $id = PerformancePeriod::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id');

        if ($id !== null) {
            return $id;
        }

        $id = PerformancePeriod::where('is_active', true)->value('id');

        if ($id !== null) {
            return $id;
        }

        return PerformancePeriod::orderBy('start_date', 'desc')->value('id');
    }

    /**
     * Calculate workflow completion rate
     */
    private function calculateWorkflowCompletionRate(Collection $workflows): float
    {
        $total = $workflows->count();
        // Include both 'approved' and 'final_approval' as completed states
        $completed = $workflows->whereIn('workflow_state', ['approved', 'final_approval'])->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Calculate average rating for period and office
     */
    private function calculateAverageRating(?int $periodId, ?int $officeId): float
    {
        try {
            // Direct query to OPCR workflows for average rating
            $query = OPCRWorkflow::whereNotNull('overall_rating');

            if ($periodId !== null) {
                $query->where('period_id', $periodId);
            }

            if ($officeId) {
                $query->where('office_id', $officeId);
            }

            $averageRating = $query->avg('overall_rating');

            return round($averageRating ?? 0, 2);
        } catch (\Exception $e) {
            \Log::error('Average rating calculation failed', [
                'error' => $e->getMessage(),
                'period_id' => $periodId,
                'office_id' => $officeId,
            ]);
            return 0.0;
        }
    }

    /**
     * Calculate target completion rate for period and office
     */
    private function calculateTargetCompletionRate(?int $periodId, ?int $officeId): float
    {
        try {
            $query = PerformanceTarget::query();

            if ($periodId !== null) {
                $query->where('period_id', $periodId);
            }

            if ($officeId !== null) {
                $query->whereHas('mfo', function ($q) use ($officeId) {
                    $q->where('office_id', $officeId);
                });
            }

            $totalTargets = $query->count();
            $metTargets = $query->where('is_target_met', true)->count();

            return $totalTargets > 0 ? round(($metTargets / $totalTargets) * 100, 2) : 0;
        } catch (\Exception $e) {
            // Handle case where is_target_met column doesn't exist
            \Log::warning('Target completion rate calculation failed - column may not exist', [
                'error' => $e->getMessage(),
                'period_id' => $periodId,
                'office_id' => $officeId,
            ]);
            return 0.0;
        }
    }

    /**
     * Clear all performance-related cache
     */
    public function clearPerformanceCache(): void
    {
        $patterns = [
            'opcr_metrics_*',
            'office_performance_comparison_*',
            'mfo_performance_breakdown_*',
            'performance_trends_*',
            'top_performers_*',
            'workflow_distribution_*',
            'performance_summary_*',
            'pending_actions_*',
            'pending_actions_by_stage_*',
            'recent_opcr_activities_*',
            'avg_processing_time_*',
            'fastest_processing_*',
            'efficiency_score_*',
            'bottleneck_analysis_*',
            'workflow_timeline_*',
        ];

        foreach ($patterns as $pattern) {
            Cache::flush(); // Simple approach - in production, use more granular cache clearing
        }
    }

    /**
     * Clear cache for specific OPCR workflow changes
     */
    public function clearOPCRCache(?int $periodId = null, ?int $officeId = null): void
    {
        $cacheKeys = [
            'performance_summary_' . ($periodId ?? 'null') . '_' . ($officeId ?? 'null'),
            'opcr_metrics_' . ($periodId ?? 'null') . '_' . ($officeId ?? 'null'),
            'pending_actions_' . ($periodId ?? 'null') . '_' . ($officeId ?? 'null'),
            'recent_opcr_activities_10',
            'recent_opcr_activities_5',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Also clear related summary caches
        if ($periodId) {
            Cache::forget('office_performance_comparison_' . $periodId);
            Cache::forget('workflow_distribution_' . $periodId . '_' . ($officeId ?? 'null'));
        }
    }

    /**
     * Get pending actions count for dashboard
     */
    private function getPendingActionsCount(?int $periodId, ?int $officeId): int
    {
        // Calculate without caching for now to ensure fresh data
        if ($periodId === null) {
            return 0;
        }

        $query = OPCRWorkflow::where('period_id', $periodId)
            ->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation', 'final_approval', 'returned']);

        if ($officeId) {
            $query->where('office_id', $officeId);
        }

        // Count workflows that need immediate attention
        // Draft: need completion by department heads
        // Committed: ready for assessment
        // Evaluation: need evaluator attention
        // Final_approval: need final approver attention (critical)
        // Returned: need department head revision (high priority)
        $criticalCount = $query->clone()
            ->whereIn('workflow_state', ['draft', 'committed', 'evaluation', 'final_approval', 'returned'])
            ->count();

        // Add a smaller portion of in_progress workflows (10% as they're being actively worked on)
        $inProgressCount = $query->clone()
            ->where('workflow_state', 'in_progress')
            ->count() * 0.1;

        return (int) ($criticalCount + $inProgressCount);
    }

    /**
     * Calculate average processing time for workflows
     */
    public function calculateAverageProcessingTime(?int $periodId = null, ?int $officeId = null): float
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "avg_processing_time_{$periodId}_{$officeId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION * 2, function () use ($periodId, $officeId) {
            $query = OPCRWorkflow::where('period_id', $periodId)
                ->whereNotNull('created_at')
                ->whereNotNull('updated_at');

            if ($officeId) {
                $query->where('office_id', $officeId);
            }

            $workflows = $query->get();

            if ($workflows->isEmpty()) {
                return 0.0;
            }

            $totalDays = 0;
            $count = 0;

            foreach ($workflows as $workflow) {
                $days = $workflow->created_at->diffInDays($workflow->updated_at);
                $totalDays += $days;
                $count++;
            }

            return $count > 0 ? round($totalDays / $count, 1) : 0.0;
        });
    }

    /**
     * Get fastest processing workflows
     */
    public function getFastestProcessingWorkflows(?int $periodId = null, int $limit = 5): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "fastest_processing_{$periodId}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_DURATION * 2, function () use ($periodId, $limit) {
            return OPCRWorkflow::with(['office', 'committedBy'])
                ->where('period_id', $periodId)
                ->whereNotNull('created_at')
                ->whereNotNull('updated_at')
                ->selectRaw('*, ' . DatabaseExpression::dateDiffDays('updated_at', 'created_at') . ' as processing_days')
                ->orderBy('processing_days', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($workflow) {
                    return [
                        'id' => $workflow->id,
                        'office' => $workflow->office ? $workflow->office->name : 'Unknown',
                        'processing_days' => $workflow->processing_days,
                        'created_at' => $workflow->created_at->format('Y-m-d'),
                        'completed_by' => $workflow->committedBy ? $workflow->committedBy->name : 'System',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get pending actions categorized by stage
     */
    public function getPendingActionsByStage(?int $periodId = null, ?int $officeId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "pending_actions_by_stage_{$periodId}_{$officeId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $officeId) {
            $query = OPCRWorkflow::where('period_id', $periodId)
                ->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation', 'final_approval', 'returned']);

            if ($officeId) {
                $query->where('office_id', $officeId);
            }

            $workflows = $query->get();
            $pendingByStage = [];

            foreach ($workflows as $workflow) {
                $stage = $workflow->workflow_state;
                if (!isset($pendingByStage[$stage])) {
                    $pendingByStage[$stage] = [
                        'count' => 0,
                        'description' => $this->getStageDescription($stage),
                        'priority' => $this->getStagePriority($stage)
                    ];
                }
                $pendingByStage[$stage]['count']++;
            }

            return $pendingByStage;
        });
    }

    /**
     * Calculate efficiency score based on completion rates and processing times
     */
    public function calculateEfficiencyScore(?int $periodId = null, ?int $officeId = null): float
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "efficiency_score_{$periodId}_{$officeId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION * 2, function () use ($periodId, $officeId) {
            $metrics = $this->getOPCRMetrics($periodId, $officeId);
            $avgProcessingTime = $this->calculateAverageProcessingTime($periodId, $officeId);

            $completionRate = $metrics['workflow_completion_rate'];
            $totalWorkflows = $metrics['total_workflows'];

            if ($totalWorkflows === 0) {
                return 0.0;
            }

            // Efficiency score based on:
            // 1. Completion rate (40% weight)
            // 2. Processing time (30% weight) - lower is better
            // 3. Active workflow ratio (30% weight) - lower active ratio is better

            $completionScore = $completionRate;
            $processingScore = max(0, 100 - ($avgProcessingTime * 2)); // 2 points per day over ideal
            $activeRatio = ($totalWorkflows - $metrics['completed_workflows']) / $totalWorkflows;
            $activeScore = max(0, 100 - ($activeRatio * 100));

            $efficiencyScore = ($completionScore * 0.4) + ($processingScore * 0.3) + ($activeScore * 0.3);

            return round($efficiencyScore, 1);
        });
    }

    /**
     * Get bottleneck analysis to identify stage delays
     */
    public function getBottleneckAnalysis(?int $periodId = null, ?int $officeId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "bottleneck_analysis_{$periodId}_{$officeId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION * 2, function () use ($periodId, $officeId) {
            $query = OPCRWorkflow::where('period_id', $periodId)
                ->whereNotNull('created_at')
                ->whereNotNull('updated_at');

            if ($officeId) {
                $query->where('office_id', $officeId);
            }

            $workflows = $query->get();
            $stageDelays = [];

            foreach ($workflows as $workflow) {
                $stage = $workflow->workflow_state;
                $daysInStage = $workflow->created_at->diffInDays($workflow->updated_at);

                if (!isset($stageDelays[$stage])) {
                    $stageDelays[$stage] = [
                        'total_workflows' => 0,
                        'total_days' => 0,
                        'avg_days' => 0,
                        'max_days' => 0,
                        'description' => $this->getStageDescription($stage)
                    ];
                }

                $stageDelays[$stage]['total_workflows']++;
                $stageDelays[$stage]['total_days'] += $daysInStage;
                $stageDelays[$stage]['max_days'] = max($stageDelays[$stage]['max_days'], $daysInStage);
            }

            // Calculate averages and identify bottlenecks
            foreach ($stageDelays as $stage => &$data) {
                if ($data['total_workflows'] > 0) {
                    $data['avg_days'] = round($data['total_days'] / $data['total_workflows'], 1);
                    // Lower threshold for returned workflows (7 days) as they need urgent attention
                    $threshold = $stage === 'returned' ? 7 : 14;
                    $data['is_bottleneck'] = $data['avg_days'] > $threshold;
                }
            }

            // Sort by average days (descending) to identify worst bottlenecks
            uasort($stageDelays, function ($a, $b) {
                return $b['avg_days'] <=> $a['avg_days'];
            });

            return $stageDelays;
        });
    }

    /**
     * Get workflow processing timeline data
     */
    public function getWorkflowProcessingTimeline(?int $periodId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "workflow_timeline_{$periodId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION * 2, function () use ($periodId) {
            return OPCRWorkflow::where('period_id', $periodId)
                ->whereNotNull('created_at')
                ->whereNotNull('updated_at')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get stage description for display
     */
    private function getStageDescription(string $stage): string
    {
        $descriptions = [
            'draft' => 'Workflows awaiting initial completion',
            'committed' => 'Workflows ready for assessment',
            'in_progress' => 'Workflows currently being assessed',
            'evaluation' => 'Workflows under detailed evaluation',
            'final_approval' => 'Workflows awaiting final approval',
            'approved' => 'Completed workflows',
            'rejected' => 'Rejected workflows',
            'returned' => 'Workflows returned for revision'
        ];

        return $descriptions[$stage] ?? 'Unknown stage';
    }

    /**
     * Get stage priority level
     */
    private function getStagePriority(string $stage): int
    {
        $priorities = [
            'draft' => 1,           // Low priority - just created
            'committed' => 2,       // Medium - ready for next step
            'in_progress' => 3,     // Medium - being worked on
            'evaluation' => 4,      // High - needs attention
            'final_approval' => 5,  // Critical - blocking completion
            'returned' => 4,        // High - needs revision
            'rejected' => 1,        // Low - resolved
            'approved' => 1         // Low - completed
        ];

        return $priorities[$stage] ?? 1;
    }

    /**
     * Safely calculate average rating with fallback for missing columns
     */
    private function safeAverageRating($ratings): float
    {
        try {
            if ($ratings->isEmpty()) {
                return 0.0;
            }

            // Try different rating columns that might exist
            $rating = $ratings->avg('average_qet_rating');

            if ($rating === null) {
                $rating = $ratings->avg('final_rating');
            }

            if ($rating === null) {
                $rating = $ratings->avg('average_rating');
            }

            return round($rating ?? 0, 2);
        } catch (\Exception $e) {
            \Log::warning('Average rating calculation failed - column may not exist', [
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    /**
     * Get count of active performance periods
     */
    private function getActivePeriodsCount(): int
    {
        try {
            return PerformancePeriod::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count();
        } catch (\Exception $e) {
            \Log::warning('Active periods count failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Calculate overall performance score based on completion rate and ratings
     */
    private function calculatePerformanceScore(array $opcrMetrics): float
    {
        try {
            $completionRate = $opcrMetrics['workflow_completion_rate'] ?? 0;
            $averageRating = $opcrMetrics['average_rating'] ?? 0;
            $targetCompletionRate = $opcrMetrics['target_completion_rate'] ?? 0;

            // Performance score calculation:
            // - Completion Rate: 40% weight
            // - Average Rating: 40% weight (scaled to 100 if max is 5)
            // - Target Completion: 20% weight

            $ratingScore = ($averageRating > 0) ? ($averageRating * 20) : 0; // Convert 5-point scale to 100
            $completionScore = $completionRate;
            $targetScore = $targetCompletionRate;

            $performanceScore = ($completionScore * 0.4) + ($ratingScore * 0.4) + ($targetScore * 0.2);

            return round(min(100, max(0, $performanceScore)), 1);
        } catch (\Exception $e) {
            \Log::warning('Performance score calculation failed', ['error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Calculate workflow efficiency based on processing times and completion rates
     */
    private function calculateWorkflowEfficiency(?int $periodId, ?int $officeId): float
    {
        try {
            $avgProcessingTime = $this->calculateAverageProcessingTime($periodId, $officeId);
            $completionRate = $this->getOPCRMetrics($periodId, $officeId)['workflow_completion_rate'] ?? 0;

            // Efficiency calculation:
            // - Processing time score: 50% weight (lower time is better, ideal is 7 days)
            // - Completion rate score: 50% weight

            $processingScore = max(0, 100 - ($avgProcessingTime * 5)); // 5 points per day over ideal
            $completionScore = $completionRate;

            $efficiency = ($processingScore * 0.5) + ($completionScore * 0.5);

            return round(min(100, max(0, $efficiency)), 1);
        } catch (\Exception $e) {
            \Log::warning('Workflow efficiency calculation failed', ['error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Calculate compliance rate based on government standards and timeliness
     */
    private function calculateComplianceRate(?int $periodId, ?int $officeId): float
    {
        return $this->evaluateComplianceInsights($periodId, $officeId)['compliance_rate'];
    }

    /**
     * Get compliance analytics insights with supporting details.
     */
    public function getComplianceInsights(?int $periodId = null, ?int $officeId = null): array
    {
        return $this->evaluateComplianceInsights($periodId, $officeId);
    }

    /**
     * Evaluate workflow compliance against government standards and derive insights.
     */
    private function evaluateComplianceInsights(?int $periodId, ?int $officeId): array
    {
        try {
            $periodId = $periodId ?? $this->getCurrentPeriodId();

            $workflows = OPCRWorkflow::with(['office:id,name', 'period:id,name,end_date'])
                ->when($periodId, fn ($builder) => $builder->where('period_id', $periodId))
                ->when($officeId, fn ($builder) => $builder->where('office_id', $officeId))
                ->get();

            if ($workflows->isEmpty()) {
                return [
                    'compliance_rate' => 0.0,
                    'on_time_rate' => 0.0,
                    'document_completeness_rate' => 0.0,
                    'overdue_count' => 0,
                    'critical_issues' => [],
                    'upcoming_deadlines' => $this->buildUpcomingPeriodDeadlines($periodId),
                    'recommendations' => [],
                    'total_workflows' => 0,
                    'compliant_count' => 0,
                ];
            }

            $compliantCount = 0;
            $totalCount = $workflows->count();
            $onTimeCount = 0;
            $completeDocsCount = 0;
            $overdueCount = 0;
            $criticalIssues = [];

            foreach ($workflows as $workflow) {
                $complianceIssues = [];
                $hasTimeIssue = false;

                $daysSinceCreation = $workflow->created_at ? (int) $workflow->created_at->diffInDays(now()) : 0;
                $daysSinceUpdate = $workflow->updated_at ? (int) $workflow->updated_at->diffInDays(now()) : 0;

                switch ($workflow->workflow_state) {
                    case OPCRWorkflow::STATE_DRAFT:
                        if ($daysSinceCreation > 7) {
                            $complianceIssues[] = "Draft stage exceeds 7-day limit ({$daysSinceCreation} days)";
                            $hasTimeIssue = true;
                        }
                        break;

                    case OPCRWorkflow::STATE_COMMITTED:
                        if ($daysSinceUpdate > 14) {
                            $complianceIssues[] = "Committed stage exceeds 14-day limit ({$daysSinceUpdate} days)";
                            $hasTimeIssue = true;
                        }
                        break;

                    case OPCRWorkflow::STATE_IN_PROGRESS:
                        if ($daysSinceUpdate > 14) {
                            $complianceIssues[] = "Assessment exceeds 14-day limit ({$daysSinceUpdate} days)";
                            $hasTimeIssue = true;
                        }
                        break;

                    case OPCRWorkflow::STATE_EVALUATION:
                        if ($daysSinceUpdate > 7) {
                            $complianceIssues[] = "Evaluation exceeds 7-day limit ({$daysSinceUpdate} days)";
                            $hasTimeIssue = true;
                        }
                        break;

                    case OPCRWorkflow::STATE_FINAL_APPROVAL:
                        if (empty($workflow->overall_rating)) {
                            $complianceIssues[] = 'Completed workflow missing overall rating';
                        }
                        if (empty($workflow->summary)) {
                            $complianceIssues[] = 'Completed workflow missing summary';
                        }
                        break;

                    case OPCRWorkflow::STATE_RETURNED:
                        if ($daysSinceUpdate > 7) {
                            $complianceIssues[] = "Returned workflow exceeds 7-day rework limit ({$daysSinceUpdate} days)";
                            $hasTimeIssue = true;
                        }
                        break;
                }

                if ($daysSinceCreation > 60 && $workflow->workflow_state !== OPCRWorkflow::STATE_FINAL_APPROVAL) {
                    $complianceIssues[] = "Workflow exceeds 60-day maximum processing time ({$daysSinceCreation} days)";
                    $hasTimeIssue = true;
                }

                if (in_array($workflow->workflow_state, [
                    OPCRWorkflow::STATE_COMMITTED,
                    OPCRWorkflow::STATE_IN_PROGRESS,
                    OPCRWorkflow::STATE_EVALUATION,
                    OPCRWorkflow::STATE_FINAL_APPROVAL,
                ])) {
                    if (empty($workflow->title)) {
                        $complianceIssues[] = 'Missing workflow title';
                    }
                }

                if ($daysSinceUpdate > 21) {
                    $complianceIssues[] = "Workflow inactive for {$daysSinceUpdate} days (limit: 21 days)";
                    $hasTimeIssue = true;
                }

                $documentsComplete = !empty($workflow->overall_rating) && !empty($workflow->summary);
                if ($documentsComplete) {
                    $completeDocsCount++;
                }

                $isCompliant = empty($complianceIssues);

                if ($isCompliant) {
                    $compliantCount++;
                    $onTimeCount++;
                } else {
                    if (!$hasTimeIssue) {
                        $onTimeCount++;
                    } else {
                        $overdueCount++;
                    }

                    $title = $workflow->title ?: 'Workflow #' . $workflow->id;
                    $primaryIssue = $complianceIssues[0] ?? 'Compliance exception detected';

                    $criticalIssues[] = [
                        'title' => $title,
                        'description' => $primaryIssue,
                        'workflow_state' => $workflow->workflow_state,
                        'office' => $workflow->office->name ?? null,
                    ];
                }
            }

            $complianceRate = ($totalCount > 0) ? round(($compliantCount / $totalCount) * 100, 1) : 0.0;
            $onTimeRate = ($totalCount > 0) ? round(($onTimeCount / $totalCount) * 100, 1) : 0.0;
            $documentCompleteness = ($totalCount > 0) ? round(($completeDocsCount / $totalCount) * 100, 1) : 0.0;

            $upcomingDeadlines = $this->buildUpcomingPeriodDeadlines($periodId);

            $recommendations = $this->buildComplianceRecommendations(
                $complianceRate,
                $onTimeRate,
                $documentCompleteness,
                $overdueCount,
                $criticalIssues,
                $upcomingDeadlines
            );

            \Log::info('Compliance calculation completed', [
                'period_id' => $periodId,
                'office_id' => $officeId,
                'total_workflows' => $totalCount,
                'compliant_workflows' => $compliantCount,
                'compliance_rate' => $complianceRate,
                'non_compliant_count' => count($criticalIssues)
            ]);

            return [
                'compliance_rate' => $complianceRate,
                'on_time_rate' => $onTimeRate,
                'document_completeness_rate' => $documentCompleteness,
                'overdue_count' => $overdueCount,
                'critical_issues' => array_slice($criticalIssues, 0, 6),
                'upcoming_deadlines' => $upcomingDeadlines,
                'recommendations' => $recommendations,
                'total_workflows' => $totalCount,
                'compliant_count' => $compliantCount,
            ];
        } catch (\Exception $e) {
            \Log::warning('Compliance insight evaluation failed', ['error' => $e->getMessage()]);

            return [
                'compliance_rate' => 0.0,
                'on_time_rate' => 0.0,
                'document_completeness_rate' => 0.0,
                'overdue_count' => 0,
                'critical_issues' => [],
                'upcoming_deadlines' => [],
                'recommendations' => [],
                'total_workflows' => 0,
                'compliant_count' => 0,
            ];
        }
    }

    /**
     * Build upcoming deadline summaries based on the active performance period.
     */
    private function buildUpcomingPeriodDeadlines(?int $periodId): array
    {
        if (!$periodId) {
            return [];
        }

        $period = PerformancePeriod::find($periodId);

        if (!$period) {
            return [];
        }

        $daysRemaining = (int) Carbon::now()->diffInDays($period->end_date, false);

        if ($daysRemaining < 0 || $daysRemaining > 45) {
            return [];
        }

        return [[
            'title' => $period->name . ' closeout',
            'due_date' => $period->end_date->format('M d, Y'),
            'days_remaining' => max(0, $daysRemaining),
        ]];
    }

    /**
     * Build recommendation entries based on compliance metrics.
     */
    private function buildComplianceRecommendations(
        float $complianceRate,
        float $onTimeRate,
        float $documentCompleteness,
        int $overdueCount,
        array $criticalIssues,
        array $upcomingDeadlines
    ): array {
        $recommendations = [];

        if ($complianceRate < 85) {
            $recommendations[] = [
                'title' => 'Boost compliance completion',
                'description' => 'Review non-compliant workflows and assign follow-up to responsible offices.',
                'impact' => 'High',
            ];
        }

        if ($onTimeRate < 80) {
            $recommendations[] = [
                'title' => 'Improve deadline adherence',
                'description' => 'Schedule reminder notices for departments exceeding processing time thresholds.',
                'impact' => 'Medium',
            ];
        }

        if ($documentCompleteness < 90) {
            $recommendations[] = [
                'title' => 'Complete documentation gaps',
                'description' => 'Ensure completed workflows include ratings and summaries before final approval.',
                'impact' => 'Medium',
            ];
        }

        if ($overdueCount > 0) {
            $recommendations[] = [
                'title' => 'Address overdue workflows',
                'description' => 'Escalate workflows with repeated time-limit violations to the HR compliance officer.',
                'impact' => 'High',
            ];
        }

        if (empty($recommendations) && !empty($upcomingDeadlines)) {
            $dueSoon = $upcomingDeadlines[0];
            $recommendations[] = [
                'title' => 'Maintain compliance momentum',
                'description' => 'Send a reminder about the upcoming ' . ($dueSoon['title'] ?? 'deadline') . ' to sustain performance.',
                'impact' => 'Low',
            ];
        }

        return array_slice($recommendations, 0, 5);
    }

    /**
     * Get performance distribution data for charts
     */
    public function getPerformanceDistribution(?int $periodId = null): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "performance_distribution_{$periodId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId) {
            // Get all rated workflows for the period
            $ratedWorkflows = OPCRWorkflow::where('period_id', $periodId)
                ->whereNotNull('overall_rating')
                ->pluck('overall_rating');

            if ($ratedWorkflows->isEmpty()) {
                return [
                    'categories' => ['No Data'],
                    'counts' => [0],
                    'percentages' => [100],
                    'total_rated' => 0
                ];
            }

            // Initialize distribution categories based on Philippine government performance ratings
            $distribution = [
                'Outstanding (4.5-5.0)' => 0,
                'Very Satisfactory (3.5-4.49)' => 0,
                'Satisfactory (2.5-3.49)' => 0,
                'Unsatisfactory (1.0-2.49)' => 0,
                'Poor (Below 1.0)' => 0
            ];

            // Categorize each rating
            foreach ($ratedWorkflows as $rating) {
                if ($rating >= 4.5) {
                    $distribution['Outstanding (4.5-5.0)']++;
                } elseif ($rating >= 3.5) {
                    $distribution['Very Satisfactory (3.5-4.49)']++;
                } elseif ($rating >= 2.5) {
                    $distribution['Satisfactory (2.5-3.49)']++;
                } elseif ($rating >= 1.0) {
                    $distribution['Unsatisfactory (1.0-2.49)']++;
                } else {
                    $distribution['Poor (Below 1.0)']++;
                }
            }

            // Remove empty categories
            $distribution = array_filter($distribution, function($count) {
                return $count > 0;
            });

            $total = array_sum($distribution);
            $categories = array_keys($distribution);
            $counts = array_values($distribution);
            $percentages = array_map(function($count) use ($total) {
                return round(($count / $total) * 100, 1);
            }, $counts);

            return [
                'categories' => $categories,
                'counts' => $counts,
                'percentages' => $percentages,
                'total_rated' => $total,
                'average_rating' => round($ratedWorkflows->avg(), 2),
                'highest_rating' => $ratedWorkflows->max(),
                'lowest_rating' => $ratedWorkflows->min()
            ];
        });
    }

    /**
     * Get performance trends data for charts
     */
    public function getPerformanceTrendsData(?int $periodId = null, int $months = 12): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();
        $cacheKey = "performance_trends_data_{$periodId}_{$months}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($periodId, $months) {
            // Get current period data
            $currentPeriod = PerformancePeriod::find($periodId);

            if (!$currentPeriod) {
                return [
                    'labels' => ['No Data'],
                    'average_ratings' => [0],
                    'completion_rates' => [0],
                    'total_workflows' => [0],
                    'rated_workflows' => [0],
                    'periods' => []
                ];
            }

            // Since we only have one period, we'll show monthly data within the current period
            $startDate = $currentPeriod->start_date;
            $endDate = $currentPeriod->end_date;

            // Get all workflows for the period grouped by month
            $monthExpression = DatabaseExpression::yearMonth('created_at');

            $monthlyData = OPCRWorkflow::where('period_id', $periodId)
                ->select([
                    DB::raw("{$monthExpression} as month"),
                    DB::raw('COUNT(*) as total_workflows'),
                    DB::raw('COUNT(CASE WHEN overall_rating IS NOT NULL THEN 1 END) as rated_workflows'),
                    DB::raw("COUNT(CASE WHEN workflow_state = 'final_approval' THEN 1 END) as completed_workflows"),
                    DB::raw('AVG(CASE WHEN overall_rating IS NOT NULL THEN overall_rating END) as average_rating')
                ])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw($monthExpression))
                ->orderBy('month')
                ->get();

            if ($monthlyData->isEmpty()) {
                // If no monthly data, show current period summary
                $periodSummary = OPCRWorkflow::where('period_id', $periodId)
                    ->selectRaw("
                        COUNT(*) as total_workflows,
                        COUNT(CASE WHEN overall_rating IS NOT NULL THEN 1 END) as rated_workflows,
                        COUNT(CASE WHEN workflow_state = 'final_approval' THEN 1 END) as completed_workflows,
                        AVG(CASE WHEN overall_rating IS NOT NULL THEN overall_rating END) as average_rating
                    ")
                    ->first();

                $completionRate = $periodSummary->total_workflows > 0
                    ? round(($periodSummary->completed_workflows / $periodSummary->total_workflows) * 100, 2)
                    : 0;

                return [
                    'labels' => [$currentPeriod->name],
                    'average_ratings' => [round($periodSummary->average_rating ?? 0, 2)],
                    'completion_rates' => [$completionRate],
                    'total_workflows' => [$periodSummary->total_workflows],
                    'rated_workflows' => [$periodSummary->rated_workflows],
                    'periods' => [
                        [
                            'id' => $currentPeriod->id,
                            'name' => $currentPeriod->name,
                            'start_date' => $currentPeriod->start_date->format('M Y'),
                            'end_date' => $currentPeriod->end_date->format('M Y')
                        ]
                    ]
                ];
            }

            // Process monthly data
            $labels = [];
            $averageRatings = [];
            $completionRates = [];
            $totalWorkflows = [];
            $ratedWorkflows = [];

            foreach ($monthlyData as $month) {
                $labels[] = date('M Y', strtotime($month->month . '-01'));
                $averageRatings[] = round($month->average_rating ?? 0, 2);
                $totalWorkflows[] = $month->total_workflows;
                $ratedWorkflows[] = $month->rated_workflows;

                $completionRate = $month->total_workflows > 0
                    ? round(($month->completed_workflows / $month->total_workflows) * 100, 2)
                    : 0;
                $completionRates[] = $completionRate;
            }

            return [
                'labels' => $labels,
                'average_ratings' => $averageRatings,
                'completion_rates' => $completionRates,
                'total_workflows' => $totalWorkflows,
                'rated_workflows' => $ratedWorkflows,
                'periods' => [
                    [
                        'id' => $currentPeriod->id,
                        'name' => $currentPeriod->name,
                        'start_date' => $currentPeriod->start_date->format('M Y'),
                        'end_date' => $currentPeriod->end_date->format('M Y')
                    ]
                ],
                'trend_summary' => [
                    'average_rating_change' => $this->calculateTrendChange($averageRatings),
                    'completion_rate_change' => $this->calculateTrendChange($completionRates),
                    'total_workflows_change' => $this->calculateTrendChange($totalWorkflows)
                ]
            ];
        });
    }

    /**
     * Calculate trend change between first and last values
     */
    private function calculateTrendChange(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $first = $values[0];
        $last = end($values);

        if ($first == 0) {
            return $last > 0 ? 100.0 : 0.0;
        }

        return round((($last - $first) / $first) * 100, 1);
    }

    /**
     * Calculate top performers count for the period
     */
    public function calculateTopPerformersCount(?int $periodId = null, ?int $officeId = null): int
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();

        $query = OPCRWorkflow::where('period_id', $periodId)
            ->whereNotNull('overall_rating')
            ->where('overall_rating', '>=', 4.0); // Top performers have 4.0+ rating

        if ($officeId) {
            $query->where('office_id', $officeId);
        }

        return $query->count();
    }

    /**
     * Calculate improvement rate between current and previous period
     */
    public function calculateImprovementRate(?int $periodId = null): float
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();

        if (!$periodId) {
            return 0.0;
        }

        // Get current period and its year
        $currentPeriod = PerformancePeriod::find($periodId);
        if (!$currentPeriod) {
            return 0.0;
        }

        $currentAvg = OPCRWorkflow::where('period_id', $periodId)
            ->whereNotNull('overall_rating')
            ->avg('overall_rating') ?? 0;

        // Get previous period based on year, not ID
        $previousPeriod = PerformancePeriod::where('year', '<', $currentPeriod->year)
            ->orderBy('year', 'desc')
            ->first();

        if (!$previousPeriod) {
            return 0.0; // No previous period to compare
        }

        $previousAvg = OPCRWorkflow::where('period_id', $previousPeriod->id)
            ->whereNotNull('overall_rating')
            ->avg('overall_rating') ?? 0;

        if ($previousAvg == 0) {
            return 0.0;
        }

        // Calculate percentage improvement
        $improvement = (($currentAvg - $previousAvg) / $previousAvg) * 100;

        return round(max(0, $improvement), 1); // Return positive improvement only
    }

    /**
     * Calculate target achievement rate for the period
     */
    public function calculateTargetAchievement(?int $periodId = null, ?int $officeId = null): float
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();

        $query = OPCRWorkflow::where('period_id', $periodId);

        if ($officeId) {
            $query->where('office_id', $officeId);
        }

        $totalWorkflows = $query->count();

        if ($totalWorkflows == 0) {
            return 0.0;
        }

        // Consider workflows with ratings as having achieved targets
        $achievedWorkflows = $query->whereNotNull('overall_rating')->count();

        $achievementRate = ($achievedWorkflows / $totalWorkflows) * 100;

        return round($achievementRate, 1);
    }

    /**
     * Get top individual performers for detailed display
     */
    public function getTopIndividualPerformers(?int $periodId = null, int $limit = 10): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();

        // Get workflows with ratings and group by department heads
        $topPerformers = OPCRWorkflow::select([
            'offices.name as office_name',
            DB::raw('MAX(opcr_workflows.overall_rating) as rating'),
            DB::raw('COUNT(opcr_workflows.id) as workflow_count')
        ])
            ->join('offices', 'opcr_workflows.office_id', '=', 'offices.id')
            ->where('opcr_workflows.period_id', $periodId)
            ->whereNotNull('opcr_workflows.overall_rating')
            ->groupBy('offices.id', 'offices.name')
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();

        return $topPerformers->map(function ($performer) {
            return [
                'name' => $performer->office_name,
                'rating' => round($performer->rating, 2),
                'workflow_count' => $performer->workflow_count
            ];
        })->toArray();
    }

    /**
     * Get most improved performers between periods
     */
    public function getMostImprovedPerformers(?int $periodId = null, int $limit = 5): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();

        if (!$periodId) {
            return [];
        }

        // Get current period and its year
        $currentPeriod = PerformancePeriod::find($periodId);
        if (!$currentPeriod) {
            return [];
        }

        // Get previous period based on year, not ID
        $previousPeriod = PerformancePeriod::where('year', '<', $currentPeriod->year)
            ->orderBy('year', 'desc')
            ->first();

        if (!$previousPeriod) {
            return [];
        }

        // Calculate improvement by office
        $improvements = OPCRWorkflow::selectRaw(
            'offices.name as office_name, ' .
            'AVG(CASE WHEN opcr_workflows.period_id = ? THEN opcr_workflows.overall_rating END) as current_rating, ' .
            'AVG(CASE WHEN opcr_workflows.period_id = ? THEN opcr_workflows.overall_rating END) as previous_rating',
            [(int) $periodId, (int) $previousPeriod->id]
        )
            ->join('offices', 'opcr_workflows.office_id', '=', 'offices.id')
            ->whereNotNull('opcr_workflows.overall_rating')
            ->whereIn('opcr_workflows.period_id', [$periodId, $previousPeriod->id])
            ->groupBy('offices.id', 'offices.name')
            ->get();

        return $improvements->map(function ($improvement) use ($previousPeriod) {
            $current = $improvement->current_rating ?? 0;
            $previous = $improvement->previous_rating ?? 0;

            if ($previous == 0) {
                $improvementRate = 0;
            } else {
                $improvementRate = (($current - $previous) / $previous) * 100;
            }

            return [
                'name' => $improvement->office_name,
                'improvement' => round(max(0, $improvementRate), 1),
                'improvement_rate' => round(max(0, $improvementRate), 1), // Keep for compatibility
                'current_rating' => round($current, 2),
                'previous_rating' => round($previous, 2)
            ];
        })
        ->where('improvement', '>', 0)
        ->sortByDesc('improvement_rate')
        ->take($limit)
        ->values()
        ->toArray();
    }

    /**
     * Get consistent performers (offices with stable high performance)
     */
    public function getConsistentPerformers(?int $periodId = null, int $limit = 5): array
    {
        $periodId = $periodId ?? $this->getCurrentPeriodId();

        // Get offices with consistent high ratings (4.0+) in current period
        $consistentPerformers = OPCRWorkflow::select([
            'offices.name as office_name',
            DB::raw('AVG(opcr_workflows.overall_rating) as avg_rating'),
            DB::raw('MIN(opcr_workflows.overall_rating) as min_rating'),
            DB::raw('COUNT(opcr_workflows.id) as workflow_count')
        ])
            ->join('offices', 'opcr_workflows.office_id', '=', 'offices.id')
            ->where('opcr_workflows.period_id', $periodId)
            ->whereNotNull('opcr_workflows.overall_rating')
            ->groupBy('offices.id', 'offices.name')
            ->havingRaw('AVG(opcr_workflows.overall_rating) >= 4.0') // Consistently high performers
            ->havingRaw('MIN(opcr_workflows.overall_rating) >= 3.5') // Minimum acceptable rating
            ->havingRaw('COUNT(opcr_workflows.id) >= 1') // At least one rated workflow
            ->orderByDesc('avg_rating')
            ->limit($limit)
            ->get();

        return $consistentPerformers->map(function ($performer) {
            return [
                'name' => $performer->office_name,
                'avg_rating' => round($performer->avg_rating, 2),
                'min_rating' => round($performer->min_rating, 2),
                'workflow_count' => $performer->workflow_count
            ];
        })->toArray();
    }
}
