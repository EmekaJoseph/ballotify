<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** "You've been added to the team for <event>." */
class TeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    private const ROLE_TEXT = [
        EventMember::EDITOR => 'edit the ballot and settings, manage voters and see the results',
        EventMember::VOTERS => 'manage the voter list, send codes and see the results',
        EventMember::VIEWER => 'see the event, its activity and the results',
    ];

    public function __construct(
        public Event $event,
        public User $inviter,
        public EventMember $member,
        public bool $hasAccount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->inviter->name} added you to {$this->event->name} on Ballotify");
    }

    public function content(): Content
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        $query = '?email=' . rawurlencode($this->member->email);

        return new Content(view: 'mail.receipt', with: [
            'heading' => 'You’re on the team',
            'title' => $this->event->name,
            'greeting' => 'Hello,',
            'intro' => "{$this->inviter->name} added you as a co-organiser. You can " . self::ROLE_TEXT[$this->member->role] . '.',
            'rows' => [],
            'footnote' => $this->hasAccount
                ? 'Sign in with this email address to find the event in your dashboard.'
                : 'Create a free account with this email address and confirm it: the event will appear in your dashboard.',
            'link' => $this->hasAccount ? "{$frontend}/login{$query}" : "{$frontend}/signup{$query}",
            'linkLabel' => $this->hasAccount ? 'Open Ballotify' : 'Create your account',
        ]);
    }
}
