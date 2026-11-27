<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what on an event.
 *
 *   ability   owner  editor  voters  viewer
 *   view        ✓      ✓       ✓       ✓     overview, results, activity
 *   voters      ✓      ✓       ✓             voter list, codes, invitations
 *   manage      ✓      ✓                     ballot, settings, branding, upgrade
 *   own         ✓                            delete the event, manage the team
 *
 * People with no role get a 404 (not 403) so event ids cannot be probed.
 */
class EventPolicy
{
    private const ABILITIES = [
        'view' => ['owner', EventMember::EDITOR, EventMember::VOTERS, EventMember::VIEWER],
        'voters' => ['owner', EventMember::EDITOR, EventMember::VOTERS],
        'manage' => ['owner', EventMember::EDITOR],
        'own' => ['owner'],
    ];

    /** @var \WeakMap<Event, array<int, ?string>>|null  per-request cache, never stored on the model */
    private static ?\WeakMap $roles = null;

    /** 'owner', a member role, or null. */
    public static function roleOf(User $user, Event $event): ?string
    {
        if ($event->user_id === $user->id) {
            return 'owner';
        }
        self::$roles ??= new \WeakMap();
        $cache = self::$roles[$event] ?? [];
        if (! array_key_exists($user->id, $cache)) {
            $cache[$user->id] = EventMember::where('event_id', $event->id)->where('user_id', $user->id)->value('role');
            self::$roles[$event] = $cache;
        }
        return $cache[$user->id];
    }

    /** Forget cached roles (after a membership changes). */
    public static function flush(): void
    {
        self::$roles = null;
    }

    /** Abilities this user has on the event (sent to the dashboard to show/hide controls). */
    public static function abilitiesOf(User $user, Event $event): array
    {
        $role = self::roleOf($user, $event);
        return array_keys(array_filter(self::ABILITIES, fn ($roles) => in_array($role, $roles, true)));
    }

    public function view(User $user, Event $event): Response
    {
        return $this->check($user, $event, 'view');
    }

    public function voters(User $user, Event $event): Response
    {
        return $this->check($user, $event, 'voters');
    }

    public function manage(User $user, Event $event): Response
    {
        return $this->check($user, $event, 'manage');
    }

    public function own(User $user, Event $event): Response
    {
        return $this->check($user, $event, 'own');
    }

    private function check(User $user, Event $event, string $ability): Response
    {
        $role = self::roleOf($user, $event);
        if ($role === null) {
            return Response::denyAsNotFound();
        }
        return in_array($role, self::ABILITIES[$ability], true)
            ? Response::allow()
            : Response::deny('Your role on this event doesn’t allow this. Ask the event owner.');
    }
}
