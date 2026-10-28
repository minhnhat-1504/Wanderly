<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trip_id',
        'day',
        'time_slot',
        'start_time',
        'end_time',
        'title',
        'cost',
        'category',
        'location',
        'address',
        'notes',
        'type',
        'position',
        'payer_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'day' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Get the trip that owns the activity.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who paid for this activity.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the participant records for this activity.
     */
    public function participants()
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    /**
     * Get the users participating in this activity.
     */
    public function participantUsers()
    {
        return $this->belongsToMany(User::class, 'activity_participants')
            ->withPivot('custom_amount')
            ->withTimestamps();
    }

    /**
     * Calculate total spent for this activity based on participating users.
     * When 0 participants, cost counted toward trip budget is 0.
     * When N participants, cost is N * unit cost (or sum of custom amounts).
     */
    public function calculateTotalSpent(): float
    {
        $unitCost = (float) $this->cost;
        if ($unitCost <= 0) {
            return 0.00;
        }

        // Confirmed marketplace bookings/vouchers purchased for the trip
        if ($this->type === 'booking' || $this->type === 'room') {
            return $unitCost;
        }

        $participants = $this->relationLoaded('participants')
            ? $this->participants
            : $this->participants()->get();

        if ($participants->isEmpty()) {
            return 0.00;
        }

        $total = 0.00;
        foreach ($participants as $p) {
            $total += ($p->custom_amount !== null ? (float) $p->custom_amount : $unitCost);
        }

        return round($total, 2);
    }

    /**
     * Check if a specific user is participating in this activity.
     */
    public function isParticipating(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        if ($this->relationLoaded('participants')) {
            return $this->participants->contains('user_id', $userId);
        }

        return $this->participants()->where('user_id', $userId)->exists();
    }
}
