<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'provider_id',
        'total_price',
        'message',
        'valid_until',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'valid_until' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class, 'request_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
