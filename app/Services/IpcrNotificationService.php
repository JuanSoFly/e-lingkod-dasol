<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\OPCRWorkflow;
use App\Models\User;
use App\Notifications\IpcrCascadeProgressNotification;
use App\Notifications\IpcrSupervisorAssignmentNotification;
use App\Notifications\IpcrTargetsGeneratedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class IpcrNotificationService
{
    public function notifyCascadeCompletion(OPCRWorkflow $workflow, Collection $ipcrs, array $options = []): void
    {
        if ($ipcrs->isEmpty()) {
            return;
        }

        $this->notifyEmployees($ipcrs);
        $this->notifySupervisors($ipcrs);
        $this->notifyAdministrators($workflow, $ipcrs, $options);
    }

    protected function notifyEmployees(Collection $ipcrs): void
    {
        $ipcrs->each(function (Ipcr $ipcr) {
            if (!$ipcr->employee?->user) {
                return;
            }

            Notification::send($ipcr->employee->user, new IpcrTargetsGeneratedNotification($ipcr));
        });
    }

    protected function notifySupervisors(Collection $ipcrs): void
    {
        $ipcrs->each(function (Ipcr $ipcr) {
            if (!$ipcr->supervisor || !$ipcr->supervisor->user) {
                return;
            }

            Notification::send($ipcr->supervisor->user, new IpcrSupervisorAssignmentNotification($ipcr));
        });
    }

    protected function notifyAdministrators(OPCRWorkflow $workflow, Collection $ipcrs, array $options = []): void
    {
        $admins = collect($options['notify_users'] ?? [])
            ->filter()
            ->map(function ($user) {
                if ($user instanceof User) {
                    return $user;
                }

                return User::find($user);
            })
            ->merge([$workflow->office?->departmentHead?->user])
            ->filter()
            ->unique('id');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new IpcrCascadeProgressNotification($workflow, $ipcrs));
    }
}
