<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Free vs Pro, per event.
 *
 *  Free: unlimited drafts, but only a few LIVE events per account (an event
 *        goes live when it gets its first voter), a small voter cap, no
 *        email/SMS, "Powered by Ballotify" on the ballot.
 *  Pro:  unlocked for one event by a Paystack payment, or by the platform
 *        admin (per event, or for a whole account). Larger voter cap,
 *        email/SMS and reminders, custom branding, co-organisers.
 *
 * Paid public voting events earn the platform a fee per vote instead, so they
 * never count towards the free live-event limit.
 *
 * Prices and limits come from config defaults, overridden by the admin's saved
 * settings. With billing disabled every event behaves as Pro.
 */
class Plans
{
    private const CACHE_KEY = 'ballotify.plan-settings';

    public const KEYS = [
        'pro_price_naira', 'free_max_active_events', 'free_max_voters', 'pro_max_voters', 'paid_vote_fee_percent',
    ];

    /** Co-organisers per Pro event. */
    public const MAX_MEMBERS = 10;

    public static function enabled(): bool
    {
        return (bool) config('ballotify.billing.enabled', true);
    }

    /** Current prices and limits (admin overrides on top of config defaults). */
    public static function settings(): array
    {
        $defaults = config('ballotify.billing.defaults');
        $saved = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return Setting::whereIn('key', self::KEYS)->pluck('value', 'key')->all();
            } catch (\Throwable) {
                return []; // e.g. before migrations have run
            }
        });

        $out = [];
        foreach (self::KEYS as $key) {
            $out[$key] = (int) ($saved[$key] ?? $defaults[$key] ?? 0);
        }
        return $out;
    }

    public static function update(array $values): array
    {
        foreach (array_intersect_key($values, array_flip(self::KEYS)) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (int) $value]);
        }
        Cache::forget(self::CACHE_KEY);

        return self::settings();
    }

    public static function priceKobo(): int
    {
        return self::settings()['pro_price_naira'] * 100;
    }

    /** Platform's share of a paid vote, in kobo. */
    public static function voteFeeKobo(int $amountKobo): int
    {
        return (int) round($amountKobo * self::settings()['paid_vote_fee_percent'] / 100);
    }

    /** The whole account is Pro: billing off, the platform admin, or granted by the admin. */
    public static function accountIsPro(?User $user): bool
    {
        return ! self::enabled() || ($user && ($user->is_admin || $user->pro_account_at !== null));
    }

    public static function isPro(Event $event): bool
    {
        return $event->plan === 'pro' || self::accountIsPro($event->user);
    }

    /** What this event may do. Sent to the dashboard so it can show limits and prompts. */
    public static function limits(Event $event): array
    {
        $s = self::settings();
        $pro = self::isPro($event);

        return [
            'plan' => $pro ? 'pro' : 'free',
            // Billing off = no cap at all; otherwise the plan's cap.
            'max_voters' => ! self::enabled() ? null : ($pro ? $s['pro_max_voters'] : $s['free_max_voters']),
            'messaging' => $pro,
            'branding' => ! $pro,          // "Powered by Ballotify" shown
            'custom_branding' => $pro,     // own logo and colour
            'team' => $pro,                // co-organisers
            'max_members' => $pro ? self::MAX_MEMBERS : 0,
            // Pro through the account (admin grant) rather than a payment on this event.
            'via_account' => $pro && $event->plan !== 'pro',
        ];
    }

    /** Adds `limits` to an event for the dashboard, without serialising its owner. */
    public static function withLimits(Event $event): Event
    {
        $hadUser = $event->relationLoaded('user');
        $event->setAttribute('limits', self::limits($event));
        if (! $hadUser) {
            $event->unsetRelation('user');
        }
        return $event;
    }

    // ------------------------------------------------------------------ live-event allowance

    /** Free code-voting events of this account that have voters and haven't closed. */
    private static function liveFreeEvents(User $user, ?int $exceptEventId = null)
    {
        return Event::where('user_id', $user->id)
            ->where('plan', 'free')
            ->where('voting_mode', Event::MODE_CODES)
            ->notClosed()
            ->whereHas('voters')
            ->when($exceptEventId, fn ($q) => $q->whereKeyNot($exceptEventId))
            ->orderBy('id')
            ->get(['id', 'name', 'ends_at']);
    }

    /**
     * How many live events this account may run, and which ones use the allowance.
     * Drafts (no voters yet) are always allowed.
     *
     * @return array{can_create: bool, can_go_live: bool, max: int|null, open_events: list<array{id: int, name: string, ends_at: mixed}>}
     */
    public static function eventQuota(User $user): array
    {
        if (self::accountIsPro($user)) {
            return ['can_create' => true, 'can_go_live' => true, 'max' => null, 'open_events' => []];
        }
        $max = self::settings()['free_max_active_events'];
        $live = self::liveFreeEvents($user);

        return [
            'can_create' => true,
            'can_go_live' => $live->count() < $max,
            'max' => $max,
            'open_events' => $live->map(fn (Event $e) => ['id' => $e->id, 'name' => $e->name, 'ends_at' => $e->ends_at])->all(),
        ];
    }

    /**
     * Adding the first voters makes a free event live. Refused when the owner
     * already runs the maximum number of live free events.
     */
    public static function ensureCanGoLive(Event $event): void
    {
        if ($event->isPaid() || self::isPro($event) || $event->voters()->exists()) {
            return;
        }
        $max = self::settings()['free_max_active_events'];
        if (self::liveFreeEvents($event->user, $event->id)->count() >= $max) {
            self::deny(
                $max === 1
                    ? 'Free accounts can run one live event at a time. Upgrade this event (or your other one) to Pro, or close voting on the other event, to add voters here.'
                    : "Free accounts can run {$max} live events at a time. Upgrade one to Pro, or close one, to add voters here.",
                'events',
            );
        }
    }

    /** Adding $adding voters must stay within the event's voter cap. */
    public static function ensureVoterCapacity(Event $event, int $adding): void
    {
        self::ensureCanGoLive($event);

        $max = self::limits($event)['max_voters'];
        if ($max === null) {
            return;
        }
        $current = $event->voters()->count();
        if ($current + $adding <= $max) {
            return;
        }

        $room = max(0, $max - $current);
        $pro = self::isPro($event);
        $message = $pro
            ? "Pro events can have up to {$max} voters"
            : "Free events can have up to {$max} voters";
        $message .= $room > 0 ? " (room for {$room} more)." : ' and this event is full.';
        if (! $pro) {
            $message .= ' Upgrade this event to Pro for up to ' . number_format(self::settings()['pro_max_voters']) . ' voters.';
        }

        self::deny($message, 'voters', ! $pro);
    }

    public static function ensureCanMessage(Event $event): void
    {
        if (! self::isPro($event)) {
            self::deny('Sending codes and reminders by email or SMS is a Pro feature. Upgrade this event, or share codes yourself (WhatsApp, CSV or printed slips).', 'messaging');
        }
    }

    public static function ensureCustomBranding(Event $event): void
    {
        if (! self::isPro($event)) {
            self::deny('Your own logo and colour on the ballot is a Pro feature. Upgrade this event to use it.', 'branding');
        }
    }

    public static function ensureTeam(Event $event, int $currentMembers): void
    {
        if (! self::isPro($event)) {
            self::deny('Co-organisers are a Pro feature. Upgrade this event to invite your committee.', 'team');
        }
        if ($currentMembers >= self::MAX_MEMBERS) {
            self::deny('An event can have up to ' . self::MAX_MEMBERS . ' co-organisers.', 'team', false);
        }
    }

    private static function deny(string $message, string $reason, bool $upgrade = true): never
    {
        abort(response()->json([
            'message' => $message,
            'plan_limit' => $reason,
            'upgrade' => $upgrade,
        ], 402));
    }
}
