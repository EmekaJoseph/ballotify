<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A public nomination for a category (before voting starts). */
class Nomination extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['event_id', 'category_id', 'nominee_name', 'normalized_name', 'reason', 'nominator_email', 'ip_hash'];

    protected $hidden = ['ip_hash', 'nominator_email'];

    protected $casts = ['created_at' => 'datetime'];

    public static function normalize(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? ''));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
