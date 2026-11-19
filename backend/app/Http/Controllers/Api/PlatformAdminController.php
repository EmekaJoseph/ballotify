<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voter;
use App\Support\Audit;
use App\Support\Paystack;
use App\Support\Plans;
use Illuminate\Http\Request;
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
        ]);
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
        $data = $request->validate(['plan' => ['required', Rule::in(['free', 'pro'])]]);
        if ($event->plan !== $data['plan']) {
            $event->forceFill($data['plan'] === 'pro'
                ? ['plan' => 'pro', 'upgraded_at' => now(), 'upgraded_via' => 'admin']
                : ['plan' => 'free', 'upgraded_at' => null, 'upgraded_via' => null])->save();
            Audit::log($event, 'event.plan', $data['plan'] === 'pro'
                ? 'Upgraded to Pro by the platform admin'
                : 'Set back to Free by the platform admin');
        }

        return response()->json(['id' => $event->id, 'plan' => $event->plan, 'upgraded_via' => $event->upgraded_via]);
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
            'payments_enabled' => Paystack::configured(),
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
            ->select(['id', 'user_id', 'event_type_id', 'name', 'starts_at', 'ends_at', 'results_visibility', 'plan', 'upgraded_via', 'created_at'])
            ->with(['user:id,name,email,suspended_at,pro_account_at,is_admin', 'type'])
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
