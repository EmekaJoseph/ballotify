<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\VoterController;
use App\Models\Event;
use App\Models\Voter;
use Illuminate\Console\Command;

/**
 * Emails each event's scheduled reminder ("you haven't voted yet") once its
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
            ->get();

        foreach ($due as $event) {
            // Claim it first so a slow run can't send twice.
            $claimed = Event::whereKey($event->id)->whereNull('reminder_sent_at')->update(['reminder_sent_at' => now()]);
            if (! $claimed) {
                continue;
            }
            if (! $event->isOpen()) {
                $this->line("Skipped “{$event->name}” (voting is not open).");
                continue;
            }
            $counts = VoterController::sendMessages($event, Voter::where('event_id', $event->id)->whereNull('used_at'), 'reminder');
            $this->info("Reminder for “{$event->name}”: {$counts['queued']} queued.");
        }

        return self::SUCCESS;
    }
}
