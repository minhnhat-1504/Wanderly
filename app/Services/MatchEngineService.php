<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class MatchEngineService
{
    protected CollaborationService $collaborationService;

    protected VotingService $votingService;

    protected BudgetService $budgetService;

    public function __construct(
        CollaborationService $collaborationService,
        VotingService $votingService,
        BudgetService $budgetService
    ) {
        $this->collaborationService = $collaborationService;
        $this->votingService = $votingService;
        $this->budgetService = $budgetService;
    }

    /**
     * Generate an intelligent rule-based draft itinerary for the trip.
     *
     * Formula:
     *   Score = w1 * VoteScore + w2 * MoodMatch + w3 * Rating - w4 * (Cost / Budget)
     */
    public function generateDraftItinerary(Trip $trip): array
    {
        // 1. Calculate Group Mood & Preferred Pace
        $groupMood = $this->collaborationService->calculateGroupMood($trip);
        $commonPace = $groupMood['common_pace'] ?? 'balanced';
        $topInterests = array_column($groupMood['top_interests'] ?? [], 'tag');

        // 2. Pace slots definition (compatible with activities table enum)
        $slotsByPace = match ($commonPace) {
            'relaxed' => ['morning', 'afternoon', 'evening'],
            default => ['morning', 'midday', 'afternoon', 'evening'],
        };
        $dailyQuota = count($slotsByPace);

        // 3. Determine trip duration in days
        $tripDays = 3;
        if ($trip->start_date && $trip->end_date) {
            $tripDays = (int) $trip->start_date->diffInDays($trip->end_date) + 1;
        }
        $tripDays = max(1, min(14, $tripDays));

        // 4. Retrieve candidate pool
        $candidates = $this->votingService->getCandidatePool($trip);
        $tripBudget = (float) ($trip->budget ?? 0);

        // Weights
        $w1 = 0.35; // Votes
        $w2 = 0.30; // Mood
        $w3 = 0.20; // Rating
        $w4 = 0.15; // Cost penalty

        // 5. Score every candidate
        $scoredCandidates = $candidates->map(function ($c) use ($w1, $w2, $w3, $w4, $topInterests, $tripBudget) {
            // VoteScore normalized between 0 and 10 (base 5, +1 vote = +2)
            $voteScore = max(0.0, min(10.0, 5.0 + ($c->score * 2.0)));

            // MoodMatch (0 to 10)
            $catLower = strtolower($c->category ?? '');
            $moodMatch = 5.0;
            if (! empty($topInterests)) {
                if (in_array($catLower, $topInterests, true)) {
                    $moodMatch = 9.5;
                } else {
                    foreach ($topInterests as $interest) {
                        if (str_contains($catLower, $interest) || str_contains($interest, $catLower)) {
                            $moodMatch = 8.5;
                            break;
                        }
                    }
                }
            }

            // RatingScore normalized to 0-10
            $ratingScore = min(10.0, max(0.0, ((float) $c->rating) * 2.0));

            // Cost penalty (0 to 10)
            $costPenalty = 0.0;
            if ($tripBudget > 0 && $c->cost > 0) {
                $costRatio = $c->cost / $tripBudget;
                $costPenalty = min(10.0, $costRatio * 50.0);
            }

            // Objective score
            $finalScore = ($w1 * $voteScore) + ($w2 * $moodMatch) + ($w3 * $ratingScore) - ($w4 * $costPenalty);
            $normalizedScore = round(max(1.0, min(10.0, $finalScore)), 1);

            // Explanation string
            $reasons = [];
            if ($c->score > 0) {
                $reasons[] = "+{$c->score} group votes";
            }
            if ($moodMatch >= 8.0) {
                $reasons[] = "matches '{$c->category}' group preference";
            }
            if ($c->rating >= 4.5) {
                $reasons[] = "top rated ({$c->rating}★)";
            }
            if ($costPenalty > 3.0) {
                $reasons[] = 'budget impact considered';
            }
            $explanation = empty($reasons) ? 'Balanced destination highlight' : implode(', ', $reasons);

            $c->match_score = $normalizedScore;
            $c->score_breakdown = [
                'vote_score' => round($voteScore, 1),
                'mood_match' => round($moodMatch, 1),
                'rating_score' => round($ratingScore, 1),
                'cost_penalty' => round($costPenalty, 1),
                'final_score' => $normalizedScore,
                'explanation' => $explanation,
            ];

            return $c;
        });

        // 6. Zone clustering: Group candidates by zone to minimize travel distance
        $zoneGroups = $scoredCandidates->groupBy(function ($item) {
            return $item->zone_id ? 'zone_'.$item->zone_id : ($item->zone ?: 'default');
        });

        // Sort zones by average match score
        $sortedZones = $zoneGroups->sortByDesc(function ($items) {
            return $items->avg('match_score');
        })->values();

        // 7. Distribute activities into days and slots adhering to budget and zone clustering
        $days = [];
        $accumulatedCost = 0.0;
        $usedCandidateKeys = [];
        $zoneCount = $sortedZones->count();

        for ($day = 1; $day <= $tripDays; $day++) {
            // Pick a zone for this day (cycle through available zones)
            $dayZoneGroup = $zoneCount > 0 ? $sortedZones[($day - 1) % $zoneCount] : collect();
            $zoneName = $dayZoneGroup->first()?->zone ?? __('City Exploration');

            // Sort zone items by match score
            $availableInZone = $dayZoneGroup->filter(function ($item) use ($usedCandidateKeys) {
                return ! in_array($item->key, $usedCandidateKeys, true);
            })->sortByDesc('match_score')->values();

            // Fallback to general pool if zone doesn't have enough items
            $backupItems = $scoredCandidates->filter(function ($item) use ($usedCandidateKeys) {
                return ! in_array($item->key, $usedCandidateKeys, true);
            })->sortByDesc('match_score')->values();

            $dayActivities = [];
            $slotIndex = 0;

            foreach ($slotsByPace as $slot) {
                // Get next best candidate
                $candidate = $availableInZone->shift() ?? $backupItems->shift();

                if (! $candidate) {
                    break;
                }

                // Check budget headroom
                if ($tripBudget > 0 && ($accumulatedCost + $candidate->cost) > $tripBudget && $candidate->cost > 0) {
                    // Try to find a cheaper or free activity
                    $cheaper = $backupItems->first(function ($item) use ($tripBudget, $accumulatedCost) {
                        return ($accumulatedCost + $item->cost) <= $tripBudget;
                    });
                    if ($cheaper) {
                        $candidate = $cheaper;
                    }
                }

                $usedCandidateKeys[] = $candidate->key;
                $accumulatedCost += $candidate->cost;

                $dayActivities[] = [
                    'day' => $day,
                    'time_slot' => $slot,
                    'title' => $candidate->name,
                    'category' => $candidate->category,
                    'location' => $candidate->description ?: $candidate->name,
                    'cost' => $candidate->cost,
                    'zone' => $candidate->zone,
                    'score_breakdown' => $candidate->score_breakdown,
                ];

                $slotIndex++;
            }

            $days[] = [
                'day_number' => $day,
                'zone_name' => $zoneName,
                'activities' => $dayActivities,
            ];
        }

        return [
            'trip_id' => $trip->id,
            'trip_name' => $trip->name,
            'pace' => $commonPace,
            'total_activities' => count($usedCandidateKeys),
            'total_cost' => $accumulatedCost,
            'budget' => $tripBudget,
            'is_within_budget' => $tripBudget <= 0 || $accumulatedCost <= $tripBudget,
            'group_mood_score' => $groupMood['harmony_score'] ?? 100,
            'days' => $days,
        ];
    }

    /**
     * Apply generated draft activities to the trip timeline.
     */
    public function applyDraftToTimeline(Trip $trip, array $draft): bool
    {
        return DB::transaction(function () use ($trip, $draft) {
            $days = $draft['days'] ?? [];

            foreach ($days as $dayData) {
                $dayNumber = (int) ($dayData['day_number'] ?? 1);
                $activities = $dayData['activities'] ?? [];

                $position = 1;
                foreach ($activities as $act) {
                    $rawSlot = $act['time_slot'] ?? 'morning';
                    $slot = match ($rawSlot) {
                        'lunch' => 'midday',
                        default => in_array($rawSlot, ['morning', 'midday', 'afternoon', 'evening']) ? $rawSlot : 'morning',
                    };

                    Activity::create([
                        'trip_id' => $trip->id,
                        'day' => $dayNumber,
                        'time_slot' => $slot,
                        'title' => $act['title'],
                        'category' => $this->normalizeCategory($act['category'] ?? null),
                        'cost' => (float) ($act['cost'] ?? 0),
                        'location' => $act['location'] ?? $act['title'],
                        'position' => $position++,
                    ]);
                }
            }

            return true;
        });
    }

    /**
     * Normalize category to match the activities table enum constraint.
     */
    protected function normalizeCategory(?string $category): string
    {
        $cat = strtolower(trim((string) $category));

        if (in_array($cat, ['sightseeing', 'dining', 'cafe', 'shopping', 'entertainment', 'other'], true)) {
            return $cat;
        }

        if (in_array($cat, ['foodie', 'food', 'restaurant'], true)) {
            return 'dining';
        }

        if (in_array($cat, ['chill', 'coffee'], true)) {
            return 'cafe';
        }

        if (in_array($cat, ['nature', 'culture', 'monument', 'landmark'], true)) {
            return 'sightseeing';
        }

        if (in_array($cat, ['market', 'store'], true)) {
            return 'shopping';
        }

        if (in_array($cat, ['nightlife', 'bar', 'club', 'adventure'], true)) {
            return 'entertainment';
        }

        return 'sightseeing';
    }
}
