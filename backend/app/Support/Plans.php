<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Free vs Pro, per event.
 *
 *  Free: a few open events per account, a small voter cap, no email/SMS,
 *        "Powered by Ballotify" on the ballot.
 *  Pro:  unlocked for one event by a Paystack payment, or by the platform
 *        admin (per event, or for a whole account). Larger voter cap,
 *        email/SMS, no branding.
 *
 * Prices and limits come from config defaults, overridden by the admin's saved
 * settings. With billing disabled every event behaves as Pro.
 */
class Plans
{
    private const CACHE_KEY = 'ballotify.plan-settings';

    public const KEYS = ['pro_price_naira', 'free_max_active_events', 'free_max_voters', 'pro_max_voters'];

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
            $out[$key] = (int) ($saved[$key] ?? $defaults[$key]);
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
            'branding' => ! $pro,
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

    // ------------------------------------------------------------------ checks

    /**
     * Can this account create another event, and which open Free events use up the allowance?
     * Shown by the dashboard before the organiser starts filling in a new event.
     *
     * @return array{can_create: bool, max: int|null, open_events: list<array{id: int, name: string, ends_at: mixed}>}
     */
    public static function eventQuota(User $user): array
    {
        if (self::accountIsPro($user)) {
            return ['can_create' => true, 'max' => null, 'open_events' => []];
        }
        $max = self::settings()['free_max_active_events'];
        $open = Event::where('user_id', $user->id)
            ->where('plan', 'free')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('id')
            ->get(['id', 'name', 'ends_at']);

        return [
            'can_create' => $open->count() < $max,
            'max' => $max,
            'open_events' => $open->map(fn (Event $e) => ['id' => $e->id, 'name' => $e->name, 'ends_at' => $e->ends_at])->all(),
        ];
    }

    /** Free accounts may only have a limited number of free events that haven't closed. */
    public static function ensureCanCreateEvent(User $user): void
    {
        $quota = self::eventQuota($user);
        if (! $quota['can_create']) {
            $max = $quota['max'];
            self::deny(
                $max === 1
                    ? 'Free accounts can run one event at a time. Upgrade your current event to Pro, or close it, to create another.'
                    : "Free accounts can run {$max} events at a time. Upgrade one to Pro, or close one, to create another.",
                'events',
            );
        }
    }

    /** Adding $adding voters must stay within the event's voter cap. */
    public static function ensureVoterCapacity(Event $event, int $adding): void
    {
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
            self::deny('Sending codes by email or SMS is a Pro feature. Upgrade this event to send invitations, or share codes yourself (CSV or printed slips).', 'messaging');
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
