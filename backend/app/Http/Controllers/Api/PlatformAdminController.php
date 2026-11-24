<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Models\VotePurchase;
use App\Models\Voter;
use App\Support\Audit;
use App\Support\Paystack;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Platform-owner area: who is using the service and the ability to suspend
 * abuse. It deliberately exposes no ballot contents, voter lists or codes.
 */
class PlatformAdminController extends Controller
{
    public function overview()
    {
        return response()->json([
            'users' => User::count(),
            'users_last_30_days' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'suspended_users' => User::whereNotNull('suspended_at')->count(),
            'events' => Event::count(),
            'events_live' => Event::withStatus(Event::STATUS_ACTIVE)->count(),
            'voters' => Voter::count(),
            'ballots_cast' => Voter::whereNotNull('used_at')->count(),
            'votes_counted' => (int) Candidate::sum('votes'),
            'pro_events' => Event::where('plan', 'pro')->count(),
            'revenue_naira' => intdiv((int) Payment::where('status', Payment::SUCCESS)->sum('amount_kobo'), 100),
            'revenue_last_30_days_naira' => intdiv((int) Payment::where('status', Payment::SUCCESS)
                ->where('paid_at', '>=', now()->subDays(30))->sum('amount_kobo'), 100),
            // Paid public voting: money taken, and the platform's share of it.
            'paid_votes_gross_naira' => intdiv((int) VotePurchase::where('status', VotePurchase::SUCCESS)->sum('amount_kobo'), 100),
            'paid_votes_fees_naira' => intdiv((int) VotePurchase::where('status', VotePurchase::SUCCESS)->sum('fee_kobo'), 100),
        ]);
    }

    // ------------------------------------------------------------------ paid voting payouts

    /** Paid-voting events with what each organiser is owed. */
    public function payouts()
    {
        $events = Event::where('voting_mode', Event::MODE_PAID)
            ->whereHas('purchases', fn ($q) => $q->whereIn('status', [VotePurchase::SUCCESS, VotePurchase::LATE]))
            ->with('user:id,name,email,payout_bank_name,payout_account_number,payout_account_name')
            ->withSum(['purchases as gross_kobo' => fn ($q) => $q->where('status', VotePurchase::SUCCESS)], 'amount_kobo')
            ->withSum(['purchases as fee_kobo' => fn ($q) => $q->where('status', VotePurchase::SUCCESS)], 'fee_kobo')
            // Paid straight to the organiser by Paystack (split): not owed by us.
            ->withSum(['purchases as split_gross_kobo' => fn ($q) => $q->where('status', VotePurchase::SUCCESS)->whereNotNull('subaccount')], 'amount_kobo')
            ->withSum(['purchases as split_fee_kobo' => fn ($q) => $q->where('status', VotePurchase::SUCCESS)->whereNotNull('subaccount')], 'fee_kobo')
            ->withCount(['purchases as late_count' => fn ($q) => $q->where('status', VotePurchase::LATE)])
            ->withSum('payouts as paid_out_kobo', 'amount_kobo')
            ->latest()
            ->get(['id', 'user_id', 'name', 'starts_at', 'ends_at', 'created_at']);

        return response()->json($events->map(function (Event $e) {
            $net = (int) $e->gross_kobo - (int) $e->fee_kobo;
            $auto = (int) $e->split_gross_kobo - (int) $e->split_fee_kobo;
            return [
                'id' => $e->id,
                'name' => $e->name,
                'status' => $e->status,
                'ends_at' => $e->ends_at,
                'organiser' => $e->user?->only(['id', 'name', 'email']),
                'bank' => $e->user && $e->user->payout_account_number ? [
                    'bank_name' => $e->user->payout_bank_name,
                    'account_number' => $e->user->payout_account_number,
                    'account_name' => $e->user->payout_account_name,
                ] : null,
                'gross_naira' => (int) $e->gross_kobo / 100,
                'fee_naira' => (int) $e->fee_kobo / 100,
                'net_naira' => $net / 100,
                'paid_out_naira' => ((int) $e->paid_out_kobo + $auto) / 100,
                'auto_paid_naira' => $auto / 100,
                'balance_naira' => ($net - $auto - (int) $e->paid_out_kobo) / 100,
                'late_payments' => (int) $e->late_count,
            ];
        }));
    }

    /** Record money sent to the organiser (by bank transfer, outside the app). */
    public function recordPayout(Request $request, Event $event)
    {
        $data = $request->validate([
            'amount_naira' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        Payout::create([
            'event_id' => $event->id,
            'user_id' => $event->user_id,
            'amount_kobo' => (int) round($data['amount_naira'] * 100),
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        Audit::log($event, 'event.payout', 'Payout of ₦' . number_format((float) $data['amount_naira'], 2) . ' recorded by the platform admin');

        return response()->json(['ok' => true], 201);
    }

    // ------------------------------------------------------------------ system health

    /**
     * Is mail/SMS delivery working? Queue backlog, failed jobs, recent delivery
     * errors, and whether the scheduler is running.
     */
    public function health()
    {
        $failed = collect();
        $failedCount = 0;
        try {
            $failedCount = DB::table('failed_jobs')->count();
            $failed = DB::table('failed_jobs')->latest('failed_at')->limit(25)->get(['uuid', 'queue', 'payload', 'exception', 'failed_at'])
                ->map(fn ($j) => [
                    'uuid' => $j->uuid,
                    'queue' => $j->queue,
                    'job' => class_basename((string) (json_decode($j->payload, true)['displayName'] ?? 'Job')),
                    'error' => Str::limit(strtok((string) $j->exception, "\n"), 300),
                    'failed_at' => $j->failed_at,
                ]);
        } catch (\Throwable) {
            // No failed_jobs table: nothing to show.
        }

        $pending = null;
        if (config('queue.default') === 'database') {
            try {
                $pending = DB::table(config('queue.connections.database.table', 'jobs'))->count();
            } catch (\Throwable) {
            }
        }

        $heartbeat = Cache::get('ballotify.scheduler_heartbeat');
        $since = now()->subDays(7);

        return response()->json([
            'queue' => [
                'driver' => config('queue.default'),
                'pending' => $pending,
                'failed' => $failedCount,
                // sync = sent during the request; anything else needs `php artisan queue:work` running.
                'needs_worker' => ! in_array(config('queue.default'), ['sync', 'null'], true),
            ],
            'scheduler' => [
                'last_run' => $heartbeat,
                'running' => $heartbeat && now()->diffInMinutes($heartbeat, true) <= 3,
            ],
            'mail' => ['driver' => config('mail.default'), 'from' => config('mail.from.address')],
            'sms' => ['driver' => config('services.sms.driver', config('sms.driver'))],
            'paystack' => Paystack::configured(),
            'flutterwave' => \App\Support\Flutterwave::configured(),
            // Failed to start a checkout in the last couple of minutes (payers were sent to the backup).
            'paystack_down' => \App\Support\Payments::isDown(\App\Support\Payments::PAYSTACK),
            'broadcasting' => config('broadcasting.default'),
            'delivery_errors' => [
                'last_7_days' => Voter::whereNotNull('invite_error')->where('updated_at', '>=', $since)->count(),
                'recent' => Voter::whereNotNull('invite_error')->where('updated_at', '>=', $since)
                    ->with('event:id,name')->latest('updated_at')->limit(15)
                    ->get(['id', 'event_id', 'invite_error', 'updated_at'])
                    ->map(fn ($v) => ['event' => $v->event?->name, 'event_id' => $v->event_id, 'error' => $v->invite_error, 'at' => $v->updated_at]),
            ],
            'failed_jobs' => $failed,
        ]);
    }

    /** Retry one failed job ("all" retries every one). */
    public function retryFailedJob(string $uuid)
    {
        Artisan::call('queue:retry', ['id' => [$uuid === 'all' ? 'all' : $uuid]]);
        return response()->json(['ok' => true]);
    }

    /** Remove one failed job ("all" clears the list). */
    public function forgetFailedJob(string $uuid)
    {
        $uuid === 'all' ? Artisan::call('queue:flush') : Artisan::call('queue:forget', ['id' => $uuid]);
        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------ billing

    public function plans()
    {
        return response()->json($this->planSettings());
    }

    public function updatePlans(Request $request)
    {
        $data = $request->validate([
            'pro_price_naira' => ['sometimes', 'integer', 'min:100', 'max:100000000'],
            'free_max_active_events' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'free_max_voters' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'pro_max_voters' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'paid_vote_fee_percent' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'pro_small_price_naira' => ['sometimes', 'integer', 'min:100', 'max:100000000'],
            'pro_small_max_voters' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'pro_large_price_naira' => ['sometimes', 'integer', 'min:100', 'max:100000000'],
            'pro_large_max_voters' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'sms_price_naira' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ]);
        Plans::update($data);

        return response()->json($this->planSettings());
    }

    /** Make every event of an account Pro, free of charge (or take it back). */
    public function setAccountPro(Request $request, User $user)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $user->forceFill(['pro_account_at' => $data['enabled'] ? ($user->pro_account_at ?? now()) : null])->save();

        return response()->json($user->loadCount('events'));
    }

    /** Upgrade or downgrade a single event without payment. */
    public function setEventPlan(Request $request, Event $event)
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(['free', 'pro'])],
            'tier' => ['sometimes', Rule::in(Plans::TIERS)],
        ]);
        $tier = $data['tier'] ?? 'large';
        if ($data['plan'] === 'pro' && ($event->plan !== 'pro' || $event->pro_tier !== $tier)) {
            $size = Plans::tiers()[$tier];
            $event->forceFill([
                'plan' => 'pro', 'pro_tier' => $tier, 'upgraded_at' => now(), 'upgraded_via' => 'admin',
                'sms_included' => max((int) $event->sms_included, $size['sms_included']),
            ])->save();
            Audit::log($event, 'event.plan', "Upgraded to {$size['name']} by the platform admin");
        } elseif ($data['plan'] === 'free' && $event->plan !== 'free') {
            $event->forceFill(['plan' => 'free', 'pro_tier' => null, 'upgraded_at' => null, 'upgraded_via' => null])->save();
            Audit::log($event, 'event.plan', 'Set back to Free by the platform admin');
        }

        return response()->json(['id' => $event->id, 'plan' => $event->plan, 'pro_tier' => $event->pro_tier, 'upgraded_via' => $event->upgraded_via]);
    }

    public function payments(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([Payment::PENDING, Payment::SUCCESS, Payment::FAILED])],
        ]);

        $payments = Payment::query()
            ->with('user:id,name,email')
            ->when($data['search'] ?? null, function ($q, $term) {
                $like = $this->like($term);
                $q->where(fn ($w) => $w->where('reference', 'like', $like)->orWhere('event_name', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(25);

        return response()->json($payments);
    }

    private function planSettings(): array
    {
        return Plans::settings() + [
            'billing_enabled' => Plans::enabled(),
            'payments_enabled' => \App\Support\Payments::available(),
            'payment_providers' => \App\Support\Payments::providers(),
        ];
    }

    /** LIKE pattern with the user's % and _ treated literally. */
    private function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    public function users(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', Rule::in(['all', 'suspended', 'unverified', 'admins', 'pro'])],
        ]);

        $users = User::query()
            ->when($data['search'] ?? null, function ($q, $term) {
                $like = $this->like($term);
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when(($data['filter'] ?? null) === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when(($data['filter'] ?? null) === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->when(($data['filter'] ?? null) === 'admins', fn ($q) => $q->where('is_admin', true))
            ->when(($data['filter'] ?? null) === 'pro', fn ($q) => $q->whereNotNull('pro_account_at'))
            ->withCount('events')
            ->latest()
            ->paginate(25);

        return response()->json($users);
    }

    public function suspend(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => ['You can’t suspend your own account.']]);
        }
        if ($user->is_admin) {
            throw ValidationException::withMessages(['user' => ['Remove admin rights before suspending this account.']]);
        }

        $user->forceFill(['suspended_at' => now()])->save();
        // Sign them out everywhere.
        $user->tokens()->delete();

        return response()->json($user->loadCount('events'));
    }

    public function unsuspend(User $user)
    {
        $user->forceFill(['suspended_at' => null])->save();

        return response()->json($user->loadCount('events'));
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([Event::STATUS_UPCOMING, Event::STATUS_ACTIVE, Event::STATUS_COMPLETED])],
        ]);

        $events = Event::query()
            ->select(['id', 'user_id', 'voting_mode', 'name', 'starts_at', 'ends_at', 'results_visibility', 'plan', 'upgraded_via', 'created_at'])
            ->with(['user:id,name,email,suspended_at,pro_account_at,is_admin'])
            ->when($data['search'] ?? null, function ($q, $term) {
                $like = $this->like($term);
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->when($data['status'] ?? null, fn ($q, $status) => $q->withStatus($status))
            ->withCount(['voters', 'voters as voted_count' => fn ($q) => $q->whereNotNull('used_at')])
            ->latest()
            ->paginate(25);

        return response()->json($events);
    }
}
