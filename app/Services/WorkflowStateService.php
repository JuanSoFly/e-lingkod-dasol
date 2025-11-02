<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\OfficeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowStateService
{
    private OPCRManagementService $opcrManagementService;
    private OPCRNotificationService $notificationService;

    public function __construct(
        OPCRManagementService $opcrManagementService,
        OPCRNotificationService $notificationService
    ) {
        $this->opcrManagementService = $opcrManagementService;
        $this->notificationService = $notificationService;
    }

    /**
     * Transition workflow to new state
     */
    public function transitionState(OPCRWorkflow $workflow, string $newState, array $data = []): bool
    {
        $this->validateStateTransition($workflow, $newState);
        $this->validateUserPermissions($workflow, $newState);

        return DB::transaction(function () use ($workflow, $newState, $data) {
            $oldState = $workflow->workflow_state;

            // Update workflow state
            $success = $this->opcrManagementService->updateWorkflowState($workflow, $newState, $data);

            if ($success) {
                // Send notifications
                $this->notificationService->sendWorkflowStateNotification($workflow, $newState, $data);

                // Log state transition
                $this->logStateTransition($workflow, $oldState, $newState, $data);
            }

            return $success;
        });
    }

    /**
     * Validate state transition
     */
    private function validateStateTransition(OPCRWorkflow $workflow, string $newState): void
    {
        $allowedTransitions = $workflow->getAllowedTransitions();

        if (!in_array($newState, $allowedTransitions)) {
            throw ValidationException::withMessages([
                'workflow_state' => "Cannot transition from '{$workflow->workflow_state}' to '{$newState}'. Allowed transitions: " . implode(', ', $allowedTransitions),
            ]);
        }
    }

    /**
     * Validate user permissions for state transition
     */
    private function validateUserPermissions(OPCRWorkflow $workflow, string $newState): void
    {
        $user = Auth::user();

        if (!$user) {
            throw ValidationException::withMessages([
                'authorization' => 'User must be authenticated to perform workflow actions.',
            ]);
        }

        $canTransition = match ($newState) {
            'committed' => $this->canCommitWorkflow($workflow, $user),
            'in_progress' => $this->canSubmitWorkflow($workflow, $user),
            'evaluation' => $this->canAssessWorkflow($workflow, $user),
            'final_approval' => $this->canApproveWorkflow($workflow, $user),
            'returned' => $this->canReturnWorkflow($workflow, $user),
            default => false,
        };

        if (!$canTransition) {
            throw ValidationException::withMessages([
                'authorization' => 'User does not have permission to perform this action on this workflow.',
            ]);
        }
    }

    /**
     * Check if user can commit workflow
     */
    private function canCommitWorkflow(OPCRWorkflow $workflow, User $user): bool
    {
        if (!in_array($workflow->workflow_state, ['draft', 'returned'])) {
            return false;
        }

        return $user->officeAssignments()
            ->where('office_id', $workflow->office_id)
            ->where('role', 'Department Head')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Check if user can submit workflow
     */
    private function canSubmitWorkflow(OPCRWorkflow $workflow, User $user): bool
    {
        if (!in_array($workflow->workflow_state, ['committed', 'returned'])) {
            return false;
        }

        return $user->officeAssignments()
            ->where('office_id', $workflow->office_id)
            ->where('role', 'Department Head')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Check if user can assess workflow
     */
    private function canAssessWorkflow(OPCRWorkflow $workflow, User $user): bool
    {
        if ($workflow->workflow_state !== 'in_progress') {
            return false;
        }

        return $user->officeAssignments()
            ->where('office_id', $workflow->office_id)
            ->where('role', 'Assessor')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Check if user can approve workflow
     */
    private function canApproveWorkflow(OPCRWorkflow $workflow, User $user): bool
    {
        if ($workflow->workflow_state !== 'evaluation') {
            return false;
        }

        return $user->officeAssignments()
            ->where('office_id', $workflow->office_id)
            ->where('role', 'Final Approver')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Check if user can return workflow
     */
    private function canReturnWorkflow(OPCRWorkflow $workflow, User $user): bool
    {
        if (!in_array($workflow->workflow_state, ['committed', 'in_progress', 'evaluation'])) {
            return false;
        }

        return $user->officeAssignments()
            ->where('office_id', $workflow->office_id)
            ->whereIn('role', ['Assessor', 'Final Approver'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Log state transition
     */
    private function logStateTransition(OPCRWorkflow $workflow, string $oldState, string $newState, array $data): void
    {
        $logData = [
            'workflow_id' => $workflow->id,
            'old_state' => $oldState,
            'new_state' => $newState,
            'user_id' => Auth::id(),
            'data' => $data,
            'timestamp' => now(),
        ];

        // Log to activity log
        activity()
            ->performedOn($workflow)
            ->causedBy(Auth::user())
            ->withProperties($logData)
            ->log("OPCR workflow state changed from {$oldState} to {$newState}");
    }

    /**
     * Get available actions for user on workflow
     */
    public function getAvailableActions(OPCRWorkflow $workflow, User $user): array
    {
        $actions = [];
        $currentState = $workflow->workflow_state;

        // Check user role in office
        $userRole = $this->getUserRoleInOffice($user, $workflow->office_id);

        if (!$userRole) {
            return $actions;
        }

        match ($userRole) {
            'Department Head' => $actions = $this->getDepartmentHeadActions($workflow, $currentState),
            'Assessor' => $actions = $this->getAssessorActions($workflow, $currentState),
            'Final Approver' => $actions = $this->getFinalApproverActions($workflow, $currentState),
            default => $actions = [],
        };

        return $actions;
    }

    /**
     * Get user role in office
     */
    private function getUserRoleInOffice(User $user, int $officeId): ?string
    {
        $assignment = $user->officeAssignments()
            ->where('office_id', $officeId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->first();

        return $assignment ? $assignment->role : null;
    }

    /**
     * Get department head actions
     */
    private function getDepartmentHeadActions(OPCRWorkflow $workflow, string $currentState): array
    {
        return match ($currentState) {
            'draft' => [
                'edit' => 'Edit OPCR',
                'commit' => 'Commit OPCR',
                'delete' => 'Delete OPCR',
            ],
            'committed' => [
                'edit' => 'Edit OPCR',
                'submit' => 'Submit for Evaluation',
                'uncommit' => 'Uncommit OPCR',
            ],
            'returned' => [
                'edit' => 'Edit OPCR',
                'commit' => 'Resubmit OPCR',
            ],
            'final_approval' => [
                'view' => 'View OPCR',
                'export' => 'Export OPCR',
            ],
            default => [],
        };
    }

    /**
     * Get assessor actions
     */
    private function getAssessorActions(OPCRWorkflow $workflow, string $currentState): array
    {
        return match ($currentState) {
            'in_progress' => [
                'view' => 'View OPCR',
                'assess' => 'Assess OPCR',
                'return' => 'Return for Revision',
            ],
            'evaluation' => [
                'view' => 'View OPCR',
            ],
            'final_approval' => [
                'view' => 'View OPCR',
                'export' => 'Export OPCR',
            ],
            default => [],
        };
    }

    /**
     * Get final approver actions
     */
    private function getFinalApproverActions(OPCRWorkflow $workflow, string $currentState): array
    {
        return match ($currentState) {
            'evaluation' => [
                'view' => 'View OPCR',
                'approve' => 'Approve OPCR',
                'return' => 'Return for Revision',
            ],
            'final_approval' => [
                'view' => 'View OPCR',
                'export' => 'Export OPCR',
            ],
            default => [],
        };
    }

    /**
     * Bulk state transition
     */
    public function bulkTransitionState(array $workflowIds, string $newState, array $data = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'errors' => [],
        ];

        foreach ($workflowIds as $workflowId) {
            try {
                $workflow = OPCRWorkflow::findOrFail($workflowId);
                $this->transitionState($workflow, $newState, $data);
                $results['success'][] = $workflowId;
            } catch (\Exception $e) {
                $results['failed'][] = $workflowId;
                $results['errors'][$workflowId] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get workflow state statistics
     */
    public function getStateStatistics(): array
    {
        $states = OPCRWorkflow::selectRaw('workflow_state, COUNT(*) as count')
            ->groupBy('workflow_state')
            ->pluck('count', 'workflow_state')
            ->toArray();

        $total = array_sum($states);

        return [
            'total' => $total,
            'by_state' => $states,
            'percentages' => collect($states)->mapWithKeys(function ($count, $state) use ($total) {
                return [$state => $total > 0 ? round(($count / $total) * 100, 2) : 0];
            })->toArray(),
        ];
    }

    /**
     * Get workflow aging information
     */
    public function getWorkflowAging(): array
    {
        $workflows = OPCRWorkflow::whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation'])
            ->get()
            ->groupBy('workflow_state');

        $aging = [];

        foreach ($workflows as $state => $stateWorkflows) {
            $aging[$state] = [
                'count' => $stateWorkflows->count(),
                'average_age_days' => $stateWorkflows->avg(function ($workflow) {
                    return $workflow->updated_at->diffInDays(now());
                }),
                'oldest_days' => $stateWorkflows->max(function ($workflow) {
                    return $workflow->updated_at->diffInDays(now());
                }),
                'newest_days' => $stateWorkflows->min(function ($workflow) {
                    return $workflow->updated_at->diffInDays(now());
                }),
            ];
        }

        return $aging;
    }

    /**
     * Check for workflows needing attention
     */
    public function getWorkflowsNeedingAttention(): array
    {
        $today = now();

        return [
            'overdue_draft' => OPCRWorkflow::where('workflow_state', 'draft')
                ->where('updated_at', '<', $today->subDays(7))
                ->count(),

            'overdue_in_progress' => OPCRWorkflow::where('workflow_state', 'in_progress')
                ->where('submitted_at', '<', $today->subDays(5))
                ->count(),

            'overdue_evaluation' => OPCRWorkflow::where('workflow_state', 'evaluation')
                ->where('assessed_at', '<', $today->subDays(3))
                ->count(),

            'returned_workflows' => OPCRWorkflow::where('workflow_state', 'returned')
                ->where('returned_at', '<', $today->subDays(2))
                ->count(),
        ];
    }

    /**
     * Get state transition history
     */
    public function getStateTransitionHistory(OPCRWorkflow $workflow): array
    {
        return $workflow->timeline;
    }

    /**
     * Check if workflow is in final state
     */
    public function isFinalState(string $state): bool
    {
        return $state === OPCRWorkflow::STATE_FINAL_APPROVAL;
    }

    /**
     * Check if workflow is active (not final)
     */
    public function isActiveState(string $state): bool
    {
        return in_array($state, [
            OPCRWorkflow::STATE_DRAFT,
            OPCRWorkflow::STATE_COMMITTED,
            OPCRWorkflow::STATE_IN_PROGRESS,
            OPCRWorkflow::STATE_EVALUATION,
        ]);
    }

    /**
     * Get state display configuration
     */
    public function getStateDisplayConfig(): array
    {
        return [
            OPCRWorkflow::STATE_DRAFT => [
                'label' => 'Draft',
                'color' => 'gray',
                'icon' => 'document',
                'description' => 'Department Head is preparing the OPCR',
            ],
            OPCRWorkflow::STATE_COMMITTED => [
                'label' => 'Committed',
                'color' => 'blue',
                'icon' => 'check-circle',
                'description' => 'OPCR targets have been committed',
            ],
            OPCRWorkflow::STATE_IN_PROGRESS => [
                'label' => 'In Progress',
                'color' => 'yellow',
                'icon' => 'clock',
                'description' => 'OPCR is under evaluation by Assessor',
            ],
            OPCRWorkflow::STATE_EVALUATION => [
                'label' => 'Under Evaluation',
                'color' => 'orange',
                'icon' => 'search',
                'description' => 'OPCR is pending final approval',
            ],
            OPCRWorkflow::STATE_FINAL_APPROVAL => [
                'label' => 'Approved',
                'color' => 'green',
                'icon' => 'check',
                'description' => 'OPCR has been finally approved',
            ],
            OPCRWorkflow::STATE_RETURNED => [
                'label' => 'Returned',
                'color' => 'red',
                'icon' => 'arrow-back',
                'description' => 'OPCR returned for revision',
            ],
        ];
    }
}