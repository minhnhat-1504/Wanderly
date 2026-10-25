<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'needs',
        'pace',
        'max_budget',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'needs' => 'array',
            'max_budget' => 'decimal:2',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'request_id');
    }
}
