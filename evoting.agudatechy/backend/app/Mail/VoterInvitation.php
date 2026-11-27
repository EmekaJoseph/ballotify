<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Voter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A voter's code: the first invitation, or a reminder to vote. */
class VoterInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Voter $voter,
        public Event $event,
        public string $voteUrl,
        public bool $reminder = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->reminder
            ? "Reminder: you haven't voted in {$this->event->name} yet"
            : "Your voting code for {$this->event->name}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.voter-invitation',
            text: 'mail.voter-invitation-text',
        );
    }
}
