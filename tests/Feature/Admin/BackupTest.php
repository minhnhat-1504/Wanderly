<?php

namespace Tests\Feature\Admin;

use App\Models\Backup;
use App\Models\User;
use App\Services\Admin\AdminBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $traveler;

    protected User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('backups');

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

    public function test_guest_cannot_access_backup_routes(): void
    {
        $this->get('/admin/backups')->assertRedirect('/login');
        $this->post('/admin/backups', ['type' => 'database'])->assertRedirect('/login');
        $this->get('/admin/backups/1/download')->assertRedirect('/login');
        $this->delete('/admin/backups/1')->assertRedirect('/login');
    }

    public function test_traveler_and_provider_receive_403_from_backup_routes(): void
    {
        $this->actingAs($this->traveler)->get('/admin/backups')->assertForbidden();
        $this->actingAs($this->traveler)->post('/admin/backups', ['type' => 'database'])->assertForbidden();

        $this->actingAs($this->provider)->get('/admin/backups')->assertForbidden();
        $this->actingAs($this->provider)->post('/admin/backups', ['type' => 'database'])->assertForbidden();
    }

    public function test_admin_can_view_backups_index_and_stats(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/backups');

        $response->assertOk()
            ->assertViewIs('admin.backups.index')
            ->assertViewHasAll(['backups', 'stats'])
            ->assertSee('Backups & Disaster Recovery')
            ->assertSee('Storage Occupied');
    }

    public function test_admin_can_create_database_backup(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/backups', [
            'type' => 'database',
        ]);

        $response->assertRedirect(route('admin.backups.index'));
        $response->assertSessionHas('status');

        $backup = Backup::where('type', 'database')->first();
        $this->assertNotNull($backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertGreaterThan(0, $backup->size_bytes);
        $this->assertEquals($this->admin->id, $backup->admin_id);

        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'backup.create',
        ]);
    }

    public function test_admin_can_create_full_backup(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/backups', [
            'type' => 'full',
        ]);

        $response->assertRedirect(route('admin.backups.index'));
        $response->assertSessionHas('status');

        $backup = Backup::where('type', 'full')->first();
        $this->assertNotNull($backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertGreaterThan(0, $backup->size_bytes);
    }

    public function test_backup_validation_enforces_allowed_types(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/backups', [
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_admin_can_download_completed_backup(): void
    {
        $filename = 'test_backup_download.zip';
        Storage::disk('backups')->put($filename, 'dummy zip content');

        $backup = Backup::create([
            'admin_id' => $this->admin->id,
            'type' => 'database',
            'filename' => $filename,
            'disk' => 'backups',
            'size_bytes' => 18,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.backups.download', $backup));

        $response->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'backup.download',
        ]);
    }

    public function test_admin_cannot_download_incomplete_or_missing_backup(): void
    {
        $backup = Backup::create([
            'admin_id' => $this->admin->id,
            'type' => 'database',
            'filename' => 'nonexistent_file.zip',
            'disk' => 'backups',
            'size_bytes' => 100,
            'status' => 'failed',
            'error_message' => 'Something went wrong',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.backups.download', $backup));

        $response->assertNotFound();
    }

    public function test_admin_can_delete_backup(): void
    {
        $filename = 'test_backup_delete.zip';
        Storage::disk('backups')->put($filename, 'content to delete');

        $backup = Backup::create([
            'admin_id' => $this->admin->id,
            'type' => 'database',
            'filename' => $filename,
            'disk' => 'backups',
            'size_bytes' => 17,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.backups.destroy', $backup));

        $response->assertRedirect(route('admin.backups.index'));
        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
        Storage::disk('backups')->assertMissing($filename);

        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'backup.delete',
        ]);
    }

    public function test_retention_policy_cleans_older_backups(): void
    {
        $service = app(AdminBackupService::class);

        // Create 9 completed backups
        for ($i = 1; $i <= 9; $i++) {
            Backup::create([
                'admin_id' => $this->admin->id,
                'type' => 'database',
                'filename' => "test_retention_{$i}.zip",
                'disk' => 'backups',
                'size_bytes' => 100,
                'status' => 'completed',
                'completed_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $this->assertEquals(9, Backup::where('status', 'completed')->count());

        $deleted = $service->cleanOldBackups(7);

        $this->assertEquals(2, $deleted);
        $this->assertEquals(7, Backup::where('status', 'completed')->count());
    }

    public function test_admin_can_fetch_disaster_recovery_instructions(): void
    {
        $backup = Backup::create([
            'admin_id' => $this->admin->id,
            'type' => 'database',
            'filename' => 'wanderly_db_test.zip',
            'disk' => 'backups',
            'size_bytes' => 1024,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.backups.instructions', $backup));

        $response->assertOk()
            ->assertJsonStructure([
                'backup',
                'filename',
                'type',
                'steps' => [
                    '*' => ['title', 'command', 'note'],
                ],
            ]);
    }
}
