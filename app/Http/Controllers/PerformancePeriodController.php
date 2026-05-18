<?php

namespace App\Http\Controllers;

use App\Models\PerformancePeriod;
use App\Models\Office;
use App\Models\OPCRWorkflow;
use App\Services\PerformancePeriodService;
use App\Services\OPCRWorkflowService;
use App\Http\Requests\StorePerformancePeriodRequest;
use App\Http\Requests\UpdatePerformancePeriodRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class PerformancePeriodController extends Controller
{
    protected $performancePeriodService;
    protected $opcrWorkflowService;

    public function __construct(
        PerformancePeriodService $performancePeriodService,
        OPCRWorkflowService $opcrWorkflowService
    ) {
        $this->performancePeriodService = $performancePeriodService;
        $this->opcrWorkflowService = $opcrWorkflowService;

        // OPCR-specific permissions
        $this->middleware('permission:performance-period.view')->only(['index', 'show', 'analytics']);
        $this->middleware('permission:performance-period.create')->only(['create', 'store']);
        $this->middleware('permission:performance-period.edit')->only(['edit', 'update']);
        $this->middleware('permission:performance-period.delete')->only(['destroy']);
        $this->middleware('permission:performance-period.manage')->only(['activate', 'close', 'bulkOperations']);
    }

    /**
     * Display a listing of performance periods with OPCR status
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => 'nullable|in:active,inactive,closed,upcoming',
            'office_id' => 'nullable|integer|exists:offices,id',
            'year' => 'nullable|integer|min:2020|max:2030',
            'per_page' => 'nullable|integer|min:10|max:100'
        ]);

        // Set default values for filters to avoid undefined array key errors
        $filters = array_merge([
            'status' => '',
            'office_id' => '',
            'year' => '',
            'per_page' => 15
        ], $filters);

        $periods = $this->performancePeriodService->getPeriodsWithOPCRStatus($filters);

        // Determine which view to use based on route name
        $viewPrefix = request()->routeIs('admin.*') ? 'admin.performance-periods' : 'performance_periods';

        return view($viewPrefix . '.index', [
            'periods' => $periods,
            'filters' => $filters,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'availableYears' => $this->performancePeriodService->getAvailableYears(),
            'periodStatuses' => $this->performancePeriodService->getStatusOptions()
        ]);
    }

    /**
     * Show the form for creating a new performance period
     */
    public function create(): View
    {
        // Determine which view to use based on route name
        $viewPrefix = request()->routeIs('admin.*') ? 'admin.performance-periods' : 'performance_periods';

        return view($viewPrefix . '.create', [
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'defaultSettings' => $this->performancePeriodService->getDefaultPeriodSettings(),
            'workflowStages' => []
        ]);
    }

    /**
     * Store a newly created performance period
     */
    public function store(StorePerformancePeriodRequest $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $period = $this->performancePeriodService->createPeriod($request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Performance period created successfully',
                    'period' => $period->loadMissing(['office'])
                ]);
            }

            $routePrefix = request()->routeIs('admin.*') ? 'admin.performance-periods.index' : 'performance-periods.index';
            return redirect()->route($routePrefix)->with('success', 'Performance period created successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create performance period: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Failed to create performance period: ' . $e->getMessage());
        }
    }

    /**
     * Display specific performance period with OPCR analytics
     */
    public function show(PerformancePeriod $performancePeriod, Request $request): Response
    {
        $analytics = $request->has('analytics')
            ? $this->performancePeriodService->getPeriodAnalytics($performancePeriod, $request->all())
            : null;

        $period = $performancePeriod->load([
            'office',
            'opcrWorkflows' => function ($query) {
                $query->with(['employee', 'currentState'])->latest();
            }
        ]);

        // Determine which view to use based on route name
        $viewPrefix = request()->routeIs('admin.*') ? 'Admin/performance_periods' : 'performance_periods';

        return Inertia::render($viewPrefix . '/Show', [
            'period' => $period,
            'analytics' => $analytics,
            'workflowSummary' => $this->performancePeriodService->getWorkflowSummary($performancePeriod)
        ]);
    }

    /**
     * Show the form for editing the specified performance period
     */
    public function edit(PerformancePeriod $performancePeriod): View
    {
        if ($performancePeriod->is_locked) {
            abort(403, 'Cannot edit a locked performance period');
        }

        // Determine which view to use based on route name
        $viewPrefix = request()->routeIs('admin.*') ? 'admin.performance-periods' : 'performance_periods';

        return view($viewPrefix . '.edit', [
            'performancePeriod' => $performancePeriod,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'workflowSettings' => $this->performancePeriodService->getWorkflowSettings($performancePeriod)
        ]);
    }

    /**
     * Update the specified performance period
     */
    public function update(UpdatePerformancePeriodRequest $request, PerformancePeriod $performancePeriod): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($performancePeriod->is_locked) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update a locked performance period'
                ], 422);
            }
            return redirect()->back()->with('error', 'Cannot update a locked performance period.');
        }

        try {
            $updatedPeriod = $this->performancePeriodService->updatePeriod($performancePeriod, $request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Performance period updated successfully',
                    'period' => $updatedPeriod->loadMissing(['office'])
                ]);
            }

            $routePrefix = request()->routeIs('admin.*') ? 'admin.performance-periods.index' : 'performance-periods.index';
            return redirect()->route($routePrefix)->with('success', 'Performance period updated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update performance period: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Failed to update performance period: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified performance period
     */
    public function destroy(PerformancePeriod $performancePeriod): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $result = $this->performancePeriodService->deletePeriod($performancePeriod);

            if ($result['deleted']) {
                if (request()->wantsJson() || request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Performance period deleted successfully'
                    ]);
                }

                $routePrefix = request()->routeIs('admin.*') ? 'admin.performance-periods.index' : 'performance-periods.index';
                return redirect()->route($routePrefix)->with('success', 'Performance period deleted successfully.');
            } else {
                if (request()->wantsJson() || request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'] ?? 'Cannot delete performance period'
                    ], 422);
                }

                return redirect()->back()->with('error', $result['message'] ?? 'Cannot delete performance period.');
            }
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete performance period: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete performance period: ' . $e->getMessage());
        }
    }

    /**
     * Activate a performance period for OPCR submissions
     */
    public function activate(PerformancePeriod $performancePeriod, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_settings' => 'array',
            'notification_settings.email_department_heads' => 'boolean',
            'notification_settings.notification_message' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->performancePeriodService->activatePeriod($performancePeriod, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Performance period activated successfully',
                'activated_workflows' => $result['activated_workflows'],
                'notifications_sent' => $result['notifications_sent']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate performance period: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close a performance period and archive OPCR workflows
     */
    public function close(PerformancePeriod $performancePeriod, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'final_report' => 'nullable|string|max:5000',
            'archive_settings' => 'array',
            'archive_settings.include_drafts' => 'boolean',
            'archive_settings.backup_location' => 'nullable|string|max:255'
        ]);

        try {
            $result = $this->performancePeriodService->closePeriod($performancePeriod, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Performance period closed successfully',
                'archived_workflows' => $result['archived_workflows'],
                'final_report' => $result['final_report']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close performance period: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get period analytics for dashboard
     */
    public function analytics(PerformancePeriod $performancePeriod, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metrics' => 'array',
            'metrics.*' => 'in:submissions,approvals,ratings,timeline,office_performance',
            'office_id' => 'nullable|integer|exists:offices,id'
        ]);

        $analytics = $this->performancePeriodService->getPeriodAnalytics($performancePeriod, $validated);

        return response()->json($analytics);
    }

    /**
     * Bulk operations on performance periods
     */
    public function bulkOperations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operation' => 'required|in:activate,close,extend,archive',
            'period_ids' => 'required|array',
            'period_ids.*' => 'exists:performance_periods,id',
            'operation_settings' => 'array',
            'operation_settings.extension_days' => 'nullable|integer|min:1|max:365',
            'operation_settings.notification_message' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->performancePeriodService->performBulkOperation($validated);

            return response()->json([
                'success' => true,
                'message' => "Bulk {$validated['operation']} completed successfully",
                'affected_periods' => $result['affected_periods'],
                'operation_details' => $result['details']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a performance period with all settings
     */
    public function duplicate(PerformancePeriod $performancePeriod, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_name' => 'required|string|max:255',
            'new_start_date' => 'required|date',
            'new_end_date' => 'required|date|after:new_start_date',
            'copy_settings' => 'array',
            'copy_settings.copy_workflow_configuration' => 'boolean',
            'copy_settings.copy_office_assignments' => 'boolean'
        ]);

        try {
            $newPeriod = $this->performancePeriodService->duplicatePeriod($performancePeriod, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Performance period duplicated successfully',
                'new_period' => $newPeriod->load(['office'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate performance period: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export performance period data
     */
    public function export(PerformancePeriod $performancePeriod, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'include_data' => 'array',
            'include_data.workflows' => 'boolean',
            'include_data.analytics' => 'boolean',
            'include_data.evaluations' => 'boolean',
            'office_id' => 'nullable|integer|exists:offices,id'
        ]);

        try {
            $filePath = $this->performancePeriodService->exportPeriodData($performancePeriod, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Performance period data exported successfully',
                'file_path' => $filePath,
                'download_url' => route('performance-periods.download-export', [
                    'performancePeriod' => $performancePeriod->id,
                    'filename' => basename($filePath)
                ])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export period data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download exported file
     */
    public function downloadExport(PerformancePeriod $performancePeriod, string $filename)
    {
        $filePath = storage_path('app/exports/performance-periods/' . $filename);

        if (!file_exists($filePath)) {
            abort(404, 'Export file not found');
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Get performance period calendar view
     */
    public function calendar(Request $request): Response
    {
        $filters = $request->validate([
            'year' => 'nullable|integer|min:2020|max:2030',
            'office_id' => 'nullable|integer|exists:offices,id'
        ]);

        $calendarData = $this->performancePeriodService->getCalendarData($filters);

        // Determine which view to use based on route name
        $viewPrefix = request()->routeIs('admin.*') ? 'Admin/performance_periods' : 'performance_periods';

        return Inertia::render($viewPrefix . '/Calendar', [
            'calendarData' => $calendarData,
            'filters' => $filters,
            'offices' => Office::orderBy('name')->get(['id', 'name'])
        ]);
    }
}