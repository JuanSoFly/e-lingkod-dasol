<?php

namespace App\Listeners\OPCR;

use App\Events\OPCR\OPCRWorkflowStateChanged;
use App\Models\OPCRWorkflow;
use App\Models\User;
use App\Notifications\OPCRWorkflowNotification;
use Illuminate\Support\Facades\Notification;

class SendOPCRStateNotification
{
    /**
     * Handle the event.
     */
    public function handle(OPCRWorkflowStateChanged $event): void
    {
        $workflow = $event->workflow;
        $newState = $event->newState;

        $notificationType = $this->mapStateToNotificationType($newState);

        if (!$notificationType) {
            return;
        }

        $users = $this->getUsersToNotify($workflow, $notificationType);

        if ($users->isEmpty()) {
            return;
        }

        $payload = $this->buildNotificationPayload($workflow, $notificationType, $event->context);

        Notification::send($users, new OPCRWorkflowNotification($notificationType, $payload));
    }

    /**
     * Map workflow state to notification action keyword
     */
    private function mapStateToNotificationType(string $state): ?string
    {
        return match ($state) {
            OPCRWorkflow::STATE_PLANNING_REVIEW => 'opcr.planning_review',
            OPCRWorkflow::STATE_PMT_REVIEW => 'opcr.pmt_review',
            OPCRWorkflow::STATE_COMMITTED => 'opcr.committed',
            OPCRWorkflow::STATE_IN_PROGRESS => 'opcr.submitted',
            OPCRWorkflow::STATE_EVALUATION => 'opcr.evaluation_started',
            OPCRWorkflow::STATE_FINAL_APPROVAL => 'opcr.ready_for_final_approval',
            OPCRWorkflow::STATE_RETURNED => 'opcr.returned',
            default => null,
        };
    }

    /**
     * Get users to notify based on workflow state and action
     */
    private function getUsersToNotify(OPCRWorkflow $workflow, string $notificationType)
    {
        return match ($notificationType) {
            'opcr.planning_review' => ($users = User::permission('opcr.planning_review')->get())->isNotEmpty()
                ? $users
                : User::role('HR Admin')->get(),
            'opcr.pmt_review' => ($users = User::permission('opcr.pmt_review')->get())->isNotEmpty()
                ? $users
                : User::role('HR Admin')->get(),
            'opcr.committed', 'opcr.submitted' => User::role('Assessor')->get(),
            'opcr.evaluation_started', 'opcr.ready_for_final_approval' => User::role('Final Approver')->get(),
            // Ensure we handle the case where committedBy might be null or deleted
            'opcr.returned' => $workflow->committedBy ? collect([$workflow->committedBy]) : collect(),
            'opcr.initialized' => User::role('HR Admin')->get(),
            default => collect(),
        };
    }

    private function buildNotificationPayload(OPCRWorkflow $workflow, string $notificationType, array $context = []): array
    {
        return array_merge([
            'workflow' => $workflow->loadMissing(['office', 'period', 'committedBy', 'returnedBy']),
            // Use authenticated user or system as initiator? 
            // The event context might trigger from a job, but usually from controller
            'user' => auth()->check() ? auth()->user() : null, 
            'notification_type' => $notificationType,
            'workflow_state' => $workflow->workflow_state,
        ], $context);
    }
}
