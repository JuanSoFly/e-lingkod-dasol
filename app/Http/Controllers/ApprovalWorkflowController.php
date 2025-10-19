<?php

namespace App\Http\Controllers;

use App\Services\LeaveWorkflowService;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationWorkflowStep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApprovalWorkflowController extends Controller
{
    public function __construct(private LeaveWorkflowService $workflowService)
    {
        $this->middleware('auth');
    }

    /**
     * Get pending approvals for current user
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $pendingApprovals = $this->workflowService->getPendingApprovals(auth()->user());

        return response()->json([
            'pending_approvals' => $pendingApprovals,
            'total_count' => count($pendingApprovals),
        ]);
    }

    /**
     * Approve a leave application
     */
    public function approve(LeaveApplication $application, Request $request): JsonResponse
    {
        $this->authorize('leave.approve');

        $validated = $request->validate([
            'step_id' => ['required', 'exists:leave_application_workflow_steps,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $step = LeaveApplicationWorkflowStep::findOrFail($validated['step_id']);

            // Validate that this user can approve this step
            if (!$this->canUserApproveStep(auth()->user(), $step)) {
                return response()->json([
                    'message' => 'You are not authorized to approve this application',
                ], 403);
            }

            $success = $this->workflowService->processApproval(
                $application,
                $step,
                auth()->user(),
                'approved',
                $validated['remarks'] ?? null
            );

            if ($success) {
                return response()->json([
                    'message' => 'Leave application approved successfully',
                    'application' => $application->load(['employee', 'leaveType']),
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to approve leave application',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while approving the application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a leave application
     */
    public function reject(LeaveApplication $application, Request $request): JsonResponse
    {
        $this->authorize('leave.approve');

        $validated = $request->validate([
            'step_id' => ['required', 'exists:leave_application_workflow_steps,id'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        try {
            $step = LeaveApplicationWorkflowStep::findOrFail($validated['step_id']);

            // Validate that this user can approve this step
            if (!$this->canUserApproveStep(auth()->user(), $step)) {
                return response()->json([
                    'message' => 'You are not authorized to reject this application',
                ], 403);
            }

            $success = $this->workflowService->processApproval(
                $application,
                $step,
                auth()->user(),
                'rejected',
                $validated['remarks']
            );

            if ($success) {
                return response()->json([
                    'message' => 'Leave application rejected successfully',
                    'application' => $application->load(['employee', 'leaveType']),
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to reject leave application',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while rejecting the application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get application workflow status
     */
    public function workflowStatus(LeaveApplication $application): JsonResponse
    {
        $this->authorize('leave.view');

        $workflowSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->with(['leaveWorkflowStep', 'approvedBy'])
            ->orderBy('step_order')
            ->get();

        return response()->json([
            'application' => $application->load(['employee', 'leaveType']),
            'workflow_steps' => $workflowSteps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'step_order' => $step->step_order,
                    'step_name' => $step->leaveWorkflowStep->step_name ?? "Step {$step->step_order}",
                    'step_type' => $step->leaveWorkflowStep->step_type,
                    'status' => $step->status,
                    'approved_by' => $step->approvedBy?->name,
                    'approved_at' => $step->approved_at?->format('Y-m-d H:i:s'),
                    'remarks' => $step->remarks,
                    'escalated_at' => $step->escalated_at?->format('Y-m-d H:i:s'),
                    'escalated_to' => $step->escalatedTo?->name,
                ];
            }),
            'is_complete' => $application->status !== 'pending',
        ]);
    }

    /**
     * Check if user can approve the given step
     */
    private function canUserApproveStep(User $user, LeaveApplicationWorkflowStep $step): bool
    {
        $approvers = $step->leaveWorkflowStep->getCurrentApprovers($step->leaveApplication);

        return collect($approvers)->contains(function ($approver) use ($user) {
            return $approver->id === $user->id;
        });
    }

    /**
     * Manually escalate a pending application
     */
    public function escalate(LeaveApplication $application, Request $request): JsonResponse
    {
        $this->authorize('leave.manage');

        $validated = $request->validate([
            'step_id' => ['required', 'exists:leave_application_workflow_steps,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $step = LeaveApplicationWorkflowStep::findOrFail($validated['step_id']);

            if ($step->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending applications can be escalated',
                ], 422);
            }

            $success = $this->workflowService->manualEscalate(
                $step,
                auth()->user(),
                $validated['reason']
            );

            if ($success) {
                return response()->json([
                    'message' => 'Application escalated successfully',
                    'application' => $application->load(['employee', 'leaveType']),
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to escalate application',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while escalating the application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Override delegation and approve/reject
     */
    public function overrideApproval(LeaveApplication $application, Request $request): JsonResponse
    {
        $this->authorize('leave.manage');

        $validated = $request->validate([
            'step_id' => ['required', 'exists:leave_application_workflow_steps,id'],
            'action' => ['required', 'in:approved,rejected'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        try {
            $step = LeaveApplicationWorkflowStep::findOrFail($validated['step_id']);

            if ($step->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending applications can be processed',
                ], 422);
            }

            $success = $this->workflowService->overrideDelegation(
                $application,
                $step,
                auth()->user(),
                $validated['action'],
                $validated['remarks']
            );

            if ($success) {
                return response()->json([
                    'message' => "Application {$validated['action']} successfully with delegation override",
                    'application' => $application->load(['employee', 'leaveType']),
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to process application',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing the application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}