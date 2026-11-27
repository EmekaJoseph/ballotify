<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * Votes over time, for the turnout chart. Uses only WHEN ballots were cast
 * (already known from voters.used_at), never what they contained.
 */
class Timeline
{
    /** Bucket sizes to choose from, in minutes. */
    private const STEPS = [5, 15, 30, 60, 180, 360, 720, 1440, 2880, 10080];
    private const MAX_BUCKETS = 36;

    /** @return array{bucket_minutes: int, points: list<array{t: string, votes: int}>} */
    public static function for(Event $event): array
    {
        // [timestamp => weight]
        $stamps = $event->voters()->whereNotNull('used_at')->pluck('used_at')
            ->map(fn ($t) => [Carbon::parse($t)->getTimestamp(), 1])->all();

        if (! $stamps) {
            return ['bucket_minutes' => 60, 'points' => []];
        }

        $times = array_column($stamps, 0);
        $first = min($times);
        $last = max(max($times), $event->status === Event::STATUS_ACTIVE ? time() : 0);
        // Start no later than the opening time, so the chart starts at zero.
        if ($event->starts_at && $event->starts_at->getTimestamp() < $first) {
            $first = max($event->starts_at->getTimestamp(), $first - 86400 * 7);
        }
        $span = max(1, $last - $first);

        $step = self::STEPS[count(self::STEPS) - 1];
        foreach (self::STEPS as $minutes) {
            if ($span / ($minutes * 60) <= self::MAX_BUCKETS) {
                $step = $minutes;
                break;
            }
        }
        $size = $step * 60;
        $start = intdiv($first, $size) * $size;
        $count = intdiv($last - $start, $size) + 1;
        $buckets = array_fill(0, $count, 0);
        foreach ($stamps as [$t, $w]) {
            $buckets[min($count - 1, intdiv($t - $start, $size))] += $w;
        }

        $points = [];
        foreach ($buckets as $i => $votes) {
            $points[] = ['t' => Carbon::createFromTimestamp($start + $i * $size)->toIso8601String(), 'votes' => $votes];
        }

        return ['bucket_minutes' => $step, 'points' => $points];
    }
}
