<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\VoterController;
use App\Models\Event;
use App\Models\Voter;
use App\Support\Plans;
use Illuminate\Console\Command;

/**
 * Sends each event's scheduled reminder ("you haven't voted yet") once its
 * time arrives. Runs every minute from the scheduler.
 */
class SendScheduledReminders extends Command
{
    protected $signature = 'ballotify:send-reminders';

    protected $description = 'Send scheduled reminders to voters who have not voted yet';

    public function handle(): int
    {
        $due = Event::whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->where('reminder_at', '<=', now())
            ->where('voting_mode', Event::MODE_CODES)
            ->get();

        foreach ($due as $event) {
            // Claim it first so a slow run can't send twice.
            $claimed = Event::whereKey($event->id)->whereNull('reminder_sent_at')->update(['reminder_sent_at' => now()]);
            if (! $claimed) {
                continue;
            }
            if (! $event->isOpen() || ! Plans::isPro($event)) {
                $this->line("Skipped “{$event->name}” (not open, or no longer Pro).");
                continue;
            }
            $counts = VoterController::sendMessages(
                $event,
                Voter::where('event_id', $event->id)->whereNull('used_at'),
                $event->reminder_channel ?: 'email',
                'reminder',
            );
            $this->info("Reminder for “{$event->name}”: {$counts['queued']} queued.");
        }

        return self::SUCCESS;
    }
}
