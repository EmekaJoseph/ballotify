<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only record of organiser actions on an event. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['event_id', 'user_id', 'action', 'description', 'during_voting', 'meta'];

    protected $casts = [
        'during_voting' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
