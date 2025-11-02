<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\User;
use App\Models\OfficeAssignment;
use App\Notifications\OPCRWorkflowNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OPCRNotificationService
{
    /**
     * Send notification for workflow state change
     */
    public function sendWorkflowStateNotification(OPCRWorkflow $workflow, string $action, array $data = []): void
    {
        $notificationData = [
            'workflow' => $workflow,
            'action' => $action,
            'data' => $data,
            'user' => auth()->user(),
            'timestamp' => now(),
        ];

        try {
            switch ($action) {
                case 'initialized':
                    $this->notifyDepartmentHeads($workflow, 'opcr.initialized', $notificationData);
                    break;

                case 'committed':
                    $this->notifyAssessors($workflow, 'opcr.committed', $notificationData);
                    break;

                case 'submitted':
                    $this->notifyAssessors($workflow, 'opcr.submitted', $notificationData);
                    break;

                case 'assessed':
                    $this->notifyFinalApprovers($workflow, 'opcr.assessed', $notificationData);
                    break;

                case 'approved':
                    $this->notifyDepartmentHeads($workflow, 'opcr.approved', $notificationData);
                    $this->notifyAllParticipants($workflow, 'opcr.final_approval', $notificationData);
                    break;

                case 'returned':
                    $this->notifyDepartmentHeads($workflow, 'opcr.returned', $notificationData);
                    break;

                case 'escalated':
                    $this->notifySupervisors($workflow, 'opcr.escalated', $notificationData);
                    break;

                case 'reminder':
                    $this->sendReminderNotification($workflow, $notificationData);
                    break;

                default:
                    Log::warning("Unknown OPCR notification action: {$action}");
                    break;
            }
        } catch (\Exception $e) {
            Log::error('OPCR notification failed', [
                'action' => $action,
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Notify department heads
     */
    private function notifyDepartmentHeads(OPCRWorkflow $workflow, string $notificationType, array $data): void
    {
        $departmentHeads = $this->getUsersByRole($workflow->office_id, 'Department Head');

        if ($departmentHeads->isEmpty()) {
            Log::warning('No department heads found for office', [
                'office_id' => $workflow->office_id,
                'workflow_id' => $workflow->id,
            ]);
            return;
        }

        $this->sendNotification($departmentHeads, $notificationType, $data);
    }

    /**
     * Notify assessors
     */
    private function notifyAssessors(OPCRWorkflow $workflow, string $notificationType, array $data): void
    {
        $assessors = $this->getUsersByRole($workflow->office_id, 'Assessor');

        if ($assessors->isEmpty()) {
            Log::warning('No assessors found for office', [
                'office_id' => $workflow->office_id,
                'workflow_id' => $workflow->id,
            ]);
            return;
        }

        $this->sendNotification($assessors, $notificationType, $data);
    }

    /**
     * Notify final approvers
     */
    private function notifyFinalApprovers(OPCRWorkflow $workflow, string $notificationType, array $data): void
    {
        $finalApprovers = $this->getUsersByRole($workflow->office_id, 'Final Approver');

        if ($finalApprovers->isEmpty()) {
            Log::warning('No final approvers found for office', [
                'office_id' => $workflow->office_id,
                'workflow_id' => $workflow->id,
            ]);
            return;
        }

        $this->sendNotification($finalApprovers, $notificationType, $data);
    }

    /**
     * Notify supervisors
     */
    private function notifySupervisors(OPCRWorkflow $workflow, string $notificationType, array $data): void
    {
        $supervisors = $this->getUsersByRole($workflow->office_id, 'Supervisor');

        if ($supervisors->isEmpty()) {
            return;
        }

        $this->sendNotification($supervisors, $notificationType, $data);
    }

    /**
     * Notify all participants in the workflow
     */
    private function notifyAllParticipants(OPCRWorkflow $workflow, string $notificationType, array $data): void
    {
        $participants = collect();

        // Get all users involved in the workflow
        if ($workflow->committed_by) {
            $participants->push(User::find($workflow->committed_by));
        }
        if ($workflow->assessed_by) {
            $participants->push(User::find($workflow->assessed_by));
        }
        if ($workflow->approved_by) {
            $participants->push(User::find($workflow->approved_by));
        }

        // Remove null values and duplicates
        $participants = $participants->filter()->unique('id');

        if ($participants->isNotEmpty()) {
            $this->sendNotification($participants, $notificationType, $data);
        }
    }

    /**
     * Send notification to users
     */
    private function sendNotification($users, string $notificationType, array $data): void
    {
        try {
            Notification::send($users, new OPCRWorkflowNotification($notificationType, $data));
        } catch (\Exception $e) {
            Log::error('Failed to send OPCR notification', [
                'notification_type' => $notificationType,
                'users_count' => is_iterable($users) ? count($users) : 1,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get users by role for office
     */
    private function getUsersByRole(int $officeId, string $role): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('officeAssignments', function ($query) use ($officeId, $role) {
            $query->where('office_id', $officeId)
                  ->where('role', $role)
                  ->where('is_active', true)
                  ->where(function ($q) {
                      $q->whereNull('ended_date')
                        ->orWhere('ended_date', '>=', now());
                  });
        })->get();
    }

    /**
     * Send reminder notification
     */
    private function sendReminderNotification(OPCRWorkflow $workflow, array $data): void
    {
        $targetUsers = match ($workflow->workflow_state) {
            'draft', 'returned' => $this->getUsersByRole($workflow->office_id, 'Department Head'),
            'in_progress' => $this->getUsersByRole($workflow->office_id, 'Assessor'),
            'evaluation' => $this->getUsersByRole($workflow->office_id, 'Final Approver'),
            default => collect(),
        };

        if ($targetUsers->isNotEmpty()) {
            $this->sendNotification($targetUsers, 'opcr.reminder', $data);
        }
    }

    /**
     * Send overdue notifications
     */
    public function sendOverdueNotifications(): void
    {
        $overdueWorkflows = $this->getOverdueWorkflows();

        foreach ($overdueWorkflows as $workflow) {
            $this->sendWorkflowStateNotification($workflow, 'overdue', [
                'days_overdue' => $workflow->days_overdue,
                'due_date' => $workflow->due_date,
            ]);
        }
    }

    /**
     * Get overdue workflows
     */
    private function getOverdueWorkflows(): \Illuminate\Database\Eloquent\Collection
    {
        return OPCRWorkflow::whereIn('workflow_state', ['draft', 'in_progress', 'evaluation'])
            ->where(function ($query) {
                $query->where('created_at', '<', now()->subDays(7)) // Draft workflows older than 7 days
                      ->orWhere(function ($q) {
                          $q->where('workflow_state', 'in_progress')
                            ->where('submitted_at', '<', now()->subDays(5)); // In progress workflows older than 5 days
                      })
                      ->orWhere(function ($q) {
                          $q->where('workflow_state', 'evaluation')
                            ->where('assessed_at', '<', now()->subDays(3)); // Evaluation workflows older than 3 days
                      });
            })
            ->with(['office'])
            ->get();
    }

    /**
     * Send daily summary notifications
     */
    public function sendDailySummary(): void
    {
        $today = now();

        // Get workflows that changed state today
        $todayWorkflows = OPCRWorkflow::whereDate('updated_at', $today)
            ->with(['office', 'committedBy', 'assessedBy', 'approvedBy'])
            ->get();

        if ($todayWorkflows->isEmpty()) {
            return;
        }

        $summaryData = [
            'date' => $today->toDateString(),
            'total_workflows' => $todayWorkflows->count(),
            'workflows_by_state' => $todayWorkflows->groupBy('workflow_state')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'offices_involved' => $todayWorkflows->pluck('office.name')->unique()->values(),
        ];

        // Send to HR admins and supervisors
        $recipients = $this->getAdministrativeUsers();

        if ($recipients->isNotEmpty()) {
            try {
                foreach ($recipients as $recipient) {
                    Notification::send($recipient, new OPCRWorkflowNotification('opcr.daily_summary', array_merge($summaryData, [
                        'recipient' => $recipient,
                    ])));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send daily summary notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get administrative users (HR admins, supervisors)
     */
    private function getAdministrativeUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['HR Admin', 'Super Admin']);
        })
        ->orWhereHas('officeAssignments', function ($query) {
            $query->whereIn('role', ['Supervisor', 'Department Head'])
                  ->where('is_active', true);
        })
        ->get();
    }

    /**
     * Send custom notification
     */
    public function sendCustomNotification(OPCRWorkflow $workflow, array $recipients, string $message, array $data = []): void
    {
        $users = User::whereIn('id', $recipients)->get();

        $notificationData = [
            'workflow' => $workflow,
            'message' => $message,
            'data' => $data,
            'user' => auth()->user(),
            'timestamp' => now(),
        ];

        $this->sendNotification($users, 'opcr.custom', $notificationData);
    }

    /**
     * Get notification settings for user
     */
    public function getUserNotificationSettings(User $user): array
    {
        return [
            'email_notifications' => $user->email_notifications ?? true,
            'in_app_notifications' => $user->in_app_notifications ?? true,
            'opcr_notifications' => [
                'initialized' => $user->getSetting('opcr_notifications_initialized', true),
                'committed' => $user->getSetting('opcr_notifications_committed', true),
                'submitted' => $user->getSetting('opcr_notifications_submitted', true),
                'assessed' => $user->getSetting('opcr_notifications_assessed', true),
                'approved' => $user->getSetting('opcr_notifications_approved', true),
                'returned' => $user->getSetting('opcr_notifications_returned', true),
                'overdue' => $user->getSetting('opcr_notifications_overdue', true),
                'daily_summary' => $user->getSetting('opcr_notifications_daily_summary', false),
            ],
        ];
    }

    /**
     * Check if user should receive notification type
     */
    public function shouldReceiveNotification(User $user, string $notificationType): bool
    {
        $settings = $this->getUserNotificationSettings($user);

        if (!$settings['email_notifications'] && !$settings['in_app_notifications']) {
            return false;
        }

        return $settings['opcr_notifications'][$notificationType] ?? true;
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications(array $workflowIds, string $action, array $data = []): void
    {
        $workflows = OPCRWorkflow::whereIn('id', $workflowIds)
            ->with(['office'])
            ->get();

        foreach ($workflows as $workflow) {
            $this->sendWorkflowStateNotification($workflow, $action, $data);
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStatistics(): array
    {
        $last30Days = now()->subDays(30);

        return [
            'total_notifications_sent' => $this->getNotificationCount($last30Days),
            'notifications_by_type' => $this->getNotificationsByType($last30Days),
            'notifications_by_office' => $this->getNotificationsByOffice($last30Days),
            'failed_notifications' => $this->getFailedNotificationCount($last30Days),
        ];
    }

    /**
     * Get notification count for period
     */
    private function getNotificationCount(\DateTime $since): int
    {
        // This would require a notifications table to track sent notifications
        // For now, return a placeholder value
        return 0;
    }

    /**
     * Get notifications by type
     */
    private function getNotificationsByType(\DateTime $since): array
    {
        // This would require a notifications table to track sent notifications
        // For now, return a placeholder value
        return [];
    }

    /**
     * Get notifications by office
     */
    private function getNotificationsByOffice(\DateTime $since): array
    {
        // This would require a notifications table to track sent notifications
        // For now, return a placeholder value
        return [];
    }

    /**
     * Get failed notification count
     */
    private function getFailedNotificationCount(\DateTime $since): int
    {
        // This would require a notifications table to track sent notifications
        // For now, return a placeholder value
        return 0;
    }
}