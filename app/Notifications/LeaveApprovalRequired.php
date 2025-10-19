<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use App\Models\LeaveApplicationWorkflowStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApprovalRequired extends Notification
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
            ->subject('Leave Application Approval Required')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line('A leave application requires your approval.')
            ->line('Employee: ' . $this->application->employee->full_name)
            ->line('Leave Type: ' . $this->application->leaveType->name)
            ->line('Duration: ' . $this->application->start_date->format('M d, Y') . ' to ' . $this->application->end_date->format('M d, Y'))
            ->line('Days Requested: ' . $this->application->days_requested)
            ->line('Reason: ' . $this->application->reason)
            ->action('Review Application', url('/approvals/' . $this->application->id . '/workflow'))
            ->line('Please review and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_approval_required',
            'application_id' => $this->application->id,
            'step_id' => $this->step->id,
            'employee_name' => $this->application->employee->full_name,
            'leave_type' => $this->application->leaveType->name,
            'start_date' => $this->application->start_date->format('Y-m-d'),
            'end_date' => $this->application->end_date->format('Y-m-d'),
            'days_requested' => $this->application->days_requested,
            'step_order' => $this->step->step_order,
        ];
    }
}
