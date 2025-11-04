<?php

namespace App\Policies;

use App\Models\OPCRWorkflow;
use App\Models\OfficeAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OPCRPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'opcr.view',
            'opcr.view_own',
            'opcr.view_assigned',
            'opcr.view_team',
            'opcr.analytics',
            'opcr.admin'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OPCRWorkflow $workflow): bool
    {
        // Super admin and HR admin can view all
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return true;
        }

        // Check office-based permissions
        if ($user->hasPermission('opcr.view')) {
            return true;
        }

        // Department Head can view workflows for their office
        if ($user->hasPermission('opcr.view_own')) {
            return $this->isUserDepartmentHeadForWorkflow($user, $workflow);
        }

        // Assessor can view workflows assigned to them for evaluation
        if ($user->hasPermission('opcr.view_assigned') && $this->hasActiveRole($user, OfficeAssignment::ROLE_ASSESSOR)) {
            // Assessors can only access workflows they are specifically assigned to evaluate
            return $this->isUserAssessorForWorkflow($user, $workflow);
        }

        // Final Approver can view workflows assigned to them for approval
        if ($user->hasPermission('opcr.view_assigned') && $this->hasActiveRole($user, OfficeAssignment::ROLE_FINAL_APPROVER)) {
            // Final Approvers can only access workflows they are specifically assigned to approve
            return $this->isUserFinalApproverForWorkflow($user, $workflow);
        }

        // Employee can view their own workflows
        if ($user->hasPermission('opcr.view_own') && $user->employee) {
            return $workflow->committedBy?->employee_id === $user->employee->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'opcr.create',
            'opcr.admin'
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OPCRWorkflow $workflow): bool
    {
        // Super admin and HR admin can edit all
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return true;
        }

        // Check general edit permission
        if ($user->hasPermission('opcr.edit')) {
            return true;
        }

        // Department Head can edit workflows in draft or returned state for their office
        if ($user->hasPermission('opcr.edit') && in_array($workflow->workflow_state, ['draft', 'returned'])) {
            return $this->isUserDepartmentHeadForWorkflow($user, $workflow);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OPCRWorkflow $workflow): bool
    {
        // Super admin can delete all
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // HR admin can delete (with additional checks)
        if ($user->hasRole('HR Admin') && $user->hasPermission('opcr.delete')) {
            return $workflow->workflow_state === 'draft';
        }

        return $user->hasPermission('opcr.delete');
    }

    /**
     * Determine whether the user can commit the workflow.
     */
    public function commit(User $user, OPCRWorkflow $workflow): bool
    {
        return $user->hasPermission('opcr.commit') &&
               $this->isUserDepartmentHeadForWorkflow($user, $workflow) &&
               in_array($workflow->workflow_state, ['draft', 'returned']);
    }

    /**
     * Determine whether the user can submit the workflow.
     */
    public function submit(User $user, OPCRWorkflow $workflow): bool
    {
        return $user->hasPermission('opcr.submit') &&
               $this->isUserDepartmentHeadForWorkflow($user, $workflow) &&
               in_array($workflow->workflow_state, ['committed', 'returned']);
    }

    /**
     * Determine whether the user can assess the workflow.
     */
    public function assess(User $user, OPCRWorkflow $workflow): bool
    {
        return $user->hasPermission('opcr.assess') &&
               $this->isUserAssessorForWorkflow($user, $workflow) &&
               $workflow->workflow_state === 'in_progress';
    }

    /**
     * Determine whether the user can approve the workflow.
     */
    public function approve(User $user, OPCRWorkflow $workflow): bool
    {
        return $user->hasPermission('opcr.approve') &&
               $this->isUserFinalApproverForWorkflow($user, $workflow) &&
               $workflow->workflow_state === 'evaluation';
    }

    /**
     * Determine whether the user can return the workflow.
     */
    public function return(User $user, OPCRWorkflow $workflow): bool
    {
        return $user->hasPermission('opcr.return') &&
               $this->canUserReturnWorkflow($user, $workflow);
    }

    /**
     * Determine whether the user can export the workflow.
     */
    public function export(User $user, OPCRWorkflow $workflow): bool
    {
        // Super admin and HR admin can export all
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return $user->hasAnyPermission(['opcr.export', 'opcr.admin']);
        }

        // Department Head can export their own workflows
        if ($user->hasPermission('opcr.export_own')) {
            return $this->isUserDepartmentHeadForWorkflow($user, $workflow);
        }

        // Assessor and Final Approver can export assigned workflows
        if ($user->hasPermission('opcr.export_assigned')) {
            return $this->isUserAssessorForWorkflow($user, $workflow) ||
                   $this->isUserFinalApproverForWorkflow($user, $workflow);
        }

        return false;
    }

    /**
     * Determine whether the user can view analytics.
     */
    public function analytics(User $user): bool
    {
        return $user->hasAnyPermission([
            'opcr.analytics',
            'opcr.admin'
        ]);
    }

    /**
     * Determine whether the user can manage settings.
     */
    public function settings(User $user): bool
    {
        return $user->hasAnyPermission([
            'opcr.settings',
            'opcr.admin'
        ]);
    }

    /**
     * Determine whether the user can perform admin tasks.
     */
    public function admin(User $user): bool
    {
        return $user->hasPermission('opcr.admin');
    }

    /**
     * Check if user is department head for the workflow's office
     */
    private function isUserDepartmentHeadForWorkflow(User $user, OPCRWorkflow $workflow): bool
    {
        return $this->hasActiveRole($user, OfficeAssignment::ROLE_DEPARTMENT_HEAD, $workflow->office_id);
    }

    /**
     * Check if user is assessor for the workflow's office
     */
    private function isUserAssessorForWorkflow(User $user, OPCRWorkflow $workflow): bool
    {
        return $this->hasActiveRole($user, OfficeAssignment::ROLE_ASSESSOR, $workflow->office_id);
    }

    /**
     * Check if user is final approver for the workflow's office
     */
    private function isUserFinalApproverForWorkflow(User $user, OPCRWorkflow $workflow): bool
    {
        return $this->hasActiveRole($user, OfficeAssignment::ROLE_FINAL_APPROVER, $workflow->office_id);
    }

    /**
     * Check if user can return workflow based on their role
     */
    private function canUserReturnWorkflow(User $user, OPCRWorkflow $workflow): bool
    {
        if (!in_array($workflow->workflow_state, ['committed', 'in_progress', 'evaluation'])) {
            return false;
        }

        // Assessor can return workflows they are assigned to evaluate
        if ($this->hasActiveRole($user, OfficeAssignment::ROLE_ASSESSOR) &&
            $this->isUserAssessorForWorkflow($user, $workflow)) {
            return in_array($workflow->workflow_state, ['committed', 'in_progress']);
        }

        // Final Approver can return workflows they are assigned to approve
        if ($this->hasActiveRole($user, OfficeAssignment::ROLE_FINAL_APPROVER) &&
            $this->isUserFinalApproverForWorkflow($user, $workflow) &&
            $workflow->workflow_state === 'evaluation') {
            return true;
        }

        return false;
    }

    /**
     * Determine if user holds an active OPCR assignment for a role.
     */
    private function hasActiveRole(User $user, string $role, ?int $officeId = null): bool
    {
        $query = $user->officeAssignments()
            ->where('role', $role)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            });

        if ($officeId !== null) {
            $query->where('office_id', $officeId);
        }

        return $query->exists();
    }
}
