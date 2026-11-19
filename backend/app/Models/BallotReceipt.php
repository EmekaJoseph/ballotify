<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Proof that a ballot was counted. Only a hash of the code is stored, and it is
 * not linked to a voter or to any choice, so it reveals nothing about the vote.
 */
class BallotReceipt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['event_id', 'code_hash'];

    protected $casts = ['created_at' => 'datetime'];

    /** Unambiguous characters, like voter codes. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function hash(int $eventId, string $code): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
        return hash_hmac('sha256', $eventId . '|' . $clean, (string) config('app.key'));
    }

    /** Creates a receipt and returns its code, e.g. "K7QM-3XPA-9D". */
    public static function issue(int $eventId): string
    {
        $max = strlen(self::ALPHABET) - 1;
        do {
            $raw = '';
            for ($i = 0; $i < 10; $i++) {
                $raw .= self::ALPHABET[random_int(0, $max)];
            }
            $hash = self::hash($eventId, $raw);
        } while (self::where('event_id', $eventId)->where('code_hash', $hash)->exists());

        self::create(['event_id' => $eventId, 'code_hash' => $hash]);

        return substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8);
    }
}
