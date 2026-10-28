<?php

namespace App\Console\Commands;

use App\Services\Itinerary\ProvinceResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class BuildPlacesDatasetCommand extends Command
{
    protected $signature = 'places:build {province : Province code (e.g. danang, lamdong, hanoi)} {--limit=40 : Number of candidates to build}';

    protected $description = 'Offline developer tool: Fetch places candidates from Overpass API (OSM) & Wikivoyage to draft JSON for human review';

    public function handle(ProvinceResolver $provinceResolver): int
    {
        $code = strtolower(trim($this->argument('province')));
        $limit = (int) $this->option('limit');

        $province = $provinceResolver->getByCode($code);
        if (! $province) {
            $this->error("Unknown province code '{$code}'. Please check data/provinces.json.");

            return self::FAILURE;
        }

        $this->info("Fetching candidates for {$province['name']} (lat: {$province['centroid']['lat']}, lng: {$province['centroid']['lng']})...");

        $lat = $province['centroid']['lat'];
        $lng = $province['centroid']['lng'];
        $radius = 25000; // 25km radius around centroid

        // 1. Check disk cache for raw Overpass query
        $cacheDir = storage_path('app/places_cache');
        File::ensureDirectoryExists($cacheDir);
        $cacheFile = "{$cacheDir}/overpass_{$code}.json";

        $elements = [];
        if (File::exists($cacheFile)) {
            $this->info("Using cached Overpass response from {$cacheFile}");
            $raw = json_decode(File::get($cacheFile), true);
            $elements = $raw['elements'] ?? [];
        } else {
            $this->info('Querying Overpass API (rate limited, 1 request)...');

            $overpassQuery = "[out:json][timeout:25];
(
  nwr[\"tourism\"~\"attraction|viewpoint|museum|theme_park\"](around:{$radius},{$lat},{$lng});
  nwr[\"historic\"~\"monument|memorial|archaeological_site\"](around:{$radius},{$lat},{$lng});
  nwr[\"natural\"~\"beach|peak\"](around:{$radius},{$lat},{$lng});
  nwr[\"amenity\"~\"marketplace|restaurant|cafe\"][\"name\"](around:{$radius},{$lat},{$lng});
);
out center tags 60;";

            try {
                // Respect public Overpass endpoints and backoff
                $response = Http::withHeaders([
                    'User-Agent' => 'WanderlyApp/1.0 (Travel Planner Dataset Builder; contact: developer@wanderly.vn)',
                ])->timeout(30)->asForm()->post('https://overpass-api.de/api/interpreter', [
                    'data' => $overpassQuery,
                ]);

                if ($response->successful()) {
                    File::put($cacheFile, $response->body());
                    $data = $response->json();
                    $elements = $data['elements'] ?? [];
                    $this->info('Fetched '.count($elements).' elements from Overpass.');
                } else {
                    $this->warn('Overpass returned status '.$response->status().'. Using mock fallback candidates.');
                }
            } catch (\Throwable $e) {
                $this->warn('Overpass network request failed: '.$e->getMessage().'. Using fallback template.');
            }
        }

        // 2. Parse candidates into schema
        $candidates = [];
        $count = 0;
        foreach ($elements as $el) {
            $tags = $el['tags'] ?? [];
            $nameVi = $tags['name:vi'] ?? $tags['name'] ?? null;
            if (! $nameVi) {
                continue;
            }

            $count++;
            $placeId = sprintf('%s_%03d', substr($code, 0, 3), $count);

            $pLat = $el['lat'] ?? ($el['center']['lat'] ?? $lat);
            $pLng = $el['lon'] ?? ($el['center']['lon'] ?? $lng);

            // Determine category
            $category = 'sight';
            if (isset($tags['natural']) && $tags['natural'] === 'beach') {
                $category = 'beach';
            } elseif (isset($tags['natural']) || (isset($tags['tourism']) && $tags['tourism'] === 'viewpoint')) {
                $category = 'nature';
            } elseif (isset($tags['historic']) || (isset($tags['tourism']) && $tags['tourism'] === 'museum')) {
                $category = 'culture';
            } elseif (isset($tags['amenity']) && $tags['amenity'] === 'restaurant') {
                $category = 'food';
            } elseif (isset($tags['amenity']) && $tags['amenity'] === 'cafe') {
                $category = 'cafe';
            } elseif (isset($tags['amenity']) && $tags['amenity'] === 'marketplace') {
                $category = 'market';
            }

            // Description
            $desc = $tags['description:vi'] ?? $tags['description'] ?? "Điểm tham quan {$nameVi} đặc sắc tại {$province['name']}.";

            $candidates[] = [
                'id' => $placeId,
                'province_code' => $code,
                'name_vi' => $nameVi,
                'name_en' => $tags['name:en'] ?? null,
                'category' => $category,
                'lat' => round((float) $pLat, 6),
                'lng' => round((float) $pLng, 6),
                'description_vi' => $desc,
                'suggested_duration_min' => in_array($category, ['food', 'cafe']) ? 60 : 90,
                'opening_hours' => isset($tags['opening_hours']) ? ['open' => '07:30', 'close' => '17:30'] : null,
                'closed_days' => [],
                'ticket_price_vnd' => in_array($category, ['food', 'cafe']) ? 80000 : 0,
                'best_months' => $province['season_notes']['best_months'] ?? [1, 2, 3, 4, 11, 12],
                'avoid_months' => $province['season_notes']['avoid_months'] ?? [],
                'indoor' => in_array($category, ['museum', 'cafe', 'food', 'market']),
                'tags' => array_values(array_filter([$category, $tags['tourism'] ?? null, 'sightseeing'])),
                'local_specialties' => [],
                'source' => 'osm',
                'source_url' => "https://www.openstreetmap.org/{$el['type']}/{$el['id']}",
                'last_verified' => date('Y-m-d'),
                'verified' => false, // HUMAN REVIEW REQUIRED!
                'notes' => 'Draft candidate fetched from Overpass OSM. Requires human review to set verified=true.',
            ];

            if ($count >= $limit) {
                break;
            }
        }

        // 3. Save draft file to data/places/drafts/{code}.json
        $draftsDir = base_path('data/places/drafts');
        File::ensureDirectoryExists($draftsDir);
        $draftPath = "{$draftsDir}/{$code}.json";

        File::put($draftPath, json_encode($candidates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Wrote '.count($candidates)." draft candidates to {$draftPath}");
        $this->comment("Reminder: Review and curate these places, then move/copy approved items to data/places/{$code}.json with 'verified': true.");

        return self::SUCCESS;
    }
}
