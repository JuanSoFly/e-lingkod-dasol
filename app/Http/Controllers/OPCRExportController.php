<?php

namespace App\Http\Controllers;

use App\Models\OPCRWorkflow;
use App\Models\PerformancePeriod;
use App\Models\Office;
use App\Services\OPCRExportService;
use App\Services\OPCRManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class OPCRExportController extends Controller
{
    protected $opcrExportService;
    protected $opcrManagementService;

    public function __construct(
        OPCRExportService $opcrExportService,
        OPCRManagementService $opcrManagementService
    ) {
        $this->opcrExportService = $opcrExportService;
        $this->opcrManagementService = $opcrManagementService;

        // OPCR export permissions
        $this->middleware('permission:opcr.export')->only(['index', 'export', 'exportPDF']);
        $this->middleware('permission:opcr.export.manage')->only(['templates', 'createTemplate', 'downloadExport', 'bulkExport']);
        $this->middleware('permission:opcr.export.history')->only(['history', 'download']);
        $this->middleware('permission:opcr.export.schedule')->only(['schedule', 'scheduledExports']);
    }

    /**
     * Display OPCR export interface with options
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'period_id' => 'nullable|exists:performance_periods,id',
            'office_id' => 'nullable|exists:offices,id',
            'status' => 'nullable|in:draft,committed,in_progress,evaluation,final_approval,completed,archived',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        $exportableWorkflows = $this->opcrManagementService->getExportableWorkflows($filters);
        $recentExports = $this->opcrExportService->getRecentExports(10);

        return Inertia::render('Admin/OPCR/Exports/Index', [
            'exportableWorkflows' => $exportableWorkflows,
            'recentExports' => $recentExports,
            'filters' => $filters,
            'periods' => PerformancePeriod::orderBy('end_date', 'desc')->get(['id', 'name', 'start_date', 'end_date']),
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'exportFormats' => $this->opcrExportService->getSupportedFormats(),
            'exportTemplates' => $this->opcrExportService->getExportTemplates()
        ]);
    }

    /**
     * Export OPCR data in specified format
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'workflow_ids' => 'required|array',
            'workflow_ids.*' => 'exists:opcr_workflows,id',
            'export_options' => 'array',
            'export_options.include_sections' => 'array',
            'export_options.include_sections.*' => 'in:mfo,targets,accomplishments,evaluations,signatures',
            'export_options.include_ratings' => 'boolean',
            'export_options.include_comments' => 'boolean',
            'export_options.include_attachments' => 'boolean',
            'export_options.include_analytics' => 'boolean',
            'export_options.template_id' => 'nullable|exists:export_templates,id',
            'export_options.custom_template' => 'nullable|string|max:5000',
            'export_options.watermark' => 'nullable|string|max:255',
            'export_options.page_orientation' => 'nullable|in:portrait,landscape',
            'export_options.paper_size' => 'nullable|in:a4,letter,legal'
        ]);

        try {
            $result = $this->opcrExportService->exportOPCRData($validated);

            return response()->json([
                'success' => true,
                'message' => 'OPCR export completed successfully',
                'export_id' => $result['export_id'],
                'file_info' => $result['file_info'],
                'download_url' => route('opcr.exports.download', ['exportId' => $result['export_id']]),
                'preview_url' => $result['preview_url'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download exported OPCR file
     */
    public function download(string $exportId)
    {
        try {
            $result = $this->opcrExportService->downloadExport($exportId);

            if (!$result['success']) {
                abort(404, $result['message'] ?? 'Export file not found');
            }

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend($result['delete_after_download']);
        } catch (\Exception $e) {
            abort(404, 'Export file not available');
        }
    }

    /**
     * Export management interface
     */
    public function history(Request $request): Response
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'format' => 'nullable|in:pdf,excel,csv',
            'user_id' => 'nullable|exists:users,id',
            'office_id' => 'nullable|exists:offices,id',
            'per_page' => 'nullable|integer|min:10|max:100'
        ]);

        $exportHistory = $this->opcrExportService->getExportHistory($filters);

        return Inertia::render('Admin/OPCR/Exports/History', [
            'exportHistory' => $exportHistory,
            'filters' => $filters,
            'users' => $this->opcrExportService->getExportUsers(),
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'statistics' => $this->opcrExportService->getExportStatistics()
        ]);
    }

    /**
     * Export template management
     */
    public function templates(): Response
    {
        $templates = $this->opcrExportService->getExportTemplates(true);
        $defaultSections = $this->opcrExportService->getDefaultExportSections();

        return Inertia::render('Admin/OPCR/Exports/Templates', [
            'templates' => $templates,
            'defaultSections' => $defaultSections,
            'supportedFormats' => $this->opcrExportService->getSupportedFormats()
        ]);
    }

    /**
     * Create new export template
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'format' => 'required|in:pdf,excel,csv',
            'template_structure' => 'required|array',
            'template_structure.sections' => 'required|array',
            'template_structure.sections.*.name' => 'required|string|max:255',
            'template_structure.sections.*.included' => 'boolean',
            'template_structure.sections.*.order' => 'integer',
            'template_structure.layout' => 'array',
            'template_structure.layout.page_orientation' => 'nullable|in:portrait,landscape',
            'template_structure.layout.paper_size' => 'nullable|in:a4,letter,legal',
            'template_structure.layout.header' => 'nullable|string|max:1000',
            'template_structure.layout.footer' => 'nullable|string|max:1000',
            'template_structure.layout.watermark' => 'nullable|string|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean'
        ]);

        try {
            $template = $this->opcrExportService->createExportTemplate($validated);

            return response()->json([
                'success' => true,
                'message' => 'Export template created successfully',
                'template' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk export multiple OPCR workflows
     */
    public function bulkExport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'selection_type' => 'required|in:all,filtered,selected',
            'criteria' => 'array',
            'criteria.period_id' => 'nullable|exists:performance_periods,id',
            'criteria.office_id' => 'nullable|exists:offices,id',
            'criteria.status' => 'nullable|array',
            'criteria.status.*' => 'in:draft,committed,in_progress,evaluation,final_approval,completed,archived',
            'criteria.date_from' => 'nullable|date',
            'criteria.date_to' => 'nullable|date|after_or_equal:criteria.date_from',
            'workflow_ids' => 'required_if:selection_type,selected|array',
            'workflow_ids.*' => 'exists:opcr_workflows,id',
            'export_settings' => 'array',
            'export_settings.format' => 'required|in:pdf,excel,csv',
            'export_settings.combine_files' => 'boolean',
            'export_settings.include_sections' => 'array',
            'export_settings.include_sections.*' => 'in:mfo,targets,accomplishments,evaluations,signatures',
            'export_settings.template_id' => 'nullable|exists:export_templates,id',
            'export_settings.filename_pattern' => 'nullable|string|max:255',
            'export_settings.create_summary' => 'boolean'
        ]);

        try {
            $result = $this->opcrExportService->performBulkExport($validated);

            return response()->json([
                'success' => true,
                'message' => 'Bulk export initiated successfully',
                'batch_id' => $result['batch_id'],
                'estimated_time' => $result['estimated_time'],
                'progress_url' => route('opcr.exports.bulk-progress', ['batchId' => $result['batch_id']])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk export progress
     */
    public function bulkProgress(string $batchId): JsonResponse
    {
        $progress = $this->opcrExportService->getBulkExportProgress($batchId);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Batch export not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * Schedule automated OPCR exports
     */
    public function schedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'schedule_type' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'schedule_config' => 'array',
            'schedule_config.time' => 'required|string|date_format:H:i',
            'schedule_config.day_of_week' => 'nullable|integer|min:1|max:7',
            'schedule_config.day_of_month' => 'nullable|integer|min:1|max:31',
            'schedule_config.month' => 'nullable|integer|min:1|max:12',
            'export_criteria' => 'array',
            'export_criteria.status' => 'nullable|array',
            'export_criteria.status.*' => 'in:draft,committed,in_progress,evaluation,final_approval,completed,archived',
            'export_criteria.office_ids' => 'nullable|array',
            'export_criteria.office_ids.*' => 'exists:offices,id',
            'export_settings' => 'array',
            'export_settings.format' => 'required|in:pdf,excel,csv',
            'export_settings.template_id' => 'nullable|exists:export_templates,id',
            'export_settings.email_recipients' => 'array',
            'export_settings.email_recipients.*' => 'email',
            'is_active' => 'boolean'
        ]);

        try {
            $schedule = $this->opcrExportService->createExportSchedule($validated);

            return response()->json([
                'success' => true,
                'message' => 'Export schedule created successfully',
                'schedule' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View scheduled exports
     */
    public function scheduledExports(): Response
    {
        $schedules = $this->opcrExportService->getExportSchedules();
        $executionHistory = $this->opcrExportService->getScheduleExecutionHistory();

        return Inertia::render('Admin/OPCR/Exports/Schedules', [
            'schedules' => $schedules,
            'executionHistory' => $executionHistory,
            'scheduleTypes' => $this->opcrExportService->getScheduleTypes()
        ]);
    }

    /**
     * Generate summary report for OPCR exports
     */
    public function summaryReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:performance_periods,id',
            'office_ids' => 'nullable|array',
            'office_ids.*' => 'exists:offices,id',
            'include_metrics' => 'boolean',
            'include_charts' => 'boolean',
            'format' => 'required|in:pdf,excel'
        ]);

        try {
            $report = $this->opcrExportService->generateSummaryReport($validated);

            return response()->json([
                'success' => true,
                'message' => 'Summary report generated successfully',
                'report_id' => $report['report_id'],
                'download_url' => route('opcr.exports.download-summary', ['reportId' => $report['report_id']])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download summary report
     */
    public function downloadSummary(string $reportId)
    {
        try {
            $result = $this->opcrExportService->downloadSummaryReport($reportId);

            if (!$result['success']) {
                abort(404, $result['message'] ?? 'Summary report not found');
            }

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(404, 'Summary report not available');
        }
    }

    /**
     * Get export statistics and analytics
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:today,week,month,quarter,year',
            'office_id' => 'nullable|exists:offices,id',
            'format' => 'nullable|in:pdf,excel,csv'
        ]);

        $statistics = $this->opcrExportService->getExportStatistics($validated);

        return response()->json($statistics);
    }

    /**
     * Delete export record and associated files
     */
    public function delete(string $exportId): JsonResponse
    {
        try {
            $result = $this->opcrExportService->deleteExport($exportId);

            if ($result['deleted']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Export deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Cannot delete export'
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export preview for specific workflow
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_id' => 'required|exists:opcr_workflows,id',
            'format' => 'required|in:pdf,excel',
            'sections' => 'array',
            'sections.*' => 'in:mfo,targets,accomplishments,evaluations,signatures'
        ]);

        try {
            $preview = $this->opcrExportService->generateExportPreview($validated);

            return response()->json([
                'success' => true,
                'preview' => $preview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export OPCR workflow to PDF
     */
    public function exportPDF(OPCRWorkflow $workflow, Request $request)
    {
        try {
            // Validate export options
            $options = $request->validate([
                'include_sections' => 'nullable|array',
                'include_sections.*' => 'in:mfo,targets,accomplishments,evaluations,signatures',
                'include_ratings' => 'boolean',
                'include_comments' => 'boolean',
                'include_analytics' => 'boolean',
                'watermark' => 'nullable|string|max:255',
                'page_orientation' => 'nullable|in:portrait,landscape',
                'paper_size' => 'nullable|in:a4,letter,legal'
            ]);

            // Generate filename for download
            $filename = 'OPCR_' . $workflow->office->code . '_' . $workflow->period->name . '_' . now()->format('Y_m_d_H_i_s') . '.pdf';

            // Stream PDF directly using the service
            $response = $this->opcrExportService->streamPDF($workflow, $options);

            // Update Content-Disposition to attachment for proper download
            return $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors specifically
            \Log::error('OPCR PDF export database error', [
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database error occurred while generating PDF. Please check the data structure.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal database error'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to export OPCR to PDF due to a data error. Please contact the system administrator.');

        } catch (\Spatie\Permission\Exceptions\UnauthorizedException $e) {
            // Handle permission errors specifically
            \Log::warning('OPCR PDF export unauthorized attempt', [
                'workflow_id' => $workflow->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to export this OPCR to PDF.'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'You do not have permission to export this OPCR to PDF.');

        } catch (\Exception $e) {
            // Handle general errors
            \Log::error('OPCR PDF export failed', [
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to export OPCR to PDF: ' . $e->getMessage(),
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to export OPCR to PDF: ' . $e->getMessage());
        }
    }
}