<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Receipt for an organiser's Pro upgrade. */
class PaymentReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Ballotify receipt {$this->payment->reference}");
    }

    public function content(): Content
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        return new Content(view: 'mail.receipt', with: [
            'heading' => 'Payment receipt',
            'title' => 'Ballotify Pro',
            'greeting' => 'Hello' . ($this->payment->user?->name ? ' ' . $this->payment->user->name : '') . ',',
            'intro' => 'Thank you for your payment. Your event has been upgraded to Pro.',
            'rows' => [
                'Item' => 'Pro upgrade: ' . ($this->payment->event_name ?: 'event'),
                'Amount' => '₦' . number_format($this->payment->amount_kobo / 100, 2),
                'Paid on' => $this->payment->paid_at?->timezone(config('app.timezone'))->format('j M Y, g:i A'),
                'Method' => $this->payment->channel ? ucfirst(str_replace('_', ' ', $this->payment->channel)) : 'Paystack',
                'Reference' => $this->payment->reference,
            ],
            'footnote' => 'You can print or download this receipt any time from your account page.',
            'link' => "{$frontend}/receipt/{$this->payment->reference}",
            'linkLabel' => 'View printable receipt',
        ]);
    }
}
