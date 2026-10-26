<?php

namespace Tests\Feature;

use App\Events\BookingConfirmed;
use App\Events\TravelRequestCreated;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Proposal;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\TravelRequest;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\MarketplaceBookingService;
use App\Services\ProposalService;
use App\Services\TravelRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected TravelRequestService $requestService;

    protected ProposalService $proposalService;

    protected MarketplaceBookingService $bookingService;

    protected BudgetService $budgetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestService = app(TravelRequestService::class);
        $this->proposalService = app(ProposalService::class);
        $this->bookingService = app(MarketplaceBookingService::class);
        $this->budgetService = app(BudgetService::class);
    }

    public function test_guest_cannot_access_marketplace_or_post_requests(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        $this->get(route('trips.marketplace.index', $trip))->assertRedirect(route('login'));
        $this->post(route('trips.marketplace.requests.store', $trip), [
            'needs' => ['transport'],
        ])->assertRedirect(route('login'));
    }

    public function test_viewer_role_cannot_post_travel_request_returns_403(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);

        $this->actingAs($viewer)
            ->post(route('trips.marketplace.requests.store', $trip), [
                'needs' => ['transport', 'tour'],
                'max_budget' => 5000000,
            ])
            ->assertForbidden();
    }

    public function test_leader_and_editor_can_post_travel_request_and_dispatches_event(): void
    {
        Event::fake([TravelRequestCreated::class]);

        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'destination' => 'Da Lat',
            'budget' => 15000000,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('trips.marketplace.requests.store', $trip), [
                'needs' => ['transport', 'tour'],
                'pace' => 'balanced',
                'max_budget' => 8000000,
                'notes' => 'Looking for 7-seater car and countryside tour',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('travel_requests', [
            'trip_id' => $trip->id,
            'status' => 'open',
            'pace' => 'balanced',
            'max_budget' => 8000000.00,
        ]);

        Event::assertDispatched(TravelRequestCreated::class, function ($event) use ($trip) {
            return $event->trip->id === $trip->id;
        });
    }

    public function test_travel_request_service_matches_providers_accurately(): void
    {
        $dalat = Destination::factory()->create(['name' => 'Da Lat']);
        $danang = Destination::factory()->create(['name' => 'Da Nang']);

        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'destination' => 'Da Lat',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
        ]);

        $travelRequest = TravelRequest::create([
            'trip_id' => $trip->id,
            'needs' => ['transport'],
            'status' => 'open',
            'max_budget' => 6000000,
        ]);

        // Provider 1: Da Lat, transport, available -> MATCH
        $providerAvailable = Provider::factory()->create([
            'destination_id' => $dalat->id,
            'service_type' => 'transport',
            'rating' => 4.8,
        ]);

        // Provider 2: Da Lat, transport, BLOCKED on trip date -> EXCLUDED
        $providerBlocked = Provider::factory()->create([
            'destination_id' => $dalat->id,
            'service_type' => 'transport',
            'rating' => 5.0,
        ]);
        ProviderSchedule::create([
            'provider_id' => $providerBlocked->id,
            'blocked_date' => '2026-11-03',
            'reason' => 'Fully Booked',
        ]);

        // Provider 3: Da Nang, transport -> EXCLUDED (wrong destination)
        $providerWrongDest = Provider::factory()->create([
            'destination_id' => $danang->id,
            'service_type' => 'transport',
        ]);

        $matched = $this->requestService->matchProviders($travelRequest);

        $matchedIds = $matched->pluck('id')->all();
        $this->assertContains($providerAvailable->id, $matchedIds);
        $this->assertNotContains($providerBlocked->id, $matchedIds);
        $this->assertNotContains($providerWrongDest->id, $matchedIds);
    }

    public function test_provider_can_submit_proposal_with_items(): void
    {
        $providerUser = User::factory()->create(['role' => 'provider']);
        $destination = Destination::factory()->create(['name' => 'Da Lat']);
        $provider = Provider::factory()->create([
            'user_id' => $providerUser->id,
            'destination_id' => $destination->id,
            'business_name' => 'Dalat Express Car',
        ]);

        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'destination' => 'Da Lat']);
        $travelRequest = TravelRequest::factory()->create(['trip_id' => $trip->id, 'status' => 'open']);

        $proposalData = [
            'message' => 'We offer clean, brand-new 7-seater SUV with English speaking driver.',
            'valid_until' => now()->addDays(5)->toDateTimeString(),
            'items' => [
                ['name' => 'Airport Pickup 7-Seater', 'price' => 600000.00],
                ['name' => 'Full Day City Tour Car', 'price' => 1200000.00],
            ],
        ];

        $proposal = $this->proposalService->submitProposal($provider, $travelRequest, $proposalData);

        $this->assertInstanceOf(Proposal::class, $proposal);
        $this->assertEquals(1800000.00, (float) $proposal->total_price);
        $this->assertEquals('pending', $proposal->status);
        $this->assertCount(2, $proposal->items);
        $this->assertEquals('bidding', $travelRequest->fresh()->status);
    }

    public function test_proposal_service_generates_comparison_matrix(): void
    {
        $destination = Destination::factory()->create(['name' => 'Da Lat']);
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'destination' => 'Da Lat']);
        $travelRequest = TravelRequest::factory()->create([
            'trip_id' => $trip->id,
            'max_budget' => 3000000,
        ]);

        $provider1 = Provider::factory()->create(['destination_id' => $destination->id, 'business_name' => 'Provider One']);
        $provider2 = Provider::factory()->create(['destination_id' => $destination->id, 'business_name' => 'Provider Two']);

        $p1 = $this->proposalService->submitProposal($provider1, $travelRequest, [
            'message' => 'Affordable bundle',
            'valid_until' => now()->addDays(7)->toDateTimeString(),
            'items' => [
                ['name' => 'Service A', 'price' => 1500000.00],
            ],
        ]);

        $p2 = $this->proposalService->submitProposal($provider2, $travelRequest, [
            'message' => 'Premium bundle',
            'valid_until' => now()->addDays(7)->toDateTimeString(),
            'items' => [
                ['name' => 'Service B', 'price' => 2500000.00],
            ],
        ]);

        $matrix = $this->proposalService->getComparisonMatrix($travelRequest);

        $this->assertEquals(2, $matrix['stats']['count']);
        $this->assertEquals(1500000.00, $matrix['stats']['min_price']);
        $this->assertEquals(2500000.00, $matrix['stats']['max_price']);

        $proposals = $matrix['proposals'];
        $this->assertTrue($proposals[0]['is_lowest_price']);
        $this->assertEquals(1500000.00, $proposals[0]['savings']);
    }

    public function test_accept_full_bundle_creates_bookings_vouchers_timeline_activity_and_updates_budget(): void
    {
        Event::fake([BookingConfirmed::class]);

        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'destination' => 'Da Lat',
            'budget' => 10000000.00,
        ]);

        $destination = Destination::factory()->create(['name' => 'Da Lat']);
        $providerA = Provider::factory()->create(['destination_id' => $destination->id, 'business_name' => 'Dalat Adventures']);
        $providerB = Provider::factory()->create(['destination_id' => $destination->id, 'business_name' => 'Dalat Tours']);

        $travelRequest = TravelRequest::factory()->create(['trip_id' => $trip->id, 'status' => 'bidding']);

        $proposalA = $this->proposalService->submitProposal($providerA, $travelRequest, [
            'message' => 'Winning bundle',
            'valid_until' => now()->addDays(5)->toDateTimeString(),
            'items' => [
                ['name' => 'Car 4-Seater 2 Days', 'price' => 1200000.00],
                ['name' => 'Coffee Farm Tour', 'price' => 800000.00],
            ],
        ]);

        $proposalB = $this->proposalService->submitProposal($providerB, $travelRequest, [
            'message' => 'Competing bundle',
            'valid_until' => now()->addDays(5)->toDateTimeString(),
            'items' => [
                ['name' => 'Car 7-Seater', 'price' => 2500000.00],
            ],
        ]);

        // Leader accepts Proposal A
        $bookings = $this->bookingService->acceptFullBundle($proposalA, $owner);

        $this->assertCount(2, $bookings);
        $this->assertEquals('accepted', $proposalA->fresh()->status);
        $this->assertEquals('rejected', $proposalB->fresh()->status);
        $this->assertEquals('fulfilled', $travelRequest->fresh()->status);

        // Check bookings & vouchers
        foreach ($bookings as $booking) {
            $this->assertStringStartsWith('WND-', $booking->voucher_code);
            $this->assertEquals('confirmed', $booking->status);
            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'trip_id' => $trip->id,
                'voucher_code' => $booking->voucher_code,
            ]);
        }

        // Check timeline activity integration
        $activities = Activity::where('trip_id', $trip->id)->where('type', 'booking')->get();
        $this->assertCount(2, $activities);
        $this->assertEquals(2000000.00, (float) $activities->sum('cost'));

        // Check budget deduction
        $budgetStatus = $this->budgetService->calculateBudgetStatus($trip);
        $this->assertEquals(2000000.00, $budgetStatus['total_spent']);
        $this->assertEquals(8000000.00, $budgetStatus['remaining_budget']);

        Event::assertDispatched(BookingConfirmed::class);
    }

    public function test_accept_single_item_creates_single_booking_and_voucher(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'destination' => 'Da Lat',
            'budget' => 5000000.00,
        ]);

        $destination = Destination::factory()->create(['name' => 'Da Lat']);
        $provider = Provider::factory()->create(['destination_id' => $destination->id]);

        $travelRequest = TravelRequest::factory()->create(['trip_id' => $trip->id]);
        $proposal = $this->proposalService->submitProposal($provider, $travelRequest, [
            'message' => 'Bundle offer',
            'valid_until' => now()->addDays(5)->toDateTimeString(),
            'items' => [
                ['name' => 'Item 1 - Car Transfer', 'price' => 700000.00],
                ['name' => 'Item 2 - City Guide', 'price' => 500000.00],
            ],
        ]);

        $itemToBook = $proposal->items()->first();

        $booking = $this->bookingService->acceptSingleItem($itemToBook, $owner);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals('Item 1 - Car Transfer', $booking->item_name);
        $this->assertEquals(700000.00, (float) $booking->price);
        $this->assertStringStartsWith('WND-', $booking->voucher_code);
        $this->assertTrue($itemToBook->fresh()->is_accepted);

        $this->assertDatabaseHas('activities', [
            'trip_id' => $trip->id,
            'type' => 'booking',
            'cost' => 700000.00,
        ]);
    }
}
