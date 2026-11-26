<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\RankedBallot;
use App\Models\ResultCertificate;
use App\Models\Voter;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Reset votes and results: for test runs ("we tried the ballot, now start clean").
 * Owner only, never on paid voting (people paid), never once voting has closed or
 * results were certified. Voters, codes and the ballot stay. Every reset is recorded
 * for good and shown on the results, report and any later certificate.
 */
class VoteResetController extends Controller
{
    public function store(Request $request, Event $event)
    {
        Gate::authorize('own', $event);
        $data = $request->validate(['confirm_name' => ['required', 'string', 'max:255']]);

        $refuse = fn (string $why) => throw ValidationException::withMessages(['reset' => [$why]]);
        if ($event->isPaid()) {
            $refuse('Paid votes can’t be reset: people paid for them.');
        }
        if ($event->status === Event::STATUS_COMPLETED) {
            $refuse('Voting has closed, so the votes can’t be reset. The result stands.');
        }
        if (ResultCertificate::where('event_id', $event->id)->exists()) {
            $refuse('These results were certified, so the votes can’t be reset.');
        }
        if (mb_strtolower(trim($data['confirm_name'])) !== mb_strtolower(trim($event->name))) {
            throw ValidationException::withMessages(['confirm_name' => ['Type the event name exactly to confirm.']]);
        }

        $erased = DB::transaction(function () use ($event) {
            // Lock every code first: a ballot arriving now waits, then counts after the reset.
            $voted = Voter::where('event_id', $event->id)->lockForUpdate()->whereNotNull('used_at')->count();
            $categoryIds = Category::where('event_id', $event->id)->pluck('id');

            Candidate::where('event_id', $event->id)->update(['votes' => 0]);
            Category::where('event_id', $event->id)->update(['ballots' => 0]);
            RankedBallot::whereIn('category_id', $categoryIds)->delete();
            BallotReceipt::where('event_id', $event->id)->delete();
            Voter::where('event_id', $event->id)->whereNotNull('used_at')->update(['used_at' => null]);

            return $voted;
        });

        Audit::log($event, 'votes.reset', "Votes and results reset: {$erased} voter" . ($erased === 1 ? '' : 's') . '’ votes erased; every code works again', ['voters' => $erased]);

        return response()->json(['erased' => $erased, 'message' => $erased
            ? "Votes reset. {$erased} voter" . ($erased === 1 ? '' : 's') . ' can vote again with the same codes.'
            : 'Votes reset. There were no votes yet.']);
    }

    /** Every reset on this event: when, by whom, how many voters' votes. Shown wherever results are. */
    public static function history(Event $event): array
    {
        return $event->auditLogs()->where('action', 'votes.reset')->with('user:id,name')->orderBy('created_at')->get()
            ->map(fn ($log) => [
                'at' => $log->created_at?->toIso8601String(),
                'by' => $log->user?->name,
                'voters' => (int) ($log->meta['voters'] ?? 0),
            ])->all();
    }
}
