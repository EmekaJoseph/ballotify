<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    public const RESULTS_LIVE = 'live';
    public const RESULTS_AFTER_CLOSE = 'after_close';
    public const RESULTS_PRIVATE = 'private';
    public const RESULTS_OPTIONS = [self::RESULTS_LIVE, self::RESULTS_AFTER_CLOSE, self::RESULTS_PRIVATE];

    protected $fillable = [
        'user_id',
        'event_type_id',
        'name',
        'description',
        'link_token',
        'starts_at',
        'ends_at',
        'results_visibility',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $attributes = [
        'results_visibility' => self::RESULTS_AFTER_CLOSE,
    ];

    protected $appends = ['status', 'selection_mode', 'ballot_locked'];

    protected $hidden = ['type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    /** In the order they were added, which is the order on the ballot. */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('id');
    }

    /** In the order they were added, which is the order on the ballot. */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('id');
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    protected function status(): Attribute
    {
        return Attribute::get(function () {
            $now = now();
            if ($this->starts_at && $now->lt($this->starts_at)) {
                return self::STATUS_UPCOMING;
            }
            if ($this->ends_at && $now->gt($this->ends_at)) {
                return self::STATUS_COMPLETED;
            }
            return self::STATUS_ACTIVE;
        });
    }

    /** 'single' (one candidate per category) or 'multiple'. Defaults to 'single'. */
    protected function selectionMode(): Attribute
    {
        return Attribute::get(fn () => $this->type?->key ?? 'single');
    }

    /**
     * Once the first ballot is cast (or voting has closed) the ballot structure is
     * frozen: no adding/removing/moving categories or candidates, no change of
     * voting style. Cosmetic fixes (names, photos) stay possible and are audited.
     */
    protected function ballotLocked(): Attribute
    {
        return Attribute::get(fn () => $this->status === self::STATUS_COMPLETED || $this->hasVotes());
    }

    public function hasVotes(): bool
    {
        if (array_key_exists('voted_count', $this->attributes)) {
            return (int) $this->attributes['voted_count'] > 0;
        }
        return $this->voters()->whereNotNull('used_at')->exists();
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Whether the public results page may show the tally right now. */
    public function resultsArePublic(): bool
    {
        return match ($this->results_visibility) {
            self::RESULTS_LIVE => true,
            self::RESULTS_AFTER_CLOSE => $this->status === self::STATUS_COMPLETED,
            default => false,
        };
    }

    /**
     * Public lookup by voting-link token. Events of suspended organisers are
     * treated as not found, so their ballots and results go dark at once.
     */
    public function scopePublicToken(Builder $query, string $token): Builder
    {
        return $query->where('link_token', $token)
            ->whereHas('user', fn (Builder $q) => $q->whereNull('suspended_at'));
    }

    /** Filter by computed status (upcoming / active / completed) in SQL. */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        $now = now();
        return match ($status) {
            self::STATUS_UPCOMING => $query->whereNotNull('starts_at')->where('starts_at', '>', $now),
            self::STATUS_COMPLETED => $query->whereNotNull('ends_at')->where('ends_at', '<', $now),
            self::STATUS_ACTIVE => $query
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now)),
            default => $query,
        };
    }
}
