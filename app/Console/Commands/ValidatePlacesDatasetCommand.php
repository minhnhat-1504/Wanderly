<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidatePlacesDatasetCommand extends Command
{
    protected $signature = 'places:validate {province?}';

    protected $description = 'Validate places dataset JSON files for schema correctness, missing fields, and duplicates';

    protected array $validCategories = [
        'sight', 'beach', 'nature', 'culture', 'food', 'cafe', 'market', 'hotel', 'experience',
    ];

    protected array $validSources = [
        'osm', 'wikivoyage', 'curated', 'llm_draft',
    ];

    public function handle(): int
    {
        $baseDir = base_path('data/places');
        if (! File::exists($baseDir)) {
            $this->error("Directory {$baseDir} does not exist.");

            return self::FAILURE;
        }

        $targetProvince = $this->argument('province');
        $files = File::files($baseDir);

        $totalPlaces = 0;
        $totalVerified = 0;
        $allIds = [];
        $errors = [];
        $summary = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $provinceCode = $file->getFilenameWithoutExtension();
            if ($targetProvince && $provinceCode !== $targetProvince) {
                continue;
            }

            $content = File::get($file->getRealPath());
            $places = json_decode($content, true);

            if (! is_array($places)) {
                $errors[] = "[{$provinceCode}.json] Invalid JSON format.";

                continue;
            }

            $provTotal = count($places);
            $provVerified = 0;

            foreach ($places as $idx => $p) {
                $totalPlaces++;
                $id = $p['id'] ?? "idx_{$idx}";

                // 1. Check ID uniqueness
                if (empty($p['id'])) {
                    $errors[] = "[{$provinceCode}] Item #{$idx} missing 'id'.";
                } elseif (isset($allIds[$p['id']])) {
                    $errors[] = "[{$provinceCode}] Duplicate ID '{$p['id']}' found in {$allIds[$p['id']]}.";
                } else {
                    $allIds[$p['id']] = "{$provinceCode}.json";
                }

                // 2. Mandatory fields
                if (empty($p['name_vi'])) {
                    $errors[] = "[{$provinceCode}:{$id}] Missing 'name_vi'.";
                }

                if (empty($p['province_code']) || $p['province_code'] !== $provinceCode) {
                    $errors[] = "[{$provinceCode}:{$id}] 'province_code' ('".($p['province_code'] ?? 'null')."') does not match file '{$provinceCode}'.";
                }

                if (empty($p['category']) || ! in_array($p['category'], $this->validCategories, true)) {
                    $errors[] = "[{$provinceCode}:{$id}] Invalid or missing category '".($p['category'] ?? '')."'.";
                }

                // 3. Coordinates range in Vietnam (approx 8.0 - 24.0 N, 102.0 - 110.0 E)
                $lat = $p['lat'] ?? null;
                $lng = $p['lng'] ?? null;
                if (! is_numeric($lat) || $lat < 8.0 || $lat > 24.0) {
                    $errors[] = "[{$provinceCode}:{$id}] Latitude '{$lat}' out of Vietnam range (8.0 - 24.0).";
                }
                if (! is_numeric($lng) || $lng < 102.0 || $lng > 110.5) {
                    $errors[] = "[{$provinceCode}:{$id}] Longitude '{$lng}' out of Vietnam range (102.0 - 110.5).";
                }

                // 4. Duration
                $duration = $p['suggested_duration_min'] ?? null;
                if (! is_numeric($duration) || $duration <= 0) {
                    $errors[] = "[{$provinceCode}:{$id}] 'suggested_duration_min' must be positive integer.";
                }

                // 5. Opening hours format
                if (isset($p['opening_hours']) && $p['opening_hours'] !== null) {
                    if (! is_array($p['opening_hours']) || empty($p['opening_hours']['open']) || empty($p['opening_hours']['close'])) {
                        $errors[] = "[{$provinceCode}:{$id}] 'opening_hours' must have 'open' and 'close' (HH:MM) or be null.";
                    }
                }

                // 6. Source & Verification
                $source = $p['source'] ?? '';
                if (! in_array($source, $this->validSources, true)) {
                    $errors[] = "[{$provinceCode}:{$id}] Invalid source '{$source}'.";
                }

                if (! isset($p['verified']) || ! is_bool($p['verified'])) {
                    $errors[] = "[{$provinceCode}:{$id}] 'verified' must be a boolean.";
                } elseif ($p['verified']) {
                    $provVerified++;
                    $totalVerified++;
                }

                // 7. Months format
                foreach (['best_months', 'avoid_months'] as $mKey) {
                    if (isset($p[$mKey]) && ! is_array($p[$mKey])) {
                        $errors[] = "[{$provinceCode}:{$id}] '{$mKey}' must be an array of integers.";
                    }
                }
            }

            $summary[] = [
                'province' => $provinceCode,
                'total' => $provTotal,
                'verified' => $provVerified,
                'draft' => $provTotal - $provVerified,
            ];
        }

        $this->table(['Province', 'Total Places', 'Verified', 'Draft'], $summary);

        $this->info("Total: {$totalPlaces} places ({$totalVerified} verified).");

        if (! empty($errors)) {
            $this->error('Found '.count($errors).' validation errors:');
            foreach ($errors as $err) {
                $this->line(" - {$err}");
            }

            return self::FAILURE;
        }

        $this->info('All dataset files passed validation cleanly!');

        return self::SUCCESS;
    }
}
