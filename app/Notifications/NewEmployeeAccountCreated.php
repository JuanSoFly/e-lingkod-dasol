<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class NewEmployeeAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct($token)
    {
        $this->token = $token;
        $this->afterCommit = true;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $resetUrl = URL::temporarySignedRoute(
            'password.reset',
            now()->addMinutes(60),
            ['token' => $this->token, 'email' => $notifiable->email]
        );

        return (new MailMessage)
            ->subject('Welcome to E-Lingkod Dasol HRIS')
            ->greeting('Welcome to E-Lingkod Dasol!')
            ->line('Your employee account has been created successfully.')
            ->line('To set up your password and activate your account, please click the button below.')
            ->action('Set Your Password', $resetUrl)
            ->line('This link will expire in 60 minutes for security reasons.')
            ->line('If you did not expect to receive this email, please contact your HR administrator.')
            ->salutation('Best regards, HR Team');
    }
}