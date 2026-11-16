<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAccountSetupNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('password.setup', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Set up your LEMU faculty password')
            ->greeting('Welcome to LEMU!')
            ->line('Your faculty account is ready. Set a password to sign in.')
            ->action('Set Password', $url)
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
