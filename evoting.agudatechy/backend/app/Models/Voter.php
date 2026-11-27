<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'member_id',
        'groups',
        'weight',
        'proxy_voter_id',
        'code',
        'used_at',
        'invited_at',
        'invited_via',
        'reminded_at',
        'invite_error',
    ];

    protected $casts = [
        'groups' => 'array',
        'weight' => 'integer',
        'proxy_voter_id' => 'integer',
        'used_at' => 'datetime',
        'invited_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    /**
     * Clean a group list: "Lagos; Youth" or ["Lagos", "Youth"] -> ["Lagos", "Youth"].
     * Trimmed, duplicates removed (ignoring case), at most 10 of 40 characters.
     */
    public static function normalizeGroups(mixed $input): ?array
    {
        $parts = is_array($input) ? $input : preg_split('/[,;|]/', (string) $input);
        $out = [];
        foreach ($parts as $p) {
            $g = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $p) ?? ''), 0, 40);
            if ($g !== '' && ! in_array(mb_strtolower($g), array_map('mb_strtolower', $out), true)) {
                $out[] = $g;
            }
        }
        return $out ? array_slice($out, 0, 10) : null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** The member who votes for this voter (proxy). */
    public function proxy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'proxy_voter_id');
    }

    /** Members this voter holds a proxy for. */
    public function principals(): HasMany
    {
        return $this->hasMany(self::class, 'proxy_voter_id');
    }
}
