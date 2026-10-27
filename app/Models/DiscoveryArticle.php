<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveryArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'destination_id',
        'title',
        'slug',
        'category',
        'summary',
        'content',
        'best_season',
        'featured_image',
        'gallery',
        'location_name',
        'highlights',
        'specialties',
        'estimated_duration',
        'is_published',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'highlights' => 'array',
            'specialties' => 'array',
            'is_published' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    /**
     * Get the region this article belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(DiscoveryRegion::class, 'region_id');
    }

    /**
     * Get the destination this article is linked to (if any).
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    /**
     * Scope to published articles only.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope filter by category.
     */
    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if (! $category || $category === 'all') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Scope filter by region ID or slug.
     */
    public function scopeByRegion(Builder $query, $region): Builder
    {
        if (! $region || $region === 'all') {
            return $query;
        }

        if (is_numeric($region)) {
            return $query->where('region_id', $region);
        }

        return $query->whereHas('region', function (Builder $q) use ($region) {
            $q->where('slug', $region);
        });
    }

    /**
     * Scope search query across title, summary, content, and location_name.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        $term = '%'.trim($keyword).'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', $term)
                ->orWhere('summary', 'like', $term)
                ->orWhere('content', 'like', $term)
                ->orWhere('location_name', 'like', $term)
                ->orWhere('best_season', 'like', $term);
        });
    }

    /**
     * Increment article view count safely.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Human-readable category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'culture' => 'Culture & Heritage',
            'cuisine' => 'Gastronomy & Food',
            'festival' => 'Festivals & Events',
            'nature' => 'Nature & Wonder',
            default => ucfirst($this->category),
        };
    }

    /**
     * Badge color classes for UI pills.
     */
    public function getCategoryColorClassAttribute(): string
    {
        return match ($this->category) {
            'culture' => 'bg-amber-50 text-amber-800 border-amber-200',
            'cuisine' => 'bg-rose-50 text-rose-800 border-rose-200',
            'festival' => 'bg-purple-50 text-purple-800 border-purple-200',
            'nature' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            default => 'bg-teal-50 text-teal-800 border-teal-200',
        };
    }
}
