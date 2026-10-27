<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_destinations(): void
    {
        $this->get(route('admin.destinations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_traveler_cannot_access_admin_routes_returns_403(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);

        $this->actingAs($traveler)
            ->get(route('admin.destinations.index'))
            ->assertForbidden();

        $this->actingAs($traveler)
            ->get(route('admin.destinations.create'))
            ->assertForbidden();

        $this->actingAs($traveler)
            ->post(route('admin.destinations.store'), ['name' => 'Hacker City'])
            ->assertForbidden();
    }

    public function test_provider_cannot_access_admin_routes_returns_403(): void
    {
        $providerUser = User::factory()->create(['role' => 'provider']);

        $this->actingAs($providerUser)
            ->get(route('admin.destinations.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_destinations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.destinations.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.destinations.create'))
            ->assertOk();
    }

    public function test_admin_destination_input_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Empty name rejected
        $this->actingAs($admin)
            ->post(route('admin.destinations.store'), ['name' => ''])
            ->assertSessionHasErrors(['name']);

        // Valid creation
        $this->actingAs($admin)
            ->post(route('admin.destinations.store'), [
                'name' => 'Sapa Heaven',
                'description' => 'Misty mountains and terraced fields',
            ])
            ->assertRedirect(route('admin.destinations.index'));

        $this->assertDatabaseHas('destinations', ['name' => 'Sapa Heaven']);

        // Duplicate name rejected
        $this->actingAs($admin)
            ->post(route('admin.destinations.store'), [
                'name' => 'Sapa Heaven',
            ])
            ->assertSessionHasErrors(['name']);
    }
}
