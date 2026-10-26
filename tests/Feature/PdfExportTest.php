<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\PdfExportService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    // ══════════════════════════════════════════════════════════════════════
    //  PDF EXPORT — HTTP LAYER
    // ══════════════════════════════════════════════════════════════════════

    public function test_guest_cannot_export_pdf(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $this->get(route('trips.export-pdf', $trip))
            ->assertRedirect(route('login'));
    }

    public function test_trip_owner_can_export_pdf(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'budget' => 5000000]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $response = $this->actingAs($owner)
            ->get(route('trips.export-pdf', $trip));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('content-disposition') ?? ''
        );
    }

    public function test_pdf_filename_contains_trip_name(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'name' => 'Dalat Adventure']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $response = $this->actingAs($owner)
            ->get(route('trips.export-pdf', $trip));

        $response->assertOk();

        $contentDisposition = $response->headers->get('content-disposition', '');
        $this->assertStringContainsString('Dalat_Adventure', $contentDisposition);
    }

    public function test_viewer_member_can_export_pdf(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id,  'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $this->actingAs($viewer)
            ->get(route('trips.export-pdf', $trip))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_stranger_cannot_export_private_trip_pdf(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'privacy' => 'private']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $this->actingAs($stranger)
            ->get(route('trips.export-pdf', $trip))
            ->assertForbidden();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PDF SERVICE — DATA COLLECTION
    // ══════════════════════════════════════════════════════════════════════

    public function test_pdf_export_service_generates_pdf_instance(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'budget' => 3000000]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $service = app(PdfExportService::class);
        $pdf = $service->generate($trip);

        $this->assertInstanceOf(PDF::class, $pdf);
    }

    public function test_pdf_export_includes_trip_activities(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'budget' => 2000000]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        Activity::create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'title' => 'Visit Flower Garden',
            'cost' => 50000,
            'category' => 'sightseeing',
            'location' => 'Dalat Flower Park',
            'position' => 1,
        ]);

        // Generate PDF — should not throw exceptions
        $service = app(PdfExportService::class);
        $pdf = $service->generate($trip);

        $this->assertNotNull($pdf);
    }

    public function test_pdf_export_with_checklist_items(): void
    {
        if (! Schema::hasTable('checklist_items')) {
            $this->markTestSkipped('checklist_items table does not exist.');
        }

        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        ChecklistItem::create([
            'trip_id' => $trip->id,
            'title' => 'Sunscreen SPF 50+',
            'category' => 'personal_care',
            'is_completed' => false,
            'position' => 1,
        ]);

        // Should generate without errors
        $response = $this->actingAs($owner)
            ->get(route('trips.export-pdf', $trip))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_export_multi_day_trip(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 10000000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
        ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        foreach (range(1, 5) as $day) {
            Activity::create([
                'trip_id' => $trip->id,
                'day' => $day,
                'time_slot' => 'morning',
                'title' => "Day {$day} activity",
                'cost' => 100000,
                'category' => 'sightseeing',
                'location' => "Dalat Attraction {$day}",
                'position' => 1,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('trips.export-pdf', $trip))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
