<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Password-reset email whose button opens the website’s reset page. */
class ResetPasswordLink extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/') . '/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Reset your ' . config('app.name') . ' password')
            ->line('We received a request to reset the password for your account.')
            ->action('Choose a new password', $url)
            ->line("This link expires in {$minutes} minutes.")
            ->line('If you did not ask for this, no action is needed. Your password stays the same.');
    }
}
