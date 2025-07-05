<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApplicationSubmitted extends Notification
{
    use Queueable;

    protected $leaveApplication;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveApplication $leaveApplication)
    {
        $this->leaveApplication = $leaveApplication;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->leaveApplication->employee;
        $url = route('leave-applications.show', $this->leaveApplication);

        return (new MailMessage)
                    ->subject('New Leave Application for Review')
                    ->line("A new leave application has been submitted by {$employee->first_name} {$employee->last_name}.")
                    ->line("Leave Type: {$this->leaveApplication->leaveType->name}")
                    ->line("Dates: {$this->leaveApplication->start_date->format('M d, Y')} to {$this->leaveApplication->end_date->format('M d, Y')}")
                    ->action('View Application', $url)
                    ->line('Please review the application at your earliest convenience.');
    }
}