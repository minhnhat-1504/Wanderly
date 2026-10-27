<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveryRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    /**
     * Get all discovery articles belonging to this region.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(DiscoveryArticle::class, 'region_id')->orderBy('views_count', 'desc');
    }

    /**
     * Get published articles for this region.
     */
    public function publishedArticles(): HasMany
    {
        return $this->hasMany(DiscoveryArticle::class, 'region_id')
            ->where('is_published', true)
            ->orderBy('views_count', 'desc');
    }
}
