<?php

namespace App\Policies;

use App\Models\Ipcr;
use App\Models\User;

class IpcrPolicy
{
    /**
     * Determine whether the user can view any IPCR records.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ipcr.manage') || $user->can('ipcr.view');
    }

    /**
     * Determine whether the user can view the IPCR.
     */
    public function view(User $user, Ipcr $ipcr): bool
    {
        if ($user->can('ipcr.manage') || $user->can('ipcr.view')) {
            return true;
        }

        if ($user->can('ipcr.view-own') && $user->employee && $user->employee->id === $ipcr->employee_id) {
            return true;
        }

        if ($user->can('ipcr.review') && $ipcr->supervisor_id && $user->employee && $user->employee->id === $ipcr->supervisor_id) {
            return true;
        }

        if ($user->can('ipcr.approve') && $ipcr->head_of_office_id && $user->employee && $user->employee->id === $ipcr->head_of_office_id) {
            return true;
        }

        if ($user->can('ipcr.validate') && $ipcr->pmt_validator_id && $user->employee && $user->employee->id === $ipcr->pmt_validator_id) {
            return true;
        }

        if ($user->can('ipcr.finalize') && $ipcr->final_approver_id && $user->employee && $user->employee->id === $ipcr->final_approver_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('ipcr.create');
    }

    public function update(User $user, Ipcr $ipcr): bool
    {
        if ($user->can('ipcr.manage') || $user->can('ipcr.edit')) {
            return true;
        }

        return $user->can('ipcr.edit')
            && $user->employee
            && $user->employee->id === $ipcr->employee_id
            && $ipcr->status === 'draft';
    }

    public function submit(User $user, Ipcr $ipcr): bool
    {
        return $ipcr->status === 'draft'
            && $user->can('ipcr.submit')
            && $user->employee
            && $user->employee->id === $ipcr->employee_id;
    }

    public function review(User $user, Ipcr $ipcr): bool
    {
        if (!$user->can('ipcr.review')) {
            return false;
        }

        if ($user->can('ipcr.manage')) {
            return true;
        }

        return $user->employee && $user->employee->id === $ipcr->supervisor_id;
    }

    public function approve(User $user, Ipcr $ipcr): bool
    {
        if (!$user->can('ipcr.approve')) {
            return false;
        }

        if ($user->can('ipcr.manage')) {
            return true;
        }

        return $user->employee && $user->employee->id === $ipcr->head_of_office_id;
    }

    public function validate(User $user, Ipcr $ipcr): bool
    {
        if (!$user->can('ipcr.validate')) {
            return false;
        }

        if ($user->can('ipcr.manage')) {
            return true;
        }

        return $user->employee && $user->employee->id === $ipcr->pmt_validator_id;
    }

    public function finalize(User $user, Ipcr $ipcr): bool
    {
        if (!$user->can('ipcr.finalize')) {
            return false;
        }

        if ($user->can('ipcr.manage')) {
            return true;
        }

        return $user->employee && $user->employee->id === $ipcr->final_approver_id;
    }

    public function manage(User $user): bool
    {
        return $user->can('ipcr.manage');
    }

    public function manageAttachments(User $user, Ipcr $ipcr): bool
    {
        if ($user->can('ipcr.manage')) {
            return true;
        }

        if ($user->can('ipcr.attachments.manage')) {
            return $this->view($user, $ipcr);
        }

        return false;
    }

    public function manageAdjustments(User $user, Ipcr $ipcr): bool
    {
        if ($user->can('ipcr.manage')) {
            return true;
        }

        if ($user->can('ipcr.adjustments.manage')) {
            return $this->view($user, $ipcr);
        }

        return false;
    }
}
