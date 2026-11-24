<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BallotReceipt;
use App\Models\Event;
use App\Models\ResultCertificate;
use App\Models\VotePurchase;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Verifiable results certificate: a signed snapshot of the final results, with
 * a public page (and QR code) where anyone can check it is genuine and that the
 * totals haven't changed since.
 */
class CertificateController extends Controller
{
    /** The results as they are right now, in certificate form. */
    private function snapshot(Event $event): array
    {
        $event->loadMissing('user:id,name');
        $categories = $event->categories()->with('candidates:id,category_id,name,votes')->orderBy('id')->get();

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'organiser' => $event->user?->name,
                'voting_mode' => $event->voting_mode,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
            ],
            'stats' => $event->isPaid()
                ? ['votes' => (int) $event->candidates()->sum('votes'),
                   'payments' => $event->purchases()->where('status', VotePurchase::SUCCESS)->count()]
                : ['voters' => $event->voters()->count(),
                   'ballots' => $event->voters()->whereNotNull('used_at')->count(),
                   'receipts' => BallotReceipt::where('event_id', $event->id)->count()],
            // Weighted votes / proxies / quorum, when used.
            'rules' => \App\Support\MeetingRules::summary($event),
            'categories' => $categories->map(function ($cat) {
                $tally = \App\Support\Tally::category($cat, $cat->candidates);
                $byId = $cat->candidates->keyBy('id');
                $cands = collect($tally['order'])->map(fn ($id) => $byId[$id] ?? null)->filter()->values();
                $row = [
                    'name' => $cat->name,
                    'candidates' => $cands->map(fn ($c) => ['name' => $c->name, 'votes' => (int) $c->votes])->all(),
                    'winners' => collect($tally['winners'])->map(fn ($id) => $byId[$id]->name ?? null)->filter()->values()->all(),
                ];
                if (! empty($tally['motion'])) {
                    $row['method'] = 'motion';
                    $row['motion'] = $tally['motion'];
                }
                if ($tally['rounds']) {
                    // Ranked choice: the votes shown are first choices; the final round decided it.
                    $last = end($tally['rounds']);
                    $row['method'] = 'ranked';
                    $row['final_round'] = collect($last['counts'])->map(fn ($r) => ['name' => $byId[$r['id']]->name ?? '', 'votes' => $r['votes']])->all();
                    $row['rounds'] = count($tally['rounds']);
                }
                return $row;
            })->all(),
        ];
    }

    /** Organiser: certify the final results (only once voting has closed). */
    public function issue(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        if ($event->status !== Event::STATUS_COMPLETED) {
            return response()->json(['message' => 'Results can be certified once voting has closed.'], 422);
        }

        $payload = $this->snapshot($event) + ['issued_at' => now()->toIso8601String()];
        // Same totals as the latest certificate: return that one instead of a duplicate.
        $latest = $event->certificates()->latest('issued_at')->first();
        if ($latest && $this->sameResults($latest->payload, $payload)) {
            return response()->json($this->present($latest));
        }

        $cert = ResultCertificate::create([
            'event_id' => $event->id,
            'serial' => 'BLT-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4)),
            'payload' => $payload,
            'signature' => ResultCertificate::sign($payload),
            'issued_by' => $request->user()->id,
            'issued_at' => now(),
        ]);
        Audit::log($event, 'event.certified', "Results certified (certificate {$cert->serial})");

        return response()->json($this->present($cert), 201);
    }

    /** Organiser: certificates issued for this event. */
    public function index(Event $event)
    {
        Gate::authorize('view', $event);
        return response()->json($event->certificates()->latest('issued_at')->get()->map(fn ($c) => $this->present($c)));
    }

    /** Public: verify a certificate by its serial. */
    public function verify(string $serial)
    {
        $cert = ResultCertificate::where('serial', strtoupper(trim($serial)))->with('event')->first();
        if (! $cert) {
            return response()->json(['valid' => false, 'message' => 'No certificate with this number exists.'], 404);
        }

        $authentic = $cert->isAuthentic();
        $matches = $cert->event ? $this->sameResults($cert->payload, $this->snapshot($cert->event)) : null;

        return response()->json($this->present($cert) + [
            'valid' => $authentic,
            'matches_current' => $matches,
            'message' => ! $authentic
                ? 'This certificate has been altered and is NOT valid.'
                : ($matches === false
                    ? 'Genuine certificate, but the event’s totals have changed since it was issued.'
                    : 'Genuine certificate. The results match the event’s current totals.'),
        ]);
    }

    private function present(ResultCertificate $c): array
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        return [
            'serial' => $c->serial,
            'issued_at' => $c->issued_at,
            'signature' => $c->signature,
            'payload' => $c->payload,
            'verify_url' => "{$frontend}/verify/{$c->serial}",
            'certificate_url' => "{$frontend}/certificate/{$c->serial}",
        ];
    }

    private function sameResults(array $a, array $b): bool
    {
        $totals = fn ($p) => collect($p['categories'] ?? [])->map(fn ($c) => collect($c['candidates'])->mapWithKeys(fn ($x) => [$x['name'] => $x['votes']])->sortKeys()->all())->all();
        return $totals($a) === $totals($b);
    }
}
