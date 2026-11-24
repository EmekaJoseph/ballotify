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
            'title' => $this->payment->kind === Payment::KIND_SMS ? 'SMS credits' : 'Ballotify ' . $this->tierName(),
            'greeting' => 'Hello' . ($this->payment->user?->name ? ' ' . $this->payment->user->name : '') . ',',
            'intro' => $this->payment->kind === Payment::KIND_SMS
                ? 'Thank you for your payment. ' . number_format($this->payment->sms_credits) . ' SMS credits were added to your account.'
                : 'Thank you for your payment. Your event has been upgraded to ' . $this->tierName() . '.',
            'rows' => [
                'Item' => $this->payment->kind === Payment::KIND_SMS
                    ? number_format($this->payment->sms_credits) . ' SMS credits'
                    : $this->tierName() . ' upgrade: ' . ($this->payment->event_name ?: 'event'),
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

    private function tierName(): string
    {
        return \App\Support\Plans::tiers()[$this->payment->tier ?? 'standard']['name'] ?? 'Pro';
    }
}
