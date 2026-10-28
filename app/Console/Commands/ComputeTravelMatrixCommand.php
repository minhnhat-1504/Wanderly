<?php

namespace App\Console\Commands;

use App\Services\Itinerary\PlaceRepository;
use App\Services\Itinerary\TravelTimeService;
use Illuminate\Console\Command;

class ComputeTravelMatrixCommand extends Command
{
    protected $signature = 'travel:matrix {province? : Specific province code to compute (optional)}';

    protected $description = 'Precompute per-province travel time matrix between verified places and cache to disk';

    public function handle(TravelTimeService $travelTimeService, PlaceRepository $placeRepository): int
    {
        $targetProvince = $this->argument('province');
        $provinces = $targetProvince ? [$targetProvince] : ['danang', 'lamdong', 'khanhhoa', 'hanoi', 'quangninh'];

        foreach ($provinces as $code) {
            $places = $placeRepository->getVerifiedByProvince($code);
            $count = count($places);

            if ($count === 0) {
                $this->warn("No verified places found for province '{$code}'. Skipping.");
                continue;
            }

            $this->info("Computing {$count}x{$count} travel matrix for '{$code}'...");
            $start = microtime(true);
            $matrix = $travelTimeService->precomputeMatrix($code);
            $duration = round((microtime(true) - $start) * 1000, 2);

            $pairs = $count * $count;
            $this->info("Successfully precomputed {$pairs} pairs for '{$code}' in {$duration}ms.");
        }

        $this->info('Travel time matrix precomputation complete!');
        return self::SUCCESS;
    }
}
