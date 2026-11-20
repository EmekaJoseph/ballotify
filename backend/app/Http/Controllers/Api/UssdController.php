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
 * Voters pick one candidate per category here (a keypad can't do multi-select).
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
        $voterOk = Voter::where('event_id', $event->id)->where('code', $code)->whereNull('used_at')->exists();
        if (! $voterOk) {
            return 'END That code is not valid or has already voted.';
        }

        // Replay the keypresses through the category screens.
        $categories = $event->categories()->with('candidates:id,category_id,name')->get()
            ->filter(fn ($c) => $c->candidates->isNotEmpty())->values();
        if ($categories->isEmpty()) {
            return 'END This ballot isn’t ready yet. Please try again later.';
        }
        $picks = [];
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
            $candidates = $categories[$catIndex]->candidates->values();
            $pages = (int) ceil($candidates->count() / self::PER_PAGE);
            if ($key === self::NEXT && $page < $pages - 1) {
                $page++;
                $invalid = false;
            } elseif ($key === self::SKIP) {
                $catIndex++;
                $page = 0;
                $invalid = false;
            } elseif (ctype_digit($key) && (int) $key >= 1 && (int) $key <= $candidates->count()) {
                $picks[$categories[$catIndex]->id] = $candidates[(int) $key - 1];
                $catIndex++;
                $page = 0;
                $invalid = false;
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
                ->map(fn ($c) => Str::limit($c->name, 14, '') . ': ' . Str::limit($picks[$c->id]->name, 16, '…'))
                ->take(5)->implode("\n");
            return "CON {$prefix}Confirm your vote:\n{$lines}\n1. Submit\n2. Cancel";
        }

        $cat = $categories[$catIndex];
        $candidates = $cat->candidates->values();
        $pages = (int) ceil($candidates->count() / self::PER_PAGE);
        $lines = [];
        foreach ($candidates->slice($page * self::PER_PAGE, self::PER_PAGE) as $i => $c) {
            $lines[] = ($i + 1) . '. ' . Str::limit($c->name, 22, '…');
        }
        if ($page < $pages - 1) $lines[] = self::NEXT . '. More';
        $lines[] = self::SKIP . '. Skip';
        $step = ($catIndex + 1) . '/' . $categories->count();

        return "CON {$prefix}" . Str::limit($cat->name, 28) . " ({$step})\n" . implode("\n", $lines);
    }

    private function submit(Event $event, string $code, array $picks): string
    {
        try {
            $receipt = BallotBox::cast($event, $code, collect($picks)
                ->map(fn ($cand, $catId) => ['category_id' => $catId, 'candidate_id' => $cand->id])->values()->all());
        } catch (BallotRejected $e) {
            return 'END ' . Str::limit($e->getMessage(), 150);
        }
        return "END Thank you! Your vote was recorded.\nReceipt: {$receipt}";
    }
}
