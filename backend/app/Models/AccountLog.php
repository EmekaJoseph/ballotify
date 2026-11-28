<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the platform account log (see the account_logs migration).
 * Write entries with App\Support\AccountLogger::record().
 */
class AccountLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'user_id', 'user_email', 'action', 'description', 'ip', 'meta'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    /** Groups shown as filters on the admin page. */
    public const GROUPS = [
        'admin' => 'admin.',
        'signin' => 'auth.login',
        'security' => 'account.',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
