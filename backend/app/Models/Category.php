<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    public const PLURALITY = 'plurality';
    public const RANKED = 'ranked';
    public const METHODS = [self::PLURALITY, self::RANKED];

    protected $fillable = ['event_id', 'name', 'max_choices', 'shuffle_candidates', 'method'];

    protected $casts = ['max_choices' => 'integer', 'shuffle_candidates' => 'boolean', 'ballots' => 'integer'];

    /** New categories are "choose one" unless told otherwise. */
    protected $attributes = ['max_choices' => 1, 'shuffle_candidates' => false, 'method' => self::PLURALITY, 'ballots' => 0];

    public function isRanked(): bool
    {
        return $this->method === self::RANKED;
    }

    /** How voters fill it in, in words (activity log, certificate). */
    public function methodLabel(): string
    {
        return match (true) {
            $this->isRanked() => $this->max_choices ? "ranked choice (up to {$this->max_choices})" : 'ranked choice',
            $this->max_choices === null => 'approval (pick any number)',
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
