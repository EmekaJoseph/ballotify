<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\VotePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to someone who paid for votes, once the votes are counted. */
class VotePurchaseReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VotePurchase $purchase, public Event $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Receipt: {$this->purchase->quantity} vote(s) in {$this->event->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.receipt', with: [
            'heading' => 'Your votes were counted',
            'title' => $this->event->name,
            'greeting' => 'Hello' . ($this->purchase->name ? ' ' . $this->purchase->name : '') . ',',
            'intro' => "Thank you for voting. Your payment was received and your votes were added.",
            'rows' => [
                'Candidate' => $this->purchase->candidate_name,
                'Votes' => number_format($this->purchase->quantity),
                'Amount paid' => '₦' . number_format($this->purchase->amount_kobo / 100, 2),
                'Paid on' => $this->purchase->paid_at?->timezone(config('app.timezone'))->format('j M Y, g:i A'),
                'Reference' => $this->purchase->reference,
            ],
            'footnote' => 'Keep this reference: you can use it on the results page to check that your votes were counted.',
            'link' => rtrim((string) config('app.frontend_url'), '/') . "/event/{$this->event->link_token}/results",
            'linkLabel' => 'See the results',
        ]);
    }
}
