<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\SettlementPayment;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\MinCashFlowService;
use App\Services\SplitBillService;
use App\Services\VietQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitBillTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $memberA;

    protected User $memberB;

    protected User $memberC;

    protected Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Owner Leader']);
        $this->memberA = User::factory()->create(['name' => 'Member Alice']);
        $this->memberB = User::factory()->create(['name' => 'Member Bob']);
        $this->memberC = User::factory()->create(['name' => 'Member Charlie']);

        $this->trip = Trip::factory()->create([
            'owner_id' => $this->owner->id,
            'name' => 'Da Nang Adventure',
            'destination' => 'Da Nang',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        TripMember::create(['trip_id' => $this->trip->id, 'user_id' => $this->owner->id, 'role' => 'leader']);
        TripMember::create(['trip_id' => $this->trip->id, 'user_id' => $this->memberA->id, 'role' => 'editor']);
        TripMember::create(['trip_id' => $this->trip->id, 'user_id' => $this->memberB->id, 'role' => 'member']);
        TripMember::create(['trip_id' => $this->trip->id, 'user_id' => $this->memberC->id, 'role' => 'viewer']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  E2-01: DATABASE, MODELS & RELATIONSHIPS
    // ══════════════════════════════════════════════════════════════════════

    public function test_models_and_relationships_are_properly_wired(): void
    {
        $activity = Activity::factory()->create([
            'trip_id' => $this->trip->id,
            'cost' => 300000,
            'payer_id' => $this->memberA->id,
        ]);

        $participant = ActivityParticipant::create([
            'activity_id' => $activity->id,
            'user_id' => $this->memberB->id,
            'custom_amount' => 150000,
        ]);

        $payment = SettlementPayment::create([
            'trip_id' => $this->trip->id,
            'from_user_id' => $this->memberB->id,
            'to_user_id' => $this->memberA->id,
            'amount' => 150000,
            'status' => 'pending',
        ]);

        // Test Activity relationships
        $this->assertEquals($this->memberA->id, $activity->payer->id);
        $this->assertCount(1, $activity->participants);
        $this->assertEquals($this->memberB->id, $activity->participantUsers->first()->id);

        // Test ActivityParticipant relationships
        $this->assertEquals($activity->id, $participant->activity->id);
        $this->assertEquals($this->memberB->id, $participant->user->id);

        // Test SettlementPayment relationships
        $this->assertEquals($this->trip->id, $payment->trip->id);
        $this->assertEquals($this->memberB->id, $payment->fromUser->id);
        $this->assertEquals($this->memberA->id, $payment->toUser->id);
        $this->assertFalse($payment->isCompleted());
    }

    // ══════════════════════════════════════════════════════════════════════
    //  E2-02: SPLIT BILL SERVICE
    // ══════════════════════════════════════════════════════════════════════

    public function test_assign_participants_equal_split(): void
    {
        $activity = Activity::factory()->create([
            'trip_id' => $this->trip->id,
            'cost' => 600000,
        ]);

        $service = app(SplitBillService::class);
        $service->assignParticipants(
            $activity,
            [$this->memberA->id, $this->memberB->id, $this->memberC->id],
            $this->memberA->id
        );

        $activity->refresh();
        $this->assertEquals($this->memberA->id, $activity->payer_id);
        $this->assertCount(3, $activity->participants);

        $details = $service->getActivitySplitDetails($activity);
        $this->assertTrue($details['is_split']);
        $this->assertFalse($details['is_custom']);
        $this->assertEquals(200000, $details['participants'][0]['calculated_share']);
    }

    public function test_assign_participants_custom_amounts(): void
    {
        $activity = Activity::factory()->create([
            'trip_id' => $this->trip->id,
            'cost' => 500000,
        ]);

        $service = app(SplitBillService::class);
        $service->assignParticipants(
            $activity,
            [$this->memberA->id, $this->memberB->id],
            $this->memberA->id,
            [
                $this->memberA->id => 200000,
                $this->memberB->id => 300000,
            ]
        );

        $details = $service->getActivitySplitDetails($activity);
        $this->assertTrue($details['is_custom']);
        $this->assertEquals(200000, $details['participants'][0]['calculated_share']);
        $this->assertEquals(300000, $details['participants'][1]['calculated_share']);
    }

    public function test_calculate_trip_balances_multiple_activities(): void
    {
        $service = app(SplitBillService::class);

        // Activity 1: Dinner 600k paid by Alice (memberA), split between Alice, Bob, Charlie (200k each)
        $act1 = Activity::factory()->create(['trip_id' => $this->trip->id, 'cost' => 600000]);
        $service->assignParticipants($act1, [$this->memberA->id, $this->memberB->id, $this->memberC->id], $this->memberA->id);

        // Activity 2: Taxi 300k paid by Bob (memberB), split between Bob & Charlie (150k each)
        $act2 = Activity::factory()->create(['trip_id' => $this->trip->id, 'cost' => 300000]);
        $service->assignParticipants($act2, [$this->memberB->id, $this->memberC->id], $this->memberB->id);

        $result = $service->calculateTripBalances($this->trip);

        $members = collect($result['members'])->keyBy('user_id');

        // Alice: spent 600k, owes 200k => Net +400k (Creditor)
        $this->assertEquals(600000, $members[$this->memberA->id]['total_spent']);
        $this->assertEquals(200000, $members[$this->memberA->id]['total_owed']);
        $this->assertEquals(400000, $members[$this->memberA->id]['net_balance']);
        $this->assertEquals('creditor', $members[$this->memberA->id]['status']);

        // Bob: spent 300k, owes (200k + 150k = 350k) => Net -50k (Debtor)
        $this->assertEquals(300000, $members[$this->memberB->id]['total_spent']);
        $this->assertEquals(350000, $members[$this->memberB->id]['total_owed']);
        $this->assertEquals(-50000, $members[$this->memberB->id]['net_balance']);
        $this->assertEquals('debtor', $members[$this->memberB->id]['status']);

        // Charlie: spent 0, owes (200k + 150k = 350k) => Net -350k (Debtor)
        $this->assertEquals(0, $members[$this->memberC->id]['total_spent']);
        $this->assertEquals(350000, $members[$this->memberC->id]['total_owed']);
        $this->assertEquals(-350000, $members[$this->memberC->id]['net_balance']);
        $this->assertEquals('debtor', $members[$this->memberC->id]['status']);

        // Conservation of money: Sum of net balances must equal 0
        $sumNet = collect($result['members'])->sum('net_balance');
        $this->assertEquals(0, $sumNet);

        $this->assertFalse($result['is_settled']);
        $this->assertCount(1, $result['creditors']);
        $this->assertCount(2, $result['debtors']);
    }

    public function test_equal_split_penny_rounding_integrity(): void
    {
        $service = app(SplitBillService::class);

        // Activity with non-divisible cost: 100,000 / 3 = 33,333.33 each
        $act = Activity::factory()->create(['trip_id' => $this->trip->id, 'cost' => 100000]);
        $service->assignParticipants($act, [$this->memberA->id, $this->memberB->id, $this->memberC->id], $this->memberA->id);

        $result = $service->calculateTripBalances($this->trip);
        $sumOwed = collect($result['members'])->sum('total_owed');

        $this->assertEquals(100000, $sumOwed);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  E2-03: MIN CASH FLOW SERVICE (GREEDY ENGINE)
    // ══════════════════════════════════════════════════════════════════════

    public function test_greedy_min_cash_flow_minimizes_transactions(): void
    {
        $splitService = app(SplitBillService::class);
        $minCashFlowService = app(MinCashFlowService::class);

        // Setup:
        // Act 1: 600k paid by Alice (A, B, C share 200k)
        $act1 = Activity::factory()->create(['trip_id' => $this->trip->id, 'cost' => 600000]);
        $splitService->assignParticipants($act1, [$this->memberA->id, $this->memberB->id, $this->memberC->id], $this->memberA->id);

        // Act 2: 300k paid by Bob (B, C share 150k)
        $act2 = Activity::factory()->create(['trip_id' => $this->trip->id, 'cost' => 300000]);
        $splitService->assignParticipants($act2, [$this->memberB->id, $this->memberC->id], $this->memberB->id);

        // Net balances: A = +400k, B = -50k, C = -350k
        $plan = $minCashFlowService->generateSettlementPlan($this->trip);

        // Greedy Min-Cash-Flow should reduce 3 people's debts to exactly 2 transactions:
        // Tx 1: Charlie pays Alice 350,000
        // Tx 2: Bob pays Alice 50,000
        $this->assertEquals(2, $plan['total_transactions']);
        $this->assertEquals(400000, $plan['total_settlement_amount']);

        $tx1 = $plan['transactions'][0];
        $this->assertEquals($this->memberC->id, $tx1['from_user_id']);
        $this->assertEquals($this->memberA->id, $tx1['to_user_id']);
        $this->assertEquals(350000, $tx1['amount']);

        $tx2 = $plan['transactions'][1];
        $this->assertEquals($this->memberB->id, $tx2['from_user_id']);
        $this->assertEquals($this->memberA->id, $tx2['to_user_id']);
        $this->assertEquals(50000, $tx2['amount']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  E2-04: VIETQR SERVICE
    // ══════════════════════════════════════════════════════════════════════

    public function test_vietqr_url_generation(): void
    {
        $vietQr = app(VietQrService::class);
        $memo = $vietQr->formatSettlementMemo($this->trip->id, $this->memberB->id, $this->memberA->id);

        $url = $vietQr->generateQrUrl(
            bankBin: '970422',
            accountNumber: '0987654321',
            amount: 250000,
            memo: $memo,
            accountName: 'NGUYEN VAN A'
        );

        $this->assertStringContainsString('https://img.vietqr.io/image/970422-0987654321-compact2.png', $url);
        $this->assertStringContainsString('amount=250000', $url);
        $this->assertStringContainsString("WND_SETTLE_{$this->trip->id}", urldecode($url));
    }

    public function test_confirm_settlement_payment(): void
    {
        $vietQr = app(VietQrService::class);
        $payment = $vietQr->createOrUpdateSettlementPayment(
            $this->trip,
            $this->memberB->id,
            $this->memberA->id,
            250000,
            '970422',
            '0987654321'
        );

        $this->assertEquals('pending', $payment->status);
        $this->assertNull($payment->completed_at);

        $vietQr->confirmPayment($payment);

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertNotNull($payment->completed_at);
        $this->assertTrue($payment->isCompleted());
    }

    // ══════════════════════════════════════════════════════════════════════
    //  E2-05: HTTP CONTROLLER & AUTHORIZATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_guest_cannot_access_settlement_plan(): void
    {
        $this->getJson(route('ajax.trips.settlements', $this->trip))
            ->assertUnauthorized();
    }

    public function test_stranger_cannot_access_trip_settlements(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->getJson(route('ajax.trips.settlements', $this->trip))
            ->assertForbidden();
    }

    public function test_trip_member_can_fetch_settlement_plan(): void
    {
        $this->actingAs($this->memberB)
            ->getJson(route('ajax.trips.settlements', $this->trip))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'plan' => [
                    'trip_id',
                    'is_settled',
                    'total_transactions',
                    'transactions',
                    'members_summary',
                ],
                'supported_banks',
            ]);
    }

    public function test_viewer_cannot_assign_participants_to_activity(): void
    {
        $activity = Activity::factory()->create(['trip_id' => $this->trip->id, 'cost' => 100000]);

        $this->actingAs($this->memberC) // memberC is viewer
            ->postJson(route('ajax.trips.activities.split.assign', [$this->trip, $activity]), [
                'payer_id' => $this->memberA->id,
                'user_ids' => [$this->memberA->id, $this->memberB->id],
            ])
            ->assertForbidden();
    }

    public function test_editor_or_leader_can_assign_participants_to_activity(): void
    {
        $activity = Activity::factory()->create(['trip_id' => $this->trip->id, 'cost' => 200000]);

        $response = $this->actingAs($this->memberA) // memberA is editor
            ->postJson(route('ajax.trips.activities.split.assign', [$this->trip, $activity]), [
                'payer_id' => $this->memberA->id,
                'user_ids' => [$this->memberA->id, $this->memberB->id],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'split_details' => [
                    'activity_id' => $activity->id,
                    'payer_id' => $this->memberA->id,
                    'participants_count' => 2,
                    'is_split' => true,
                ],
            ]);

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $activity->id,
            'user_id' => $this->memberB->id,
        ]);
    }

    public function test_generate_vietqr_endpoint(): void
    {
        $response = $this->actingAs($this->memberB)
            ->postJson(route('ajax.trips.settlements.vietqr', $this->trip), [
                'bank_bin' => '970422',
                'account_number' => '123456789',
                'amount' => 150000,
                'to_user_id' => $this->memberA->id,
                'from_user_id' => $this->memberB->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'amount' => 150000,
            ])
            ->assertJsonStructure(['qr_url', 'memo']);
    }

    public function test_confirm_payment_endpoint(): void
    {
        $response = $this->actingAs($this->memberB)
            ->postJson(route('ajax.trips.settlements.confirm', $this->trip), [
                'from_user_id' => $this->memberB->id,
                'to_user_id' => $this->memberA->id,
                'amount' => 150000,
                'bank_bin' => '970422',
                'bank_account_number' => '123456789',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('settlement_payments', [
            'trip_id' => $this->trip->id,
            'from_user_id' => $this->memberB->id,
            'to_user_id' => $this->memberA->id,
            'amount' => 150000,
            'status' => 'completed',
        ]);
    }
}
