<?php

namespace App\Notifications;

use App\Models\Ipcr;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IpcrSupervisorAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Ipcr $ipcr)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('IPCR Review Required')
            ->greeting('Good day ' . $notifiable->name)
            ->line('An employee under your supervision has received cascaded IPCR targets.')
            ->line('Employee: ' . trim(($this->ipcr->employee->first_name ?? '') . ' ' . ($this->ipcr->employee->last_name ?? '')))
            ->line('Please review the targets and provide feedback once the self-assessment is submitted.')
            ->action('View IPCR', url('/supervisor/ipcr/' . $this->ipcr->id))
            ->line('Thank you for supporting the performance management cycle.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ipcr_id' => $this->ipcr->id,
            'employee_id' => $this->ipcr->employee_id,
            'message' => 'A new IPCR requires your guidance as the immediate supervisor.',
        ];
    }
}
