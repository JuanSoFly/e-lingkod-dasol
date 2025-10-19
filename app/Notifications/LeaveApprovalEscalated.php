<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use App\Models\LeaveApplicationWorkflowStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApprovalEscalated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private LeaveApplication $application,
        private LeaveApplicationWorkflowStep $step
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('URGENT: Leave Application Approval Escalated')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line('A leave application has been escalated to you due to delay in approval.')
            ->line('Employee: ' . $this->application->employee->full_name)
            ->line('Leave Type: ' . $this->application->leaveType->name)
            ->line('Duration: ' . $this->application->start_date->format('M d, Y') . ' to ' . $this->application->end_date->format('M d, Y'))
            ->line('Days Requested: ' . $this->application->days_requested)
            ->line('Step: ' . ($this->step->leaveWorkflowStep->step_name ?? "Step {$this->step->step_order}"))
            ->line('Original Approver: ' . ($this->step->leaveWorkflowStep->approvers[0]['name'] ?? 'Unknown'))
            ->line('Please take immediate action on this escalated request.')
            ->action('Review Application', url('/approvals/' . $this->application->id . '/workflow'))
            ->line('This requires your urgent attention.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_approval_escalated',
            'application_id' => $this->application->id,
            'step_id' => $this->step->id,
            'employee_name' => $this->application->employee->full_name,
            'leave_type' => $this->application->leaveType->name,
            'start_date' => $this->application->start_date->format('Y-m-d'),
            'end_date' => $this->application->end_date->format('Y-m-d'),
            'days_requested' => $this->application->days_requested,
            'escalated_at' => $this->step->escalated_at,
            'urgency' => 'high',
        ];
    }
}