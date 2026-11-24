<?php

namespace App\Models;

use App\Notifications\ResetPasswordLink;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $appends = ['two_factor_enabled'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Encrypted at rest with APP_KEY.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
            'pro_account_at' => 'datetime',
            'sms_credits' => 'integer',
            'onboarded_at' => 'datetime',
        ];
    }

    /** Co-organiser memberships (events shared with this user). */
    public function memberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function getTwoFactorEnabledAttribute(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLink($token));
    }

    /** Verification link points at the frontend, which confirms it with the API. */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailAddress());
    }
}
