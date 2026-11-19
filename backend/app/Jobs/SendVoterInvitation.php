<?php

namespace App\Jobs;

use App\Mail\VoterInvitation;
use App\Models\Voter;
use App\Services\Sms\SmsSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Delivers one voter's code by email and/or SMS, then records the outcome
 * (invited_at / reminded_at on success, invite_error on failure) for the
 * organiser to see. $kind is 'invite' or 'reminder'.
 */
class SendVoterInvitation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Delivery failures are recorded on the voter rather than retried blindly. */
    public int $tries = 1;
    public int $timeout = 60;

    /** @param  string[]  $channels  'email' and/or 'sms' */
    public function __construct(public int $voterId, public array $channels, public string $kind = 'invite') {}

    public function handle(SmsSender $sms): void
    {
        $voter = Voter::with('event')->find($this->voterId);
        if (! $voter || $voter->used_at !== null || ! $voter->event) {
            return; // deleted, or already voted: nothing to send
        }

        $event = $voter->event;
        $reminder = $this->kind === 'reminder';
        $voteUrl = rtrim((string) config('app.frontend_url'), '/') . '/vote/' . $event->link_token;
        $errors = [];
        $sent = [];

        if (in_array('email', $this->channels, true) && $voter->email) {
            try {
                Mail::to($voter->email, $voter->name)->send(new VoterInvitation($voter, $event, $voteUrl, $reminder));
                $sent[] = 'email';
            } catch (\Throwable $e) {
                report($e);
                $errors[] = 'Email: ' . $e->getMessage();
            }
        }

        if (in_array('sms', $this->channels, true) && $voter->phone) {
            try {
                $sms->send($voter->phone, self::smsText($event->name, $voter->code, $voteUrl, $reminder, $event->ends_at));
                $sent[] = 'sms';
            } catch (\Throwable $e) {
                report($e);
                $errors[] = 'SMS: ' . $e->getMessage();
            }
        }

        $update = ['invite_error' => $errors ? Str::limit(implode(' | ', $errors), 480) : null];
        if ($sent) {
            if ($reminder) {
                $update['reminded_at'] = now();
            } else {
                $update['invited_at'] = now();
                $update['invited_via'] = $sent[0];
            }
        }
        $voter->forceFill($update)->save();
    }

    /**
     * Called by the queue worker if the job dies outside handle()'s own error
     * handling (timeout, worker crash). Records it so the organiser sees it.
     */
    public function failed(?\Throwable $e): void
    {
        try {
            Voter::whereKey($this->voterId)->update([
                'invite_error' => Str::limit('Delivery failed: ' . ($e?->getMessage() ?: 'unknown error'), 480),
            ]);
        } catch (\Throwable) {
            // Never let failure bookkeeping throw.
        }
    }

    /** Kept short so it fits in one or two SMS segments. */
    public static function smsText(string $eventName, string $code, string $voteUrl, bool $reminder = false, $endsAt = null): string
    {
        $name = Str::limit($eventName, 40);
        if ($reminder) {
            $when = $endsAt ? ' Voting closes ' . $endsAt->format('j M, g:ia') . '.' : '';
            return "Reminder: you haven't voted in {$name} yet.{$when} Code {$code}: {$voteUrl}";
        }
        return "{$name}: your voting code is {$code}. Vote at {$voteUrl} (code works once).";
    }
}
