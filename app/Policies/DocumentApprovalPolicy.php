<?php

namespace App\Policies;

use App\Models\DocumentApprovalRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        // Only HR Admin and Super Admin can view all requests
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    public function view(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('HR Admin')) {
            return true;
        }

        // Employees can only view their own requests
        if ($user->hasPermissionTo('document-approval.view-own') && 
            $user->employee && $user->employee->id === $documentApprovalRequest->employee_id) {
            return true;
        }

        return $this->isCurrentApprover($user, $documentApprovalRequest) ||
               $this->hasApprovedInWorkflow($user, $documentApprovalRequest);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin', 'Employee']);
    }

    public function update(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('HR Admin')) {
            return true;
        }

        if ($documentApprovalRequest->status !== 'draft') {
            return false;
        }

        return $user->employee && $user->employee->id === $documentApprovalRequest->employee_id;
    }

    public function delete(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('HR Admin')) {
            return true;
        }

        if ($documentApprovalRequest->status !== 'draft') {
            return false;
        }

        return $user->employee && $user->employee->id === $documentApprovalRequest->employee_id;
    }

    public function submit(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($documentApprovalRequest->status !== 'draft') {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('HR Admin')) {
            return true;
        }

        return $user->employee && $user->employee->id === $documentApprovalRequest->employee_id;
    }

    public function approve(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (!in_array($documentApprovalRequest->status, ['submitted', 'under_review'])) {
            return false;
        }

        return $this->isCurrentApprover($user, $documentApprovalRequest);
    }

    public function reject(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        return $this->approve($user, $documentApprovalRequest);
    }

    public function requestChanges(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        return $this->approve($user, $documentApprovalRequest);
    }

    public function comment(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('HR Admin')) {
            return true;
        }

        if ($user->employee && $user->employee->id === $documentApprovalRequest->employee_id) {
            return true;
        }

        return $this->isCurrentApprover($user, $documentApprovalRequest) ||
               $this->hasApprovedInWorkflow($user, $documentApprovalRequest);
    }

    public function manage(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    public function reassign(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('HR Admin')) {
            return true;
        }

        return $this->isCurrentApprover($user, $documentApprovalRequest);
    }

    public function escalate(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('HR Admin')) {
            return true;
        }

        if (!in_array($documentApprovalRequest->status, ['submitted', 'under_review'])) {
            return false;
        }

        $currentStep = $documentApprovalRequest->getCurrentApprovalStep();
        if (!$currentStep) {
            return false;
        }

        return $currentStep->deadline && 
               $currentStep->deadline->isPast() && 
               $this->isCurrentApprover($user, $documentApprovalRequest);
    }

    public function bulkApprove(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    public function bulkReject(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    public function viewPendingApprovals(User $user): bool
    {
        // Only HR Admin and Super Admin can view pending approvals
        return $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    /**
     * Determine if user can view their own requests only
     */
    public function viewOwn(User $user): bool
    {
        return $user->hasRole('Employee') || $user->hasAnyRole(['Super Admin', 'HR Admin']);
    }

    protected function isCurrentApprover(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        $currentStep = $documentApprovalRequest->getCurrentApprovalStep();
        
        if (!$currentStep) {
            return false;
        }

        return $currentStep->approvalWorkflowSteps()
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->exists();
    }

    protected function hasApprovedInWorkflow(User $user, DocumentApprovalRequest $documentApprovalRequest): bool
    {
        return $documentApprovalRequest->approvalSteps()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();
    }
}