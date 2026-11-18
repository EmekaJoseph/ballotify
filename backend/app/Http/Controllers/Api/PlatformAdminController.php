<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
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
        ]);
    }

    public function users(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', Rule::in(['all', 'suspended', 'unverified', 'admins'])],
        ]);

        $users = User::query()
            ->when($data['search'] ?? null, function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when(($data['filter'] ?? null) === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when(($data['filter'] ?? null) === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->when(($data['filter'] ?? null) === 'admins', fn ($q) => $q->where('is_admin', true))
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
            ->select(['id', 'user_id', 'event_type_id', 'name', 'starts_at', 'ends_at', 'results_visibility', 'created_at'])
            ->with(['user:id,name,email,suspended_at', 'type'])
            ->when($data['search'] ?? null, function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
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
