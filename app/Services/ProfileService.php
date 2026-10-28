<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\MemberPreference;
use App\Models\Post;
use App\Models\Provider;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    public const AVATAR_DIRECTORY = 'avatars';

    public const COVER_DIRECTORY = 'covers';

    /**
     * Get aggregated profile data for the requested user.
     */
    public function getProfileData(User $user, ?User $viewer = null): array
    {
        $isOwner = $viewer && $viewer->id === $user->id;
        $stats = $this->getStats($user);
        $badges = $this->getBadges($user, $stats, $isOwner);
        $travelStyle = $this->getTravelStyle($user);
        $visitedDestinations = $this->getVisitedDestinations($user);

        $providerProfile = null;
        if ($user->role === 'provider' || $user->provider) {
            $providerProfile = $this->getProviderProfile($user);
        }

        return [
            'user' => $user,
            'is_owner' => $isOwner,
            'viewer' => $viewer,
            'stats' => $stats,
            'badges' => $badges,
            'travel_style' => $travelStyle,
            'visited_destinations' => $visitedDestinations,
            'provider_profile' => $providerProfile,
        ];
    }

    /**
     * Calculate and cache user statistics.
     */
    public function getStats(User $user): array
    {
        $cacheKey = "user_profile_stats_{$user->id}";

        return Cache::remember($cacheKey, 600, function () use ($user) {
            // Public trips vs total trips
            $publicTripsCount = Trip::where('owner_id', $user->id)
                ->where('is_shared', true)
                ->count();

            $totalTripsCount = Trip::where('owner_id', $user->id)->count();

            // Distinct destinations visited
            $destinationsFromTrips = Trip::where('owner_id', $user->id)
                ->whereNotNull('destination')
                ->where('destination', '!=', '')
                ->distinct()
                ->pluck('destination')
                ->toArray();

            $locationsFromActivities = DB::table('activities')
                ->join('trips', 'activities.trip_id', '=', 'trips.id')
                ->where('trips.owner_id', $user->id)
                ->whereNotNull('activities.location')
                ->where('activities.location', '!=', '')
                ->distinct()
                ->pluck('activities.location')
                ->toArray();

            $distinctDestinations = array_unique(array_filter(array_merge($destinationsFromTrips, $locationsFromActivities)));
            $destinationsCount = count($distinctDestinations);

            // Posts count
            $postsCount = Post::where('user_id', $user->id)->count();

            // Total clones: how many times other people duplicated trips owned by this user
            // We can detect clones by name like 'Copy of %' where owner_id != $user->id
            $clonesCount = 0;
            $userTripNames = Trip::where('owner_id', $user->id)->pluck('name')->toArray();
            if (! empty($userTripNames)) {
                $patterns = array_map(fn ($name) => 'Copy of '.$name, $userTripNames);
                $clonesCount = Trip::whereIn('name', $patterns)
                    ->where('owner_id', '!=', $user->id)
                    ->count();
            }

            // Total likes received on user's posts
            $totalLikesReceived = (int) Post::where('user_id', $user->id)->sum('likes_count');

            return [
                'public_trips_count' => $publicTripsCount,
                'total_trips_count' => $totalTripsCount,
                'destinations_count' => $destinationsCount,
                'posts_count' => $postsCount,
                'clones_count' => $clonesCount,
                'likes_count' => $totalLikesReceived,
            ];
        });
    }

    /**
     * Clear cached statistics for a user.
     */
    public function clearStatsCache(User $user): void
    {
        Cache::forget("user_profile_stats_{$user->id}");
    }

    /**
     * Retrieve user badges based on their achievements.
     */
    public function getBadges(User $user, ?array $stats = null, bool $forOwner = false): array
    {
        $stats = $stats ?? $this->getStats($user);

        $definitions = [
            [
                'id' => 'first_trip',
                'name' => __('First Explorer'),
                'description' => __('Created their first trip on Wanderly'),
                'icon_name' => 'compass',
                'earned' => $stats['total_trips_count'] >= 1,
                'progress' => min($stats['total_trips_count'], 1).'/1 trips',
            ],
            [
                'id' => 'five_trips',
                'name' => __('Seasoned Traveler'),
                'description' => __('Planned 5 or more trips'),
                'icon_name' => 'map',
                'earned' => $stats['total_trips_count'] >= 5,
                'progress' => min($stats['total_trips_count'], 5).'/5 trips',
            ],
            [
                'id' => 'top_planner',
                'name' => __('Top Planner'),
                'description' => __('Itineraries are inspiring and loved by the community'),
                'icon_name' => 'copy',
                'earned' => ($stats['clones_count'] >= 1 || $stats['total_trips_count'] >= 10),
                'progress' => min($stats['clones_count'], 1).'/1 clones',
            ],
            [
                'id' => 'social_butterfly',
                'name' => __('Social Butterfly'),
                'description' => __('Actively sharing adventures on the community feed'),
                'icon_name' => 'heart',
                'earned' => ($stats['posts_count'] >= 3 || $stats['likes_count'] >= 10),
                'progress' => min($stats['posts_count'], 3).'/3 posts',
            ],
        ];

        if ($user->role === 'provider' && $user->provider && $user->provider->is_verified) {
            $definitions[] = [
                'id' => 'verified_provider',
                'name' => __('Verified Partner'),
                'description' => __('Officially verified local travel service provider'),
                'icon_name' => 'badge-check',
                'earned' => true,
                'progress' => __('Verified'),
            ];
        }

        if ($forOwner) {
            return $definitions;
        }

        // Strangers only see earned badges
        return array_values(array_filter($definitions, fn ($b) => $b['earned']));
    }

    /**
     * Get paginated user posts.
     */
    public function getPosts(User $user, int $perPage = 6): LengthAwarePaginator
    {
        return Post::with(['author', 'trip', 'comments.author'])
            ->withCount(['likedByUsers as likes_count', 'comments as comments_count'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get public trips (or all trips for owner).
     */
    public function getTrips(User $user, ?User $viewer = null, int $perPage = 6): LengthAwarePaginator
    {
        $isOwner = $viewer && $viewer->id === $user->id;

        $query = Trip::with(['owner', 'members'])
            ->withCount('members')
            ->where('owner_id', $user->id);

        if (! $isOwner) {
            $query->where('is_shared', true);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get posts saved by the user (strictly for the owner).
     */
    public function getSavedPosts(User $user, ?User $viewer = null, int $perPage = 6): ?LengthAwarePaginator
    {
        if (! $viewer || $viewer->id !== $user->id) {
            return null;
        }

        return $user->savedPosts()
            ->with(['author', 'trip'])
            ->withCount(['likedByUsers as likes_count', 'comments as comments_count'])
            ->latest('post_saves.created_at')
            ->paginate($perPage);
    }

    /**
     * Get user travel style from MemberPreference.
     * Returns whether user has actually configured preferences.
     */
    public function getTravelStyle(User $user): array
    {
        $preference = MemberPreference::where('user_id', $user->id)
            ->latest()
            ->first();

        $allInterests = MemberPreference::where('user_id', $user->id)
            ->pluck('interests')
            ->filter()
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        $hasPreferences = ($preference !== null && ($preference->pace || ! empty($preference->interests)))
            || ! empty($allInterests);

        return [
            'has_preferences' => $hasPreferences,
            'pace' => $preference?->pace ?? null,
            'budget' => 'moderate', // default budget tier if not specified
            'interests' => $allInterests,
        ];
    }

    /**
     * Get list of visited destinations.
     */
    public function getVisitedDestinations(User $user): array
    {
        $fromTrips = Trip::where('owner_id', $user->id)
            ->whereNotNull('destination')
            ->where('destination', '!=', '')
            ->distinct()
            ->pluck('destination')
            ->toArray();

        $fromActivities = DB::table('activities')
            ->join('trips', 'activities.trip_id', '=', 'trips.id')
            ->where('trips.owner_id', $user->id)
            ->whereNotNull('activities.location')
            ->where('activities.location', '!=', '')
            ->distinct()
            ->pluck('activities.location')
            ->toArray();

        return array_values(array_unique(array_filter(array_merge($fromTrips, $fromActivities))));
    }

    /**
     * Aggregate provider information.
     */
    public function getProviderProfile(User $user): ?array
    {
        $provider = $user->provider()->with(['destination', 'services'])->first();
        if (! $provider) {
            return null;
        }

        $completedBookingsCount = Booking::where('provider_id', $provider->id)
            ->where('status', 'confirmed')
            ->count();

        return [
            'provider' => $provider,
            'services' => $provider->services,
            'completed_bookings_count' => $completedBookingsCount,
            'rating' => $provider->rating,
            'is_verified' => (bool) $provider->is_verified,
        ];
    }

    /**
     * Update main profile information including file uploads.
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatarFile = null, ?UploadedFile $coverFile = null): User
    {
        if ($avatarFile) {
            $data['avatar'] = $this->uploadAvatar($user, $avatarFile);
        }

        if ($coverFile) {
            $data['cover_image'] = $this->uploadCover($user, $coverFile);
        }

        $user->update($data);
        $this->clearStatsCache($user);

        return $user->fresh();
    }

    /**
     * Upload and store avatar image, deleting the old one if it exists.
     */
    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $filename = Str::random(32).'.'.$file->getClientOriginalExtension();

        return $file->storeAs(self::AVATAR_DIRECTORY, $filename, 'public');
    }

    /**
     * Upload and store cover image, deleting the old one if it exists.
     */
    public function uploadCover(User $user, UploadedFile $file): string
    {
        if ($user->cover_image && Storage::disk('public')->exists($user->cover_image)) {
            Storage::disk('public')->delete($user->cover_image);
        }

        $filename = Str::random(32).'.'.$file->getClientOriginalExtension();

        return $file->storeAs(self::COVER_DIRECTORY, $filename, 'public');
    }

    /**
     * Remove current user avatar and fallback to default.
     */
    public function removeAvatar(User $user): bool
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return true;
    }

    /**
     * Remove current user cover image.
     */
    public function removeCover(User $user): bool
    {
        if ($user->cover_image && Storage::disk('public')->exists($user->cover_image)) {
            Storage::disk('public')->delete($user->cover_image);
        }

        $user->update(['cover_image' => null]);

        return true;
    }

    /**
     * Update travel preferences and locale/currency.
     */
    public function updatePreferences(User $user, array $data): void
    {
        $userUpdates = [];
        if (isset($data['locale'])) {
            $userUpdates['locale'] = $data['locale'];
        }
        if (isset($data['currency'])) {
            $userUpdates['currency'] = $data['currency'];
        }

        if (! empty($userUpdates)) {
            $user->update($userUpdates);
        }

        // Update or create a baseline MemberPreference for this user
        // Using a general trip reference or latest user preference
        if (isset($data['interests']) || isset($data['pace'])) {
            $latestPref = MemberPreference::where('user_id', $user->id)->latest()->first();

            if ($latestPref) {
                $latestPref->update([
                    'pace' => $data['pace'] ?? $latestPref->pace,
                    'interests' => $data['interests'] ?? $latestPref->interests,
                ]);
            } else {
                // Find any trip user belongs to or create default
                $trip = $user->trips()->first();
                if ($trip) {
                    MemberPreference::create([
                        'trip_id' => $trip->id,
                        'user_id' => $user->id,
                        'pace' => $data['pace'] ?? 'balanced',
                        'interests' => $data['interests'] ?? [],
                    ]);
                }
            }
        }
    }

    /**
     * Update password for user.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Invalidate all sessions except the current one.
     */
    public function logoutOtherDevices(User $user, string $password): void
    {
        Auth::logoutOtherDevices($password);

        // Also clean up database sessions if SESSION_DRIVER = database
        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->delete();
        }
    }

    /**
     * Safely delete the user account and associated relationships in a transaction.
     */
    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Delete avatar and cover images
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            if ($user->cover_image && Storage::disk('public')->exists($user->cover_image)) {
                Storage::disk('public')->delete($user->cover_image);
            }

            // Remove user from trips where user is not sole leader
            // If user is sole leader of a trip, delete the trip and related items
            $ownedTrips = Trip::where('owner_id', $user->id)->get();
            foreach ($ownedTrips as $trip) {
                if ($trip->cover && Storage::disk('public')->exists($trip->cover)) {
                    Storage::disk('public')->delete($trip->cover);
                }
                $trip->delete();
            }

            // Delete sessions
            if (config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $user->forceDelete();
        });
    }
}
