<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\MajorFinalOutput;
use App\Models\SuccessIndicator;
use App\Models\Office;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OPCRExportService
{
    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Export OPCR workflow to PDF
     */
    public function exportToPDF(OPCRWorkflow $workflow, array $options = []): string
    {
        $workflowData = $this->prepareWorkflowData($workflow, $options);

        $filename = 'OPCR_' . $workflow->office->code . '_' . $workflow->period->name . '_' . now()->format('Y_m_d_H_i_s') . '.pdf';
        $path = 'exports/opcr/' . $filename;

        // Generate PDF
        $pdf = PDF::loadView('exports.opcr.pdf', $workflowData)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        // Store PDF
        Storage::disk('public')->put($path, $pdf->output());

        // Log export activity
        $this->auditTrailService->logFileActivity(
            'exported',
            'pdf',
            $workflow,
            [
                'filename' => $filename,
                'file_path' => $path,
                'file_size' => Storage::disk('public')->size($path),
                'export_options' => $options,
            ]
        );

        return $path;
    }

    /**
     * Stream OPCR workflow PDF directly to response
     */
    public function streamPDF(OPCRWorkflow $workflow, array $options = []): \Illuminate\Http\Response
    {
        $workflowData = $this->prepareWorkflowData($workflow, $options);

        $filename = 'OPCR_' . $workflow->office->code . '_' . $workflow->period->name . '_' . now()->format('Y_m_d_H_i_s') . '.pdf';

        // Generate PDF
        $pdf = PDF::loadView('exports.opcr.pdf', $workflowData)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        // Get PDF content
        $pdfContent = $pdf->output();

        // Log export activity
        $this->auditTrailService->logFileActivity(
            'exported',
            'pdf',
            $workflow,
            [
                'filename' => $filename,
                'file_size' => strlen($pdfContent),
                'export_options' => $options,
                'streamed' => true,
            ]
        );

        // Return stream response
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent))
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }

    /**
     * Export OPCR workflow to Excel
     */
    public function exportToExcel(OPCRWorkflow $workflow, array $options = []): string
    {
        $workflowData = $this->prepareWorkflowData($workflow, $options);

        $filename = 'OPCR_' . $workflow->office->code . '_' . $workflow->period->name . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $path = 'exports/opcr/' . $filename;

        // Generate Excel
        Excel::store(
            new \App\Exports\OPCRExport($workflowData),
            $path,
            'public',
            \Maatwebsite\Excel\Excel::XLSX
        );

        // Log export activity
        $this->auditTrailService->logFileActivity(
            'exported',
            'excel',
            $workflow,
            [
                'filename' => $filename,
                'file_path' => $path,
                'file_size' => Storage::disk('public')->size($path),
                'export_options' => $options,
            ]
        );

        return $path;
    }

    /**
     * Export multiple OPCR workflows to Excel
     */
    public function exportMultipleToExcel(array $workflowIds, array $options = []): string
    {
        $workflows = OPCRWorkflow::whereIn('id', $workflowIds)
            ->with(['office', 'period', 'committedBy', 'assessedBy', 'approvedBy'])
            ->get();

        $workflowsData = $workflows->map(function ($workflow) use ($options) {
            return $this->prepareWorkflowData($workflow, $options);
        })->toArray();

        $filename = 'OPCR_Bulk_Export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $path = 'exports/opcr/' . $filename;

        // Generate Excel
        Excel::store(
            new \App\Exports\OPCRBulkExport($workflowsData),
            $path,
            'public',
            \Maatwebsite\Excel\Excel::XLSX
        );

        // Log bulk export activity
        $this->auditTrailService->logOPCRActivity(
            'bulk_exported',
            null, // No specific workflow model
            [
                'workflow_ids' => $workflowIds,
                'filename' => $filename,
                'file_path' => $path,
                'file_size' => Storage::disk('public')->size($path),
                'export_options' => $options,
                'workflows_count' => count($workflowIds),
            ]
        );

        return $path;
    }

    /**
     * Prepare workflow data for export
     */
    private function prepareWorkflowData(OPCRWorkflow $workflow, array $options): array
    {
        // Get MFOs and success indicators
        $mfos = MajorFinalOutput::with(['activeSuccessIndicators'])
            ->where('office_id', $workflow->office_id)
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('code')
            ->get();

        $workflow->load(['office', 'period', 'committedBy', 'assessedBy', 'approvedBy']);

        $workflowData = [
            'title' => $workflow->title,
            'workflow' => [
                'office' => $workflow->office,
                'period' => $workflow->period,
                'committedBy' => $workflow->committedBy,
                'assessedBy' => $workflow->assessedBy,
                'approvedBy' => $workflow->approvedBy,
                'workflow_state' => $workflow->workflow_state,
                'overall_rating' => $workflow->overall_rating,
            ],
            'mfos' => $mfos->map(function ($mfo) {
                return [
                    'mfo' => $mfo,
                    'success_indicators' => $mfo->activeSuccessIndicators->map(function ($si) {
                        return [
                            'si' => $si,
                            'qet_details' => $si->qet_details,
                            'performance_percentage' => $si->performance_percentage,
                            'is_target_met' => $si->is_target_met,
                        ];
                    }),
                ];
            }),
            'summary' => $this->calculateWorkflowSummary($workflow, $mfos),
            'options' => $options,
            'generated_at' => now(),
            'generated_by' => Auth::user(),
        ];

        return $workflowData;
    }

    /**
     * Calculate workflow summary
     */
    private function calculateWorkflowSummary(OPCRWorkflow $workflow, \Illuminate\Database\Eloquent\Collection $mfos): array
    {
        $allIndicators = $mfos->pluck('activeSuccessIndicators')->flatten();
        $ratedIndicators = $allIndicators->whereNotNull('average_rating');

        return [
            'total_mfos' => $mfos->count(),
            'total_success_indicators' => $allIndicators->count(),
            'rated_success_indicators' => $ratedIndicators->count(),
            'rating_completion_percentage' => $allIndicators->count() > 0
                ? round(($ratedIndicators->count() / $allIndicators->count()) * 100, 2)
                : 0,
            'average_rating' => $ratedIndicators->isNotEmpty()
                ? round($ratedIndicators->avg('average_rating'), 2)
                : null,
            'targets_met_count' => $allIndicators->where('is_target_met', true)->count(),
            'targets_met_percentage' => $allIndicators->count() > 0
                ? round(($allIndicators->where('is_target_met', true)->count() / $allIndicators->count()) * 100, 2)
                : 0,
            'rating_distribution' => $ratedIndicators->groupBy('adjectival_rating')
                ->map(fn($group) => $group->count())
                ->toArray(),
        ];
    }

    /**
     * Archive OPCR workflow
     */
    public function archiveWorkflow(OPCRWorkflow $workflow, array $options = []): string
    {
        // Generate PDF archive
        $pdfPath = $this->exportToPDF($workflow, array_merge($options, ['archive' => true]));

        // Create archive record
        $archiveData = [
            'workflow_id' => $workflow->id,
            'archive_date' => now()->toDateString(),
            'archived_by' => Auth::id(),
            'pdf_path' => $pdfPath,
            'options' => $options,
        ];

        // Update workflow metadata with archive information
        $workflow->update([
            'metadata' => array_merge($workflow->metadata ?? [], [
                'archived' => true,
                'archive_data' => $archiveData,
                'archived_at' => now()->toISOString(),
            ]),
        ]);

        // Log archive activity
        $this->auditTrailService->logOPCRActivity(
            'archived',
            $workflow,
            $archiveData
        );

        return $pdfPath;
    }

    /**
     * Archive multiple workflows
     */
    public function archiveMultipleWorkflows(array $workflowIds, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'errors' => [],
        ];

        foreach ($workflowIds as $workflowId) {
            try {
                $workflow = OPCRWorkflow::findOrFail($workflowId);
                $archivePath = $this->archiveWorkflow($workflow, $options);
                $results['success'][] = [
                    'workflow_id' => $workflowId,
                    'archive_path' => $archivePath,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = $workflowId;
                $results['errors'][$workflowId] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Generate OPCR summary report
     */
    public function generateSummaryReport(array $filters = [], string $format = 'pdf'): string
    {
        $workflows = $this->getFilteredWorkflows($filters);

        $reportData = [
            'filters' => $filters,
            'workflows' => $workflows,
            'summary' => $this->calculateReportSummary($workflows),
            'generated_at' => now(),
            'generated_by' => Auth::user(),
        ];

        $filename = 'OPCR_Summary_Report_' . now()->format('Y_m_d_H_i_s') . '.' . $format;
        $path = 'reports/opcr/' . $filename;

        if ($format === 'pdf') {
            $pdf = PDF::loadView('reports.opcr.summary_pdf', $reportData)
                ->setPaper('a4', 'portrait');

            Storage::disk('public')->put($path, $pdf->output());
        } else {
            Excel::store(
                new \App\Exports\OPCRSummaryExport($reportData),
                $path,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );
        }

        return $path;
    }

    /**
     * Get filtered workflows for reports
     */
    private function getFilteredWorkflows(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = OPCRWorkflow::with(['office', 'period', 'committedBy', 'assessedBy', 'approvedBy']);

        if (!empty($filters['office_id'])) {
            $query->where('office_id', $filters['office_id']);
        }

        if (!empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (!empty($filters['workflow_state'])) {
            $query->where('workflow_state', $filters['workflow_state']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Calculate report summary
     */
    private function calculateReportSummary(\Illuminate\Database\Eloquent\Collection $workflows): array
    {
        $totalWorkflows = $workflows->count();
        $completedWorkflows = $workflows->where('workflow_state', 'final_approval')->count();
        $completionRate = $totalWorkflows > 0 ? ($completedWorkflows / $totalWorkflows) * 100 : 0;

        $averageRating = $workflows->whereNotNull('overall_rating')->avg('overall_rating');

        $workflowsByOffice = $workflows->groupBy('office_id')
            ->map(function ($group) {
                return [
                    'office_name' => $group->first()->office->name,
                    'total' => $group->count(),
                    'completed' => $group->where('workflow_state', 'final_approval')->count(),
                    'average_rating' => $group->whereNotNull('overall_rating')->avg('overall_rating'),
                ];
            });

        return [
            'total_workflows' => $totalWorkflows,
            'completed_workflows' => $completedWorkflows,
            'completion_rate' => round($completionRate, 2),
            'average_rating' => round($averageRating ?? 0, 2),
            'workflows_by_office' => $workflowsByOffice,
            'workflows_by_state' => $workflows->groupBy('workflow_state')
                ->map(fn($group) => $group->count())
                ->toArray(),
        ];
    }

    /**
     * Export performance ratings by office
     */
    public function exportPerformanceByOffice(Office $office, array $filters = []): string
    {
        $workflowQuery = OPCRWorkflow::where('office_id', $office->id)
            ->with(['period', 'committedBy', 'assessedBy', 'approvedBy']);

        if (!empty($filters['period_id'])) {
            $workflowQuery->where('period_id', $filters['period_id']);
        }

        if (!empty($filters['workflow_state'])) {
            $workflowQuery->where('workflow_state', $filters['workflow_state']);
        }

        $workflows = $workflowQuery->orderBy('created_at', 'desc')->get();

        $reportData = [
            'office' => $office,
            'workflows' => $workflows,
            'summary' => $this->calculateReportSummary($workflows),
            'filters' => $filters,
            'generated_at' => now(),
            'generated_by' => Auth::user(),
        ];

        $filename = 'OPCR_Performance_' . $office->code . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $path = 'reports/opcr/' . $filename;

        Excel::store(
            new \App\Exports\OPCRPerformanceExport($reportData),
            $path,
            'public',
            \Maatwebsite\Excel\Excel::XLSX
        );

        return $path;
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysToKeep = 30): array
    {
        $cutoffDate = now()->subDays($daysToKeep);
        $deletedFiles = [];

        $directories = ['exports/opcr', 'reports/opcr'];

        foreach ($directories as $directory) {
            $files = Storage::disk('public')->files($directory);

            foreach ($files as $file) {
                $lastModified = Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file));

                if ($lastModified < $cutoffDate) {
                    Storage::disk('public')->delete($file);
                    $deletedFiles[] = $file;
                }
            }
        }

        Log::info('OPCR export cleanup completed', [
            'deleted_files_count' => count($deletedFiles),
            'cutoff_date' => $cutoffDate->toDateString(),
            'deleted_files' => $deletedFiles,
        ]);

        return $deletedFiles;
    }

    /**
     * Get export statistics
     */
    public function getExportStatistics(): array
    {
        $directories = ['exports/opcr', 'reports/opcr'];
        $statistics = [
            'total_files' => 0,
            'total_size' => 0,
            'files_by_type' => [],
            'files_by_date' => [],
        ];

        foreach ($directories as $directory) {
            $files = Storage::disk('public')->files($directory);

            foreach ($files as $file) {
                $statistics['total_files']++;
                $statistics['total_size'] += Storage::disk('public')->size($file);

                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $statistics['files_by_type'][$extension] = ($statistics['files_by_type'][$extension] ?? 0) + 1;

                $date = Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->toDateString();
                $statistics['files_by_date'][$date] = ($statistics['files_by_date'][$date] ?? 0) + 1;
            }
        }

        $statistics['total_size_mb'] = round($statistics['total_size'] / (1024 * 1024), 2);

        return $statistics;
    }

    /**
     * Download exported file
     */
    public function downloadFile(string $path): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!Storage::disk('public')->exists($path)) {
            throw new \Exception('Export file not found');
        }

        $filename = basename($path);

        return response()->download(
            Storage::disk('public')->path($path),
            $filename
        );
    }

    /**
     * Get recent exports for display
     */
    public function getRecentExports(int $limit = 10): array
    {
        // Since we don't have a dedicated exports table, return empty array for now
        // This could be implemented later with proper export tracking
        return [];
    }

    /**
     * Get supported export formats
     */
    public function getSupportedFormats(): array
    {
        return [
            'pdf' => [
                'name' => 'PDF Document',
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'description' => 'Portable Document Format - best for printing and sharing'
            ],
            'excel' => [
                'name' => 'Excel Spreadsheet',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extension' => 'xlsx',
                'description' => 'Microsoft Excel format - best for data analysis'
            ],
            'csv' => [
                'name' => 'CSV File',
                'mime_type' => 'text/csv',
                'extension' => 'csv',
                'description' => 'Comma Separated Values - best for data import'
            ]
        ];
    }

    /**
     * Get export templates
     */
    public function getExportTemplates(bool $includeInactive = false): array
    {
        // Return basic default templates since we don't have a templates table yet
        return [
            [
                'id' => 'default_opcr',
                'name' => 'Default OPCR Template',
                'description' => 'Standard OPCR export format with all sections',
                'format' => 'pdf',
                'is_default' => true,
                'is_active' => true,
                'sections' => ['mfo', 'targets', 'accomplishments', 'evaluations', 'signatures']
            ],
            [
                'id' => 'summary_opcr',
                'name' => 'OPCR Summary Template',
                'description' => 'Summary view with key metrics and ratings',
                'format' => 'pdf',
                'is_default' => false,
                'is_active' => true,
                'sections' => ['mfo', 'evaluations']
            ]
        ];
    }
}