<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->username)) {
                $base = Str::slug($user->name ?: 'user', '_');
                $base = preg_replace('/[^a-z0-9_]/', '', strtolower($base));
                if (empty($base) || strlen($base) < 3) {
                    $base = 'traveler';
                }
                $candidate = substr($base, 0, 20);
                $unique = $candidate.'_'.rand(100, 999);
                while (static::where('username', $unique)->exists()) {
                    $unique = $candidate.'_'.rand(1000, 99999);
                }
                $user->username = $unique;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'avatar',
        'cover_image',
        'location',
        'phone',
        'website',
        'bio',
        'is_profile_public',
        'show_budget_publicly',
        'locale',
        'currency',
        'notify_in_app',
        'notify_email',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_profile_public' => 'boolean',
            'show_budget_publicly' => 'boolean',
            'notify_in_app' => 'boolean',
            'notify_email' => 'boolean',
        ];
    }

    /**
     * Accessor for username with automatic fallback if null.
     */
    public function getUsernameAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        $fallback = Str::slug($this->name ?: 'user', '_');
        $clean = preg_replace('/[^a-z0-9_]/', '', strtolower($fallback));

        return (! empty($clean) && strlen($clean) >= 3) ? $clean : 'user_'.$this->id;
    }

    /**
     * Accessor for avatar URL with UI-Avatars fallback.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        $name = urlencode($this->name ?: 'User');

        return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=0F766E&bold=true";
    }

    /**
     * Accessor for cover image URL with fallback to null.
     */
    public function getCoverUrlAttribute(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/'.$this->cover_image);
        }

        return null;
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'owner_id');
    }

    public function tripMemberships(): HasMany
    {
        return $this->hasMany(TripMember::class);
    }

    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function savedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_saves')->withTimestamps();
    }

    public function memberPreferences(): HasMany
    {
        return $this->hasMany(MemberPreference::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function settlementPaymentsSent(): HasMany
    {
        return $this->hasMany(SettlementPayment::class, 'from_user_id');
    }

    public function settlementPaymentsReceived(): HasMany
    {
        return $this->hasMany(SettlementPayment::class, 'to_user_id');
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
