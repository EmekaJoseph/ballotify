<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Voter;
use App\Support\BallotBox;
use App\Support\BallotRejected;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * USSD voting through Africa's Talking, for voters without smartphones or data.
 *
 *   *384*1234#  -> event number -> voting code -> one screen per category -> confirm
 *
 * Africa's Talking sends every keypress so far in `text`, joined with "*". The
 * menu is rebuilt from that on each request (no server-side session), and the
 * ballot goes through the same BallotBox as the web, so the rules are identical.
 * Voters pick one candidate per category here (a keypad can't do multi-select),
 * except in ranked-choice categories, where they pick their 1st choice, then
 * their 2nd from the names left, and so on, pressing 0 when they're done.
 */
class UssdController extends Controller
{
    private const PER_PAGE = 6;
    private const NEXT = '98';
    private const SKIP = '0';

    public function handle(Request $request, string $secret)
    {
        $expected = (string) config('services.ussd.webhook_secret');
        abort_if($expected === '' || ! hash_equals($expected, $secret), 404);

        $text = (string) $request->input('text', '');
        $inputs = $text === '' ? [] : explode('*', $text);

        return response($this->menu($inputs), 200)->header('Content-Type', 'text/plain');
    }

    private function menu(array $in): string
    {
        if (! $in) {
            return "CON Welcome to Ballotify\nEnter the event number:";
        }

        $event = Event::where('ussd_code', trim($in[0]))->where('ussd_enabled', true)->with('user')->first();
        if (! $event || $event->user?->suspended_at || $event->isPaid() || ! Plans::isPro($event)) {
            return 'END Event not found. Check the number and dial again.';
        }
        if (! $event->isOpen()) {
            return 'END ' . ($event->status === Event::STATUS_UPCOMING ? 'Voting has not started yet.' : 'Voting has ended.');
        }
        $title = Str::limit($event->name, 40);

        if (count($in) === 1) {
            return "CON {$title}\nEnter your voting code:";
        }

        $code = VoterController::normalizeCode($in[1]);
        $voter = Voter::where('event_id', $event->id)->where('code', $code)->whereNull('used_at')->first(['id', 'groups', 'weight', 'proxy_voter_id']);
        if (! $voter) {
            return 'END That code is not valid or has already voted.';
        }
        if ($voter->proxy_voter_id !== null) {
            return 'END Your vote is being cast by your proxy. Ask the organiser if this is wrong.';
        }
        // A proxy holder also sees the categories of the members they vote for.
        $members = \App\Support\MeetingRules::members($event, $voter);

        // Replay the keypresses through the category screens.
        $categories = $event->categories()->with('candidates:id,category_id,name')->get()
            ->filter(fn ($c) => $c->candidates->isNotEmpty() && $members->contains(fn ($m) => $c->allows($m->groups)))->values();
        if ($categories->isEmpty()) {
            return 'END This ballot isn’t ready yet. Please try again later.';
        }
        $picks = [];      // category id => Candidate, or Candidate[] (ranked, in order)
        $ranking = [];    // the ranked category being filled in
        $catIndex = 0;
        $page = 0;
        $invalid = false;
        $rest = array_slice($in, 2);

        foreach ($rest as $pos => $key) {
            $isLast = $pos === count($rest) - 1;
            if ($catIndex >= $categories->count()) {
                // Confirmation screen.
                if ($key === '1') {
                    return $this->submit($event, $code, $picks);
                }
                if ($key === '2') {
                    return 'END Vote cancelled. Dial again to start over.';
                }
                $invalid = $isLast;
                continue;
            }
            $cat = $categories[$catIndex];
            $candidates = $this->choicesLeft($cat, $ranking);
            $pages = (int) ceil($candidates->count() / self::PER_PAGE);
            if ($key === self::NEXT && $page < $pages - 1) {
                $page++;
                $invalid = false;
            } elseif ($key === self::SKIP) {
                // Ranked: 0 = "done" once there's a 1st choice; otherwise skip the category.
                if ($ranking) {
                    $picks[$cat->id] = $ranking;
                }
                $ranking = [];
                $catIndex++;
                $page = 0;
                $invalid = false;
            } elseif (ctype_digit($key) && (int) $key >= 1 && (int) $key <= $candidates->count()) {
                $chosen = $candidates[(int) $key - 1];
                $page = 0;
                $invalid = false;
                if ($cat->isRanked()) {
                    $ranking[] = $chosen;
                    $full = $cat->max_choices && count($ranking) >= $cat->max_choices;
                    if ($full || $this->choicesLeft($cat, $ranking)->isEmpty()) {
                        $picks[$cat->id] = $ranking;
                        $ranking = [];
                        $catIndex++;
                    }
                } else {
                    $picks[$cat->id] = $chosen;
                    $catIndex++;
                }
            } else {
                $invalid = $isLast;
            }
        }

        $prefix = $invalid ? "Invalid choice.\n" : '';

        if ($catIndex >= $categories->count()) {
            if (! $picks) {
                return 'END You skipped every category, so no vote was cast. Dial again to vote.';
            }
            $lines = $categories->filter(fn ($c) => isset($picks[$c->id]))
                ->map(fn ($c) => Str::limit($c->name, 14, '') . ': ' . (is_array($picks[$c->id])
                    ? Str::limit(collect($picks[$c->id])->map(fn ($x) => Str::limit($x->name, 10, ''))->implode('>'), 24, '…')
                    : Str::limit($picks[$c->id]->name, 16, '…')))
                ->take(5)->implode("\n");
            return "CON {$prefix}Confirm your vote:\n{$lines}\n1. Submit\n2. Cancel";
        }

        $cat = $categories[$catIndex];
        $candidates = $this->choicesLeft($cat, $ranking);
        $pages = (int) ceil($candidates->count() / self::PER_PAGE);
        $lines = [];
        foreach ($candidates->slice($page * self::PER_PAGE, self::PER_PAGE) as $i => $c) {
            $lines[] = ($i + 1) . '. ' . Str::limit($c->name, 22, '…');
        }
        if ($page < $pages - 1) $lines[] = self::NEXT . '. More';
        $lines[] = self::SKIP . ($ranking ? '. Done' : '. Skip');
        $step = ($catIndex + 1) . '/' . $categories->count();
        $heading = Str::limit($cat->name, 28) . " ({$step})";
        if ($cat->isRanked()) {
            $heading .= "\nYour " . $this->ordinal(count($ranking) + 1) . ' choice:';
        }

        return "CON {$prefix}{$heading}\n" . implode("\n", $lines);
    }

    /** Candidates still to choose from (ranked: the ones not ranked yet). */
    private function choicesLeft($category, array $ranking)
    {
        $taken = array_map(fn ($c) => $c->id, $ranking);
        return $category->candidates->reject(fn ($c) => in_array($c->id, $taken, true))->values();
    }

    private function ordinal(int $n): string
    {
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
        return $n . $suffix;
    }

    private function submit(Event $event, string $code, array $picks): string
    {
        $choices = [];
        foreach ($picks as $catId => $pick) {
            foreach (is_array($pick) ? $pick : [$pick] as $i => $cand) {
                $choices[] = ['category_id' => $catId, 'candidate_id' => $cand->id, 'rank' => $i + 1];
            }
        }
        try {
            $receipt = BallotBox::cast($event, $code, $choices);
        } catch (BallotRejected $e) {
            return 'END ' . Str::limit($e->getMessage(), 150);
        }
        return "END Thank you! Your vote was recorded.\nReceipt: {$receipt}";
    }
}
