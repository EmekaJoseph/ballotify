<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Paystack payment that upgrades one event to Pro. */
class Payment extends Model
{
    public const PENDING = 'pending';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';

    protected $fillable = [
        'user_id', 'event_id', 'event_name', 'reference', 'provider', 'amount_kobo', 'currency',
        'status', 'channel', 'failure_reason', 'paid_at',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
