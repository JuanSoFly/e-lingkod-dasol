<?php

namespace App\Http\Controllers;

use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuditTrailController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;

        // Apply OPCR-specific permissions
        $this->middleware('permission:audit.view')->only(['index', 'show']);
        $this->middleware('permission:audit.export')->only(['export', 'download']);
        $this->middleware('permission:audit.delete')->only(['destroy']);
    }

    /**
     * Display audit trail listing with filtering capabilities
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|max:255',
            'action_type' => 'nullable|string|max:255',
            'subject_type' => 'nullable|string|max:255',
            'office_id' => 'nullable|integer|exists:offices,id',
            'per_page' => 'nullable|integer|min:10|max:100'
        ]);

        $auditLogs = $this->auditTrailService->getFilteredAuditLogs($filters, true);

        // Get statistics for the dashboard
        $statistics = $this->auditTrailService->getDashboardAuditStatistics($filters);

        return view('admin.audit-trail.index', [
            'auditLogs' => $auditLogs,
            'activities' => $auditLogs, // Add this for compatibility with the view
            'statistics' => $statistics,
            'filters' => $filters,
            'offices' => $this->auditTrailService->getOfficeList(),
            'users' => $this->auditTrailService->getUserList(),
            'availableActions' => $this->auditTrailService->getAvailableActions()
        ]);
    }

    /**
     * Display specific audit log details
     */
    public function show(string $id): \Illuminate\View\View
    {
        $auditLog = $this->auditTrailService->getAuditLogDetails($id);

        if (!$auditLog) {
            abort(404, 'Audit log not found');
        }

        return view('admin.audit-trail.show', [
            'activity' => $auditLog,
            'relatedLogs' => $this->auditTrailService->getRelatedAuditLogs($auditLog)
        ]);
    }

    /**
     * Export audit trail data
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,xlsx,csv,excel',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|max:255',
            'subject_type' => 'nullable|string|max:255',
            'office_id' => 'nullable|integer|exists:offices,id',
            'include_old_values' => 'boolean',
            'include_new_values' => 'boolean',
            'action_type' => 'nullable|string|max:255',
        ]);

        try {
            $export = $this->auditTrailService->exportAuditLogs($validated);

            return response()
                ->download($export['path'], $export['download_name'], $export['headers'] ?? [])
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('Audit trail export failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'format' => $validated['format'] ?? null,
                'filters' => $validated,
            ]);
            return back()->withErrors([
                'export' => 'Failed to export audit trail: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download exported audit trail file
     */
    public function download(string $filename)
    {
        $filePath = storage_path('app/exports/audit-trail/' . $filename);

        if (!file_exists($filePath)) {
            abort(404, 'Export file not found');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv; charset=UTF-8',
            default => 'application/octet-stream',
        };

        return response()->download($filePath, $filename, ['Content-Type' => $mime])->deleteFileAfterSend(true);
    }

    /**
     * Get audit statistics for dashboard
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:today,week,month,quarter,year',
            'office_id' => 'nullable|integer|exists:offices,id'
        ]);

        $stats = $this->auditTrailService->getAuditStatistics($validated);

        return response()->json($stats);
    }

    /**
     * Get real-time audit activity
     */
    public function liveActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $activities = $this->auditTrailService->getRecentActivities(
            $validated['limit'] ?? 10
        );

        return response()->json($activities);
    }

    /**
     * Search audit logs with advanced filters
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:255',
            'filters' => 'array',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'filters.user_id' => 'nullable|integer|exists:users,id',
            'filters.action' => 'nullable|string|max:255',
            'filters.subject_type' => 'nullable|string|max:255',
            'filters.office_id' => 'nullable|integer|exists:offices,id',
            'per_page' => 'nullable|integer|min:10|max:100'
        ]);

        $results = $this->auditTrailService->searchAuditLogs($validated);

        return response()->json($results);
    }

    /**
     * Bulk operations on audit logs (archive/delete old logs)
     */
    public function bulkOperations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operation' => 'required|in:archive,delete',
            'criteria' => 'required|array',
            'criteria.older_than' => 'required|date',
            'criteria.actions' => 'nullable|array',
            'criteria.actions.*' => 'string',
            'criteria.subject_types' => 'nullable|array',
            'criteria.subject_types.*' => 'string',
            'criteria.except_critical' => 'boolean'
        ]);

        try {
            $result = $this->auditTrailService->performBulkOperation($validated);

            return response()->json([
                'success' => true,
                'message' => "Bulk {$validated['operation']} completed successfully",
                'affected_records' => $result['affected_records'],
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
     * Restore archived audit logs
     */
    public function restore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_ids' => 'required|array',
            'log_ids.*' => 'string|exists:activity_log,id'
        ]);

        try {
            $result = $this->auditTrailService->restoreAuditLogs($validated['log_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Audit logs restored successfully',
                'restored_count' => $result['restored_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore audit logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete audit logs (with confirmation)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->auditTrailService->deleteAuditLog($id);

            if ($result['deleted']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Audit log deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to delete audit log'
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete audit log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get compliance report for audit trail
     */
    public function complianceReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'office_id' => 'nullable|integer|exists:offices,id',
            'include_charts' => 'boolean'
        ]);

        try {
            $report = $this->auditTrailService->generateComplianceReport($validated);

            return response()->json([
                'success' => true,
                'report' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate compliance report: ' . $e->getMessage()
            ], 500);
        }
    }
}
