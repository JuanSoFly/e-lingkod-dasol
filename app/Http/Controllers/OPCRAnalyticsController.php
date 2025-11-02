<?php

namespace App\Http\Controllers;

use App\Models\OPCRWorkflow;
use App\Models\PerformancePeriod;
use App\Models\Office;
use App\Services\DashboardAnalyticsService;
use App\Services\QETRatingCalculationService;
use App\Services\OPCRManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OPCRAnalyticsController extends Controller
{
    protected $dashboardAnalyticsService;
    protected $qetRatingService;
    protected $opcrManagementService;

    public function __construct(
        DashboardAnalyticsService $dashboardAnalyticsService,
        QETRatingCalculationService $qetRatingService,
        OPCRManagementService $opcrManagementService
    ) {
        $this->dashboardAnalyticsService = $dashboardAnalyticsService;
        $this->qetRatingService = $qetRatingService;
        $this->opcrManagementService = $opcrManagementService;

        // OPCR analytics permissions
        $this->middleware('permission:opcr.analytics.view')->only([
            'dashboard', 'periodOverview', 'officePerformance', 'workflowAnalytics'
        ]);
        $this->middleware('permission:opcr.export')->only([
            'exportAnalytics', 'generateReport'
        ]);
        $this->middleware('permission:opcr.analytics.admin')->only([
            'systemMetrics', 'performanceTrends'
        ]);
    }

    /**
     * Display OPCR analytics index page
     */
    public function index(Request $request): View
    {
        return $this->dashboard($request);
    }

    /**
     * Display OPCR analytics dashboard
     */
    public function dashboard(Request $request): View
    {
        $filters = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'date_range' => 'nullable|in:last_7_days,last_30_days,last_quarter,last_year,custom',
            'custom_start' => 'nullable|date|required_if:date_range,custom',
            'custom_end' => 'nullable|date|after_or_equal:custom_start|required_if:date_range,custom'
        ]);

        $user = auth()->user();
        $periodId = $filters['period_id'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        // Use existing working pattern from OPCRController
        $dashboardData = $this->dashboardAnalyticsService->getPerformanceSummary($periodId, $officeId);
        $pendingItems = $this->getPendingItemsForUser($user);
        $userRole = $this->getUserOPCRRole($user);

        return view('admin.opcr.analytics.index', [
            'dashboardData' => $dashboardData,
            'filters' => $filters,
            'periods' => PerformancePeriod::orderBy('end_date', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'start_date', 'end_date']),
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'pendingItems' => $pendingItems,
            'userRole' => $userRole,
            'selectedPeriod' => $periodId
        ]);
    }

    /**
     * Get performance period overview analytics
     */
    public function periodOverview(PerformancePeriod $period, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metrics' => 'array',
            'metrics.*' => 'in:submission_stats,completion_rates,rating_distributions,timeline_analysis',
            'office_id' => 'nullable|exists:offices,id',
            'include_charts' => 'boolean'
        ]);

        $overview = $this->dashboardAnalyticsService->getPeriodOverview($period, $validated);

        return response()->json([
            'success' => true,
            'overview' => $overview
        ]);
    }

    /**
     * Get office performance analytics
     */
    public function officePerformance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_ids' => 'nullable|array',
            'office_ids.*' => 'exists:offices,id',
            'comparison_type' => 'in:period_over_period,office_to_office,trend_analysis',
            'metrics' => 'array',
            'metrics.*' => 'in:completion_rate,average_rating,submission_timeliness,qet_scores'
        ]);

        $officeAnalytics = $this->dashboardAnalyticsService->getOfficePerformanceAnalytics($validated);

        return response()->json([
            'success' => true,
            'analytics' => $officeAnalytics
        ]);
    }

    /**
     * Get workflow analytics and insights
     */
    public function workflowAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'workflow_stage' => 'nullable|in:draft,committed,in_progress,evaluation,final_approval,completed',
            'analysis_type' => 'in:bottlenecks,performance_comparison,prediction_analysis',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        $workflowAnalytics = $this->dashboardAnalyticsService->getWorkflowAnalytics($validated);

        return response()->json([
            'success' => true,
            'analytics' => $workflowAnalytics
        ]);
    }

    /**
     * Get QET rating analytics
     */
    public function qetAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'rating_type' => 'in:quantity,efficiency,timeliness,adjectival',
            'analysis_depth' => 'in:summary,detailed,comprehensive',
            'benchmark_comparison' => 'boolean',
            'trend_analysis' => 'boolean'
        ]);

        $qetAnalytics = $this->qetRatingService->getRatingAnalytics($validated);

        return response()->json([
            'success' => true,
            'analytics' => $qetAnalytics
        ]);
    }

    /**
     * Get real-time dashboard metrics
     */
    public function liveMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'office_id' => 'nullable|exists:offices,id',
            'period_id' => 'nullable|exists:performance_periods,id',
            'metric_types' => 'array',
            'metric_types.*' => 'in:workflow_counts,submission_stats,approval_queue,rating_summaries'
        ]);

        $liveMetrics = $this->dashboardAnalyticsService->getLiveOPCRMetrics($validated);

        return response()->json([
            'success' => true,
            'metrics' => $liveMetrics,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Generate comprehensive performance report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|in:period_summary,office_comparison,system_performance,trend_analysis',
            'period_id' => 'required|exists:performance_periods,id',
            'office_ids' => 'nullable|array',
            'office_ids.*' => 'exists:offices,id',
            'include_sections' => 'array',
            'include_sections.*' => 'in:executive_summary,workflow_analysis,performance_metrics,qet_analysis,recommendations',
            'format' => 'required|in:pdf,excel,dashboard',
            'chart_types' => 'array',
            'chart_types.*' => 'in:bar,line,pie,area,scatter',
            'include_benchmarks' => 'boolean',
            'include_forecasts' => 'boolean'
        ]);

        try {
            $report = $this->dashboardAnalyticsService->generateOPCRReport($validated);

            return response()->json([
                'success' => true,
                'message' => 'Report generated successfully',
                'report_id' => $report['report_id'],
                'download_url' => route('opcr.analytics.download-report', ['reportId' => $report['report_id']]),
                'preview_url' => $report['preview_url'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Report generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download generated analytics report
     */
    public function downloadReport(string $reportId)
    {
        try {
            $result = $this->dashboardAnalyticsService->downloadAnalyticsReport($reportId);

            if (!$result['success']) {
                abort(404, $result['message'] ?? 'Report not found');
            }

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(404, 'Report not available');
        }
    }

    /**
     * Get performance trends and forecasts
     */
    public function performanceTrends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trend_type' => 'required|in:completion_rates,rating_trends,submission_patterns,qet_evolution',
            'period_range' => 'required|in:last_6_months,last_year,last_2_years,custom',
            'custom_start' => 'nullable|date|required_if:period_range,custom',
            'custom_end' => 'nullable|date|after_or_equal:custom_start|required_if:period_range,custom',
            'office_id' => 'nullable|exists:offices,id',
            'forecast_period' => 'nullable|in:next_quarter,next_6_months,next_year',
            'confidence_level' => 'nullable|in:80,90,95'
        ]);

        $trends = $this->dashboardAnalyticsService->getPerformanceTrends($validated);

        return response()->json([
            'success' => true,
            'trends' => $trends
        ]);
    }

    /**
     * Get system performance metrics
     */
    public function systemMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric_period' => 'nullable|in:last_hour,last_24_hours,last_week,last_month',
            'include_detailed' => 'boolean',
            'health_checks' => 'array',
            'health_checks.*' => 'in:database_performance,cache_status,queue_health,storage_usage'
        ]);

        $systemMetrics = $this->dashboardAnalyticsService->getSystemPerformanceMetrics($validated);

        return response()->json([
            'success' => true,
            'metrics' => $systemMetrics,
            'health_status' => $this->dashboardAnalyticsService->getSystemHealthStatus()
        ]);
    }

    /**
     * Get department comparison analytics
     */
    public function departmentComparison(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:performance_periods,id',
            'office_ids' => 'required|array|min:2',
            'office_ids.*' => 'exists:offices,id',
            'comparison_metrics' => 'array',
            'comparison_metrics.*' => 'in:completion_rate,average_rating,submission_timeliness,qet_scores,workflow_efficiency',
            'normalize_data' => 'boolean',
            'include_statistical_tests' => 'boolean'
        ]);

        $comparison = $this->dashboardAnalyticsService->getDepartmentComparison($validated);

        return response()->json([
            'success' => true,
            'comparison' => $comparison
        ]);
    }

    /**
     * Get custom analytics based on user-defined parameters
     */
    public function customAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query_definition' => 'required|array',
            'query_definition.dimensions' => 'array',
            'query_definition.dimensions.*' => 'in:office,period,workflow_stage,rating_category',
            'query_definition.metrics' => 'array',
            'query_definition.metrics.*' => 'in:count,sum,average,min,max,percentage',
            'query_definition.filters' => 'array',
            'query_definition.group_by' => 'nullable|array',
            'query_definition.sort_by' => 'nullable|array',
            'visualization_type' => 'nullable|in:table,chart,heat_map'
        ]);

        try {
            $analytics = $this->dashboardAnalyticsService->executeCustomAnalyticsQuery($validated);

            return response()->json([
                'success' => true,
                'analytics' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analytics query failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data in various formats
     */
    public function exportAnalytics(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:excel,csv',
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id'
        ]);

        try {
            $format = $validated['format'];
            $periodId = $validated['period_id'] ?? null;
            $officeId = $validated['office_id'] ?? null;

            // Get office performance comparison data
            $performanceData = $this->dashboardAnalyticsService->getOfficePerformanceComparison($periodId);

            // Filter by office if specified
            if ($officeId) {
                $performanceData = array_filter($performanceData, function($office) use ($officeId) {
                    return $office['office_id'] == $officeId;
                });
            }

            if (empty($performanceData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data available for export'
                ], 404);
            }

            // Generate filename
            $period = $periodId ? \App\Models\PerformancePeriod::find($periodId) : null;
            $periodName = $period ? $period->name : 'All Periods';
            $timestamp = now()->format('Y-m-d_H-i-s');

            if ($format === 'excel') {
                $filename = "office_performance_comparison_{$periodName}_{$timestamp}.xlsx";

                // Create Excel export
                $export = new \App\Exports\OfficePerformanceExport($performanceData, $periodName);

                return \Maatwebsite\Excel\Facades\Excel::download(
                    $export,
                    $filename,
                    \Maatwebsite\Excel\Excel::XLSX
                );
            } else {
                // CSV export
                $filename = "office_performance_comparison_{$periodName}_{$timestamp}.csv";

                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];

                $callback = function() use ($performanceData) {
                    $file = fopen('php://output', 'w');

                    // CSV header
                    fputcsv($file, [
                        'Office Name',
                        'Average Rating',
                        'Completion Rate (%)',
                        'QET Score',
                        'Status',
                        'Total Workflows',
                        'Completed Workflows'
                    ]);

                    // CSV data
                    foreach ($performanceData as $office) {
                        fputcsv($file, [
                            $office['name'],
                            $office['avg_rating'],
                            $office['completion_rate'],
                            $office['qet_score'],
                            $office['status'],
                            $office['total_workflows'],
                            $office['completed_workflows']
                        ]);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download analytics export
     */
    public function downloadExport(string $exportId)
    {
        try {
            $result = $this->dashboardAnalyticsService->downloadAnalyticsExport($exportId);

            if (!$result['success']) {
                abort(404, $result['message'] ?? 'Export not found');
            }

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(404, 'Export not available');
        }
    }

    /**
     * Get analytics configuration and metadata
     */
    public function configuration(): JsonResponse
    {
        $config = [
            'available_metrics' => $this->dashboardAnalyticsService->getAvailableMetrics(),
            'dimension_options' => $this->dashboardAnalyticsService->getDimensionOptions(),
            'chart_types' => $this->dashboardAnalyticsService->getSupportedChartTypes(),
            'export_formats' => ['excel', 'csv', 'json', 'parquet'],
            'report_types' => [
                'period_summary' => 'Comprehensive period performance summary',
                'office_comparison' => 'Comparative analysis across departments',
                'system_performance' => 'System health and performance metrics',
                'trend_analysis' => 'Historical trends and forecasting'
            ],
            'refresh_intervals' => [
                'real_time' => 'Real-time updates (5 minutes)',
                'hourly' => 'Hourly updates',
                'daily' => 'Daily updates'
            ]
        ];

        return response()->json([
            'success' => true,
            'configuration' => $config
        ]);
    }

    /**
     * Get performance benchmarks and targets
     */
    public function benchmarks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'benchmark_type' => 'required|in:industry,departmental,historical,target_based',
            'period_id' => 'required|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'metric_categories' => 'array',
            'metric_categories.*' => 'in:completion,ratings,efficiency,timeliness'
        ]);

        $benchmarks = $this->dashboardAnalyticsService->getPerformanceBenchmarks($validated);

        return response()->json([
            'success' => true,
            'benchmarks' => $benchmarks
        ]);
    }

    /**
     * Display performance analytics page
     */
    public function performance(Request $request): View
    {
        $filters = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'date_range' => 'nullable|in:last_7_days,last_30_days,last_quarter,last_year,custom',
            'custom_start' => 'nullable|date|required_if:date_range,custom',
            'custom_end' => 'nullable|date|after_or_equal:custom_start|required_if:date_range,custom'
        ]);

        $periodId = $filters['period_id'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        $performanceData = array_merge(
            $this->dashboardAnalyticsService->getOPCRMetrics($periodId, $officeId),
            $this->dashboardAnalyticsService->getTopPerformingEmployees($periodId, 10),
            $this->dashboardAnalyticsService->getPerformanceTrends(6),
            // Add missing data keys that the view expects
            [
                'top_performers_count' => $this->dashboardAnalyticsService->calculateTopPerformersCount($periodId, $officeId),
                'improvement_rate' => $this->dashboardAnalyticsService->calculateImprovementRate($periodId),
                'target_achievement' => $this->dashboardAnalyticsService->calculateTargetAchievement($periodId, $officeId),
                'top_individuals' => $this->dashboardAnalyticsService->getTopIndividualPerformers($periodId, 10),
                'most_improved' => $this->dashboardAnalyticsService->getMostImprovedPerformers($periodId, 5),
                'consistent_performers' => $this->dashboardAnalyticsService->getConsistentPerformers($periodId, 5),
                // Add data for charts and office comparison
                'office_performance' => $this->dashboardAnalyticsService->getOfficePerformanceComparison($periodId),
                'performance_distribution' => $this->dashboardAnalyticsService->getPerformanceDistribution($periodId),
                'performance_trends_data' => $this->dashboardAnalyticsService->getPerformanceTrendsData($periodId)
            ]
        );

        return view('admin.opcr.analytics.performance', [
            'performanceData' => $performanceData,
            'filters' => $filters,
            'periods' => PerformancePeriod::orderBy('end_date', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'start_date', 'end_date']),
            'offices' => Office::orderBy('name')->get(['id', 'name'])
        ]);
    }

    /**
     * Display workflow analytics page
     */
    public function workflow(Request $request): View
    {
        $filters = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'workflow_stage' => 'nullable|in:draft,committed,in_progress,evaluation,final_approval,completed',
            'date_range' => 'nullable|in:last_7_days,last_30_days,last_quarter,last_year,custom',
            'custom_start' => 'nullable|date|required_if:date_range,custom',
            'custom_end' => 'nullable|date|after_or_equal:custom_start|required_if:date_range,custom'
        ]);

        $periodId = $filters['period_id'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        // Get base metrics
        $opcrMetrics = $this->dashboardAnalyticsService->getOPCRMetrics($periodId, $officeId);
        $stateDistribution = $this->dashboardAnalyticsService->getWorkflowStateDistribution($periodId, $officeId);
        $recentActivities = $this->dashboardAnalyticsService->getRecentOPCRActivities(10);

        // Transform state distribution to match view expectations
        $stageCounts = [];
        $stagePercentages = [];
        foreach ($stateDistribution as $stage => $data) {
            $stageCounts[$stage] = $data['count'];
            $stagePercentages[$stage] = $data['percentage'];
        }

        // Calculate active workflows (all non-completed)
        $activeWorkflows = $opcrMetrics['total_workflows'] - $opcrMetrics['completed_workflows'];

        // Get advanced analytics
        $avgProcessingDays = $this->dashboardAnalyticsService->calculateAverageProcessingTime($periodId, $officeId);
        $fastestProcessing = $this->dashboardAnalyticsService->getFastestProcessingWorkflows($periodId, 5);
        $pendingActionsByStage = $this->dashboardAnalyticsService->getPendingActionsByStage($periodId, $officeId);
        $efficiencyScore = $this->dashboardAnalyticsService->calculateEfficiencyScore($periodId, $officeId);
        $bottleneckAnalysis = $this->dashboardAnalyticsService->getBottleneckAnalysis($periodId, $officeId);
        $processingTimeline = $this->dashboardAnalyticsService->getWorkflowProcessingTimeline($periodId);

        $workflowData = array_merge($opcrMetrics, [
            'active_workflows' => $activeWorkflows,
            'avg_processing_days' => $avgProcessingDays,
            'stage_counts' => $stageCounts,
            'stage_percentages' => $stagePercentages,
            'recent_activities' => $recentActivities,
            'fastest_processing' => $fastestProcessing,
            'pending_actions_by_stage' => $pendingActionsByStage,
            'efficiency_score' => $efficiencyScore,
            'bottleneck_analysis' => $bottleneckAnalysis,
            'processing_timeline' => $processingTimeline
        ]);

        return view('admin.opcr.analytics.workflow', [
            'workflowData' => $workflowData,
            'filters' => $filters,
            'periods' => PerformancePeriod::orderBy('end_date', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'start_date', 'end_date']),
            'offices' => Office::orderBy('name')->get(['id', 'name'])
        ]);
    }

    /**
     * Display compliance analytics page
     */
    public function compliance(Request $request): View
    {
        $filters = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'compliance_type' => 'nullable|in:submission_rate,deadline_adherence,document_completeness',
            'date_range' => 'nullable|in:last_7_days,last_30_days,last_quarter,last_year,custom',
            'custom_start' => 'nullable|date|required_if:date_range,custom',
            'custom_end' => 'nullable|date|after_or_equal:custom_start|required_if:date_range,custom'
        ]);

        $periodId = $filters['period_id'] ?? null;
        $officeId = $filters['office_id'] ?? null;

        // Use available service methods for compliance data
        $metrics = $this->dashboardAnalyticsService->getOPCRMetrics($periodId, $officeId);
        $officeComparison = $this->dashboardAnalyticsService->getOfficePerformanceComparison($periodId);

        // Create compliance-specific data from available metrics
        $complianceData = [
            'overall_compliance_rate' => $metrics['workflow_completion_rate'] ?? 0,
            'on_time_submission_rate' => $metrics['target_completion_rate'] ?? 0,
            'document_completeness' => $metrics['average_rating'] ? ($metrics['average_rating'] * 20) : 0, // Convert to percentage
            'overdue_count' => $metrics['draft_workflows'] ?? 0,
            'office_compliance' => $this->formatOfficeComplianceData($officeComparison),
            'critical_issues_count' => 0,
            'upcoming_deadlines_count' => 0,
            'recommendations' => []
        ];

        return view('admin.opcr.analytics.compliance', [
            'complianceData' => $complianceData,
            'filters' => $filters,
            'periods' => PerformancePeriod::orderBy('end_date', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'start_date', 'end_date']),
            'offices' => Office::orderBy('name')->get(['id', 'name'])
        ]);
    }

    /**
     * Format office compliance data from performance comparison
     */
    private function formatOfficeComplianceData(array $officeComparison): array
    {
        $formattedData = [];

        foreach ($officeComparison as $office) {
            $complianceRate = isset($office['completion_rate']) ? $office['completion_rate'] : 0;
            $avgRating = isset($office['average_rating']) ? $office['average_rating'] : 0;

            // Determine compliance status based on rate and rating
            if ($complianceRate >= 90 && $avgRating >= 4.0) {
                $status = 'Excellent';
            } elseif ($complianceRate >= 75 && $avgRating >= 3.0) {
                $status = 'Good';
            } elseif ($complianceRate >= 60) {
                $status = 'Needs Attention';
            } else {
                $status = 'Critical';
            }

            $formattedData[] = [
                'name' => $office['name'] ?? 'Unknown Office',
                'compliance_rate' => round($complianceRate, 1),
                'on_time_rate' => round($avgRating * 25, 1), // Convert rating to percentage
                'completeness_rate' => round($avgRating * 20, 1), // Convert rating to percentage
                'overdue_count' => $office['pending_count'] ?? 0,
                'compliance_status' => $status
            ];
        }

        return $formattedData;
    }

    /**
     * Get pending items for user based on their OPCR role
     */
    private function getPendingItemsForUser($user): array
    {
        $userRole = $this->getUserOPCRRole($user);
        $pendingItems = [];

        switch ($userRole) {
            case 'Department Head':
                $pendingItems['draft_workflows'] = OPCRWorkflow::whereHas('office', function ($query) use ($user) {
                    $query->whereHas('officeAssignments', function ($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id)
                               ->where('role', 'Department Head');
                    });
                })->where('workflow_state', 'draft')->count();

                $pendingItems['returned_workflows'] = OPCRWorkflow::whereHas('office', function ($query) use ($user) {
                    $query->whereHas('officeAssignments', function ($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id)
                               ->where('role', 'Department Head');
                    });
                })->where('workflow_state', 'returned')->count();
                break;

            case 'Assessor':
                $pendingItems['evaluation_workflows'] = OPCRWorkflow::where('workflow_state', 'evaluation')
                    ->whereHas('office', function ($query) use ($user) {
                        $query->whereHas('officeAssignments', function ($subQuery) use ($user) {
                            $subQuery->where('user_id', $user->id)
                                   ->where('role', 'Assessor');
                        });
                    })->count();
                break;

            case 'Final Approver':
                $pendingItems['final_approval_workflows'] = OPCRWorkflow::where('workflow_state', 'final_approval')->count();
                break;
        }

        return $pendingItems;
    }

    /**
     * Get user's OPCR role
     */
    private function getUserOPCRRole($user, ?OPCRWorkflow $workflow = null): string
    {
        if ($user->hasRole('Super Admin')) {
            return 'Super Admin';
        }

        if ($user->hasRole('HR Admin')) {
            return 'HR Admin';
        }

        if ($workflow) {
            $officeAssignment = $user->officeAssignments()
                ->where('office_id', $workflow->office_id)
                ->first();

            if ($officeAssignment) {
                return $officeAssignment->role;
            }
        }

        return 'Employee';
    }
}