<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PDSExportReady extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $exportData;
    public $downloadUrl;
    public $expiresAt;

    /**
     * Create a new message instance.
     *
     * @param mixed $user
     * @param array $exportData
     * @return void
     */
    public function __construct($user, array $exportData)
    {
        $this->user = $user;
        $this->exportData = $exportData;

        // Generate signed download URL that expires in 24 hours
        $this->downloadUrl = URL::temporarySignedRoute(
            'pds.export.download',
            now()->addHours(24),
            ['exportId' => $exportData['id']]
        );

        $this->expiresAt = now()->addHours(24);
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'PDS Export Ready for Download - E-Lingkod Dasol HRIS',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.pds-export-ready',
            with: [
                'userName' => $this->user->name ?? $this->user->first_name ?? 'User',
                'employeeCount' => $this->exportData['employee_count'] ?? 0,
                'fileName' => $this->exportData['file_name'] ?? 'pds_export.xlsx',
                'fileSize' => $this->formatFileSize($this->exportData['file_size'] ?? 0),
                'downloadUrl' => $this->downloadUrl,
                'expiresAt' => $this->expiresAt->format('F j, Y \a\t g:i A'),
                'exportDate' => now()->format('F j, Y \a\t g:i A'),
                'supportEmail' => config('mail.support_email', 'hrmo@dasol.gov.ph'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    /**
     * Format file size in human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' bytes';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('PDS Export Ready for Download - E-Lingkod Dasol HRIS')
            ->view('emails.pds-export-ready')
            ->with([
                'userName' => $this->user->name ?? $this->user->first_name ?? 'User',
                'employeeCount' => $this->exportData['employee_count'] ?? 0,
                'fileName' => $this->exportData['file_name'] ?? 'pds_export.xlsx',
                'fileSize' => $this->formatFileSize($this->exportData['file_size'] ?? 0),
                'downloadUrl' => $this->downloadUrl,
                'expiresAt' => $this->expiresAt->format('F j, Y \a\t g:i A'),
                'exportDate' => now()->format('F j, Y \a\t g:i A'),
                'supportEmail' => config('mail.support_email', 'hrmo2411@gmail.com'),
            ]);
    }
}
