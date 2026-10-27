<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_auth_user_can_view_public_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'alex_wanderer',
            'is_profile_public' => true,
            'name' => 'Alex Wanderer',
        ]);

        // Guest view
        $guestRes = $this->get("/u/{$user->username}");
        $guestRes->assertStatus(200);
        $guestRes->assertSee('Alex Wanderer');

        // Authenticated other user view
        $otherUser = User::factory()->create(['username' => 'jane_doe']);
        $authRes = $this->actingAs($otherUser)->get("/u/{$user->username}");
        $authRes->assertStatus(200);
    }

    public function test_private_profile_is_hidden_from_strangers_but_accessible_by_owner_and_admin(): void
    {
        $privateUser = User::factory()->create([
            'username' => 'private_guy',
            'is_profile_public' => false,
            'name' => 'Secret Guy',
        ]);

        // Stranger guest receives 403
        $this->get("/u/{$privateUser->username}")->assertStatus(403);

        // Stranger auth receives 403
        $stranger = User::factory()->create(['username' => 'stranger_bob']);
        $this->actingAs($stranger)->get("/u/{$privateUser->username}")->assertStatus(403);

        // Owner can view own private profile
        $this->actingAs($privateUser)->get("/u/{$privateUser->username}")->assertStatus(200);

        // Admin can view private profile
        $admin = User::factory()->create(['username' => 'admin_super', 'role' => 'admin']);
        $this->actingAs($admin)->get("/u/{$privateUser->username}")->assertStatus(200);
    }

    public function test_budget_is_hidden_when_show_budget_publicly_is_disabled(): void
    {
        $user = User::factory()->create([
            'username' => 'traveller_sam',
            'is_profile_public' => true,
            'show_budget_publicly' => false,
        ]);

        $trip = Trip::create([
            'owner_id' => $user->id,
            'name' => 'Secret Budget Tour',
            'destination' => 'Da Nang',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'budget' => 50000000.00,
            'privacy' => 'public',
            'status' => 'planning',
            'is_shared' => true,
            'is_share_budget_visible' => false,
        ]);

        $response = $this->get("/u/{$user->username}");
        $response->assertStatus(200);
        $response->assertDontSee('50,000,000');
    }

    public function test_saved_tab_is_only_accessible_by_profile_owner(): void
    {
        $owner = User::factory()->create(['username' => 'bookmark_king']);
        $otherUser = User::factory()->create(['username' => 'nosy_neighbor']);

        // Owner can access saved tab
        $this->actingAs($owner)
            ->getJson("/ajax/u/{$owner->username}/tab/saved")
            ->assertStatus(200);

        // Other user cannot access saved tab
        $this->actingAs($otherUser)
            ->getJson("/ajax/u/{$owner->username}/tab/saved")
            ->assertStatus(403);
    }

    public function test_user_can_update_profile_and_validate_rules(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['username' => 'valid_user']);
        $other = User::factory()->create(['username' => 'taken_name']);

        // Validation fails if username is reserved
        $this->actingAs($user)
            ->patch('/settings', [
                'name' => 'Valid User',
                'username' => 'admin',
            ])
            ->assertSessionHasErrors('username');

        // Validation fails if username is taken by someone else
        $this->actingAs($user)
            ->patch('/settings', [
                'name' => 'Valid User',
                'username' => 'taken_name',
            ])
            ->assertSessionHasErrors('username');

        // Validation fails if avatar is oversized
        $oversizedAvatar = UploadedFile::fake()->create('huge.png', 5000); // 5MB
        $this->actingAs($user)
            ->patch('/settings', [
                'name' => 'Valid User',
                'username' => 'valid_user',
                'avatar' => $oversizedAvatar,
            ])
            ->assertSessionHasErrors('avatar');

        // Valid update with images
        $avatar = UploadedFile::fake()->image('me.jpg', 200, 200);
        $cover = UploadedFile::fake()->image('bg.jpg', 1200, 400);

        $res = $this->actingAs($user)
            ->patch('/settings', [
                'name' => 'Alex Updated',
                'username' => 'alex_new',
                'bio' => 'Passionate mountain hiker',
                'location' => 'Da Lat, Vietnam',
                'website' => 'https://wanderly.com',
                'avatar' => $avatar,
                'cover_image' => $cover,
            ]);

        $res->assertRedirect('/settings?section=profile');

        $user->refresh();
        $this->assertEquals('alex_new', $user->username);
        $this->assertEquals('Alex Updated', $user->name);
        $this->assertEquals('Passionate mountain hiker', $user->bio);
        $this->assertNotNull($user->avatar);
        $this->assertNotNull($user->cover_image);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        // Wrong current password
        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'WrongPassword',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertSessionHasErrors('current_password');

        // Correct password change
        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings?section=security');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_user_can_delete_account_with_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('MySecretPass#1'),
        ]);

        // Wrong password fails
        $this->actingAs($user)
            ->delete('/settings', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        // Correct password deletes account
        $this->actingAs($user)
            ->delete('/settings', ['password' => 'MySecretPass#1'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
