<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A post on the ballot. Most votes wins: choose one, pick up to N, or any number (max_choices null). */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'name', 'max_choices', 'shuffle_candidates', 'nominee_suggestions'];

    protected $casts = ['max_choices' => 'integer', 'shuffle_candidates' => 'boolean', 'ballots' => 'integer', 'nominee_suggestions' => 'array'];

    /** New categories are "choose one" unless told otherwise. */
    protected $attributes = ['max_choices' => 1, 'shuffle_candidates' => false, 'ballots' => 0];

    /** How voters fill it in, in words (activity log). */
    public function methodLabel(): string
    {
        return match (true) {
            $this->max_choices === null => 'pick any number',
            $this->max_choices === 1 => 'choose one',
            default => "pick up to {$this->max_choices}",
        };
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('id');
    }
}
