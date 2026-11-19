<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'name', 'max_choices', 'shuffle_candidates'];

    protected $casts = ['max_choices' => 'integer', 'shuffle_candidates' => 'boolean'];

    /** New categories are "choose one" unless told otherwise. */
    protected $attributes = ['max_choices' => 1, 'shuffle_candidates' => false];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('id');
    }
}
