<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use App\Support\AccountLogger;
use App\Support\Audit;
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
            // New accounts waiting for the admin before they can create events.
            'awaiting_approval' => User::whereNull('approved_at')->where('is_admin', false)->count(),
        ]);
    }

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
        AccountLogger::record('admin.jobs', $uuid === 'all' ? 'Retried every failed background job' : 'Retried a failed background job');
        return response()->json(['ok' => true]);
    }

    /** Remove one failed job ("all" clears the list). */
    public function forgetFailedJob(string $uuid)
    {
        $uuid === 'all' ? Artisan::call('queue:flush') : Artisan::call('queue:forget', ['id' => $uuid]);
        AccountLogger::record('admin.jobs', $uuid === 'all' ? 'Cleared every failed background job' : 'Cleared a failed background job');
        return response()->json(['ok' => true]);
    }

    /**
     * The platform account log, newest first. group: admin | signin | security;
     * search matches the account's email or name, the admin's name, or the text.
     */
    public function logs(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', Rule::in(array_keys(\App\Models\AccountLog::GROUPS))],
            'failed' => ['nullable', 'boolean'],
        ]);

        $logs = \App\Models\AccountLog::query()
            ->with(['actor:id,name,email', 'user:id,name,email'])
            ->when($data['group'] ?? null, fn ($q, $g) => $q->where('action', 'like', \App\Models\AccountLog::GROUPS[$g] . '%'))
            ->when($request->boolean('failed'), fn ($q) => $q->where('action', 'auth.login_failed'))
            ->when($data['search'] ?? null, function ($q, $term) {
                $like = $this->like($term);
                $q->where(fn ($w) => $w->where('user_email', 'like', $like)->orWhere('description', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like))
                    ->orWhereHas('actor', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->latest('created_at')->latest('id')
            ->paginate(50);

        return response()->json($logs);
    }

    private function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    public function users(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', Rule::in(['all', 'awaiting', 'approved', 'suspended', 'unverified', 'admins'])],
        ]);

        $users = User::query()
            ->when($data['search'] ?? null, function ($q, $term) {
                $like = $this->like($term);
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when(($data['filter'] ?? null) === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when(($data['filter'] ?? null) === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->when(($data['filter'] ?? null) === 'admins', fn ($q) => $q->where('is_admin', true))
            ->when(($data['filter'] ?? null) === 'awaiting', fn ($q) => $q->whereNull('approved_at')->where('is_admin', false))
            ->when(($data['filter'] ?? null) === 'approved', fn ($q) => $q->whereNotNull('approved_at'))
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
        AccountLogger::record('admin.suspended', 'Account suspended (signed out everywhere)', $user);

        return response()->json($user->loadCount('events'));
    }

    public function unsuspend(User $user)
    {
        $was = $user->suspended_at !== null;
        $user->forceFill(['suspended_at' => null])->save();
        if ($was) AccountLogger::record('admin.restored', 'Suspension lifted', $user);

        return response()->json($user->loadCount('events'));
    }

    /** Approve a new account (it can then create events), or withdraw the approval. */
    public function setApproval(Request $request, User $user)
    {
        $data = $request->validate(['approved' => ['required', 'boolean']]);
        $was = $user->approved_at !== null;
        $user->forceFill(['approved_at' => $data['approved'] ? ($user->approved_at ?? now()) : null])->save();
        if ($was !== (bool) $data['approved']) {
            AccountLogger::record($data['approved'] ? 'admin.approved' : 'admin.unapproved',
                $data['approved'] ? 'Account approved: can create events' : 'Approval withdrawn: can’t create new events', $user);
        }

        return response()->json($user->loadCount('events'));
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([Event::STATUS_UPCOMING, Event::STATUS_ACTIVE, Event::STATUS_COMPLETED])],
        ]);

        $events = Event::query()
            ->select(['id', 'user_id', 'name', 'starts_at', 'ends_at', 'results_visibility', 'created_at'])
            ->with(['user:id,name,email,suspended_at,approved_at,is_admin'])
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
