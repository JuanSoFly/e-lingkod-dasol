<?php

namespace App\Http\Controllers;

use App\Services\HRAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class HRAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(HRAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        $this->middleware('auth');
    }

    /**
     * Display the main analytics dashboard
     */
    public function index(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to analytics dashboard.');
        }

        return view('hr-analytics.dashboard');
    }

    /**
     * Get workforce analytics data
     */
    public function getWorkforceAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getWorkforceAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving workforce analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get turnover analytics data
     */
    public function getTurnoverAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getTurnoverAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving turnover analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance analytics data
     */
    public function getPerformanceAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getPerformanceAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving performance analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get training analytics data
     */
    public function getTrainingAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getTrainingAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving training analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get compliance analytics data
     */
    public function getComplianceAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getComplianceAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving compliance analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workforce planning analytics data
     */
    public function getWorkforcePlanningAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getWorkforcePlanningAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving workforce planning analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cost analytics data
     */
    public function getCostAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getCostAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving cost analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get predictive analytics data
     */
    public function getPredictiveAnalytics(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $data = $this->analyticsService->getPredictiveAnalytics();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving predictive analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display workforce analytics page
     */
    public function workforce(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to workforce analytics.');
        }

        return view('hr-analytics.workforce');
    }

    /**
     * Display turnover analytics page
     */
    public function turnover(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to turnover analytics.');
        }

        return view('hr-analytics.turnover');
    }

    /**
     * Display performance analytics page
     */
    public function performance(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to performance analytics.');
        }

        return view('hr-analytics.performance');
    }

    /**
     * Display training analytics page
     */
    public function training(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to training analytics.');
        }

        return view('hr-analytics.training');
    }

    /**
     * Display compliance analytics page
     */
    public function compliance(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to compliance analytics.');
        }

        return view('hr-analytics.compliance');
    }

    /**
     * Display workforce planning page
     */
    public function workforcePlanning(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to workforce planning.');
        }

        return view('hr-analytics.workforce-planning');
    }

    /**
     * Display cost analytics page
     */
    public function costs(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to cost analytics.');
        }

        return view('hr-analytics.costs');
    }

    /**
     * Display predictive analytics page
     */
    public function predictive(): View
    {
        if (!Gate::allows('view-analytics')) {
            abort(403, 'Unauthorized access to predictive analytics.');
        }

        return view('hr-analytics.predictive');
    }

    /**
     * Get comprehensive analytics summary
     */
    public function getSummary(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $summary = [
                'workforce_summary' => $this->analyticsService->getWorkforceAnalytics()['employee_demographics'],
                'turnover_summary' => [
                    'annual_rate' => $this->analyticsService->getTurnoverAnalytics()['annual_turnover_rate'],
                    'high_risk_employees' => $this->analyticsService->getTurnoverAnalytics()['high_risk_employees']->count()
                ],
                'performance_summary' => [
                    'avg_rating' => $this->analyticsService->getPerformanceAnalytics()['performance_trends'][0]['average_rating'] ?? 0,
                    'top_performers' => $this->analyticsService->getPerformanceAnalytics()['top_performers']->count()
                ],
                'compliance_summary' => [
                    'csc_compliance' => $this->analyticsService->getComplianceAnalytics()['csc_compliance_rates']['compliance_rate'],
                    'training_compliance' => $this->analyticsService->getComplianceAnalytics()['training_compliance']['compliance_rate']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving analytics summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function export(Request $request): JsonResponse
    {
        if (!Gate::allows('export-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type' => 'required|in:workforce,turnover,performance,training,compliance,cost,predictive',
            'format' => 'required|in:pdf,excel,csv'
        ]);

        try {
            // This would typically generate a file and return a download link
            $exportData = [
                'file_path' => '/exports/analytics_' . $request->type . '_' . date('Y-m-d') . '.' . $request->format,
                'download_url' => url('/storage/exports/analytics_' . $request->type . '_' . date('Y-m-d') . '.' . $request->format)
            ];

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'message' => 'Analytics export generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): JsonResponse
    {
        if (!Gate::allows('manage-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $this->analyticsService->clearCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Analytics cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics for specific department
     */
    public function getDepartmentAnalytics(Request $request): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'department' => 'required|string'
        ]);

        try {
            // This would filter analytics by department
            $data = [
                'department' => $request->department,
                'workforce' => $this->analyticsService->getWorkforceAnalytics(),
                'performance' => $this->analyticsService->getPerformanceAnalytics(),
                'training' => $this->analyticsService->getTrainingAnalytics()
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving department analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics trends over time
     */
    public function getTrends(Request $request): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'metric' => 'required|in:turnover,performance,training,compliance',
            'period' => 'required|in:monthly,quarterly,yearly'
        ]);

        try {
            $trends = [];
            
            switch ($request->metric) {
                case 'turnover':
                    $trends = $this->analyticsService->getTurnoverAnalytics()['monthly_turnover_rate'];
                    break;
                case 'performance':
                    $trends = $this->analyticsService->getPerformanceAnalytics()['performance_trends'];
                    break;
                case 'training':
                    $trends = $this->analyticsService->getTrainingAnalytics()['training_completion_rates'];
                    break;
                case 'compliance':
                    $trends = $this->analyticsService->getComplianceAnalytics()['compliance_trends'];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'metric' => $request->metric,
                    'period' => $request->period,
                    'trends' => $trends
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics insights and recommendations
     */
    public function getInsights(): JsonResponse
    {
        if (!Gate::allows('view-analytics')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $workforce = $this->analyticsService->getWorkforceAnalytics();
            $turnover = $this->analyticsService->getTurnoverAnalytics();
            $performance = $this->analyticsService->getPerformanceAnalytics();
            $compliance = $this->analyticsService->getComplianceAnalytics();

            $insights = [];

            // Turnover insights
            if ($turnover['annual_turnover_rate'] > 15) {
                $insights[] = [
                    'type' => 'warning',
                    'category' => 'Turnover',
                    'message' => 'High turnover rate detected (' . round($turnover['annual_turnover_rate'], 1) . '%). Consider retention strategies.',
                    'priority' => 'high'
                ];
            }

            // Performance insights
            $lowPerformers = $performance['improvement_needed']->count();
            if ($lowPerformers > 0) {
                $insights[] = [
                    'type' => 'alert',
                    'category' => 'Performance',
                    'message' => $lowPerformers . ' employees need performance improvement support.',
                    'priority' => 'medium'
                ];
            }

            // Compliance insights
            if ($compliance['csc_compliance_rates']['compliance_rate'] < 90) {
                $insights[] = [
                    'type' => 'danger',
                    'category' => 'Compliance',
                    'message' => 'CSC compliance below 90%. Immediate action required.',
                    'priority' => 'high'
                ];
            }

            // Workforce planning insights
            $demographics = $workforce['employee_demographics'];
            if ($demographics['average_age'] > 50) {
                $insights[] = [
                    'type' => 'info',
                    'category' => 'Workforce Planning',
                    'message' => 'Aging workforce detected. Plan for succession and knowledge transfer.',
                    'priority' => 'medium'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'insights' => $insights,
                    'total_insights' => count($insights),
                    'high_priority' => collect($insights)->where('priority', 'high')->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating insights: ' . $e->getMessage()
            ], 500);
        }
    }
}