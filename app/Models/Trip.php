<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'destination',
        'start_date',
        'end_date',
        'budget',
        'cover',
        'privacy',
        'status',
        'share_slug',
        'is_shared',
        'is_share_budget_visible',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
            'is_shared' => 'boolean',
            'is_share_budget_visible' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TripMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trip_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roomBookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    public function travelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function memberPreferences(): HasMany
    {
        return $this->hasMany(MemberPreference::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function settlementPayments(): HasMany
    {
        return $this->hasMany(SettlementPayment::class);
    }
}
