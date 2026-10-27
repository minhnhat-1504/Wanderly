<?php

namespace App\Services;

use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReverseMemoryService
{
    /**
     * Curated destination photo fallbacks when trip has no uploaded chat attachments.
     *
     * @var array<string, array<string>>
     */
    protected array $destinationPhotoMap = [
        'Ha Giang' => ['images/destinations/hagiang.jpg', 'images/destinations/hero.jpg'],
        'Ha Long' => ['images/destinations/hero.jpg', 'images/destinations/danang.jpg'],
        'Hoi An' => ['images/destinations/hoian.jpg', 'images/destinations/danang.jpg'],
        'Da Nang' => ['images/destinations/danang.jpg', 'images/destinations/hoian.jpg'],
        'Da Lat' => ['images/destinations/dalat.jpg', 'images/destinations/phuquoc.jpg'],
        'Phu Quoc' => ['images/destinations/phuquoc.jpg', 'images/destinations/dalat.jpg'],
    ];

    /**
     * Generate reverse memory scrapbook timeline for the trip.
     * Automatically scans chat attachments and activity milestones organized by day.
     *
     * @return array<string, mixed>
     */
    public function generateScrapbook(Trip $trip): array
    {
        $trip->loadMissing([
            'owner',
            'members.user',
            'activities',
            'messages.sender',
        ]);

        $startDate = $trip->start_date ? Carbon::parse($trip->start_date) : Carbon::today();
        $endDate = $trip->end_date ? Carbon::parse($trip->end_date) : $startDate->copy();
        $durationDays = max(1, $startDate->diffInDays($endDate) + 1);

        // 1. Gather chat message photos
        $chatPhotos = [];
        foreach ($trip->messages as $msg) {
            if ($msg->attachment) {
                $url = filter_var($msg->attachment, FILTER_VALIDATE_URL)
                    ? $msg->attachment
                    : Storage::url($msg->attachment);

                // Determine which day this photo belongs to
                $msgDate = $msg->created_at ? Carbon::parse($msg->created_at)->startOfDay() : $startDate;
                if ($msgDate->lt($startDate)) {
                    $dayIndex = 1;
                } elseif ($msgDate->gt($endDate)) {
                    $dayIndex = $durationDays;
                } else {
                    $dayIndex = (int) $startDate->diffInDays($msgDate) + 1;
                }
                $dayIndex = max(1, min($durationDays, $dayIndex));

                $chatPhotos[] = [
                    'id' => 'msg_'.$msg->id,
                    'type' => 'chat_photo',
                    'day' => $dayIndex,
                    'image_url' => $url,
                    'caption' => $msg->content ?: __('Group memory captured on day :day', ['day' => $dayIndex]),
                    'author_name' => $msg->sender?->name ?? __('Travel Buddy'),
                    'author_avatar' => $msg->sender?->avatar_url ?? null,
                    'created_at' => $msg->created_at?->toIso8601String(),
                    'time_formatted' => $msg->created_at?->format('h:i A') ?? '10:00 AM',
                ];
            }
        }

        // 2. Group activities by day as milestones
        $activitiesByDay = $trip->activities->groupBy('day');

        // 3. Prepare destination fallback memories if no photos exist yet
        $hasChatPhotos = count($chatPhotos) > 0;
        $matchedFallbackPhotos = $this->getDestinationFallbackPhotos($trip->destination);

        $daysTimeline = [];
        $totalPhotosCount = count($chatPhotos);
        $totalMilestonesCount = $trip->activities->count();

        for ($day = 1; $day <= $durationDays; $day++) {
            $currentDate = $startDate->copy()->addDays($day - 1);
            $dayMemories = [];

            // Add chat photos for this day
            foreach ($chatPhotos as $cp) {
                if ($cp['day'] === $day) {
                    $dayMemories[] = $cp;
                }
            }

            // If no user photos exist on this day, insert inspirational destination memory
            if (! $hasChatPhotos && ! empty($matchedFallbackPhotos)) {
                $photoIndex = ($day - 1) % count($matchedFallbackPhotos);
                $fallbackUrl = asset($matchedFallbackPhotos[$photoIndex]);
                $dayMemories[] = [
                    'id' => 'curated_'.$day,
                    'type' => 'chat_photo',
                    'day' => $day,
                    'image_url' => $fallbackUrl,
                    'caption' => __('Scenic highlight and unforgettable moments in :dest', ['dest' => $trip->destination]),
                    'author_name' => __('Wanderly Moments'),
                    'author_avatar' => null,
                    'created_at' => $currentDate->toIso8601String(),
                    'time_formatted' => '09:30 AM',
                    'is_curated' => true,
                ];
                $totalPhotosCount++;
            }

            // Add activities as milestones for this day
            $dayActs = $activitiesByDay->get($day, collect())->sortBy('position');
            foreach ($dayActs as $act) {
                $dayMemories[] = [
                    'id' => 'act_'.$act->id,
                    'type' => 'activity_milestone',
                    'day' => $day,
                    'title' => $act->title,
                    'time_slot' => ucfirst($act->time_slot ?? 'morning'),
                    'location' => $act->location ?: $act->address,
                    'cost_formatted' => number_format((float) $act->cost, 0, ',', '.').' ₫',
                    'notes' => $act->notes,
                ];
            }

            // Determine day theme / title
            $firstAct = $dayActs->first();
            $theme = $firstAct ? $firstAct->title : __('Exploring :dest', ['dest' => $trip->destination]);

            $daysTimeline[] = [
                'day' => $day,
                'date' => $currentDate->format('Y-m-d'),
                'date_formatted' => $currentDate->format('l, F j, Y'),
                'theme' => $theme,
                'memories_count' => count($dayMemories),
                'memories' => $dayMemories,
            ];
        }

        $participantsCount = $trip->members->count() + 1; // members + owner

        return [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'destination' => $trip->destination,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'date_range_formatted' => $startDate->format('M j').' – '.$endDate->format('M j, Y'),
                'duration_days' => $durationDays,
                'cover_image' => $trip->cover,
            ],
            'stats' => [
                'total_photos' => $totalPhotosCount,
                'total_milestones' => $totalMilestonesCount,
                'duration_days' => $durationDays,
                'participants_count' => $participantsCount,
            ],
            'days' => $daysTimeline,
            'participants' => $trip->members->map(fn ($m) => [
                'id' => $m->user?->id,
                'name' => $m->user?->name ?? 'Member',
                'role' => $m->role,
            ])->prepend([
                'id' => $trip->owner?->id,
                'name' => $trip->owner?->name ?? 'Leader',
                'role' => 'leader',
            ])->values()->all(),
            'generated_at' => now()->format('F j, Y'),
        ];
    }

    /**
     * Get fallback photos matching trip destination name.
     *
     * @return array<string>
     */
    protected function getDestinationFallbackPhotos(?string $destination): array
    {
        if (! $destination) {
            return ['images/destinations/hero.jpg'];
        }

        foreach ($this->destinationPhotoMap as $city => $photos) {
            if (stripos($destination, $city) !== false) {
                return $photos;
            }
        }

        return ['images/destinations/hero.jpg', 'images/destinations/dalat.jpg'];
    }
}
