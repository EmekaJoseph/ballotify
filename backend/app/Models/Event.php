<?php

namespace App\Models;

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

    protected $fillable = [
        'user_id',
        'event_type_id',
        'name',
        'description',
        'link_token',
        'expected_voters',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'expected_voters' => 'integer',
    ];

    protected $appends = ['status', 'selection_mode'];

    protected $hidden = ['type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
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

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
