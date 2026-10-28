<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendBroadcastNotificationJob;
use App\Models\Broadcast;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $traveler1;

    protected User $traveler2;

    protected User $provider1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@wanderly.test',
        ]);

        $this->traveler1 = User::factory()->create([
            'role' => 'traveler',
            'email' => 'traveler1@wanderly.test',
        ]);

        $this->traveler2 = User::factory()->create([
            'role' => 'traveler',
            'email' => 'traveler2@wanderly.test',
        ]);

        $this->provider1 = User::factory()->create([
            'role' => 'provider',
            'email' => 'provider1@wanderly.test',
        ]);
    }

    public function test_guest_cannot_access_broadcast_routes(): void
    {
        $response = $this->get('/admin/broadcasts');
        $response->assertRedirect('/login');

        $createResponse = $this->get('/admin/broadcasts/create');
        $createResponse->assertRedirect('/login');
    }

    public function test_traveler_and_provider_receive_403_from_broadcast_routes(): void
    {
        $response = $this->actingAs($this->traveler1)->get('/admin/broadcasts');
        $response->assertStatus(403);

        $response2 = $this->actingAs($this->provider1)->get('/admin/broadcasts/create');
        $response2->assertStatus(403);
    }

    public function test_admin_can_view_broadcasts_index_and_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/broadcasts');
        $response->assertOk();
        $response->assertSee('Broadcast Announcements');

        $createResponse = $this->actingAs($this->admin)->get('/admin/broadcasts/create');
        $createResponse->assertOk();
        $createResponse->assertSee('Compose Announcement');
    }

    public function test_admin_can_send_broadcast_to_all_users(): void
    {
        // When queue is synchronous (default in phpunit.xml or env), jobs execute immediately
        $payload = [
            'title' => 'System Maintenance Scheduled',
            'message' => 'We will be undergoing scheduled platform maintenance this Sunday at 2 AM.',
            'target_audience' => 'all',
            'action_url' => 'https://wanderly.test/maintenance-info',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/broadcasts', $payload);
        $response->assertRedirect(route('admin.broadcasts.index'));
        $response->assertSessionHas('success');

        // Check broadcast record in database
        $this->assertDatabaseHas('broadcasts', [
            'admin_id' => $this->admin->id,
            'title' => 'System Maintenance Scheduled',
            'target_audience' => 'all',
            'recipients_count' => 4, // admin, traveler1, traveler2, provider1
        ]);

        $broadcast = Broadcast::latest('id')->first();

        // Check notifications delivered
        $this->assertDatabaseHas('notifications', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->traveler1->id,
            'type' => 'broadcast',
            'title' => 'System Maintenance Scheduled',
        ]);

        $this->assertDatabaseHas('notifications', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->provider1->id,
            'type' => 'broadcast',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'broadcast.create',
            'subject_id' => $broadcast->id,
        ]);
    }

    public function test_admin_can_send_broadcast_to_travelers_only(): void
    {
        $payload = [
            'title' => 'Exclusive Summer Trip Deals',
            'message' => 'Check out new packages in Da Nang and Phu Quoc exclusively for travelers!',
            'target_audience' => 'travelers',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/broadcasts', $payload);
        $response->assertRedirect();

        $broadcast = Broadcast::latest('id')->first();
        $this->assertEquals(2, $broadcast->recipients_count);

        // Traveler received
        $this->assertDatabaseHas('notifications', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->traveler1->id,
        ]);

        // Provider did NOT receive
        $this->assertDatabaseMissing('notifications', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->provider1->id,
        ]);
    }

    public function test_admin_can_send_broadcast_to_providers_only(): void
    {
        $payload = [
            'title' => 'New Provider Payout Guidelines',
            'message' => 'Updated terms regarding settlement processing times have been posted.',
            'target_audience' => 'providers',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/broadcasts', $payload);
        $response->assertRedirect();

        $broadcast = Broadcast::latest('id')->first();
        $this->assertEquals(1, $broadcast->recipients_count);

        // Provider received
        $this->assertDatabaseHas('notifications', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->provider1->id,
        ]);

        // Traveler did not receive
        $this->assertDatabaseMissing('notifications', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->traveler1->id,
        ]);
    }

    public function test_broadcast_validation_enforces_rules(): void
    {
        // Title too short (< 3 chars)
        $response = $this->actingAs($this->admin)->post('/admin/broadcasts', [
            'title' => 'No',
            'message' => 'Valid message content of sufficient length.',
            'target_audience' => 'all',
        ]);
        $response->assertSessionHasErrors(['title']);

        // Message too short (< 10 chars)
        $response2 = $this->actingAs($this->admin)->post('/admin/broadcasts', [
            'title' => 'Valid Title Here',
            'message' => 'Too short',
            'target_audience' => 'all',
        ]);
        $response2->assertSessionHasErrors(['message']);

        // Invalid target audience
        $response3 = $this->actingAs($this->admin)->post('/admin/broadcasts', [
            'title' => 'Valid Title Here',
            'message' => 'Valid message content of sufficient length.',
            'target_audience' => 'unknown_group',
        ]);
        $response3->assertSessionHasErrors(['target_audience']);
    }

    public function test_admin_can_view_broadcast_detail_and_read_rate(): void
    {
        $broadcast = Broadcast::create([
            'admin_id' => $this->admin->id,
            'title' => 'Community Safety Guidelines 2026',
            'message' => 'Please review our updated community guidelines for social interactions.',
            'target_audience' => 'all',
            'recipients_count' => 2,
            'sent_at' => now(),
        ]);

        // Create 2 notifications, 1 read, 1 unread
        Notification::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->traveler1->id,
            'type' => 'broadcast',
            'title' => $broadcast->title,
            'message' => $broadcast->message,
            'read_at' => now(),
        ]);

        Notification::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $this->traveler2->id,
            'type' => 'broadcast',
            'title' => $broadcast->title,
            'message' => $broadcast->message,
            'read_at' => null,
        ]);

        $this->assertEquals(1, $broadcast->read_count);
        $this->assertEquals(50.0, $broadcast->read_rate);

        $response = $this->actingAs($this->admin)->get('/admin/broadcasts/'.$broadcast->id);
        $response->assertOk();
        $response->assertSee('50%');
        $response->assertSee('Community Safety Guidelines 2026');
        $response->assertSee('traveler1@wanderly.test');
    }

    public function test_send_broadcast_job_dispatches_in_chunks(): void
    {
        Queue::fake();

        $payload = [
            'title' => 'Testing Queue Chunking',
            'message' => 'Ensuring chunked jobs are dispatched properly without memory spikes.',
            'target_audience' => 'all',
        ];

        $this->actingAs($this->admin)->post('/admin/broadcasts', $payload);

        Queue::assertPushed(SendBroadcastNotificationJob::class);
    }
}
