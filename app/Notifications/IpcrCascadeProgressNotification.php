<?php

namespace App\Notifications;

use App\Models\OPCRWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class IpcrCascadeProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected OPCRWorkflow $workflow,
        protected Collection $ipcrs,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->ipcrs->count();

        return (new MailMessage)
            ->subject('IPCR Cascading Completed for ' . ($this->workflow->title ?? 'an OPCR'))
            ->greeting('Hello ' . $notifiable->name)
            ->line($count . ' IPCR record' . ($count === 1 ? ' has' : 's have') . ' been generated from the OPCR: ' . ($this->workflow->title ?? $this->workflow->id))
            ->line('Office: ' . optional($this->workflow->office)->name)
            ->line('Performance Period: ' . optional($this->workflow->period)->name)
            ->action('Review Cascade Status', url('/admin/ipcr/cascade-status/' . $this->workflow->id))
            ->line('You are receiving this email because you monitor IPCR implementation.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'opcr_workflow_id' => $this->workflow->id,
            'ipcr_count' => $this->ipcrs->count(),
            'office_id' => $this->workflow->office_id,
            'message' => 'IPCR cascading has completed for ' . ($this->workflow->title ?? 'an OPCR workflow') . '.',
        ];
    }
}
