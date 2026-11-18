<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Voter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoterInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Voter $voter,
        public Event $event,
        public string $voteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your voting code for {$this->event->name}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.voter-invitation',
            text: 'mail.voter-invitation-text',
        );
    }
}
