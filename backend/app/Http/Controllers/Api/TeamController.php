<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TeamInvitation;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use App\Policies\EventPolicy;
use App\Support\Audit;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Co-organisers (the election committee). Pro events only; managed by the owner. */
class TeamController extends Controller
{
    private const ROLE_LABELS = [
        EventMember::EDITOR => 'Editor',
        EventMember::VOTERS => 'Voter manager',
        EventMember::VIEWER => 'Viewer',
    ];

    public function index(Event $event)
    {
        Gate::authorize('view', $event);
        $event->loadMissing('user:id,name,email');

        return response()->json([
            'owner' => ['name' => $event->user->name, 'email' => $event->user->email],
            'members' => $event->members()->with('user:id,name')->orderBy('id')->get()
                ->map(fn (EventMember $m) => [
                    'id' => $m->id,
                    'email' => $m->email,
                    'name' => $m->user?->name,
                    'role' => $m->role,
                    'joined' => $m->user_id !== null,
                    'created_at' => $m->created_at,
                ]),
        ]);
    }

    public function store(Request $request, Event $event)
    {
        Gate::authorize('own', $event);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::in(EventMember::ROLES)],
        ]);
        Plans::ensureTeam($event, $event->members()->count());

        $email = strtolower(trim($data['email']));
        if ($email === strtolower($event->user->email)) {
            throw ValidationException::withMessages(['email' => ['That’s the event owner’s address.']]);
        }
        if ($event->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['This person is already on the team.']]);
        }

        $existing = User::where('email', $email)->first();
        $member = EventMember::create([
            'event_id' => $event->id,
            // Linked straight away only when that account's email is confirmed.
            'user_id' => $existing?->hasVerifiedEmail() ? $existing->id : null,
            'email' => $email,
            'role' => $data['role'],
            'invited_by' => $request->user()->id,
        ]);
        EventPolicy::flush();
        Audit::log($event, 'team.added', "{$email} added to the team as " . self::ROLE_LABELS[$member->role]);

        // After the response, so the organiser isn't kept waiting on the mail server.
        $inviter = $request->user();
        dispatch(function () use ($event, $inviter, $member, $existing) {
            // One invitation per membership, whatever runs this twice.
            if (! \Illuminate\Support\Facades\Cache::add('team-invite:' . sha1($member->id . '|' . $member->email . '|' . $member->created_at?->getTimestamp()), true, now()->addDay())) {
                return;
            }
            try {
                Mail::to($member->email)->send(new TeamInvitation($event, $inviter, $member, (bool) $existing));
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();

        return response()->json(['id' => $member->id, 'email' => $member->email, 'role' => $member->role, 'joined' => $member->user_id !== null], 201);
    }

    public function update(Request $request, Event $event, EventMember $member)
    {
        Gate::authorize('own', $event);
        $data = $request->validate(['role' => ['required', Rule::in(EventMember::ROLES)]]);
        $member->update(['role' => $data['role']]);
        EventPolicy::flush();
        Audit::log($event, 'team.role', "{$member->email} is now " . self::ROLE_LABELS[$member->role]);

        return response()->json(['id' => $member->id, 'role' => $member->role]);
    }

    /** The owner removes someone, or a member leaves the event. */
    public function destroy(Request $request, Event $event, EventMember $member)
    {
        $leaving = $member->user_id === $request->user()->id;
        if (! $leaving) {
            Gate::authorize('own', $event);
        }
        $member->delete();
        EventPolicy::flush();
        Audit::log($event, 'team.removed', $leaving ? "{$member->email} left the team" : "{$member->email} removed from the team");

        return response()->json([], 204);
    }
}
