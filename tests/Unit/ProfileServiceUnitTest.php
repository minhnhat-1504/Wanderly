<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\Trip;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    protected ProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = app(ProfileService::class);
    }

    public function test_get_stats_aggregates_correct_numbers(): void
    {
        $user = User::factory()->create(['username' => 'stat_master']);

        // Create 2 trips
        Trip::create([
            'owner_id' => $user->id,
            'name' => 'Trip to Hanoi',
            'destination' => 'Hanoi',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'budget' => 10000000,
            'privacy' => 'public',
            'status' => 'planning',
            'is_shared' => true,
        ]);

        Trip::create([
            'owner_id' => $user->id,
            'name' => 'Trip to Sa Pa',
            'destination' => 'Sa Pa',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'budget' => 15000000,
            'privacy' => 'private',
            'status' => 'planning',
            'is_shared' => false,
        ]);

        // Create 2 posts
        Post::create([
            'user_id' => $user->id,
            'title' => 'Post 1',
            'content' => 'Content 1',
            'likes_count' => 5,
        ]);

        Post::create([
            'user_id' => $user->id,
            'title' => 'Post 2',
            'content' => 'Content 2',
            'likes_count' => 12,
        ]);

        $stats = $this->profileService->getStats($user);

        $this->assertEquals(2, $stats['total_trips_count']);
        $this->assertEquals(1, $stats['public_trips_count']);
        $this->assertEquals(2, $stats['destinations_count']);
        $this->assertEquals(2, $stats['posts_count']);
        $this->assertEquals(17, $stats['likes_count']);
    }

    public function test_get_badges_awards_proper_badges(): void
    {
        $user = User::factory()->create([
            'username' => 'badge_winner',
            'role' => 'traveler',
        ]);

        // With 1 trip and 3 posts and 15 likes
        $stats = [
            'total_trips_count' => 1,
            'public_trips_count' => 1,
            'destinations_count' => 1,
            'posts_count' => 3,
            'clones_count' => 0,
            'likes_count' => 15,
        ];

        $badges = $this->profileService->getBadges($user, $stats);
        $badgeIds = array_column($badges, 'id');

        $this->assertContains('first_trip', $badgeIds);
        $this->assertContains('social_butterfly', $badgeIds);
        $this->assertNotContains('five_trips', $badgeIds);
    }
}
