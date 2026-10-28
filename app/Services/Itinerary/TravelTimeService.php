<?php

namespace App\Services\Itinerary;

use Illuminate\Support\Facades\File;

class TravelTimeService
{
    protected array $regionRules = [];
    protected array $matrixCache = [];

    public function __construct(
        protected PlaceRepository $placeRepository,
        protected ProvinceResolver $provinceResolver,
        ?string $rulesPath = null
    ) {
        $path = $rulesPath ?? base_path('data/region_rules.json');
        if (File::exists($path)) {
            $this->regionRules = json_decode(File::get($path), true) ?? [];
        }
    }

    /**
     * Compute travel time in minutes between two places.
     * Used by both planner and deterministic validator.
     *
     * @param  array<string, mixed>|string  $placeA
     * @param  array<string, mixed>|string  $placeB
     */
    public function travel_minutes(array|string $placeA, array|string $placeB, ?string $provinceCode = null): int
    {
        $resolvedA = $this->resolvePlace($placeA, $provinceCode);
        $resolvedB = $this->resolvePlace($placeB, $provinceCode);

        if (! $resolvedA || ! $resolvedB) {
            return 30; // Safe default estimate if place not found
        }

        // Same place requires 0 minutes
        if (($resolvedA['id'] ?? '') === ($resolvedB['id'] ?? '')) {
            return 0;
        }

        $idA = $resolvedA['id'];
        $idB = $resolvedB['id'];
        $prov = $provinceCode ?? $resolvedA['province_code'] ?? 'danang';

        // 1. Check in-memory or disk-cached precomputed matrix
        $matrix = $this->getProvinceMatrix($prov);
        if (isset($matrix[$idA][$idB])) {
            return (int) $matrix[$idA][$idB];
        }

        // 2. Compute on the fly via Haversine + Detour + Region Terrain Factor
        return $this->computeTravelMinutes($resolvedA, $resolvedB, $prov);
    }

    /**
     * Calculate direct great-circle Haversine distance in kilometers.
     */
    public function distance_km(array|string $placeA, array|string $placeB): float
    {
        $resolvedA = $this->resolvePlace($placeA);
        $resolvedB = $this->resolvePlace($placeB);

        if (! $resolvedA || ! $resolvedB) {
            return 0.0;
        }

        return $this->haversine(
            (float) $resolvedA['lat'],
            (float) $resolvedA['lng'],
            (float) $resolvedB['lat'],
            (float) $resolvedB['lng']
        );
    }

    /**
     * Haversine formula calculation between two lat/lng pairs.
     */
    public function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }

    /**
     * Compute travel minutes between two resolved place objects.
     *
     * @param  array<string, mixed>  $placeA
     * @param  array<string, mixed>  $placeB
     */
    public function computeTravelMinutes(array $placeA, array $placeB, string $provinceCode): int
    {
        $directKm = $this->haversine(
            (float) $placeA['lat'],
            (float) $placeA['lng'],
            (float) $placeB['lat'],
            (float) $placeB['lng']
        );

        if ($directKm < 0.2) {
            return 5; // Walking distance in the same neighborhood/complex
        }

        // Retrieve province & region rules
        $province = $this->provinceResolver->getByCode($provinceCode);
        $regionKey = $province['region'] ?? 'central_coast';
        $regionConfig = $this->regionRules['regions'][$regionKey] ?? [
            'terrain_factor' => 1.25,
            'base_speed_kmh' => 40,
        ];

        $roadDetour = (float) ($this->regionRules['default_road_detour_factor'] ?? 1.38);
        $terrainFactor = (float) ($regionConfig['terrain_factor'] ?? 1.25);
        $baseSpeedKmh = (float) ($regionConfig['base_speed_kmh'] ?? 40.0);
        $overheadMinutes = (int) ($this->regionRules['boarding_overhead_minutes'] ?? 10);

        // Effective estimated road distance = Haversine x Detour Factor
        $roadDistanceKm = $directKm * $roadDetour;

        // Drive time in minutes with terrain penalty (mountain/hill roads)
        $drivingMinutes = ($roadDistanceKm / $baseSpeedKmh) * 60 * $terrainFactor;

        $totalMinutes = (int) round($drivingMinutes + $overheadMinutes);

        // Check Island constraints (e.g. Cù Lao Chàm, Cô Tô)
        $islandExtraMinutes = $this->getIslandFerryExtraMinutes($placeA, $placeB, $provinceCode);
        $totalMinutes += $islandExtraMinutes;

        // Minimum 10 minutes travel between distinct non-adjacent places
        return max(10, $totalMinutes);
    }

    /**
     * Check if one place is on an island and another is mainland, adding boat time.
     */
    protected function getIslandFerryExtraMinutes(array $placeA, array $placeB, string $provinceCode): int
    {
        $rules = $this->regionRules['island_special_rules'] ?? [];

        foreach ($rules as $islandKey => $rule) {
            if (($rule['province_code'] ?? '') !== $provinceCode) {
                continue;
            }

            $keywords = $rule['keywords'] ?? [];
            $nameA = $placeA['name_vi'] ?? '';
            $nameB = $placeB['name_vi'] ?? '';

            $isAOnIsland = false;
            $isBOnIsland = false;

            foreach ($keywords as $kw) {
                if (str_contains(mb_strtolower($nameA), mb_strtolower($kw))) {
                    $isAOnIsland = true;
                }
                if (str_contains(mb_strtolower($nameB), mb_strtolower($kw))) {
                    $isBOnIsland = true;
                }
            }

            // If exactly one of them is on the island (one-way crossing required)
            if ($isAOnIsland !== $isBOnIsland) {
                return (int) ($rule['boat_travel_minutes'] ?? 30) + 15; // Boat crossing + boarding waiting time
            }
        }

        return 0;
    }

    /**
     * Build an N x N travel matrix for a given list of places.
     *
     * @param  array<int, array<string, mixed>>  $places
     * @return array<string, array<string, int>>
     */
    public function getMatrixForPlaces(array $places, string $provinceCode): array
    {
        $matrix = [];
        $ids = array_column($places, 'id');
        $placesById = array_combine($ids, $places);

        foreach ($places as $a) {
            $idA = $a['id'];
            $matrix[$idA] = [];
            foreach ($places as $b) {
                $idB = $b['id'];
                if ($idA === $idB) {
                    $matrix[$idA][$idB] = 0;
                } else {
                    $matrix[$idA][$idB] = $this->travel_minutes($a, $b, $provinceCode);
                }
            }
        }

        return $matrix;
    }

    /**
     * Precompute and save on-disk travel matrix for an entire province.
     *
     * @return array<string, array<string, int>>
     */
    public function precomputeMatrix(string $provinceCode): array
    {
        $places = $this->placeRepository->getVerifiedByProvince($provinceCode);
        $matrix = $this->getMatrixForPlaces($places, $provinceCode);

        $cacheDir = storage_path('app/travel_matrix');
        File::ensureDirectoryExists($cacheDir);
        $cacheFile = "{$cacheDir}/{$provinceCode}.json";

        File::put($cacheFile, json_encode($matrix, JSON_PRETTY_PRINT));
        $this->matrixCache[$provinceCode] = $matrix;

        return $matrix;
    }

    /**
     * Load province travel matrix from disk cache if exists.
     *
     * @return array<string, array<string, int>>
     */
    public function getProvinceMatrix(string $provinceCode): array
    {
        if (isset($this->matrixCache[$provinceCode])) {
            return $this->matrixCache[$provinceCode];
        }

        $cacheFile = storage_path("app/travel_matrix/{$provinceCode}.json");
        if (File::exists($cacheFile)) {
            $data = json_decode(File::get($cacheFile), true);
            if (is_array($data)) {
                $this->matrixCache[$provinceCode] = $data;
                return $data;
            }
        }

        return [];
    }

    /**
     * Helper to resolve place input (either array or string ID).
     *
     * @param  array<string, mixed>|string  $place
     * @return array<string, mixed>|null
     */
    protected function resolvePlace(array|string $place, ?string $provinceCode = null): ?array
    {
        if (is_array($place) && isset($place['lat'], $place['lng'])) {
            return $place;
        }

        if (is_string($place)) {
            return $this->placeRepository->getById($place, $provinceCode);
        }

        return null;
    }
}
