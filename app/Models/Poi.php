<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poi extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'name',
        'address',
        'category',
        'typical_cost',
        'rating',
        'avg_cost',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'typical_cost' => 'decimal:2',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
