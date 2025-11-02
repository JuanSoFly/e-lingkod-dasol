<?php

namespace App\Jobs;

use App\Services\OPCRManagementService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessOPCRExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Export data
     */
    private array $filters;
    private int $userId;
    private string $exportType;

    /**
     * Create a new job instance.
     */
    public function __construct(array $filters, int $userId, string $exportType = 'archive')
    {
        $this->filters = $filters;
        $this->userId = $userId;
        $this->exportType = $exportType;

        // Set queue priority based on export type
        $this->onQueue('exports');
    }

    /**
     * Execute the job.
     */
    public function handle(OPCRManagementService $opcrService): void
    {
        try {
            $user = User::find($this->userId);

            if (!$user) {
                Log::error('Export job user not found', ['user_id' => $this->userId]);
                return;
            }

            // Generate unique filename
            $filename = "opcr_{$this->exportType}_export_" . now()->format('Y-m-d_H-i-s') . "_{$user->id}.xlsx";

            // Process the export based on type
            switch ($this->exportType) {
                case 'archive':
                    $workflows = $this->getFilteredWorkflows();
                    $filepath = $opcrService->exportArchiveToExcel($workflows, $filename);
                    break;

                case 'summary':
                    $filepath = $this->generateSummaryExport($opcrService, $filename);
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported export type: {$this->exportType}");
            }

            // Store the file for download
            $storedPath = $this->storeExportFile($filepath, $filename);

            // Notify user of completion
            $this->notifyUserOfCompletion($user, $storedPath, $filename);

            // Clean up temporary file
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            Log::info('OPCR export job completed successfully', [
                'user_id' => $this->userId,
                'export_type' => $this->exportType,
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            Log::error('OPCR export job failed', [
                'user_id' => $this->userId,
                'export_type' => $this->exportType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Get filtered workflows for export
     */
    private function getFilteredWorkflows()
    {
        $query = \App\Models\OPCRWorkflow::with([
            'office:id,name',
            'period:id,name,start_date,end_date',
            'committedBy.employee:id,first_name,last_name',
            'targets.mfo:id,code,description',
            'targets.successIndicator:id,description',
            'targets.ratings'
        ])->where('workflow_state', 'approved');

        // Apply filters from the job
        if (!empty($this->filters['period_id'])) {
            $query->where('period_id', $this->filters['period_id']);
        }

        if (!empty($this->filters['office_id'])) {
            $query->where('office_id', $this->filters['office_id']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Generate summary export
     */
    private function generateSummaryExport(OPCRManagementService $opcrService, string $filename): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Create summary data
        $summaryData = $this->generateSummaryData();

        // Set headers
        $sheet->setCellValue('A1', 'OPCR Performance Summary');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', 'Period');
        $sheet->setCellValue('B3', $summaryData['period_name']);
        $sheet->setCellValue('A4', 'Total Workflows');
        $sheet->setCellValue('B4', $summaryData['total_workflows']);
        $sheet->setCellValue('A5', 'Average Rating');
        $sheet->setCellValue('B5', round($summaryData['average_rating'], 2));
        $sheet->setCellValue('A6', 'Completion Rate');
        $sheet->setCellValue('B6', round($summaryData['completion_rate'], 2) . '%');

        // Office performance data
        $sheet->setCellValue('A8', 'Office Performance');
        $sheet->getStyle('A8')->getFont()->setBold(true);

        $sheet->setCellValue('A9', 'Office Name');
        $sheet->setCellValue('B9', 'Workflows');
        $sheet->setCellValue('C9', 'Avg Rating');
        $sheet->setCellValue('D9', 'Completion Rate');

        $row = 10;
        foreach ($summaryData['office_performance'] as $office) {
            $sheet->setCellValue('A' . $row, $office['name']);
            $sheet->setCellValue('B' . $row, $office['workflow_count']);
            $sheet->setCellValue('C' . $row, round($office['avg_rating'], 2));
            $sheet->setCellValue('D' . $row, round($office['completion_rate'], 2) . '%');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Save the file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filepath = storage_path('app/temp/' . $filename);

        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $writer->save($filepath);
        return $filepath;
    }

    /**
     * Generate summary data
     */
    private function generateSummaryData(): array
    {
        $query = \App\Models\OPCRWorkflow::with('office');

        if (!empty($this->filters['period_id'])) {
            $query->where('period_id', $this->filters['period_id']);
        }

        $workflows = $query->get();

        return [
            'period_name' => \App\Models\PerformancePeriod::find($this->filters['period_id'])->name ?? 'All Periods',
            'total_workflows' => $workflows->count(),
            'average_rating' => $workflows->whereNotNull('overall_rating')->avg('overall_rating') ?? 0,
            'completion_rate' => $workflows->count() > 0
                ? ($workflows->where('workflow_state', 'approved')->count() / $workflows->count()) * 100
                : 0,
            'office_performance' => $workflows->groupBy('office_id')->map(function ($officeWorkflows) {
                $office = $officeWorkflows->first()->office;
                return [
                    'name' => $office->name,
                    'workflow_count' => $officeWorkflows->count(),
                    'avg_rating' => $officeWorkflows->whereNotNull('overall_rating')->avg('overall_rating') ?? 0,
                    'completion_rate' => $officeWorkflows->count() > 0
                        ? ($officeWorkflows->where('workflow_state', 'approved')->count() / $officeWorkflows->count()) * 100
                        : 0,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Store export file for download
     */
    private function storeExportFile(string $filepath, string $filename): string
    {
        $exportPath = "exports/opcr/" . date('Y/m');

        if (!Storage::exists($exportPath)) {
            Storage::makeDirectory($exportPath);
        }

        $storedPath = "{$exportPath}/{$filename}";
        Storage::put($storedPath, file_get_contents($filepath));

        return $storedPath;
    }

    /**
     * Notify user of export completion
     */
    private function notifyUserOfCompletion(User $user, string $storedPath, string $filename): void
    {
        try {
            // Create download link valid for 24 hours
            $downloadUrl = route('opcr.export.download', [
                'path' => encrypt($storedPath),
                'filename' => $filename,
                'expires' => now()->addHours(24)->timestamp,
            ]);

            // Send notification (you can customize this based on your notification system)
            $user->notify(new \App\Notifications\OPCRExportReadyNotification($downloadUrl, $filename));

        } catch (\Exception $e) {
            Log::error('Failed to notify user of export completion', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('OPCR export job failed permanently', [
            'user_id' => $this->userId,
            'export_type' => $this->exportType,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify user of failure
        $user = User::find($this->userId);
        if ($user) {
            try {
                $user->notify(new \App\Notifications\OPCRExportFailedNotification($this->exportType));
            } catch (\Exception $e) {
                Log::error('Failed to notify user of export failure', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}