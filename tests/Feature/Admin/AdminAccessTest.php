<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Admin\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin_routes(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.audit-logs.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_traveler_receives_403_when_accessing_admin_routes(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);

        $response = $this->actingAs($traveler)->get(route('admin.dashboard'));
        $response->assertStatus(403);

        $response = $this->actingAs($traveler)->get(route('admin.audit-logs.index'));
        $response->assertStatus(403);
    }

    public function test_provider_receives_403_when_accessing_admin_routes(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);

        $response = $this->actingAs($provider)->get(route('admin.dashboard'));
        $response->assertStatus(403);

        $response = $this->actingAs($provider)->get(route('admin.audit-logs.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard_and_see_kpi_cards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee(__('Total Users'));
        $response->assertSee(__('New Users (7 Days)'));
        $response->assertSee(__('Pending Reports'));
        $response->assertSee(__('Latest Backup'));
    }

    public function test_admin_can_access_audit_logs_and_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Commander Admin']);
        $service = app(AuditLogService::class);

        $service->record($admin, 'test_action', null, ['foo' => 'bar'], ['foo' => 'baz'], '127.0.0.1');

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['action' => 'test_action']));
        $response->assertOk();
        $response->assertSee('test_action');
        $response->assertSee('Commander Admin');
    }

    public function test_audit_log_service_records_data_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'traveler']);
        $service = app(AuditLogService::class);

        $log = $service->record(
            $admin,
            'user.role_change',
            $targetUser,
            ['role' => 'traveler'],
            ['role' => 'provider'],
            '192.168.1.1'
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'admin_id' => $admin->id,
            'action' => 'user.role_change',
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertEquals(['role' => 'traveler'], $log->old_values);
        $this->assertEquals(['role' => 'provider'], $log->new_values);
    }
}
