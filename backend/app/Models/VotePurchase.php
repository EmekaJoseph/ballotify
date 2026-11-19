<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Votes bought through Paystack on a paid (public) voting event. */
class VotePurchase extends Model
{
    public const PENDING = 'pending';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';
    /** Paid after voting closed: not counted, the buyer should be refunded. */
    public const LATE = 'late';

    protected $fillable = [
        'event_id', 'candidate_id', 'candidate_name', 'quantity', 'amount_kobo', 'fee_kobo', 'currency',
        'reference', 'email', 'name', 'status', 'channel', 'failure_reason', 'paid_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount_kobo' => 'integer',
        'fee_kobo' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
