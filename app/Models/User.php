<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
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
        ];
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
}
