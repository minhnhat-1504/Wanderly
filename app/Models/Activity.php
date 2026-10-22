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
}
