<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Voter;
use App\Support\Audit;
use Illuminate\Console\Command;

class PurgeVoterContacts extends Command
{
    protected $signature = 'ballotify:purge-voter-contacts';

    protected $description = 'Erase voter emails and phone numbers for events that closed more than the retention period ago';

    public function handle(): int
    {
        $days = (int) config('ballotify.voter_contact_retention_days');
        if ($days <= 0) {
            $this->info('Retention disabled (VOTER_CONTACT_RETENTION_DAYS=0).');
            return self::SUCCESS;
        }

        $total = 0;
        Event::whereNotNull('ends_at')
            ->where('ends_at', '<', now()->subDays($days))
            ->whereHas('voters', fn ($q) => $q->whereNotNull('email')->orWhereNotNull('phone'))
            ->each(function (Event $event) use (&$total, $days) {
                $count = Voter::where('event_id', $event->id)
                    ->where(fn ($q) => $q->whereNotNull('email')->orWhereNotNull('phone'))
                    ->update(['email' => null, 'phone' => null, 'invite_error' => null]);
                if ($count) {
                    Audit::log($event, 'voters.contacts_erased', "Contact details erased automatically for {$count} voters ({$days} days after voting closed)");
                    $total += $count;
                }
            });

        $this->info("Erased contact details for {$total} voters.");
        return self::SUCCESS;
    }
}
