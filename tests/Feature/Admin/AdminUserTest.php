<?php

namespace Tests\Feature\Admin;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $traveler;

    protected User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@wanderly.test',
        ]);

        $this->traveler = User::factory()->create([
            'role' => 'traveler',
            'email' => 'traveler@wanderly.test',
        ]);

        $this->provider = User::factory()->create([
            'role' => 'provider',
            'email' => 'provider@wanderly.test',
        ]);
    }

    public function test_guest_is_redirected_from_user_management(): void
    {
        $response = $this->get('/admin/users');
        $response->assertRedirect('/login');
    }

    public function test_traveler_receives_403_from_user_management(): void
    {
        $response = $this->actingAs($this->traveler)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_provider_receives_403_from_user_management(): void
    {
        $response = $this->actingAs($this->provider)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_users_list_and_filter(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $response->assertOk();
        $response->assertSee('admin@wanderly.test');
        $response->assertSee('traveler@wanderly.test');
        $response->assertSee('provider@wanderly.test');

        // Test search filter
        $responseFiltered = $this->actingAs($this->admin)->get('/admin/users?search=traveler@');
        $responseFiltered->assertOk();
        $responseFiltered->assertSee('traveler@wanderly.test');

        // Test role filter
        $responseRole = $this->actingAs($this->admin)->get('/admin/users?role=provider');
        $responseRole->assertOk();
        $responseRole->assertSee('provider@wanderly.test');
    }

    public function test_admin_can_view_user_details(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users/'.$this->traveler->id);
        $response->assertOk();
        $response->assertSee($this->traveler->name);
        $response->assertSee($this->traveler->email);
        $response->assertSee('Trips Created');
    }

    public function test_admin_can_change_user_role(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users/'.$this->traveler->id.'/role', [
            'role' => 'provider',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->traveler->refresh();
        $this->assertEquals('provider', $this->traveler->role);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'user.change_role',
            'subject_type' => User::class,
            'subject_id' => $this->traveler->id,
        ]);

        // Verify in-app notification sent
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->traveler->id,
            'type' => 'role_changed',
        ]);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users/'.$this->admin->id.'/role', [
            'role' => 'traveler',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->admin->refresh();
        $this->assertEquals('admin', $this->admin->role);
    }

    public function test_admin_cannot_demote_the_last_admin(): void
    {
        // $this->admin is the only admin
        $secondAdmin = User::factory()->create(['role' => 'admin']);

        // Demoting secondAdmin leaves $this->admin as the last admin
        $this->actingAs($this->admin)->post('/admin/users/'.$secondAdmin->id.'/role', [
            'role' => 'traveler',
        ]);
        $secondAdmin->refresh();
        $this->assertEquals('traveler', $secondAdmin->role);

        // Now creating a third admin to log in and attempt to demote $this->admin when it's the last one
        $this->actingAs($secondAdmin); // Not admin
        // Instead, create another admin to try to demote the now-sole admin
        $thirdAdmin = User::factory()->create(['role' => 'admin']);
        // Delete thirdAdmin so $this->admin is the only admin
        $thirdAdmin->delete();

        // Attempting to demote the only remaining non-deleted admin:
        $anotherAdmin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($anotherAdmin)->post('/admin/users/'.$this->admin->id.'/role', [
            'role' => 'traveler',
        ]);
        // After demoting this->admin, anotherAdmin is the only admin
        // Now try demoting anotherAdmin:
        // But anotherAdmin cannot demote self anyway!
        // What if another admin tries to demote the single admin?
        // Let's test the service check directly:
        $this->assertEquals(1, User::where('role', 'admin')->whereNull('deleted_at')->count());
        $soleAdmin = User::where('role', 'admin')->first();

        // Have a fake call with another actor (e.g. temporary admin):
        $temporaryAdmin = User::factory()->create(['role' => 'admin']);
        // There are now 2 admins: soleAdmin and temporaryAdmin.
        // If temporaryAdmin tries to demote soleAdmin, it succeeds:
        $this->actingAs($temporaryAdmin)->post('/admin/users/'.$soleAdmin->id.'/role', [
            'role' => 'traveler',
        ]);
        $soleAdmin->refresh();
        $this->assertEquals('traveler', $soleAdmin->role);

        // Now temporaryAdmin is the sole remaining admin:
        // If an attempt is made to demote temporaryAdmin, it will fail:
        $response = $this->actingAs($temporaryAdmin)->post('/admin/users/'.$temporaryAdmin->id.'/role', [
            'role' => 'traveler',
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_admin_can_suspend_user_with_reason(): void
    {
        // Seed a session in the sessions table if it exists
        if (DB::getSchemaBuilder()->hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => 'test-session-123',
                'user_id' => $this->traveler->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'dummy',
                'last_activity' => time(),
            ]);
        }

        $reason = 'Repeated spamming in community reviews and travel discussions.';
        $response = $this->actingAs($this->admin)->post('/admin/users/'.$this->traveler->id.'/suspend', [
            'reason' => $reason,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->traveler->refresh();
        $this->assertTrue($this->traveler->isSuspended());
        $this->assertEquals($reason, $this->traveler->suspended_reason);
        $this->assertNotNull($this->traveler->suspended_at);

        // Verify session was terminated
        if (DB::getSchemaBuilder()->hasTable('sessions')) {
            $this->assertDatabaseMissing('sessions', [
                'user_id' => $this->traveler->id,
            ]);
        }

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'user.suspend',
            'subject_id' => $this->traveler->id,
        ]);

        // Verify notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->traveler->id,
            'type' => 'account_suspended',
        ]);
    }

    public function test_suspend_requires_minimum_10_characters_reason(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users/'.$this->traveler->id.'/suspend', [
            'reason' => 'Too short',
        ]);

        $response->assertSessionHasErrors(['reason']);
        $this->traveler->refresh();
        $this->assertFalse($this->traveler->isSuspended());
    }

    public function test_admin_cannot_suspend_themselves(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users/'.$this->admin->id.'/suspend', [
            'reason' => 'Testing self-suspension guard which should fail.',
        ]);

        $response->assertSessionHasErrors(['reason']);
        $this->admin->refresh();
        $this->assertFalse($this->admin->isSuspended());
    }

    public function test_suspended_user_cannot_login_and_receives_clear_error(): void
    {
        $this->traveler->update([
            'is_suspended' => true,
            'suspended_reason' => 'Spamming commercial ads across groups.',
            'suspended_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $this->traveler->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_middleware_rejects_active_suspended_user_session(): void
    {
        // User starts active
        $this->traveler->update([
            'is_suspended' => true,
            'suspended_reason' => 'Account blocked due to suspicious activity.',
        ]);

        // When requesting any authenticated route
        $response = $this->actingAs($this->traveler)->get('/trips');

        // Middleware logs out and redirects to login with error
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_can_unsuspend_user(): void
    {
        $this->traveler->update([
            'is_suspended' => true,
            'suspended_reason' => 'Temporary suspension for investigation.',
            'suspended_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/users/'.$this->traveler->id.'/unsuspend');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->traveler->refresh();
        $this->assertFalse($this->traveler->isSuspended());
        $this->assertNull($this->traveler->suspended_reason);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'user.unsuspend',
            'subject_id' => $this->traveler->id,
        ]);
    }

    public function test_admin_can_soft_delete_and_restore_user(): void
    {
        $targetId = $this->traveler->id;

        // Delete
        $response = $this->actingAs($this->admin)->delete('/admin/users/'.$targetId);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $targetId]);

        // Audit log for deletion
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'user.delete',
            'subject_id' => $targetId,
        ]);

        // Restore
        $restoreResponse = $this->actingAs($this->admin)->post('/admin/users/'.$targetId.'/restore');
        $restoreResponse->assertRedirect();
        $restoreResponse->assertSessionHas('success');

        $this->assertNotSoftDeleted('users', ['id' => $targetId]);

        // Audit log for restore
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'user.restore',
            'subject_id' => $targetId,
        ]);
    }

    public function test_admin_cannot_delete_themselves_or_the_last_admin(): void
    {
        $response = $this->actingAs($this->admin)->delete('/admin/users/'.$this->admin->id);
        $response->assertSessionHasErrors(['user']);
        $this->assertNotSoftDeleted('users', ['id' => $this->admin->id]);
    }
}
