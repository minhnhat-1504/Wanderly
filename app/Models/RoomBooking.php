<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'hotel_name',
        'room_type',
        'check_in',
        'check_out',
        'price',
        'address',
        'notes',
        'is_budget_included',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'price' => 'decimal:2',
            'is_budget_included' => 'boolean',
        ];
    }

    /**
     * Get the trip that owns the room booking.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
