<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'category_id', 'name', 'image_path'];

    /**
     * The running vote total is never serialised by default: ballots and admin
     * lists must not leak results. Results endpoints read it explicitly.
     */
    protected $hidden = ['votes'];

    protected $casts = ['votes' => 'integer'];

    protected $appends = ['image_url'];

    /** Absolute URL of the candidate photo (served from public/), or null. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
