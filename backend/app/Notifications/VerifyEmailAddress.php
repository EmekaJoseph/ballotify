<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Email-verification message whose button opens the Ballotify frontend
 * (/verify-email?…); the page then calls the signed API URL to confirm.
 */
class VerifyEmailAddress extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        // Relative signature (path + query only), validated with the "signed:relative"
        // middleware, so it works whatever host the frontend uses to reach the API.
        $relative = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60 * 24),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
            absolute: false,
        );

        return rtrim((string) config('app.frontend_url'), '/') . '/verify-email?link=' . urlencode($relative);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your email for ' . config('app.name'))
            ->greeting('Welcome to ' . config('app.name') . '!')
            ->line('Please confirm your email address to start sending voting codes to your voters.')
            ->action('Confirm email', $this->verificationUrl($notifiable))
            ->line('This link expires in 24 hours. If you did not create an account, you can ignore this email.');
    }
}
