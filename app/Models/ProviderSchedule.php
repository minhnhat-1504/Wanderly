<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'blocked_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'blocked_date' => 'date',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
