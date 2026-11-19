<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A co-organiser of an event.
 *   editor  - everything except deleting the event and managing the team
 *   voters  - voter list and sending codes, plus viewing results
 *   viewer  - read only: overview, results, activity
 */
class EventMember extends Model
{
    public const EDITOR = 'editor';
    public const VOTERS = 'voters';
    public const VIEWER = 'viewer';
    public const ROLES = [self::EDITOR, self::VOTERS, self::VIEWER];

    protected $fillable = ['event_id', 'user_id', 'email', 'role', 'invited_by'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attach pending invitations sent to this user's email. Only for confirmed
     * addresses, so nobody can claim an invite by signing up with someone else's email.
     */
    public static function claimFor(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            return;
        }
        try {
            self::whereNull('user_id')->where('email', strtolower($user->email))->update(['user_id' => $user->id]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
