<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class OPCRWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $notificationType;
    private array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $notificationType, array $data)
    {
        $this->notificationType = $notificationType;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Database channel for in-app notifications
        $channels[] = 'database';

        // Email channel if user has email notifications enabled
        if ($notifiable->email_notifications ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $workflow = $this->data['workflow'] ?? null;
        $user = $this->data['user'] ?? null;

        return match ($this->notificationType) {
            'opcr.initialized' => $this->createInitializedMail($notifiable, $workflow, $user),
            'opcr.committed' => $this->createCommittedMail($notifiable, $workflow, $user),
            'opcr.submitted' => $this->createSubmittedMail($notifiable, $workflow, $user),
            'opcr.assessed' => $this->createAssessedMail($notifiable, $workflow, $user),
            'opcr.approved' => $this->createApprovedMail($notifiable, $workflow, $user),
            'opcr.returned' => $this->createReturnedMail($notifiable, $workflow, $user),
            'opcr.overdue' => $this->createOverdueMail($notifiable, $workflow, $user),
            'opcr.reminder' => $this->createReminderMail($notifiable, $workflow, $user),
            'opcr.daily_summary' => $this->createDailySummaryMail($notifiable, $this->data),
            'opcr.custom' => $this->createCustomMail($notifiable, $this->data),
            'opcr.evaluation_started' => $this->createEvaluationStartedMail($notifiable, $workflow, $user),
            'opcr.ready_for_final_approval' => $this->createReadyForFinalApprovalMail($notifiable, $workflow, $user),
            default => $this->createDefaultMail($notifiable, $this->data),
        };
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase(object $notifiable): array
    {
        $workflow = $this->data['workflow'] ?? null;
        $user = $this->data['user'] ?? null;

        return [
            'notification_type' => $this->notificationType,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'workflow_id' => $workflow?->id,
            'office_id' => $workflow?->office_id,
            'action_url' => $this->getActionUrl(),
            'action_text' => $this->getActionText(),
            'priority' => $this->getPriority(),
            'data' => $this->data,
            'created_by' => $user?->id,
        ];
    }

    /**
     * Create initialized notification mail
     */
    private function createInitializedMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('New OPCR Workflow Initialized')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new OPCR (Office Performance Commitment and Review) workflow has been initialized for your office.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Title:** ' . $workflow->title);
            })
            ->action('View OPCR Workflow', route('opcr.workflows.show', $workflow->id))
            ->line('Please review and commit your OPCR targets as soon as possible.')
            ->line('Thank you for using the E-Lingkod Dasol HRIS system.');
    }

    /**
     * Create committed notification mail
     */
    private function createCommittedMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Targets Committed - Ready for Assessment')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('OPCR targets have been committed and are ready for your assessment.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Committed by:** ' . $workflow->committedBy->name);
            })
            ->action('Assess OPCR', route('opcr.workflows.evaluate', $workflow->id))
            ->line('Please review the committed targets and provide your assessment.')
            ->line('Thank you for your attention to this matter.');
    }

    /**
     * Create submitted notification mail
     */
    private function createSubmittedMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Submitted for Evaluation')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An OPCR workflow has been submitted for evaluation and requires your assessment.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Submitted by:** ' . $workflow->submittedBy->name);
            })
            ->action('Review OPCR', route('opcr.workflows.evaluate', $workflow->id))
            ->line('Please assess the submitted OPCR and provide your ratings.')
            ->line('Your prompt attention to this matter is appreciated.');
    }

    /**
     * Create assessed notification mail
     */
    private function createAssessedMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Assessment Completed - Pending Final Approval')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An OPCR workflow has been assessed and is pending your final approval.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Assessed by:** ' . $workflow->assessedBy->name)
                       ->when($workflow->overall_rating, function ($msg) use ($workflow) {
                           $msg->line('**Overall Rating:** ' . $workflow->overall_rating . ' (' . $workflow->overall_adjectival_rating . ')');
                       });
            })
            ->action('Review and Approve', route('opcr.workflows.review', $workflow->id))
            ->line('Please review the assessment and provide your final approval.')
            ->line('Your decision on this matter is greatly appreciated.');
    }

    /**
     * Create approved notification mail
     */
    private function createApprovedMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Finally Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An OPCR workflow has been finally approved!')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Approved by:** ' . $workflow->approvedBy->name)
                       ->when($workflow->overall_rating, function ($msg) use ($workflow) {
                           $msg->line('**Final Rating:** ' . $workflow->overall_rating . ' (' . $workflow->overall_adjectival_rating . ')');
                       });
            })
            ->action('View Approved OPCR', route('opcr.workflows.show', $workflow->id))
            ->line('Congratulations on the successful completion of the OPCR process.')
            ->line('The approved OPCR is now available for download and reference.');
    }

    /**
     * Create returned notification mail
     */
    private function createReturnedMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Returned for Revision')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An OPCR workflow has been returned for revision.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Returned by:** ' . $workflow->returnedBy->name)
                       ->when($workflow->return_reason, function ($msg) use ($workflow) {
                           $msg->line('**Reason:** ' . $workflow->return_reason);
                       });
            })
            ->action('Review and Revise', route('opcr.workflows.edit', $workflow->id))
            ->line('Please review the feedback and make the necessary revisions.')
            ->line('After revising, please resubmit the OPCR for evaluation.');
    }

    /**
     * Create evaluation started notification mail
     */
    private function createEvaluationStartedMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Evaluation Started')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An OPCR workflow is now in the evaluation phase.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Committed Targets:** ' . $workflow->targets()->count());
            })
            ->action('Review Evaluation Status', route('opcr.workflows.review', $workflow->id))
            ->line('Please prepare for validation and ensure timelines are met.');
    }

    /**
     * Create ready for final approval notification mail
     */
    private function createReadyForFinalApprovalMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Ready for Final Approval')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An OPCR workflow has completed evaluation and awaits your final approval.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Overall Rating:** ' . ($workflow->overall_rating ?? 'Pending'));
            })
            ->action('Finalize OPCR', route('opcr.workflows.review', $workflow->id))
            ->line('Kindly review and take action to finalize the workflow.');
    }

    /**
     * Create overdue notification mail
     */
    private function createOverdueMail($notifiable, $workflow, $user): MailMessage
    {
        $daysOverdue = $this->data['days_overdue'] ?? 'unknown';

        return (new MailMessage)
            ->subject('OPCR Workflow Overdue - Action Required')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder that an OPCR workflow is overdue.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Current Status:** ' . $workflow->state_display_name);
            })
            ->line('**Days Overdue:** ' . $daysOverdue)
            ->action('View OPCR Workflow', route('opcr.workflows.show', $workflow->id))
            ->line('Please take immediate action to complete the required steps.')
            ->line('Your prompt attention to this matter is greatly appreciated.');
    }

    /**
     * Create reminder notification mail
     */
    private function createReminderMail($notifiable, $workflow, $user): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Workflow Reminder')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a friendly reminder about an OPCR workflow that requires your attention.')
            ->when($workflow, function ($message) use ($workflow) {
                $message->line('**Office:** ' . $workflow->office->name)
                       ->line('**Period:** ' . $workflow->period->name)
                       ->line('**Current Status:** ' . $workflow->state_display_name);
            })
            ->action('View OPCR Workflow', route('opcr.workflows.show', $workflow->id))
            ->line('Please review and take the necessary action.')
            ->line('Thank you for your attention to this matter.');
    }

    /**
     * Create daily summary notification mail
     */
    private function createDailySummaryMail($notifiable, array $data): MailMessage
    {
        return (new MailMessage)
            ->subject('Daily OPCR Workflow Summary - ' . $data['date'])
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Here is your daily OPCR workflow summary:')
            ->line('**Total Workflows Updated:** ' . $data['total_workflows'])
            ->line('**Offices Involved:** ' . implode(', ', $data['offices_involved']->toArray()))
            ->when(!empty($data['workflows_by_state']), function ($message) use ($data) {
                $message->line('**Workflows by State:**');
                foreach ($data['workflows_by_state'] as $state => $count) {
                    $message->line('- ' . ucfirst(str_replace('_', ' ', $state)) . ': ' . $count);
                }
            })
            ->action('View OPCR Dashboard', route('opcr.dashboard'))
            ->line('Visit the OPCR dashboard for more details.')
            ->line('Have a productive day!');
    }

    /**
     * Create custom notification mail
     */
    private function createCustomMail($notifiable, array $data): MailMessage
    {
        $workflow = $data['workflow'] ?? null;
        $message = $data['message'] ?? 'You have a new OPCR workflow notification.';

        return (new MailMessage)
            ->subject('OPCR Workflow Notification')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($message)
            ->when($workflow, function ($msg) use ($workflow) {
                $msg->line('**Office:** ' . $workflow->office->name)
                   ->line('**Period:** ' . $workflow->period->name);
            })
            ->when($workflow, function ($msg) use ($workflow) {
                $msg->action('View OPCR Workflow', route('opcr.workflows.show', $workflow->id));
            })
            ->line('Thank you for your attention to this matter.');
    }

    /**
     * Create default notification mail
     */
    private function createDefaultMail($notifiable, array $data): MailMessage
    {
        return (new MailMessage)
            ->subject('OPCR Workflow Notification')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have a new OPCR workflow notification.')
            ->line('Please check the system for more details.')
            ->action('View OPCR Dashboard', route('opcr.dashboard'))
            ->line('Thank you for using the E-Lingkod Dasol HRIS system.');
    }

    /**
     * Get notification title
     */
    private function getNotificationTitle(): string
    {
        return match ($this->notificationType) {
            'opcr.initialized' => 'OPCR Workflow Initialized',
            'opcr.committed' => 'OPCR Targets Committed',
            'opcr.submitted' => 'OPCR Submitted for Evaluation',
            'opcr.assessed' => 'OPCR Assessment Completed',
            'opcr.approved' => 'OPCR Finally Approved',
            'opcr.returned' => 'OPCR Returned for Revision',
            'opcr.evaluation_started' => 'OPCR Evaluation Started',
            'opcr.ready_for_final_approval' => 'OPCR Ready for Final Approval',
            'opcr.overdue' => 'OPCR Workflow Overdue',
            'opcr.reminder' => 'OPCR Workflow Reminder',
            'opcr.daily_summary' => 'Daily OPCR Summary',
            'opcr.custom' => 'OPCR Notification',
            default => 'OPCR Workflow Update',
        };
    }

    /**
     * Get notification message
     */
    private function getNotificationMessage(): string
    {
        $workflow = $this->data['workflow'] ?? null;
        $user = $this->data['user'] ?? null;

        return match ($this->notificationType) {
            'opcr.initialized' => 'A new OPCR workflow has been initialized.',
            'opcr.committed' => 'OPCR targets have been committed and are ready for assessment.',
            'opcr.submitted' => 'OPCR has been submitted for evaluation.',
            'opcr.assessed' => 'OPCR assessment has been completed.',
            'opcr.approved' => 'OPCR has been finally approved.',
            'opcr.returned' => 'OPCR has been returned for revision.',
            'opcr.evaluation_started' => 'OPCR evaluation has started.',
            'opcr.ready_for_final_approval' => 'OPCR is ready for final approval.',
            'opcr.overdue' => 'OPCR workflow is overdue and requires attention.',
            'opcr.reminder' => 'This is a reminder about an OPCR workflow.',
            'opcr.daily_summary' => 'Daily OPCR workflow summary is available.',
            'opcr.custom' => $this->data['message'] ?? 'You have an OPCR notification.',
            default => 'OPCR workflow has been updated.',
        };
    }

    /**
     * Get action URL
     */
    private function getActionUrl(): ?string
    {
        $workflow = $this->data['workflow'] ?? null;

        if (!$workflow) {
            return route('opcr.dashboard');
        }

        return match ($this->notificationType) {
            'opcr.initialized', 'opcr.returned' => route('opcr.workflows.edit', $workflow->id),
            'opcr.committed', 'opcr.submitted' => route('opcr.workflows.evaluate', $workflow->id),
            'opcr.evaluation_started', 'opcr.assessed', 'opcr.ready_for_final_approval' => route('opcr.workflows.review', $workflow->id),
            default => route('opcr.workflows.show', $workflow->id),
        };
    }

    /**
     * Get action text
     */
    private function getActionText(): ?string
    {
        return match ($this->notificationType) {
            'opcr.initialized' => 'View OPCR',
            'opcr.committed' => 'Assess OPCR',
            'opcr.submitted' => 'Review OPCR',
            'opcr.assessed' => 'Approve OPCR',
            'opcr.approved' => 'View Approved OPCR',
            'opcr.returned' => 'Revise OPCR',
            'opcr.evaluation_started' => 'Review Evaluation',
            'opcr.ready_for_final_approval' => 'Finalize OPCR',
            'opcr.overdue' => 'Take Action',
            'opcr.reminder' => 'View OPCR',
            'opcr.daily_summary' => 'View Dashboard',
            default => 'View Details',
        };
    }

    /**
     * Get notification priority
     */
    private function getPriority(): string
    {
        return match ($this->notificationType) {
            'opcr.overdue' => 'high',
            'opcr.returned' => 'medium',
            'opcr.ready_for_final_approval' => 'medium',
            'opcr.approved' => 'low',
            default => 'normal',
        };
    }
}
