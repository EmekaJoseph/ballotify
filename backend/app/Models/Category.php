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
    public const MOTION = 'motion';
    public const METHODS = [self::PLURALITY, self::RANKED, self::MOTION];
    /** Share of (Yes + No) a motion needs: simple = more Yes than No. */
    public const PASS_RULES = ['simple', 'two_thirds', 'three_quarters'];

    protected $fillable = ['event_id', 'name', 'max_choices', 'shuffle_candidates', 'method', 'description', 'pass_rule', 'groups'];

    protected $casts = ['max_choices' => 'integer', 'shuffle_candidates' => 'boolean', 'ballots' => 'integer', 'groups' => 'array'];

    /** New categories are "choose one" unless told otherwise. */
    protected $attributes = ['max_choices' => 1, 'shuffle_candidates' => false, 'method' => self::PLURALITY, 'ballots' => 0];

    public function isRanked(): bool
    {
        return $this->method === self::RANKED;
    }

    public function isMotion(): bool
    {
        return $this->method === self::MOTION;
    }

    /** Whether a voter in these groups may vote in this category (no groups set = everyone). */
    public function allows(?array $voterGroups): bool
    {
        if (! $this->groups) {
            return true;
        }
        $mine = array_map('mb_strtolower', $voterGroups ?? []);
        foreach ($this->groups as $g) {
            if (in_array(mb_strtolower($g), $mine, true)) {
                return true;
            }
        }
        return false;
    }

    /** How voters fill it in, in words (activity log, certificate). */
    public function methodLabel(): string
    {
        return match (true) {
            $this->isMotion() => 'motion (' . str_replace('_', '-', $this->pass_rule ?? 'simple') . ' majority)',
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
