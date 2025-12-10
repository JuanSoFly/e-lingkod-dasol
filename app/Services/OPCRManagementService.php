<?php

namespace App\Services;

use App\Models\Office;
use App\Models\MajorFinalOutput;
use App\Models\SuccessIndicator;
use App\Models\OPCRWorkflow;
use App\Models\PerformanceEvaluation;
use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use App\Models\PerformancePeriod;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\Activity;

class OPCRManagementService
{
    /**
     * Safe activity logging method with error handling
     */
    private function logActivity(string $description, array $properties = []): void
    {
        // Temporarily disable activity logging due to null activity object issue
        // TODO: Fix the underlying activity logging configuration issue
        \Log::info('OPCR Activity (logging disabled)', [
            'description' => $description,
            'properties' => $properties,
            'user_id' => Auth::id(),
        ]);

        /*
        try {
            Activity::log($description, $properties);
        } catch (\Exception $e) {
            // Log the error silently to prevent breaking the main functionality
            \Log::warning('Activity logging failed', [
                'description' => $description,
                'properties' => $properties,
                'error' => $e->getMessage(),
            ]);
        }
        */
    }

    /**
     * Create a new OPCR workflow for an office
     */
    public function createOPCRWorkflow(array $data): OPCRWorkflow
    {
        $workflow = DB::transaction(function () use ($data) {
            return OPCRWorkflow::create([
                'title' => $data['title'],
                'office_id' => $data['office_id'],
                'period_id' => $data['period_id'],
                'workflow_state' => 'draft',
                'summary' => $data['summary'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
            ]);
        });

        // Log the creation outside transaction
        $this->logActivity('OPCR workflow created', [
            'opcr_workflow_id' => $workflow->id,
            'office_id' => $workflow->office_id,
            'period_id' => $workflow->period_id,
            'created_by' => Auth::id(),
        ]);

        return $workflow;
    }

    /**
     * Create workflow record with normalized payload for controllers
     */
    public function createWorkflow(array $data): OPCRWorkflow
    {
        $workflow = DB::transaction(function () use ($data) {
            $payload = [
                'title' => $data['title'],
                'office_id' => $data['office_id'],
                'period_id' => $data['period_id'],
                'summary' => $data['description'] ?? $data['summary'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
                'committed_by' => $data['committed_by'] ?? Auth::id(),
            ];

            return OPCRWorkflow::createWorkflow($payload);
        });

        $this->logActivity('OPCR workflow created via service helper', [
            'opcr_workflow_id' => $workflow->id,
            'office_id' => $workflow->office_id,
            'period_id' => $workflow->period_id,
            'created_by' => Auth::id(),
        ]);

        return $workflow;
    }

    /**
     * Create workflow targets from request payload
     */
    public function createWorkflowTargets(OPCRWorkflow $workflow, array $targets): void
    {
        DB::transaction(function () use ($workflow, $targets) {
            $this->syncWorkflowTargets($workflow, $targets, false);
        });

        // Log activity outside transaction
        $this->logActivity('OPCR workflow targets created', [
            'opcr_workflow_id' => $workflow->id,
            'targets_count' => count($targets),
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update existing workflow targets, creating or removing as needed
     */
    public function updateWorkflowTargets(OPCRWorkflow $workflow, array $targets): void
    {
        DB::transaction(function () use ($workflow, $targets) {
            $this->syncWorkflowTargets($workflow, $targets, false);
        });

        // Log activity outside transaction
        $this->logActivity('OPCR workflow targets updated', [
            'opcr_workflow_id' => $workflow->id,
            'targets_count' => count($targets),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Add MFOs and Success Indicators to OPCR workflow
     */
    public function addMFOsToWorkflow(OPCRWorkflow $workflow, array $mfoData): array
    {
        return DB::transaction(function () use ($workflow, $mfoData) {
            $addedMFOs = [];

            foreach ($mfoData as $mfoItem) {
                // Create or find MFO
                $mfo = MajorFinalOutput::firstOrCreate(
                    ['code' => $mfoItem['code']],
                    [
                        'title' => $mfoItem['title'],
                        'description' => $mfoItem['description'] ?? null,
                        'office_id' => $workflow->office_id,
                        'level' => $mfoItem['level'] ?? 1,
                        'parent_id' => $mfoItem['parent_id'] ?? null,
                        'is_active' => true,
                    ]
                );

                // Create success indicators
                $successIndicators = [];
                foreach ($mfoItem['success_indicators'] as $siData) {
                    $successIndicator = SuccessIndicator::create([
                        'mfo_id' => $mfo->id,
                        'code' => $siData['code'],
                        'title' => $siData['title'],
                        'description' => $siData['description'] ?? null,
                        'target_quality' => $siData['target_quality'] ?? null,
                        'target_efficiency' => $siData['target_efficiency'] ?? null,
                        'target_timeliness' => $siData['target_timeliness'] ?? null,
                        'created_by' => Auth::id(),
                    ]);

                    $successIndicators[] = $successIndicator;
                }

                $addedMFOs[] = [
                    'mfo' => $mfo,
                    'success_indicators' => $successIndicators,
                ];
            }

            return $addedMFOs;
        });
    }

    /**
     * Get OPCR workflow with related data
     */
    public function getOPCRWorkflowWithDetails(OPCRWorkflow $workflow): array
    {
        $workflow->load([
            'office',
            'period',
            'committedBy',
            'assessedBy',
            'approvedBy'
        ]);

        // Get MFOs and their success indicators
        $mfos = MajorFinalOutput::with(['successIndicators' => function ($query) {
            $query->where('is_active', true);
        }])
        ->where('office_id', $workflow->office_id)
        ->where('is_active', true)
        ->orderBy('level')
        ->orderBy('code')
        ->get();

        // Get related performance evaluation if exists
        $evaluation = PerformanceEvaluation::where('opcr_workflow_id', $workflow->id)
            ->with(['employee', 'ratings.target'])
            ->first();

        return [
            'workflow' => $workflow,
            'mfos' => $mfos,
            'evaluation' => $evaluation,
            'can_edit' => $this->canEditWorkflow($workflow),
            'can_submit' => $this->canSubmitWorkflow($workflow),
            'can_assess' => $this->canAssessWorkflow($workflow),
            'can_approve' => $this->canApproveWorkflow($workflow),
        ];
    }

    /**
     * Synchronize workflow targets with provided payload
     */
    private function syncWorkflowTargets(OPCRWorkflow $workflow, array $targets, bool $logActivity = true): void
    {
        $committedUser = $workflow->committedBy ?: User::find($workflow->committed_by);
        $employeeId = optional($committedUser?->employee)->id;

        if (!$employeeId) {
            throw new \RuntimeException('Unable to determine employee record for the workflow.');
        }

        $targetCollection = collect($targets)->filter(function ($target) {
            return !empty($target['success_indicator_id']);
        });

        if ($targetCollection->isEmpty()) {
            PerformanceTarget::where('opcr_workflow_id', $workflow->id)->delete();

            if ($logActivity) {
                $this->logActivity('OPCR workflow targets cleared', [
                    'opcr_workflow_id' => $workflow->id,
                    'cleared_by' => Auth::id(),
                ]);
            }

            return;
        }

        $indicatorIds = $targetCollection->pluck('success_indicator_id')->unique();
        $indicators = SuccessIndicator::with('mfo')
            ->whereIn('id', $indicatorIds)
            ->get()
            ->keyBy('id');

        $syncedIds = [];

        foreach ($targetCollection as $targetData) {
            $indicator = $indicators->get($targetData['success_indicator_id']);
            $payload = $this->buildTargetPayload($workflow, $employeeId, $targetData, $indicator);

            if (!empty($targetData['id'])) {
                unset($payload['created_by']);
                $existing = PerformanceTarget::where('opcr_workflow_id', $workflow->id)
                    ->where('id', $targetData['id'])
                    ->first();

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();
                    $syncedIds[] = $existing->id;
                    continue;
                }
            }

            $target = PerformanceTarget::updateOrCreate(
                [
                    'opcr_workflow_id' => $workflow->id,
                    'success_indicator_id' => $payload['success_indicator_id'],
                    'employee_id' => $employeeId,
                    'period_id' => $workflow->period_id,
                ],
                $payload
            );

            $syncedIds[] = $target->id;
        }

        PerformanceTarget::where('opcr_workflow_id', $workflow->id)
            ->whereNotIn('id', $syncedIds)
            ->delete();

        if ($logActivity) {
            $this->logActivity('OPCR workflow targets synchronized', [
                'opcr_workflow_id' => $workflow->id,
                'synced_count' => count($syncedIds),
                'updated_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Build normalized payload for performance targets
     */
    private function buildTargetPayload(
        OPCRWorkflow $workflow,
        int $employeeId,
        array $targetData,
        ?SuccessIndicator $indicator
    ): array {
        $mfo = $indicator?->mfo;

        return [
            'employee_id' => $employeeId,
            'period_id' => $workflow->period_id,
            'office_id' => $workflow->office_id,
            'opcr_workflow_id' => $workflow->id,
            'mfo_id' => $targetData['mfo_id'] ?? $indicator?->mfo_id,
            'success_indicator_id' => $targetData['success_indicator_id'] ?? $indicator?->id,
            'mfo_code' => $mfo?->code,
            'si_code' => $indicator?->code,
            'objective' => $targetData['objective'] ?? ($mfo?->title ?? $workflow->title),
            'target' => $targetData['target'] ?? ($indicator?->description ?? $indicator?->title ?? ''),
            'weight' => $targetData['weight'] ?? 1,
            'success_indicator' => $targetData['success_indicator'] ?? ($indicator?->title ?? $indicator?->description ?? ''),
            'target_quality' => $targetData['target_quality'] ?? $indicator?->target_quality,
            'target_efficiency' => $targetData['target_efficiency'] ?? $indicator?->target_efficiency,
            'target_timeliness' => $targetData['target_timeliness'] ?? $indicator?->target_timeliness,
            'is_legacy_ipcr' => false,
            'created_by' => $targetData['created_by'] ?? Auth::id(),
            'updated_by' => Auth::id(),
        ];
    }

    /**
     * Update workflow state with validation
     */
    public function updateWorkflowState(OPCRWorkflow $workflow, string $newState, array $data = []): bool
    {
        $currentState = $workflow->workflow_state;

        $result = DB::transaction(function () use ($workflow, $newState, $data) {
            // Validate state transition
            if (!$this->isValidStateTransition($workflow->workflow_state, $newState)) {
                throw new \InvalidArgumentException("Invalid state transition from {$workflow->workflow_state} to {$newState}");
            }

            // Update workflow based on state
            switch ($newState) {
                case 'committed':
                    $workflow->update([
                        'workflow_state' => $newState,
                        'committed_by' => Auth::id(),
                        'committed_at' => now(),
                    ]);
                    break;

                case 'in_progress':
                    $workflow->update([
                        'workflow_state' => $newState,
                        'submitted_by' => Auth::id(),
                        'submitted_at' => now(),
                    ]);
                    break;

                case 'evaluation':
                    $workflow->update([
                        'workflow_state' => $newState,
                        'assessed_by' => Auth::id(),
                        'assessed_at' => now(),
                        'assessor_remarks' => $data['assessor_remarks'] ?? null,
                    ]);
                    break;

                case 'final_approval':
                    $workflow->update(array_merge([
                        'workflow_state' => $newState,
                    ], $data));
                    break;

                case 'returned':
                    $workflow->update([
                        'workflow_state' => $newState,
                        'returned_by' => Auth::id(),
                        'returned_at' => now(),
                        'return_reason' => $data['return_reason'] ?? null,
                    ]);
                    break;

                default:
                    $workflow->update(['workflow_state' => $newState]);
                    break;
            }

            return true;
        });

        // Log state change outside transaction
        $this->logActivity('OPCR workflow state changed', [
            'opcr_workflow_id' => $workflow->id,
            'from_state' => $currentState,
            'to_state' => $newState,
            'changed_by' => Auth::id(),
        ]);

        return $result;
    }

    /**
     * Check if user can edit workflow
     */
    public function canEditWorkflow(OPCRWorkflow $workflow): bool
    {
        $user = Auth::user();

        // Only draft state can be edited
        if ($workflow->workflow_state !== 'draft') {
            return false;
        }

        // Check if user is department head of the office
        return $user->officeAssignments()
            ->where('office_id', $workflow->office_id)
            ->where('role', 'Department Head')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user can submit workflow
     */
    public function canSubmitWorkflow(OPCRWorkflow $workflow): bool
    {
        $user = Auth::user();

        // Only draft or returned workflows can be submitted
        if (!in_array($workflow->workflow_state, ['draft', 'returned'])) {
            return false;
        }

        // Check if user is department head of the office
        return $user->officeAssignments()
            ->where('office_id', $workflow->office_id)
            ->where('role', 'Department Head')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user can assess workflow
     */
    public function canAssessWorkflow(OPCRWorkflow $workflow): bool
    {
        $user = Auth::user();

        // Only in_progress workflows can be assessed
        if ($workflow->workflow_state !== 'in_progress') {
            return false;
        }

        // Check if user has assessor role
        return $user->officeAssignments()
            ->where('role', 'Assessor')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user can approve workflow
     */
    public function canApproveWorkflow(OPCRWorkflow $workflow): bool
    {
        $user = Auth::user();

        // Only evaluation workflows can be approved
        if ($workflow->workflow_state !== 'evaluation') {
            return false;
        }

        // Check if user has final approver role
        return $user->officeAssignments()
            ->where('role', 'Final Approver')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Validate state transitions
     */
    private function isValidStateTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'draft' => ['committed', 'returned'],
            'committed' => ['in_progress', 'returned'],
            'in_progress' => ['evaluation', 'returned'],
            'evaluation' => ['final_approval', 'returned'],
            'final_approval' => [], // Terminal state
            'returned' => ['committed'], // Can be resubmitted
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Get OPCR workflows for user based on their roles
     */
    public function getUserOPCRWorkflows(): array
    {
        $user = Auth::user();
        $workflows = [];

        // Get workflows where user is department head
        $departmentHeadWorkflows = OPCRWorkflow::whereHas('office', function ($query) use ($user) {
            $query->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('role', 'Department Head')
                    ->where('is_active', true);
            });
        })->get();

        // Get workflows where user is assessor
        $assessorWorkflows = OPCRWorkflow::whereHas('office', function ($query) use ($user) {
            $query->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('role', 'Assessor')
                    ->where('is_active', true);
            });
        })->get();

        // Get workflows where user is final approver
        $approverWorkflows = OPCRWorkflow::whereHas('office', function ($query) use ($user) {
            $query->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('role', 'Final Approver')
                    ->where('is_active', true);
            });
        })->get();

        return [
            'department_head' => $departmentHeadWorkflows,
            'assessor' => $assessorWorkflows,
            'final_approver' => $approverWorkflows,
        ];
    }

    /**
     * Export archived OPCR workflows to Excel
     */
    public function exportArchiveToExcel($workflows, string $filename): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set up headers
        $headers = [
            'A1' => 'OPCR Title',
            'B1' => 'Office',
            'C1' => 'Performance Period',
            'D1' => 'Department Head',
            'E1' => 'MFO Code',
            'F1' => 'MFO Description',
            'G1' => 'Success Indicator',
            'H1' => 'Target Quality',
            'I1' => 'Accomplished Quality',
            'J1' => 'Target Efficiency',
            'K1' => 'Accomplished Efficiency',
            'L1' => 'Target Timeliness',
            'M1' => 'Accomplished Timeliness',
            'N1' => 'QET Rating',
            'O1' => 'Adjectival Rating',
            'P1' => 'Final Status',
            'Q1' => 'Date Approved',
            'R1' => 'Created At',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $sheet->getStyle('A1:R1')->getFont()->setBold(true);
        $sheet->getStyle('A1:R1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFE6E6FA');

        $row = 2;

        foreach ($workflows as $workflow) {
            $workflow->load(['office', 'period', 'committedBy.employee', 'targets.mfo', 'targets.successIndicator', 'targets.ratings']);

            // Check if workflow has targets
            if ($workflow->targets->count() > 0) {
                // Export workflows with targets (existing logic)
                foreach ($workflow->targets as $target) {
                    $rating = $target->ratings->first();

                    $sheet->setCellValue('A' . $row, $workflow->title);
                    $sheet->setCellValue('B' . $row, $workflow->office->name);
                    $sheet->setCellValue('C' . $row, $workflow->period->name);
                    $sheet->setCellValue('D' . $row, $workflow->committedBy->employee ?
                        $workflow->committedBy->employee->first_name . ' ' . $workflow->committedBy->employee->last_name : 'Unknown');
                    $sheet->setCellValue('E' . $row, $target->mfo->code ?? '');
                    $sheet->setCellValue('F' . $row, $target->mfo->description ?? '');
                    $sheet->setCellValue('G' . $row, $target->successIndicator->description ?? '');
                    $sheet->setCellValue('H' . $row, $target->target_quality ?? '');
                    $sheet->setCellValue('I' . $row, $target->accomplished_quality ?? '');
                    $sheet->setCellValue('J' . $row, $target->target_efficiency ?? '');
                    $sheet->setCellValue('K' . $row, $target->accomplished_efficiency ?? '');
                    $sheet->setCellValue('L' . $row, $target->target_timeliness ?? '');
                    $sheet->setCellValue('M' . $row, $target->accomplished_timeliness ?? '');
                    $sheet->setCellValue('N' . $row, $rating ? $rating->final_rating : '');
                    $sheet->setCellValue('O' . $row, $rating ? $this->getAdjectivalRating($rating->final_rating) : '');
                    $sheet->setCellValue('P' . $row, ucfirst($workflow->workflow_state));
                    $sheet->setCellValue('Q' . $row, $workflow->approved_at ? $workflow->approved_at->format('Y-m-d H:i:s') : '');
                    $sheet->setCellValue('R' . $row, $workflow->created_at->format('Y-m-d H:i:s'));

                    $row++;
                }
            } else {
                // Export workflows without targets (fallback for missing data)
                $sheet->setCellValue('A' . $row, $workflow->title);
                $sheet->setCellValue('B' . $row, $workflow->office->name);
                $sheet->setCellValue('C' . $row, $workflow->period->name);
                $sheet->setCellValue('D' . $row, $workflow->committedBy->employee ?
                    $workflow->committedBy->employee->first_name . ' ' . $workflow->committedBy->employee->last_name : 'Unknown');
                $sheet->setCellValue('E' . $row, 'No Performance Targets Available');
                $sheet->setCellValue('F' . $row, '');
                $sheet->setCellValue('G' . $row, '');
                $sheet->setCellValue('H' . $row, '');
                $sheet->setCellValue('I' . $row, '');
                $sheet->setCellValue('J' . $row, '');
                $sheet->setCellValue('K' . $row, '');
                $sheet->setCellValue('L' . $row, '');
                $sheet->setCellValue('M' . $row, '');
                $sheet->setCellValue('N' . $row, '');
                $sheet->setCellValue('O' . $row, '');
                $sheet->setCellValue('P' . $row, ucfirst($workflow->workflow_state));
                $sheet->setCellValue('Q' . $row, $workflow->approved_at ? $workflow->approved_at->format('Y-m-d H:i:s') : '');
                $sheet->setCellValue('R' . $row, $workflow->created_at->format('Y-m-d H:i:s'));

                $row++;
            }
        }

        // Auto-size columns
        foreach (range('A', 'R') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create the Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filepath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $writer->save($filepath);

        // Log the export
        $this->logActivity('OPCR archive exported to Excel', [
            'filename' => $filename,
            'workflow_count' => $workflows->count(),
            'exported_by' => Auth::id(),
        ]);

        return $filepath;
    }

    /**
     * Get exportable workflows based on filters
     */
    public function getExportableWorkflows(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = OPCRWorkflow::with(['office', 'period', 'committedBy', 'assessedBy', 'approvedBy']);

        // Apply filters
        if (!empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (!empty($filters['office_id'])) {
            $query->where('office_id', $filters['office_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('workflow_state', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Filter by user's accessible offices if not Super Admin
        if (!Auth::user()->hasAnyRole(['Super Admin', 'HR Admin'])) {
            $accessibleOfficeIds = Auth::user()->officeAssignments()->pluck('office_id');
            $query->whereIn('office_id', $accessibleOfficeIds);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get adjectival rating based on numeric score
     */
    private function getAdjectivalRating($score): string
    {
        if ($score >= 4.5) return 'Outstanding';
        if ($score >= 3.5) return 'Very Satisfactory';
        if ($score >= 2.5) return 'Satisfactory';
        if ($score >= 1.5) return 'Fairly Satisfactory';
        if ($score >= 0.5) return 'Poor';
        return 'No Rating';
    }
}
