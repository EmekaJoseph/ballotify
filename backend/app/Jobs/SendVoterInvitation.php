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

    /**
     * @param  string[]  $channels  'email' and/or 'sms'
     * @param  bool  $smsCharged  an SMS was paid for (event allowance / credits): refunded if not delivered
     */
    public function __construct(public int $voterId, public array $channels, public string $kind = 'invite', public bool $smsCharged = false) {}

    public function handle(SmsSender $sms): void
    {
        $voter = Voter::with('event')->find($this->voterId);
        if (! $voter || $voter->used_at !== null || ! $voter->event) {
            $this->refundSms($voter?->event_id);
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
                $this->refundSms($event->id);
            }
        } elseif (in_array('sms', $this->channels, true)) {
            $this->refundSms($event->id); // phone removed since it was queued
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
            $this->refundSms(Voter::whereKey($this->voterId)->value('event_id'));
            Voter::whereKey($this->voterId)->update([
                'invite_error' => Str::limit('Delivery failed: ' . ($e?->getMessage() ?: 'unknown error'), 480),
            ]);
        } catch (\Throwable) {
            // Never let failure bookkeeping throw.
        }
    }

    /** Give a paid-for SMS back to the event's allowance (only once per job). */
    private function refundSms(?int $eventId): void
    {
        if ($this->smsCharged && $eventId) {
            $this->smsCharged = false;
            \App\Support\Plans::refundSms($eventId);
        }
    }

    /**
     * Fits in ONE SMS (160 characters): a second segment would cost double.
     * Optional parts are dropped, then the event name shortened, until it fits.
     */
    public static function smsText(string $eventName, string $code, string $voteUrl, bool $reminder = false, $endsAt = null): string
    {
        $build = function (int $nameLen, bool $extras) use ($eventName, $code, $voteUrl, $reminder, $endsAt) {
            $name = Str::limit($eventName, $nameLen, '');
            if ($reminder) {
                $when = $extras && $endsAt ? ' Closes ' . $endsAt->format('j M g:ia') . '.' : '';
                return "Reminder: vote in {$name}.{$when} Code {$code}: {$voteUrl}";
            }
            return "{$name}: your voting code is {$code}." . ($extras ? ' Vote at' : '') . " {$voteUrl}";
        };
        foreach ([[40, true], [40, false], [24, false], [12, false]] as [$len, $extras]) {
            $text = $build($len, $extras);
            if (mb_strlen($text) <= 160) {
                return $text;
            }
        }
        return $build(0, false);
    }
}
