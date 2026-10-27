<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\DiscoveryArticle;
use App\Models\DiscoveryRegion;
use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DiscoveryService
{
    /**
     * Get all discovery regions with published article counts.
     *
     * @return Collection<int, DiscoveryRegion>
     */
    public function getRegions(): Collection
    {
        return DiscoveryRegion::withCount(['articles as published_articles_count' => function ($query) {
            $query->where('is_published', true);
        }])
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Search and filter discovery articles by keyword, region, and category.
     */
    public function searchArticles(
        ?string $keyword = null,
        ?string $region = null,
        ?string $category = null,
        int $perPage = 9
    ): LengthAwarePaginator {
        return DiscoveryArticle::query()
            ->published()
            ->with(['region', 'destination'])
            ->byRegion($region)
            ->byCategory($category)
            ->search($keyword)
            ->orderByDesc('views_count')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get top featured discovery articles.
     *
     * @return Collection<int, DiscoveryArticle>
     */
    public function getFeaturedArticles(int $limit = 6): Collection
    {
        return DiscoveryArticle::query()
            ->published()
            ->with(['region', 'destination'])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get curated thematic sample itineraries for Vietnam travel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCuratedItineraries(): array
    {
        return [
            [
                'title' => '4-Day Ha Giang Loop: Serpentine Passes & Tu San Canyon',
                'slug' => 'ha-giang-loop-4-days',
                'destination' => 'Ha Giang',
                'duration' => '4 Days / 3 Nights',
                'duration_days' => 4,
                'region' => 'Northern Vietnam',
                'difficulty' => 'Adventure',
                'cover_image' => 'images/destinations/hagiang.jpg',
                'highlights' => ['Bac Sum Pass', 'Dong Van Geopark', 'Ma Pi Leng Pass', 'Nho Que Boat Ride'],
                'overview' => 'Conquer the legendary northern mountain passes, sleep in authentic H’Mong homestays, and cruise through Southeast Asia’s deepest canyon.',
            ],
            [
                'title' => '3-Day Hoi An & Hue: Imperial Gastronomy & Lantern Heritage',
                'slug' => 'hoi-an-hue-heritage-3-days',
                'destination' => 'Hoi An & Hue',
                'duration' => '3 Days / 2 Nights',
                'duration_days' => 3,
                'region' => 'Central Vietnam',
                'difficulty' => 'Cultural & Leisure',
                'cover_image' => 'images/destinations/hoian.jpg',
                'highlights' => ['Hoi An Ancient Streets', 'Full Moon Lanterns', 'Imperial Citadel', 'Street Food Tour'],
                'overview' => 'Immerse yourself in UNESCO heritage, cycling through coconut forests, and savoring royal imperial culinary recipes passed down through generations.',
            ],
            [
                'title' => '3-Day Da Lat Highland Retreat: Pine Forests & Cloud Hunting',
                'slug' => 'da-lat-mist-retreat-3-days',
                'destination' => 'Da Lat',
                'duration' => '3 Days / 2 Nights',
                'duration_days' => 3,
                'region' => 'Central Highlands',
                'difficulty' => 'Chill & Photography',
                'cover_image' => 'images/destinations/dalat.jpg',
                'highlights' => ['Cau Dat Cloud Hunting', 'Tuyen Lam Lake SUP', 'Arabica Farm Tour', 'Night Market Pizza'],
                'overview' => 'Breathe in crisp pine air, paddle across glass-like volcanic lakes at dawn, and explore hidden hillside cafes in misty valleys.',
            ],
            [
                'title' => '4-Day Phu Quoc Tropical Escape: Island Hopping & Coral Reefs',
                'slug' => 'phu-quoc-tropical-escape-4-days',
                'destination' => 'Phu Quoc',
                'duration' => '4 Days / 3 Nights',
                'duration_days' => 4,
                'region' => 'Southern Vietnam',
                'difficulty' => 'Beach & Island',
                'cover_image' => 'images/destinations/phuquoc.jpg',
                'highlights' => ['Bai Sao Soft Sands', 'Hon Thom Cable Car', 'An Thoi Coral Diving', 'Sunset Cocktail Bars'],
                'overview' => 'Bask under tropical sunshine, soar over the turquoise sea on a record-breaking cable car, and feast on fresh island seafood catches.',
            ],
        ];
    }

    /**
     * 1-Click "Add to Current Trip":
     * Injects a discovery article destination / landmark directly into a trip's timeline as an activity.
     */
    public function addToTrip(
        Trip $trip,
        DiscoveryArticle $article,
        int $day = 1,
        string $timeSlot = 'morning'
    ): Activity {
        $allowedSlots = ['morning', 'midday', 'afternoon', 'evening'];
        if (! in_array($timeSlot, $allowedSlots, true)) {
            $timeSlot = 'morning';
        }

        $day = max(1, min(60, $day));

        // Map article category to valid activity enum category
        $category = match ($article->category) {
            'cuisine' => 'dining',
            'culture' => 'sightseeing',
            'festival' => 'entertainment',
            default => 'sightseeing',
        };

        // Find next position
        $maxPosition = (int) Activity::where('trip_id', $trip->id)
            ->where('day', $day)
            ->where('time_slot', $timeSlot)
            ->max('position');

        return Activity::create([
            'trip_id' => $trip->id,
            'title' => 'Explore: '.$article->title,
            'day' => $day,
            'time_slot' => $timeSlot,
            'position' => $maxPosition + 1,
            'cost' => 0.00,
            'category' => $category,
            'location' => $article->location_name ?: ($article->destination?->name ?? $article->title),
            'address' => $article->location_name,
            'notes' => $article->summary ?: 'Added from Wanderly Vietnam Discovery Portal',
        ]);
    }
}
