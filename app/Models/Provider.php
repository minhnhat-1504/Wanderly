<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'service_type',
        'destination_id',
        'bio',
        'rating',
        'phone',
        'address',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'is_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ProviderSchedule::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
