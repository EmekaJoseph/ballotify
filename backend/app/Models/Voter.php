<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'code',
        'used_at',
        'invited_at',
        'invited_via',
        'reminded_at',
        'invite_error',
    ];

    protected $casts = [
        'groups' => 'array',
        'used_at' => 'datetime',
        'invited_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    /**
     * Clean a group list: "Law; 300L" or ["Law", "300L"] -> ["Law", "300L"].
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
}
