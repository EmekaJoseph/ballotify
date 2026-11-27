<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Event;
use App\Models\Voter;
use Illuminate\Support\Collection;

/**
 * Optional meeting rules for AGMs, cooperatives and associations (Pro, all off by default).
 *
 *   Weighted votes: a voter's ballot counts as their `weight` (shares, units, delegates...).
 *   Proxies:        a member can let another member vote for them. The holder's one ballot
 *                   then counts for both, and the member can no longer vote themselves.
 *                   No chains: someone holding proxies can't hand their own vote on.
 *   Quorum:         the result is only valid if enough members voted, as a percentage of
 *                   the voter list or a fixed number (by weight when votes are weighted).
 *
 * The single place these rules are applied, so the web ballot, USSD and the results agree.
 */
class MeetingRules
{
    /** How many votes one member's ballot carries. */
    public static function weightOf(Event $event, Voter $voter): int
    {
        return $event->weighted_votes ? max(1, (int) $voter->weight) : 1;
    }

    /**
     * Whose votes a ballot cast with this code carries: the holder, then the members
     * who gave them their proxy and haven't voted.
     *
     * @param  bool  $lock  lock the principals' rows (inside the casting transaction)
     * @return Collection<int, Voter>
     */
    public static function members(Event $event, Voter $holder, bool $lock = false): Collection
    {
        $members = collect([$holder]);
        if ($event->proxies_enabled) {
            $query = Voter::where('event_id', $event->id)->where('proxy_voter_id', $holder->id)->whereNull('used_at')->orderBy('id');
            $members = $members->concat($lock ? $query->lockForUpdate()->get() : $query->get());
        }
        return $members;
    }

    /** Votes a ballot adds in this category: only members allowed to vote in it (voter groups). */
    public static function categoryWeight(Event $event, Collection $members, Category $category): int
    {
        return (int) $members->filter(fn (Voter $m) => $category->allows($m->groups))
            ->sum(fn (Voter $m) => self::weightOf($event, $m));
    }

    /** Categories at least one of these members may vote in. */
    public static function allowedCategoryIds(Event $event, Collection $members): Collection
    {
        return $event->categories()->get(['id', 'groups'])
            ->filter(fn (Category $c) => $members->contains(fn (Voter $m) => $c->allows($m->groups)))
            ->pluck('id')->values();
    }

    /** Whether any rule is switched on. */
    public static function any(Event $event): bool
    {
        return $event->weighted_votes || $event->proxies_enabled || $event->quorum_type !== null;
    }

    /**
     * Quorum progress, or null when no quorum is set.
     *
     * @return array{type: string, value: int, basis: string, total: int, required: int, achieved: int, met: bool}|null
     */
    public static function quorum(Event $event): ?array
    {
        if (! $event->quorum_type || ! $event->quorum_value) {
            return null;
        }
        $weighted = (bool) $event->weighted_votes;
        $voters = fn () => Voter::where('event_id', $event->id);
        $total = $weighted ? (int) $voters()->sum('weight') : $voters()->count();
        $achieved = $weighted ? (int) $voters()->whereNotNull('used_at')->sum('weight') : $voters()->whereNotNull('used_at')->count();
        $value = (int) $event->quorum_value;
        $required = $event->quorum_type === 'percent' ? (int) ceil($total * $value / 100) : $value;

        return [
            'type' => $event->quorum_type,
            'value' => $value,
            'basis' => $weighted ? 'weight' : 'voters',
            'total' => $total,
            'required' => max(1, $required),
            'achieved' => $achieved,
            'met' => $total > 0 && $achieved >= max(1, $required),
        ];
    }

    /** The rules in force, for the results, report and certificate. */
    public static function summary(Event $event): ?array
    {
        if (! self::any($event)) {
            return null;
        }
        $summary = [
            'weighted' => (bool) $event->weighted_votes,
            'proxies' => (bool) $event->proxies_enabled,
            'max_proxies' => $event->proxies_enabled ? (int) $event->max_proxies : null,
            'quorum' => self::quorum($event),
        ];
        if ($event->proxies_enabled) {
            $summary['proxy_votes'] = Voter::where('event_id', $event->id)->whereNotNull('proxy_voter_id')->whereNotNull('used_at')->count();
        }
        if ($event->weighted_votes) {
            $summary['total_weight'] = (int) Voter::where('event_id', $event->id)->sum('weight');
            $summary['voted_weight'] = (int) Voter::where('event_id', $event->id)->whereNotNull('used_at')->sum('weight');
        }
        return $summary;
    }

    /** "Quorum: 50% of members" / "Quorum: 120 votes (by weight)". */
    public static function describeQuorum(Event $event): string
    {
        $by = $event->weighted_votes ? ' (by weight)' : '';
        return $event->quorum_type === 'percent'
            ? "{$event->quorum_value}% of members{$by}"
            : number_format((int) $event->quorum_value) . ($event->weighted_votes ? ' votes' : ' members') . $by;
    }
}
