<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Proposal;
use App\Models\ProposalInsight;
use App\Models\ProposalItem;
use App\Models\Provider;
use App\Models\Service;
use App\Models\TravelRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\DynamicBudgetAdvisorService;
use App\Services\ProposalAiOptimizerService;
use App\Services\ProviderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalOptimizerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $viewer;

    protected Trip $trip;

    protected Destination $destination;

    protected Provider $provider1;

    protected Provider $provider2;

    protected TravelRequest $travelRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destination = Destination::create([
            'name' => 'Da Lat',
            'slug' => 'da-lat',
            'description' => 'City of eternal spring and pine forests',
        ]);

        $this->owner = User::factory()->create(['role' => 'traveler']);
        $this->viewer = User::factory()->create(['role' => 'traveler']);

        $this->trip = Trip::create([
            'owner_id' => $this->owner->id,
            'name' => 'Da Lat AI Test Trip',
            'destination' => 'Da Lat',
            'start_date' => Carbon::today()->next(Carbon::FRIDAY)->toDateString(),
            'end_date' => Carbon::today()->next(Carbon::FRIDAY)->addDays(2)->toDateString(),
            'budget' => 8000000.00,
            'privacy' => 'private',
            'status' => 'planning',
        ]);

        $this->trip->members()->create([
            'user_id' => $this->owner->id,
            'role' => 'leader',
        ]);

        $this->trip->members()->create([
            'user_id' => $this->viewer->id,
            'role' => 'viewer',
        ]);

        // Provider 1: Budget Shuttle & Tour
        $providerUser1 = User::factory()->create(['role' => 'provider']);
        $this->provider1 = Provider::create([
            'user_id' => $providerUser1->id,
            'business_name' => 'Da Lat Budget Transfers',
            'service_type' => 'transport',
            'destination_id' => $this->destination->id,
            'rating' => 4.6,
            'phone' => '0901234567',
            'address' => '12 Phan Dinh Phung, Da Lat',
            'is_verified' => true,
        ]);

        // Provider 2: Premium Highlands Exploration
        $providerUser2 = User::factory()->create(['role' => 'provider']);
        $this->provider2 = Provider::create([
            'user_id' => $providerUser2->id,
            'business_name' => 'Pine Highlands Premium Tours',
            'service_type' => 'tour',
            'destination_id' => $this->destination->id,
            'rating' => 4.9,
            'phone' => '0909876543',
            'address' => '88 Tran Phu, Da Lat',
            'is_verified' => true,
        ]);

        $this->travelRequest = TravelRequest::create([
            'trip_id' => $this->trip->id,
            'needs' => ['transport', 'tour'],
            'pace' => 'balanced',
            'max_budget' => 5000000.00,
            'status' => 'bidding',
        ]);
    }

    public function test_guest_cannot_access_proposal_comparison(): void
    {
        $response = $this->get(route('trips.marketplace.compare', [$this->trip, $this->travelRequest]));
        $response->assertRedirect(route('login'));
    }

    public function test_proposal_ai_optimizer_analyzes_proposal_and_detects_hidden_costs(): void
    {
        $proposal = Proposal::create([
            'request_id' => $this->travelRequest->id,
            'provider_id' => $this->provider1->id,
            'total_price' => 1800000.00,
            'message' => 'Includes 7-seat car with AC. Gas excluded, personal meals and driver tip not included.',
            'valid_until' => Carbon::now()->addDays(5),
            'status' => 'pending',
        ]);

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'name' => '7-Seater Private Car City Shuttle',
            'price' => 1200000.00,
            'specs' => ['seats' => 7, 'ac' => true],
        ]);

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'name' => 'Sightseeing Attraction Route (Self-Guided)',
            'price' => 600000.00,
            'specs' => ['tour_type' => 'sightseeing', 'tickets' => false],
        ]);

        $service = app(ProposalAiOptimizerService::class);
        $insight = $service->analyzeProposal($proposal, $this->travelRequest);

        $this->assertInstanceOf(ProposalInsight::class, $insight);
        $this->assertNotEmpty($insight->hidden_costs);
        $this->assertGreaterThanOrEqual(50, $insight->score);
        $this->assertLessThanOrEqual(98, $insight->score);
        $this->assertNotEmpty($insight->summary_verdict);

        // Verify hidden cost detects unincluded fees
        $hiddenCostsString = implode(' ', $insight->hidden_costs);
        $this->assertTrue(
            str_contains(strtolower($hiddenCostsString), 'ticket') ||
            str_contains(strtolower($hiddenCostsString), 'tip') ||
            str_contains(strtolower($hiddenCostsString), 'toll') ||
            str_contains(strtolower($hiddenCostsString), 'fuel')
        );
    }

    public function test_proposal_ai_optimizer_assigns_correct_badges_for_multiple_proposals(): void
    {
        // Proposal A: Cheaper offer (1.500.000 VND)
        $proposalA = Proposal::create([
            'request_id' => $this->travelRequest->id,
            'provider_id' => $this->provider1->id,
            'total_price' => 1500000.00,
            'message' => 'Basic shuttle service. Affordable rate.',
            'valid_until' => Carbon::now()->addDays(3),
            'status' => 'pending',
        ]);

        ProposalItem::create([
            'proposal_id' => $proposalA->id,
            'name' => 'Economy 4-Seater Transfer',
            'price' => 1500000.00,
            'specs' => ['seats' => 4],
        ]);

        // Proposal B: Premium High-end Tour (3.200.000 VND) with 4.9 rating
        $proposalB = Proposal::create([
            'request_id' => $this->travelRequest->id,
            'provider_id' => $this->provider2->id,
            'total_price' => 3200000.00,
            'message' => 'VIP 7-seater car with AC, English guide, complimentary airport pickup and coffee tasting.',
            'valid_until' => Carbon::now()->addDays(3),
            'status' => 'pending',
        ]);

        ProposalItem::create([
            'proposal_id' => $proposalB->id,
            'name' => 'VIP 7-Seater Airport & City Experience',
            'price' => 2000000.00,
            'specs' => ['seats' => 7, 'ac' => true, 'english' => true, 'airport' => true],
        ]);

        ProposalItem::create([
            'proposal_id' => $proposalB->id,
            'name' => 'Highlands Specialty Coffee Farm & Water Tour',
            'price' => 1200000.00,
            'specs' => ['coffee' => true, 'water' => true],
        ]);

        $service = app(ProposalAiOptimizerService::class);
        $proposals = $service->compareMultipleProposals($this->travelRequest);

        $this->assertCount(2, $proposals);

        $badgeA = $proposals->firstWhere('id', $proposalA->id)->insight->badge;
        $badgeB = $proposals->firstWhere('id', $proposalB->id)->insight->badge;

        $this->assertNotNull($badgeA);
        $this->assertNotNull($badgeB);

        // One must be budget_saver or best_value, and the other premium_choice or best_value
        $badges = [$badgeA, $badgeB];
        $this->assertTrue(
            in_array(ProposalInsight::BADGE_BUDGET_SAVER, $badges) ||
            in_array(ProposalInsight::BADGE_BEST_VALUE, $badges)
        );
        $this->assertTrue(
            in_array(ProposalInsight::BADGE_PREMIUM_CHOICE, $badges) ||
            in_array(ProposalInsight::BADGE_BEST_VALUE, $badges)
        );
    }

    public function test_dynamic_budget_advisor_suggests_optimal_booking_dates(): void
    {
        $advisor = app(DynamicBudgetAdvisorService::class);
        $advice = $advisor->suggestOptimalBookingDates($this->trip, $this->provider1);

        $this->assertIsArray($advice);
        $this->assertTrue($advice['has_suggestion']);
        $this->assertGreaterThan(0, $advice['estimated_savings_vnd']);
        $this->assertNotEmpty($advice['recommended_period']);
        $this->assertNotEmpty($advice['message']);
    }

    public function test_dynamic_budget_advisor_generates_cross_bundle_suggestions(): void
    {
        // Proposal A from Provider 1: Cheap Shuttle (1.000.000), Expensive Tour (2.000.000). Total: 3.000.000
        $proposalA = Proposal::create([
            'request_id' => $this->travelRequest->id,
            'provider_id' => $this->provider1->id,
            'total_price' => 3000000.00,
            'message' => 'Full bundle A',
            'valid_until' => Carbon::now()->addDays(3),
            'status' => 'pending',
        ]);
        ProposalItem::create([
            'proposal_id' => $proposalA->id,
            'name' => 'Shuttle Van Transport',
            'price' => 1000000.00,
        ]);
        ProposalItem::create([
            'proposal_id' => $proposalA->id,
            'name' => 'Countryside Tour',
            'price' => 2000000.00,
        ]);

        // Proposal B from Provider 2: Expensive Shuttle (1.800.000), Cheap Tour (900.000). Total: 2.700.000
        $proposalB = Proposal::create([
            'request_id' => $this->travelRequest->id,
            'provider_id' => $this->provider2->id,
            'total_price' => 2700000.00,
            'message' => 'Full bundle B',
            'valid_until' => Carbon::now()->addDays(3),
            'status' => 'pending',
        ]);
        ProposalItem::create([
            'proposal_id' => $proposalB->id,
            'name' => 'Luxury Private Car Transport',
            'price' => 1800000.00,
        ]);
        ProposalItem::create([
            'proposal_id' => $proposalB->id,
            'name' => 'Cultural Coffee Tasting Tour',
            'price' => 900000.00,
        ]);

        // Cross-provider combo: Shuttle A (1.000.000) + Tour B (900.000) = 1.900.000 (saves 800.000 vs 2.700.000)
        $advisor = app(DynamicBudgetAdvisorService::class);
        $combo = $advisor->generateBundleSuggestions($this->travelRequest);

        $this->assertIsArray($combo);
        $this->assertTrue($combo['has_suggestion']);
        $this->assertEquals(1900000.00, $combo['combo_price']);
        $this->assertEquals(800000.00, $combo['savings_vnd']);
        $this->assertNotEmpty($combo['tip']);
    }

    public function test_compare_page_renders_ai_badges_and_hidden_costs_warnings(): void
    {
        $proposal = Proposal::create([
            'request_id' => $this->travelRequest->id,
            'provider_id' => $this->provider1->id,
            'total_price' => 2100000.00,
            'message' => 'City tour package. Highway toll not included.',
            'valid_until' => Carbon::now()->addDays(4),
            'status' => 'pending',
        ]);

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'name' => 'Airport Transfer Car',
            'price' => 1100000.00,
            'specs' => ['ac' => true],
        ]);

        ProposalItem::create([
            'proposal_id' => $proposal->id,
            'name' => 'Da Lat Highlights Tour',
            'price' => 1000000.00,
            'specs' => ['tour_type' => 'highlights'],
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('trips.marketplace.compare', [$this->trip, $this->travelRequest]));

        $response->assertOk();
        $response->assertSee(__('AI Dynamic Budget Advisor Tips'));
        $response->assertSee(__('Best Value'));
        $response->assertSee('AI Score:');
        $response->assertSee(__('AI Co-pilot Analysis:'));
    }

    public function test_authenticated_user_can_trigger_on_demand_analyze_endpoint(): void
    {
        $proposal = Proposal::create([
            'request_id' => $this->travelRequest->id,
            'provider_id' => $this->provider1->id,
            'total_price' => 1700000.00,
            'message' => 'Special promo quote.',
            'valid_until' => Carbon::now()->addDays(2),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson(route('trips.marketplace.proposals.analyze', [$this->trip, $proposal]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'insight' => [
                'score',
                'badge',
                'pros',
                'cons',
                'hidden_costs',
                'summary_verdict',
            ],
        ]);
    }

    public function test_provider_service_recommends_services_for_destination(): void
    {
        Service::create([
            'provider_id' => $this->provider1->id,
            'name' => 'Da Lat Motorbike Rental 150cc',
            'price' => 150000.00,
            'tags' => ['transport', 'motorbike'],
            'specs' => ['type' => 'manual', 'helmet' => true],
        ]);

        Service::create([
            'provider_id' => $this->provider2->id,
            'name' => 'Lang Biang Cloud Hunting Tour',
            'price' => 450000.00,
            'tags' => ['tour', 'nature'],
            'specs' => ['duration' => 'half-day'],
        ]);

        $providerService = app(ProviderService::class);
        $recommended = $providerService->getRecommendedServicesForDestination('Da Lat', 2);

        $this->assertCount(2, $recommended);
        $this->assertTrue($recommended->contains('name', 'Da Lat Motorbike Rental 150cc'));
        $this->assertTrue($recommended->contains('name', 'Lang Biang Cloud Hunting Tour'));
    }
}
