<?php

namespace App\Jobs;

use App\Mail\VoterInvitation;
use App\Models\Voter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Emails one voter their code, then records the outcome (invited_at /
 * reminded_at on success, invite_error on failure) for the organiser to see.
 * $kind is 'invite' or 'reminder'.
 */
class SendVoterInvitation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Delivery failures are recorded on the voter rather than retried blindly. */
    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public int $voterId, public string $kind = 'invite') {}

    public function handle(): void
    {
        $voter = Voter::with('event')->find($this->voterId);
        if (! $voter || $voter->used_at !== null || ! $voter->event || ! $voter->email) {
            return; // deleted, already voted, or no address any more: nothing to send
        }

        $event = $voter->event;
        $reminder = $this->kind === 'reminder';
        $voteUrl = rtrim((string) config('app.frontend_url'), '/') . '/vote/' . $event->link_token;

        try {
            Mail::to($voter->email, $voter->name)->send(new VoterInvitation($voter, $event, $voteUrl, $reminder));
        } catch (\Throwable $e) {
            report($e);
            $voter->forceFill(['invite_error' => Str::limit('Email: ' . $e->getMessage(), 480)])->save();
            return;
        }

        $voter->forceFill($reminder
            ? ['invite_error' => null, 'reminded_at' => now()]
            : ['invite_error' => null, 'invited_at' => now(), 'invited_via' => 'email'])->save();
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
}
