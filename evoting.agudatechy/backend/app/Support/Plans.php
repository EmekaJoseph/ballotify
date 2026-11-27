<?php

namespace App\Support;

use App\Models\Event;

/**
 * E-voting has no plans, prices or SMS: every event of an approved account can have
 * any number of voters and co-organisers, and codes go out by email or WhatsApp.
 *
 * This small class keeps the checks the rest of the code asks ("may this event do X?")
 * in one place, so a limit can be added back here later without touching callers.
 */
class Plans
{
    /** Every event has everything (there is one kind of account once approved). */
    public static function isPro(Event $event): bool
    {
        return true;
    }

    /** What an event may do, as the dashboard reads it. */
    public static function limits(Event $event): array
    {
        return [
            'plan' => 'unlimited',
            'max_voters' => null,        // no cap
            'max_members' => null,       // no cap on co-organisers
            'messaging' => true,         // email invitations and reminders
            'sms' => null,               // no SMS here
            'branding' => false,         // no "Powered by" line on the ballot
            'team' => true,
        ];
    }

    /** Adds `limits` to an event for the dashboard. */
    public static function withLimits(Event $event): Event
    {
        $event->setAttribute('limits', self::limits($event));
        return $event;
    }

    /** No voter cap. */
    public static function ensureVoterCapacity(Event $event, int $adding): void
    {
    }

    /** Email invitations and reminders are always available. */
    public static function ensureCanMessage(Event $event): void
    {
    }

    /** No cap on co-organisers. */
    public static function ensureTeam(Event $event, int $currentMembers): void
    {
    }

    // ------------------------------------------------------------------ SMS: none here

    /** No SMS are ever reserved (invitations are sent by email). */
    public static function reserveSms(Event $event, int $count, bool $strict = true): int
    {
        return 0;
    }

    public static function smsUnlimited(Event $event): bool
    {
        return false;
    }

    public static function refundSms(int $eventId, int $count = 1): void
    {
    }
}
