<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\CSCReportingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CSCReportManagementController extends Controller
{
    private CSCReportingService $reportingService;

    public function __construct(CSCReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Display a listing of generated reports.
     */
    public function index(Request $request)
    {
        $query = Report::with(['generatedBy', 'reviewedBy', 'approvedBy', 'submittedBy'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        if ($request->filled('year')) {
            $query->where('report_year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('report_month', $request->month);
        }

        if ($request->filled('department')) {
            $query->forDepartment($request->department);
        }

        if ($request->filled('show_versions') && $request->show_versions === 'current_only') {
            $query->currentVersions();
        }

        $reports = $query->paginate(15);

        // Get filter options
        $reportTypes = [
            Report::TYPE_ACCESSION => 'Monthly Accession Report',
            Report::TYPE_SEPARATION => 'Monthly Separation Report',
            Report::TYPE_DIBAR => 'Monthly DIBAR Report',
            Report::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            Report::TYPE_IGHR => 'Annual IGHR Report',
        ];

        $statusOptions = [
            Report::STATUS_GENERATING => 'Generating',
            Report::STATUS_GENERATED => 'Generated',
            Report::STATUS_REVIEWED => 'Reviewed',
            Report::STATUS_APPROVED => 'Approved',
            Report::STATUS_SUBMITTED => 'Submitted',
            Report::STATUS_ACKNOWLEDGED => 'Acknowledged',
            Report::STATUS_FAILED => 'Failed',
            Report::STATUS_CANCELLED => 'Cancelled',
        ];

        $departments = Report::distinct()
            ->whereNotNull('department')
            ->pluck('department')
            ->sort()
            ->values();

        $years = Report::distinct()
            ->orderBy('report_year', 'desc')
            ->pluck('report_year');

        return view('reports.csc.index', compact(
            'reports',
            'reportTypes',
            'statusOptions',
            'departments',
            'years'
        ));
    }

    /**
     * Show the form for creating a new report.
     */
    public function create()
    {
        $reportTypes = [
            Report::TYPE_ACCESSION => 'Monthly Accession Report',
            Report::TYPE_SEPARATION => 'Monthly Separation Report',
            Report::TYPE_DIBAR => 'Monthly DIBAR Report',
            Report::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            Report::TYPE_IGHR => 'Annual IGHR Report',
        ];

        $departments = collect([
            'Human Resources',
            'Finance',
            'Information Technology',
            'Administration',
            'Public Affairs',
            'Legal Affairs',
            'Engineering',
            'Health Services',
        ])->sort()->values();

        return view('reports.csc.create', compact('reportTypes', 'departments'));
    }

    /**
     * Store a newly created report generation request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:' . implode(',', [
                Report::TYPE_ACCESSION,
                Report::TYPE_SEPARATION,
                Report::TYPE_DIBAR,
                Report::TYPE_HARASSMENT,
                Report::TYPE_IGHR,
            ]),
            'report_year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'report_month' => 'nullable|integer|min:1|max:12',
            'department' => 'nullable|string|max:255',
            'file_format' => 'required|in:pdf,excel,both',
            'description' => 'nullable|string|max:1000',
        ]);

        // Validate month requirement for monthly reports
        if (in_array($request->report_type, [
            Report::TYPE_ACCESSION,
            Report::TYPE_SEPARATION,
            Report::TYPE_DIBAR,
            Report::TYPE_HARASSMENT,
        ]) && !$request->filled('report_month')) {
            return back()->withErrors(['report_month' => 'Month is required for monthly reports.']);
        }

        // Validate no month for annual reports
        if ($request->report_type === Report::TYPE_IGHR && $request->filled('report_month')) {
            return back()->withErrors(['report_month' => 'Month should not be specified for annual reports.']);
        }

        // Check if report already exists
        $existingReport = Report::currentVersions()
            ->where('report_type', $request->report_type)
            ->where('report_year', $request->report_year)
            ->where('report_month', $request->report_month)
            ->where('department', $request->department)
            ->first();

        if ($existingReport) {
            return back()->withErrors([
                'report_type' => 'A current version of this report already exists. Please create a new version or edit the existing one.'
            ]);
        }

        try {
            // Create report record
            $report = Report::create([
                'report_type' => $request->report_type,
                'title' => $this->generateReportTitle($request),
                'description' => $request->description,
                'report_year' => $request->report_year,
                'report_month' => $request->report_month,
                'department' => $request->department,
                'file_format' => $request->file_format,
                'status' => Report::STATUS_GENERATING,
                'generated_by' => Auth::id(),
                'generation_started_at' => now(),
                'is_current_version' => true,
            ]);

            // Queue report generation (in a real implementation, this would be dispatched to a job)
            $this->generateReportAsync($report);

            return redirect()->route('csc-reports.show', $report)
                ->with('success', 'Report generation has been started. You will be notified when it\'s complete.');

        } catch (\Exception $e) {
            Log::error('Failed to create CSC report', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors(['general' => 'Failed to start report generation. Please try again.']);
        }
    }

    /**
     * Display the specified report.
     */
    public function show(Report $report)
    {
        $report->load(['generatedBy', 'reviewedBy', 'approvedBy', 'submittedBy', 'parentReport', 'childReports']);

        return view('reports.csc.show', compact('report'));
    }

    /**
     * Show the form for editing the specified report.
     */
    public function edit(Report $report)
    {
        if (!$report->isEditable()) {
            abort(403, 'This report cannot be edited in its current status.');
        }

        return view('reports.csc.edit', compact('report'));
    }

    /**
     * Update the specified report.
     */
    public function update(Request $request, Report $report)
    {
        if (!$report->isEditable()) {
            abort(403, 'This report cannot be edited in its current status.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'submission_notes' => 'nullable|string|max:1000',
        ]);

        $report->update($request->only(['title', 'description', 'submission_notes']));

        return redirect()->route('csc-reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * Download the specified report file.
     */
    public function download(Report $report, string $format)
    {
        if (!in_array($format, ['pdf', 'excel'])) {
            abort(404);
        }

        $filePath = $format === 'pdf' ? $report->pdf_file_path : $report->excel_file_path;

        if (!$filePath || !Storage::exists($filePath)) {
            abort(404, 'Report file not found.');
        }

        $filename = basename($filePath);
        
        return Storage::download($filePath, $filename);
    }

    /**
     * Update report status (for workflow management).
     */
    public function updateStatus(Request $request, Report $report)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', [
                Report::STATUS_REVIEWED,
                Report::STATUS_APPROVED,
                Report::STATUS_SUBMITTED,
                Report::STATUS_ACKNOWLEDGED,
                Report::STATUS_CANCELLED,
            ]),
            'notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $report->status;
        $newStatus = $request->status;

        // Validate status transition
        if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
            return back()->withErrors(['status' => 'Invalid status transition.']);
        }

        $updateData = ['status' => $newStatus];

        // Set appropriate user fields based on status
        switch ($newStatus) {
            case Report::STATUS_REVIEWED:
                $updateData['reviewed_by'] = Auth::id();
                break;
            case Report::STATUS_APPROVED:
                $updateData['approved_by'] = Auth::id();
                break;
            case Report::STATUS_SUBMITTED:
                $updateData['submitted_by'] = Auth::id();
                $updateData['submitted_at'] = now();
                break;
            case Report::STATUS_ACKNOWLEDGED:
                $updateData['acknowledged_at'] = now();
                break;
        }

        if ($request->filled('notes')) {
            $updateData['submission_notes'] = $request->notes;
        }

        $report->update($updateData);

        Log::info('CSC Report status updated', [
            'report_id' => $report->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('csc-reports.show', $report)
            ->with('success', "Report status updated to {$newStatus}.");
    }

    /**
     * Create a new version of the report.
     */
    public function createVersion(Report $report)
    {
        try {
            // Mark current report as not current
            $report->update(['is_current_version' => false]);

            // Create new version
            $newVersion = $report->replicate();
            $newVersion->version = $report->version + 1;
            $newVersion->parent_report_id = $report->id;
            $newVersion->is_current_version = true;
            $newVersion->status = Report::STATUS_GENERATING;
            $newVersion->generated_by = Auth::id();
            $newVersion->generation_started_at = now();
            
            // Reset workflow fields
            $newVersion->reviewed_by = null;
            $newVersion->approved_by = null;
            $newVersion->submitted_by = null;
            $newVersion->submitted_at = null;
            $newVersion->acknowledged_at = null;
            
            // Reset file fields
            $newVersion->pdf_file_path = null;
            $newVersion->excel_file_path = null;
            $newVersion->file_size = null;
            $newVersion->file_hash = null;

            $newVersion->save();

            // Generate new report
            $this->generateReportAsync($newVersion);

            return redirect()->route('csc-reports.show', $newVersion)
                ->with('success', 'New version created and generation started.');

        } catch (\Exception $e) {
            Log::error('Failed to create report version', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors(['general' => 'Failed to create new version. Please try again.']);
        }
    }

    /**
     * Remove the specified report.
     */
    public function destroy(Report $report)
    {
        if (!$report->isEditable()) {
            abort(403, 'This report cannot be deleted in its current status.');
        }

        try {
            // Delete associated files
            if ($report->pdf_file_path && Storage::exists($report->pdf_file_path)) {
                Storage::delete($report->pdf_file_path);
            }
            
            if ($report->excel_file_path && Storage::exists($report->excel_file_path)) {
                Storage::delete($report->excel_file_path);
            }

            $report->delete();

            Log::info('CSC Report deleted', [
                'report_id' => $report->id,
                'deleted_by' => Auth::id(),
            ]);

            return redirect()->route('csc-reports.index')
                ->with('success', 'Report deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete CSC report', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors(['general' => 'Failed to delete report. Please try again.']);
        }
    }

    // ==========================================================================
    // PRIVATE HELPER METHODS
    // ==========================================================================

    /**
     * Generate report title based on request parameters.
     */
    private function generateReportTitle(Request $request): string
    {
        $baseTitle = match ($request->report_type) {
            Report::TYPE_ACCESSION => 'Monthly Accession Report',
            Report::TYPE_SEPARATION => 'Monthly Separation Report',
            Report::TYPE_DIBAR => 'Monthly DIBAR Report',
            Report::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            Report::TYPE_IGHR => 'Annual IGHR Report',
            default => 'CSC Report',
        };

        $period = $request->report_month 
            ? date('F Y', mktime(0, 0, 0, $request->report_month, 1, $request->report_year))
            : $request->report_year;

        $department = $request->department ? " - {$request->department}" : " - All Departments";

        return "{$baseTitle} - {$period}{$department}";
    }

    /**
     * Check if status transition is valid.
     */
    private function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            Report::STATUS_GENERATING => [Report::STATUS_GENERATED, Report::STATUS_FAILED, Report::STATUS_CANCELLED],
            Report::STATUS_GENERATED => [Report::STATUS_REVIEWED, Report::STATUS_APPROVED, Report::STATUS_CANCELLED],
            Report::STATUS_REVIEWED => [Report::STATUS_GENERATED, Report::STATUS_APPROVED, Report::STATUS_CANCELLED],
            Report::STATUS_APPROVED => [Report::STATUS_REVIEWED, Report::STATUS_SUBMITTED, Report::STATUS_CANCELLED],
            Report::STATUS_SUBMITTED => [Report::STATUS_ACKNOWLEDGED, Report::STATUS_CANCELLED],
            Report::STATUS_FAILED => [Report::STATUS_GENERATING, Report::STATUS_CANCELLED],
        ];

        return isset($validTransitions[$from]) && in_array($to, $validTransitions[$from]);
    }

    /**
     * Generate report asynchronously (placeholder for job dispatch).
     */
    private function generateReportAsync(Report $report): void
    {
        // In a real implementation, this would dispatch a job:
        // GenerateCSCReportJob::dispatch($report);
        
        // For now, we'll just update the status to indicate generation would be complete
        // This is a simplified placeholder
        $report->update([
            'status' => Report::STATUS_GENERATED,
            'generation_completed_at' => now(),
            'generation_duration_seconds' => rand(30, 300),
            'total_records' => rand(50, 500),
            'summary_statistics' => [
                'generated_at' => now()->toISOString(),
                'total_records' => rand(50, 500),
                'departments_included' => $report->department ? 1 : rand(3, 8),
            ],
        ]);
    }
}