<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\LeaveWorkflow;
use App\Models\LeaveWorkflowStep;
use App\Models\LeaveApplicationWorkflowStep;
use App\Models\LeaveApprovalDelegate;
use App\Services\HolidayService;
use App\Models\User;
use App\Notifications\LeaveApprovalRequired;
use App\Notifications\LeaveApprovalEscalated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveWorkflowService
{
    /**
     * Initialize workflow for leave application
     */
    public function initializeWorkflow(LeaveApplication $application): array
    {
        try {
            DB::beginTransaction();

            // Find applicable workflow
            $workflow = LeaveWorkflow::findApplicableWorkflow($application);

            if (!$workflow) {
                // Use default workflow (single approval by department head)
                $workflow = $this->createDefaultWorkflow($application);
            }

            // Check if applicant is a Department Head and handle auto-approval
            if ($this->isApplicantDepartmentHead($application)) {
                $steps = $this->handleDepartmentHeadSelfApproval($application, $workflow);
                DB::commit();
                return $steps;
            }

            // Create workflow steps for application
            $workflowSteps = [];
            foreach ($workflow->steps as $step) {
                $appStep = LeaveApplicationWorkflowStep::create([
                    'leave_application_id' => $application->id,
                    'leave_workflow_step_id' => $step->id,
                    'step_order' => $step->step_order,
                    'status' => 'pending',
                ]);

                $workflowSteps[] = $appStep;
            }

            // Route to first step approvers (handle potential parallel steps)
            $firstStepOrder = $workflowSteps[0]->step_order;
            $initialSteps = collect($workflowSteps)->where('step_order', $firstStepOrder);
            
            foreach ($initialSteps as $step) {
                $this->routeToApprovers($application, $step);
            }

            DB::commit();

            return $workflowSteps;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initialize workflow', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process approval step
     */
    public function processApproval(
        LeaveApplication $application,
        LeaveApplicationWorkflowStep $step,
        User $approver,
        string $action,
        ?string $remarks = null
    ): bool {
        try {
            DB::beginTransaction();

            // Update step status
            $step->update([
                'status' => $action,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'remarks' => $remarks,
            ]);

            // Check if workflow is complete
            if ($this->isWorkflowComplete($application)) {
                $this->completeWorkflow($application, $action === 'approved');
            } else {
                // Check if there are other pending steps with the SAME order (parallel steps)
                $hasPendingParallelSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
                    ->where('step_order', $step->step_order)
                    ->where('status', 'pending')
                    ->exists();

                // Only proceed to next level if all parallel steps at current level are done
                if (!$hasPendingParallelSteps) {
                     // Route to next step(s)
                    $nextSteps = $this->getNextSteps($application, $step);
                    foreach ($nextSteps as $nextStep) {
                        $this->routeToApprovers($application, $nextStep);
                    }
                }
            }

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process approval', [
                'application_id' => $application->id,
                'step_id' => $step->id,
                'approver_id' => $approver->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check for escalations
     */
    public function checkEscalations(): int
    {
        $escalatedCount = 0;
        $deemedApprovedCount = 0;

        // Find pending steps that need escalation - use step-specific escalation hours
        $pendingSteps = LeaveApplicationWorkflowStep::with(['leaveApplication', 'leaveWorkflowStep'])
            ->where('status', 'pending')
            ->whereHas('leaveWorkflowStep', function ($query) {
                $query->whereNotNull('escalation_hours')
                    ->where('escalation_hours', '>', 0);
            })
            ->get();

        foreach ($pendingSteps as $step) {
            if ($this->shouldEscalate($step)) {
                $this->escalateStep($step);
                $escalatedCount++;
            }
        }

        // Process deemed approvals based on working-day SLA (CSC deemed-approved rule)
        $deemedApprovedCount = $this->processDeemedApprovals();

        return $escalatedCount + $deemedApprovedCount;
    }

    /**
     * Route application to approvers
     */
    private function routeToApprovers(
        LeaveApplication $application,
        LeaveApplicationWorkflowStep $step
    ): void {
        $approvers = $step->leaveWorkflowStep->getCurrentApprovers($application);

        foreach ($approvers as $approver) {
            // Check for delegation
            $delegate = $this->getActiveDelegate($approver);
            $finalApprover = $delegate ?? $approver;

            // Send notification
            if (class_exists(LeaveApprovalRequired::class)) {
                $finalApprover->notify(new LeaveApprovalRequired($application, $step));
            }

            Log::info('Leave application routed for approval', [
                'application_id' => $application->id,
                'approver_id' => $finalApprover->id,
                'step_id' => $step->id,
                'delegate_of' => $delegate ? $approver->id : null,
            ]);
        }
    }

    /**
     * Check if workflow is complete
     */
    private function isWorkflowComplete(LeaveApplication $application): bool
    {
        $totalSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)->count();
        $completedSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->whereIn('status', ['approved', 'rejected', 'deemed_approved'])
            ->count();

        return $totalSteps === $completedSteps;
    }

    /**
     * Complete workflow and update application status
     */
    private function completeWorkflow(LeaveApplication $application, bool $approved): void
    {
        $application->update([
            'status' => $approved ? 'approved' : 'rejected',
            'approved_date' => now(),
        ]);

        // Update leave card if approved
        if ($approved) {
            $leaveCardService = app(LeaveCardService::class);
            if ($leaveCardService) {
                $leaveCardService->processApprovedLeave($application);
            }
        }

        Log::info('Leave workflow completed', [
            'application_id' => $application->id,
            'final_status' => $application->status,
        ]);
    }

    /**
     * Get next workflow steps (handling parallel steps)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getNextSteps(
        LeaveApplication $application,
        LeaveApplicationWorkflowStep $currentStep
    ) {
        // Find the next step order greater than current
        $nextOrderStep = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('step_order', '>', $currentStep->step_order)
            ->orderBy('step_order')
            ->first();

        if (!$nextOrderStep) {
            return collect([]);
        }

        // Return all steps with that next order
        return LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('step_order', $nextOrderStep->step_order)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * Check if step should be escalated
     */
    private function shouldEscalate(LeaveApplicationWorkflowStep $step): bool
    {
        $workflowStep = $step->leaveWorkflowStep;

        if (!$workflowStep->escalation_hours) {
            return false;
        }

        $escalationThreshold = $step->created_at->addHours($workflowStep->escalation_hours);
        return now()->greaterThan($escalationThreshold);
    }

    /**
     * Escalate workflow step
     */
    private function escalateStep(LeaveApplicationWorkflowStep $step): void
    {
        $workflowStep = $step->leaveWorkflowStep;
        $escalationTargets = $workflowStep->escalation_to;

        if (!$escalationTargets || empty($escalationTargets)) {
            return;
        }

        $step->update([
            'status' => 'escalated',
            'escalated_at' => now(),
        ]);

        // Notify escalation targets
        foreach ($escalationTargets as $target) {
            if (isset($target['user_id'])) {
                $user = User::find($target['user_id']);
                if ($user && class_exists(LeaveApprovalEscalated::class)) {
                    $user->notify(new LeaveApprovalEscalated($step->leaveApplication, $step));
                }
            }
        }

        Log::warning('Leave approval escalated', [
            'application_id' => $step->leave_application_id,
            'step_id' => $step->id,
            'escalated_to' => $escalationTargets,
        ]);
    }

    /**
     * Get active delegate for user
     */
    private function getActiveDelegate(User $user): ?User
    {
        $delegate = LeaveApprovalDelegate::where('delegator_id', $user->id)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        return $delegate?->delegate;
    }

    /**
     * Create default workflow for application
     */
    private function createDefaultWorkflow(LeaveApplication $application): LeaveWorkflow
    {
        // CSC-standard chain seeded via LeaveWorkflowSeeder
        return LeaveWorkflow::where('name', 'CSC Standard Leave Approval')->firstOrFail();
    }

    /**
     * Check if user can approve the given application based on current workflow step
     */
    public function canUserApproveApplication(LeaveApplication $application, User $user): bool
    {
        // Get the first pending workflow step
        $pendingStep = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if (!$pendingStep) {
            return false; // No pending steps, application is complete
        }

        // Get current approvers for this step
        $approvers = $pendingStep->leaveWorkflowStep->getCurrentApprovers($application);

        // Check if user is in the approvers list
        return collect($approvers)->contains(function ($approver) use ($user) {
            return $approver->id === $user->id;
        });
    }

    /**
     * Get pending approvals for user
     */
    public function getPendingApprovals(User $user): array
    {
        // Get all applications with any pending step
        // We cannot rely on JSON query for role-based approvers (like Dept Head)
        // so we fetch all pending and filter in PHP
        $applications = LeaveApplication::whereHas('workflowSteps', function ($query) {
            $query->where('status', 'pending');
        })
        ->with(['employee', 'leaveType', 'workflowSteps.leaveWorkflowStep'])
        ->orderBy('created_at')
        ->get();

        return $applications->filter(function ($app) use ($user) {
            return $this->canUserApproveApplication($app, $user);
        })->map(function ($app) {
            return [
                'id' => $app->id,
                'employee_name' => $app->employee->full_name,
                'leave_type' => $app->leaveType->name,
                'start_date' => $app->start_date->format('Y-m-d'),
                'end_date' => $app->end_date->format('Y-m-d'),
                'days_requested' => $app->days_requested,
                'reason' => $app->reason,
                'applied_date' => $app->applied_date->format('Y-m-d'),
                'current_step' => $this->getCurrentStep($app),
            ];
        })->values()->toArray();
    }

    /**
     * Get current workflow step for application
     */
    private function getCurrentStep(LeaveApplication $application): array
    {
        $currentStep = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if (!$currentStep) {
            return [];
        }

        return [
            'id' => $currentStep->id,
            'step_order' => $currentStep->step_order,
            'step_name' => $currentStep->leaveWorkflowStep->step_name ?? "Step {$currentStep->step_order}",
            'approvers' => collect($currentStep->leaveWorkflowStep->getCurrentApprovers($application))
                ->map(fn($user) => $user->name)
                ->toArray(),
        ];
    }

    /**
     * Manually escalate a workflow step (HR-initiated escalation)
     */
    public function manualEscalate(LeaveApplicationWorkflowStep $step, User $escalatedBy, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            // Log the manual escalation
            Log::warning('Manual escalation initiated', [
                'application_id' => $step->leave_application_id,
                'step_id' => $step->id,
                'escalated_by' => $escalatedBy->id,
                'reason' => $reason,
            ]);

            // Escalate the step
            $this->escalateStep($step);

            // Add remarks about manual escalation
            $step->update([
                'remarks' => ($step->remarks ? $step->remarks . "\n\n" : '') .
                           "MANUAL ESCALATION by {$escalatedBy->name}: " . ($reason ?? 'No reason provided'),
            ]);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to manually escalate step', [
                'application_id' => $step->leave_application_id,
                'step_id' => $step->id,
                'escalated_by' => $escalatedBy->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Override delegation for emergency situations
     */
    public function overrideDelegation(
        LeaveApplication $application,
        LeaveApplicationWorkflowStep $step,
        User $approver,
        string $action,
        ?string $remarks = null
    ): bool {
        try {
            DB::beginTransaction();

            // Log the delegation override
            Log::warning('Delegation override initiated', [
                'application_id' => $application->id,
                'step_id' => $step->id,
                'approver' => $approver->id,
                'action' => $action,
            ]);

            // Process approval regardless of delegation
            $success = $this->processApproval($application, $step, $approver, $action, $remarks);

            if ($success) {
                // Add override remarks
                $step->update([
                    'remarks' => ($step->remarks ? $step->remarks . "\n\n" : '') .
                               "DELEGATION OVERRIDE by {$approver->name}: " . ($remarks ?? 'Emergency override'),
                ]);
            }

            DB::commit();

            return $success;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to override delegation', [
                'application_id' => $application->id,
                'step_id' => $step->id,
                'approver' => $approver->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply deemed-approved rule based on working-day SLA
     */
    private function processDeemedApprovals(): int
    {
        $holidayService = app(HolidayService::class);
        $processed = 0;

        $pendingSteps = LeaveApplicationWorkflowStep::with(['leaveApplication', 'leaveWorkflowStep'])
            ->where('status', 'pending')
            ->whereHas('leaveWorkflowStep', function ($query) {
                $query->whereNotNull('sla_working_days')
                    ->where('sla_working_days', '>', 0);
            })
            ->get();

        foreach ($pendingSteps as $step) {
            if ($this->shouldDeemApprove($step, $holidayService)) {
                $this->deemApproveStep($step);
                $processed++;
            }
        }

        return $processed;
    }

    private function shouldDeemApprove(LeaveApplicationWorkflowStep $step, HolidayService $holidayService): bool
    {
        $workflowStep = $step->leaveWorkflowStep;

        if (!$workflowStep->sla_working_days) {
            return false;
        }

        $application = $step->leaveApplication;
        $employee = $application?->employee;

        $start = $step->created_at?->copy()->startOfDay();
        $end = now()->startOfDay();

        if (!$start) {
            return false;
        }

        $workWeek = $employee?->workCalendar?->work_week;
        $businessDays = $holidayService->businessDaysBetween($start, $end, $employee, $workWeek);

        return $businessDays >= $workflowStep->sla_working_days;
    }

    private function deemApproveStep(LeaveApplicationWorkflowStep $step): void
    {
        $application = $step->leaveApplication;
        $step->update([
            'status' => 'deemed_approved',
            'approved_by' => null,
            'approved_at' => now(),
            'remarks' => ($step->remarks ? $step->remarks . "\n" : '') . 'Auto-approved after 5 working days (CSC deemed-approved rule)',
        ]);

        Log::info('Leave step deemed approved due to SLA breach', [
            'application_id' => $application->id,
            'step_id' => $step->id,
            'workflow_step_id' => $step->leave_workflow_step_id,
            'sla_working_days' => $step->leaveWorkflowStep->sla_working_days,
        ]);

        if ($this->isWorkflowComplete($application)) {
            $this->completeWorkflow($application, true);
            return;
        }

        // Check if parallel steps are pending
        $hasPendingParallelSteps = LeaveApplicationWorkflowStep::where('leave_application_id', $application->id)
            ->where('step_order', $step->step_order)
            ->where('status', 'pending')
            ->exists();

        if (!$hasPendingParallelSteps) {
             $nextSteps = $this->getNextSteps($application, $step);
             foreach ($nextSteps as $nextStep) {
                 $this->routeToApprovers($application, $nextStep);
             }
        }
    }

    /**
     * Check if the applicant is a Department Head
     */
    private function isApplicantDepartmentHead(LeaveApplication $application): bool
    {
        $employee = $application->employee;

        // Check using is_department_head flag
        if ($employee->is_department_head) {
            return true;
        }

        // Check using OfficeAssignment
        $headAssignment = \App\Models\OfficeAssignment::where('employee_id', $employee->id)
            ->where('role', 'Department Head')
            ->first();

        return $headAssignment !== null;
    }

    /**
     * Handle Department Head self-approval
     */
    private function handleDepartmentHeadSelfApproval(LeaveApplication $application, LeaveWorkflow $workflow): array
    {
        $workflowSteps = [];
        $hasDepartmentHeadStep = false;
        $applicantUser = $application->employee->user;
        $applicantUserId = $applicantUser ? $applicantUser->id : null;

        // Create all workflow steps but auto-approve Department Head step
        foreach ($workflow->steps as $step) {
            $appStep = LeaveApplicationWorkflowStep::create([
                'leave_application_id' => $application->id,
                'leave_workflow_step_id' => $step->id,
                'step_order' => $step->step_order,
                'status' => 'pending',
            ]);

            // Check if this step is for Department Head approval
            $stepApprovers = $step->getCurrentApprovers($application);
            $isDepartmentHeadStep = false;

            foreach ($stepApprovers as $approver) {
                if ($applicantUserId && $approver->id === $applicantUserId) {
                    $isDepartmentHeadStep = true;
                    $hasDepartmentHeadStep = true;
                    break;
                }
            }

            if ($isDepartmentHeadStep) {
                // Auto-approve this step for Department Head
                $appStep->update([
                    'status' => 'approved',
                    'approved_by' => $applicantUserId,
                    'approved_at' => now(),
                    'remarks' => 'Auto-approved: Applicant is Department Head',
                ]);

                Log::info('Department Head auto-approval step processed', [
                    'application_id' => $application->id,
                    'step_id' => $appStep->id,
                    'employee_id' => $application->employee_id,
                    'applicant_user_id' => $applicantUserId,
                ]);
            } else {
                $workflowSteps[] = $appStep;
            }
        }

        // Route to the next pending step(s) (if any)
        if (!empty($workflowSteps)) {
            // Find the minimum pending step order
            $nextPending = collect($workflowSteps)->where('status', 'pending')->sortBy('step_order')->first();
            
            if ($nextPending) {
                // Route to all steps with this order
                $nextSteps = collect($workflowSteps)
                    ->where('status', 'pending')
                    ->where('step_order', $nextPending->step_order);
                    
                foreach ($nextSteps as $nextStep) {
                    $this->routeToApprovers($application, $nextStep);
                }
            }
        } else {
            // No more steps, complete the workflow
            $this->completeWorkflow($application, true);
        }

        Log::info('Department Head self-approval handled', [
            'application_id' => $application->id,
            'employee_id' => $application->employee_id,
            'applicant_user_id' => $applicantUserId,
            'had_department_head_step' => $hasDepartmentHeadStep,
            'remaining_steps' => count($workflowSteps),
        ]);

        return $workflowSteps;
    }
}
