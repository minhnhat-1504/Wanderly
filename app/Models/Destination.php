<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'description',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function pois(): HasManyThrough
    {
        return $this->hasManyThrough(Poi::class, Zone::class);
    }
}
