<?php

namespace App\Services\Itinerary;

use Illuminate\Support\Facades\File;

class PlaceRepository
{
    /**
     * Cache of loaded places indexed by province code.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $cache = [];

    protected string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? base_path('data/places');
    }

    /**
     * Get all places for a province (both verified and unverified).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByProvince(string $provinceCode): array
    {
        $provinceCode = strtolower(trim($provinceCode));

        if (isset($this->cache[$provinceCode])) {
            return $this->cache[$provinceCode];
        }

        $filePath = "{$this->baseDir}/{$provinceCode}.json";
        if (! File::exists($filePath)) {
            $this->cache[$provinceCode] = [];
            return [];
        }

        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (! is_array($data)) {
            $this->cache[$provinceCode] = [];
            return [];
        }

        $this->cache[$provinceCode] = $data;
        return $data;
    }

    /**
     * Get verified-only places for a province.
     * Excludes any draft or unverified entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getVerifiedByProvince(string $provinceCode): array
    {
        $all = $this->getByProvince($provinceCode);

        return array_values(array_filter($all, function ($place) {
            return ! empty($place['verified']) 
                && $place['verified'] === true 
                && ($place['source'] ?? '') !== 'llm_draft';
        }));
    }

    /**
     * Find a place by its unique ID across all province datasets.
     *
     * @return array<string, mixed>|null
     */
    public function getById(string $placeId, ?string $provinceHint = null): ?array
    {
        $placeId = trim($placeId);

        // If province hint is provided, search it first
        if ($provinceHint) {
            foreach ($this->getByProvince($provinceHint) as $p) {
                if (($p['id'] ?? '') === $placeId) {
                    return $p;
                }
            }
        }

        // Search in all available province files
        if (File::exists($this->baseDir)) {
            $files = File::files($this->baseDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $provinceCode = $file->getFilenameWithoutExtension();
                    foreach ($this->getByProvince($provinceCode) as $p) {
                        if (($p['id'] ?? '') === $placeId) {
                            return $p;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Filter verified places by categories, month, indoor preference, and budget.
     *
     * @param  array{
     *     categories?: array<int, string>,
     *     month?: int,
     *     indoor?: bool,
     *     max_price?: float,
     *     interests?: array<int, string>
     * }  $filters
     * @return array<int, array<string, mixed>>
     */
    public function filterPlaces(string $provinceCode, array $filters = []): array
    {
        $places = $this->getVerifiedByProvince($provinceCode);

        return array_values(array_filter($places, function ($place) use ($filters) {
            // Category filter
            if (! empty($filters['categories'])) {
                if (! in_array($place['category'] ?? '', $filters['categories'], true)) {
                    return false;
                }
            }

            // Month season filter (exclude avoid_months)
            if (! empty($filters['month'])) {
                $month = (int) $filters['month'];
                $avoid = $place['avoid_months'] ?? [];
                if (in_array($month, $avoid, true)) {
                    return false;
                }
            }

            // Indoor filter
            if (isset($filters['indoor'])) {
                if (($place['indoor'] ?? false) !== (bool) $filters['indoor']) {
                    return false;
                }
            }

            // Max ticket price
            if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
                $price = $place['ticket_price_vnd'] ?? 0;
                if ($price !== null && $price > (float) $filters['max_price']) {
                    return false;
                }
            }

            // Interest tag match
            if (! empty($filters['interests'])) {
                $tags = $place['tags'] ?? [];
                $hasCommon = false;
                foreach ($filters['interests'] as $interest) {
                    if (in_array(strtolower($interest), array_map('strtolower', $tags), true)) {
                        $hasCommon = true;
                        break;
                    }
                }
                if (! $hasCommon) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Get dataset attributions & license requirements.
     *
     * @return array<string, string>
     */
    public function getAttributions(): array
    {
        return [
            'osm' => 'Dữ liệu địa lý và tọa độ © OpenStreetMap contributors, theo giấy phép Open Database License (ODbL).',
            'wikivoyage' => 'Nội dung hướng dẫn & giới thiệu trích dẫn từ Wikivoyage/Wikipedia Tiếng Việt theo giấy phép Creative Commons Attribution-ShareAlike 4.0 (CC BY-SA 4.0).',
            'weather' => 'Dữ liệu thời tiết cung cấp bởi Open-Meteo.com (CC BY 4.0).',
            'disclaimer' => 'Giờ mở cửa và giá vé có thể thay đổi, hãy kiểm tra lại trước khi đi.',
        ];
    }
}
