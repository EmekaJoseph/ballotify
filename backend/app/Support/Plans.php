<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Free vs Pro (in three sizes), per event, plus prepaid SMS credits.
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
        'pro_small_price_naira', 'pro_small_max_voters', 'pro_large_price_naira', 'pro_large_max_voters', 'sms_price_naira',
    ];

    /** Pro sizes sold at checkout, smallest first. */
    public const TIERS = ['small', 'standard', 'large'];

    /** No voter or co-organiser cap. Never sold at checkout: only the platform admin sets it. */
    public const UNLIMITED = 'unlimited';

    /** Every size, smallest first (what the platform admin can assign). */
    public const ALL_TIERS = ['small', 'standard', 'large', self::UNLIMITED];

    /** A size's details, Unlimited included (no price; SMS allowance as Large unless set per event). */
    public static function tierInfo(string $tier): array
    {
        if ($tier === self::UNLIMITED) {
            return ['key' => self::UNLIMITED, 'name' => 'Pro Unlimited', 'price_naira' => null, 'max_voters' => null,
                'sms_included' => self::tiers()['large']['sms_included']];
        }
        return self::tiers()[$tier] ?? self::tiers()['large'];
    }

    /** Position in ALL_TIERS (-1 for none), to compare sizes. */
    public static function tierRank(?string $tier): int
    {
        $i = $tier === null ? false : array_search($tier, self::ALL_TIERS, true);
        return $i === false ? -1 : $i;
    }

    /** The bigger of two sizes. */
    private static function biggerTier(?string $a, ?string $b): ?string
    {
        return self::tierRank($a) >= self::tierRank($b) ? $a : $b;
    }

    /** The size an admin-granted account gives every event (the platform owner: Unlimited). */
    public static function accountTier(?User $user): ?string
    {
        if (! self::accountIsPro($user)) {
            return null;
        }
        if (! self::enabled() || $user?->is_admin) {
            return self::UNLIMITED;
        }
        return in_array($user?->pro_account_tier, self::ALL_TIERS, true) ? $user->pro_account_tier : 'large';
    }

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

    /**
     * The Pro sizes: name, price, voter cap and SMS included (1 per voter).
     *
     * @return array<string, array{key: string, name: string, price_naira: int, max_voters: int, sms_included: int}>
     */
    public static function tiers(): array
    {
        $s = self::settings();
        $tier = fn (string $key, string $name, int $price, int $voters) => [
            'key' => $key, 'name' => $name, 'price_naira' => $price, 'max_voters' => $voters, 'sms_included' => $voters,
        ];
        return [
            'small' => $tier('small', 'Pro Small', $s['pro_small_price_naira'], $s['pro_small_max_voters']),
            'standard' => $tier('standard', 'Pro Medium', $s['pro_price_naira'], $s['pro_max_voters']),
            'large' => $tier('large', 'Pro Large', $s['pro_large_price_naira'], $s['pro_large_max_voters']),
        ];
    }

    /**
     * The event's Pro size, or null on Free: the bigger of its own size (paid, or set by
     * the admin) and its account's (admin grant; Large unless the admin chose another).
     */
    public static function tierOf(Event $event): ?string
    {
        if (! self::isPro($event)) {
            return null;
        }
        $own = $event->plan === 'pro'
            ? (in_array($event->pro_tier, self::ALL_TIERS, true) ? $event->pro_tier : 'large') // older Pro events: Large
            : null;
        return self::biggerTier($own, self::accountTier($event->user));
    }

    /** Price to move an event to $tier: the full price, or the difference from its current size. */
    public static function upgradePriceNaira(Event $event, string $tier): int
    {
        $tiers = self::tiers();
        $current = $event->plan === 'pro' ? self::tierOf($event) : null;
        return max(0, $tiers[$tier]['price_naira'] - ($current ? (int) self::tierInfo($current)['price_naira'] : 0));
    }

    // ------------------------------------------------------------------ SMS

    /** SMS are free of charge: billing off, or the platform owner's own events. */
    public static function smsUnlimited(Event $event): bool
    {
        return ! self::enabled() || (bool) $event->user?->is_admin;
    }

    /** SMS included with this event (paid sizes record it; admin-granted Pro gets its size's allowance). */
    public static function smsIncluded(Event $event): int
    {
        if (! self::isPro($event)) {
            return 0;
        }
        return (int) $event->sms_included > 0 ? (int) $event->sms_included : self::tierInfo(self::tierOf($event))['sms_included'];
    }

    /** @return array{included: int, used: int, left: int, credits: int, unlimited: bool, price_naira: int} */
    public static function smsStatus(Event $event): array
    {
        $included = self::smsIncluded($event);
        $used = (int) $event->sms_used;
        return [
            'included' => $included,
            'used' => $used,
            'left' => max(0, $included - $used),
            'credits' => (int) ($event->user?->sms_credits ?? 0),
            'unlimited' => self::smsUnlimited($event),
            'price_naira' => self::settings()['sms_price_naira'],
        ];
    }

    /**
     * Take $count SMS for this event: first from what the event includes, then from
     * the owner's prepaid credits. Strict: refuses (402) when there aren't enough.
     * Not strict: takes nothing and returns 0 instead (scheduled jobs fall back to email).
     */
    public static function reserveSms(Event $event, int $count, bool $strict = true): int
    {
        if ($count <= 0 || self::smsUnlimited($event)) {
            return $count;
        }
        return \Illuminate\Support\Facades\DB::transaction(function () use ($event, $count, $strict) {
            $e = Event::whereKey($event->id)->lockForUpdate()->first();
            $owner = User::whereKey($e->user_id)->lockForUpdate()->first();
            $e->setRelation('user', $owner);
            $left = max(0, self::smsIncluded($e) - (int) $e->sms_used);
            $fromEvent = min($count, $left);
            $fromCredits = $count - $fromEvent;
            $credits = (int) $owner->sms_credits;
            if ($fromCredits > $credits) {
                if (! $strict) {
                    return 0;
                }
                $short = $fromCredits - $credits;
                self::deny(
                    'This needs ' . number_format($count) . ' SMS, but only ' . number_format($left + $credits) . ' are available ('
                    . number_format($left) . ' left with this event + ' . number_format($credits) . ' SMS credits). '
                    . 'Buy at least ' . number_format($short) . ' more SMS credits, or send by email only.',
                    'sms', false, ['sms_needed' => $count, 'sms_available' => $left + $credits, 'sms_short' => $short],
                );
            }
            if ($fromEvent > 0) {
                $e->increment('sms_used', $fromEvent);
            }
            if ($fromCredits > 0) {
                $owner->decrement('sms_credits', $fromCredits);
            }
            return $count;
        });
    }

    /** An SMS that was paid for but not delivered goes back to the event's allowance. */
    public static function refundSms(int $eventId, int $count = 1): void
    {
        Event::whereKey($eventId)->where('sms_used', '>=', $count)->decrement('sms_used', $count);
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

        $tier = self::tierOf($event);

        return [
            'plan' => $pro ? 'pro' : 'free',
            'tier' => $tier,
            'tier_name' => $tier ? self::tierInfo($tier)['name'] : null,
            // Billing off or Pro Unlimited = no cap at all; otherwise the plan's (or Pro size's) cap.
            'max_voters' => ! self::enabled() ? null : ($pro ? self::tierInfo($tier)['max_voters'] : $s['free_max_voters']),
            'sms' => $pro ? self::smsStatus($event) : null,
            'messaging' => $pro,
            'branding' => ! $pro,          // "Powered by Ballotify" shown
            'custom_branding' => $pro,     // own logo and colour
            'team' => $pro,                // co-organisers
            'meeting_rules' => $pro,       // weighted votes, proxies, quorum
            // null = no cap (Pro Unlimited).
            'max_members' => $pro ? ($tier === self::UNLIMITED ? null : self::MAX_MEMBERS) : 0,
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
        $tier = self::tierOf($event);
        $tiers = self::tiers();
        $message = ($tier ? $tiers[$tier]['name'] : 'Free') . ' events can have up to ' . number_format($max) . ' voters';
        $message .= $room > 0 ? ' (room for ' . number_format($room) . ' more).' : ' and this event is full.';
        // The next size up that fits, if any.
        $bigger = collect($tiers)->first(fn ($t) => $t['max_voters'] >= $current + $adding && $t['max_voters'] > $max);
        $canGrow = $tier !== 'large' && ! ($tier && self::accountIsPro($event->user));
        if ($canGrow) {
            $message .= $bigger
                ? ' Upgrade this event to ' . $bigger['name'] . ' for up to ' . number_format($bigger['max_voters']) . ' voters.'
                : ' Upgrade this event to Pro Large for up to ' . number_format($tiers['large']['max_voters']) . ' voters.';
        }

        self::deny($message, 'voters', $canGrow);
    }

    public static function ensureCanMessage(Event $event): void
    {
        if (! self::isPro($event)) {
            self::deny('Sending codes and reminders by email or SMS is a Pro feature. Upgrade this event, or share codes yourself (WhatsApp, CSV or printed slips).', 'messaging');
        }
    }

    /** Weighted votes, proxies and quorum (AGM-style meeting rules). */
    public static function ensureMeetingRules(Event $event): void
    {
        if (! self::isPro($event)) {
            self::deny('Weighted votes, proxies and quorum are Pro features. Upgrade this event to use them.', 'meeting_rules');
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
        if (self::tierOf($event) !== self::UNLIMITED && $currentMembers >= self::MAX_MEMBERS) {
            self::deny('An event can have up to ' . self::MAX_MEMBERS . ' co-organisers.', 'team', false);
        }
    }

    private static function deny(string $message, string $reason, bool $upgrade = true, array $extra = []): never
    {
        abort(response()->json([
            'message' => $message,
            'plan_limit' => $reason,
            'upgrade' => $upgrade,
        ] + $extra, 402));
    }
}
