<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementPayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trip_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'status',
        'proof_image',
        'bank_bin',
        'bank_account_number',
        'note',
        'completed_at',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the trip associated with the settlement payment.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who pays (debtor).
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user who receives payment (creditor).
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Check if the payment has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
