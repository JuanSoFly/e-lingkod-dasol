<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApplicationActioned extends Notification
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
        $status = ucfirst($this->leaveApplication->status);
        $url = route('leave-applications.show', $this->leaveApplication);

        return (new MailMessage)
                    ->subject("Your Leave Application has been {$status}")
                    ->line("Your leave application for {$this->leaveApplication->leaveType->name} from {$this->leaveApplication->start_date->format('M d, Y')} to {$this->leaveApplication->end_date->format('M d, Y')} has been {$status}.")
                    ->lineIf($this->leaveApplication->remarks, "Remarks: {$this->leaveApplication->remarks}")
                    ->action('View Application Details', $url)
                    ->line('Thank you for using our application!');
    }
}