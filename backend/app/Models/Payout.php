<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Money from paid voting sent to an organiser, recorded by the platform admin. */
class Payout extends Model
{
    protected $fillable = ['event_id', 'user_id', 'amount_kobo', 'note', 'created_by'];

    protected $casts = ['amount_kobo' => 'integer'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
