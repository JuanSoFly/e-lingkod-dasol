<?php

namespace App\Notifications;

use App\Models\Ipcr;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class IpcrTargetsGeneratedNotification extends Notification implements ShouldQueue
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
            ->subject('Your IPCR Targets Are Ready')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your Individual Performance Commitment and Review (IPCR) has been generated from the latest OPCR.')
            ->line('Please review the cascaded targets and provide your self-assessment according to the schedule.')
            ->action('Open IPCR', url('/employee/ipcr/' . $this->ipcr->id))
            ->line('Total Weight: ' . number_format($this->ipcr->total_weight, 2) . '%')
            ->line('Thank you for keeping your performance commitments up to date.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ipcr_id' => $this->ipcr->id,
            'period_id' => $this->ipcr->period_id,
            'status' => $this->ipcr->status,
            'total_weight' => $this->ipcr->total_weight,
            'message' => 'IPCR targets have been generated from the approved OPCR.',
        ];
    }
}
