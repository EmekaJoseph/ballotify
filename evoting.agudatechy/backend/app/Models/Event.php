<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    /** Secret ballot with one-time voter codes. */
    public const MODE_CODES = 'codes';
    /** Public paid voting: anyone pays per vote (award nights, pageants). */
    public const MODES = [self::MODE_CODES];

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'link_token',
        'starts_at',
        'ends_at',
        'results_visibility',
        'voting_mode',
        'vote_price_naira',
        'brand_color',
        'reminder_at',
        'reminder_channel',
        'self_registration',
        'member_id_label',
        'language',
        'nominations_enabled',
        'nominations_ends_at',
        'weighted_votes',
        'proxies_enabled',
        'max_proxies',
        'quorum_type',
        'quorum_value',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'upgraded_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'vote_price_naira' => 'integer',
        'self_registration' => 'boolean',
        'ussd_enabled' => 'boolean',
        'nominations_enabled' => 'boolean',
        'nominations_ends_at' => 'datetime',
        'weighted_votes' => 'boolean',
        'proxies_enabled' => 'boolean',
        'max_proxies' => 'integer',
        'quorum_value' => 'integer',
    ];

    public const QUORUM_TYPES = ['percent', 'count'];

    protected $attributes = [
        'results_visibility' => self::RESULTS_AFTER_CLOSE,
        'voting_mode' => self::MODE_CODES,
        'language' => 'en',
    ];

    protected $appends = ['status', 'ballot_locked', 'brand_logo_url'];

    protected $hidden = ['brand_logo_path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function members(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(ResultCertificate::class);
    }

    /** Nominations are being accepted right now (and voting hasn't started). */
    public function nominationsOpen(): bool
    {
        return $this->nominations_enabled
            && (! $this->nominations_ends_at || $this->nominations_ends_at->isFuture())
            && ! $this->hasVotes()
            && $this->status !== self::STATUS_COMPLETED;
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

    protected function brandLogoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->brand_logo_path ? asset($this->brand_logo_path) : null);
    }

    /**
     * Once the first vote is in (or voting has closed) the ballot structure is
     * frozen: no adding/removing/moving categories or candidates, no change of
     * pick limits or voting mode. Cosmetic fixes (names, photos) stay possible
     * and are audited.
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

    /** Events this user owns or co-organises. */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('user_id', $user->id)
            ->orWhereHas('members', fn (Builder $m) => $m->where('user_id', $user->id)));
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

    /** Not closed yet (open now, or opening later). */
    public function scopeNotClosed(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
