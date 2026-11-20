<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A signed snapshot of an event's final results. The signature is an HMAC of
 * the payload with the app key, so a changed payload (even edited directly in
 * the database) no longer verifies.
 */
class ResultCertificate extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'serial', 'payload', 'signature', 'issued_by', 'issued_at'];

    protected $casts = ['payload' => 'array', 'issued_at' => 'datetime'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public static function sign(array $payload): string
    {
        return hash_hmac('sha256', self::canonical($payload), (string) config('app.key'));
    }

    public function isAuthentic(): bool
    {
        return hash_equals($this->signature, self::sign($this->payload));
    }

    /** Stable JSON (sorted keys) so the same data always gives the same signature. */
    private static function canonical(array $data): string
    {
        $sort = function ($v) use (&$sort) {
            if (! is_array($v)) {
                return $v;
            }
            if (array_is_list($v)) {
                return array_map($sort, $v);
            }
            ksort($v);
            return array_map($sort, $v);
        };
        return json_encode($sort($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
