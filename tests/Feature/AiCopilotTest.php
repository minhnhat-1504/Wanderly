<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AiChatMessage;
use App\Models\Destination;
use App\Models\Poi;
use App\Models\Trip;
use App\Models\User;
use App\Models\Zone;
use App\Services\ActionEngineService;
use App\Services\AiCopilotService;
use App\Services\WeatherAdaptiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCopilotTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;
    protected User $member;
    protected Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leader = User::factory()->create(['name' => 'Trip Leader', 'username' => 'leader']);
        $this->member = User::factory()->create(['name' => 'Member User', 'username' => 'member']);

        $destination = Destination::create(['name' => 'Hà Giang', 'description' => 'Vietnam']);
        $zone = Zone::create(['destination_id' => $destination->id, 'name' => 'Mèo Vạc']);
        Poi::create([
            'zone_id' => $zone->id,
            'name' => 'Cafe Mã Pí Lèng Panorama',
            'address' => 'Đèo Mã Pí Lèng, Mèo Vạc, Hà Giang',
            'category' => 'cafe',
            'typical_cost' => 50000,
        ]);

        $this->trip = Trip::create([
            'owner_id' => $this->leader->id,
            'name' => 'Khám phá Hà Giang',
            'destination' => 'Hà Giang',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(8),
            'budget' => 5000000,
            'status' => 'planning',
        ]);

        $this->trip->members()->create([
            'user_id' => $this->leader->id,
            'role' => 'leader',
        ]);

        $this->trip->members()->create([
            'user_id' => $this->member->id,
            'role' => 'editor',
        ]);
    }

    public function test_can_fetch_ai_messages_for_trip(): void
    {
        $this->trip->aiChatMessages()->create([
            'user_id' => $this->leader->id,
            'role' => 'user',
            'content' => 'Chào AI Co-pilot!',
        ]);

        $response = $this->actingAs($this->leader)->getJson(route('ajax.trips.ai-copilot.messages', $this->trip));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'messages');
    }

    public function test_can_send_chat_message_and_receive_ai_mediation(): void
    {
        $response = $this->actingAs($this->leader)->postJson(route('ajax.trips.ai-copilot.chat', $this->trip), [
            'message' => 'Nhóm mình 2 người thích trekking ngắm cảnh, 2 người thích ngồi cafe chill.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('assistant_message.role', 'assistant');

        $this->assertNotEmpty($response->json('assistant_message.content'));

        $this->assertDatabaseHas('ai_chat_messages', [
            'trip_id' => $this->trip->id,
            'role' => 'assistant',
        ]);
    }

    public function test_leader_can_apply_ai_action_to_timeline(): void
    {
        $msg = $this->trip->aiChatMessages()->create([
            'user_id' => $this->leader->id,
            'role' => 'assistant',
            'content' => 'Gợi ý hoạt động cafe view đẹp',
            'suggested_action' => [
                'type' => 'create_activity',
                'params' => [
                    'title' => 'Cafe Mã Pí Lèng Panorama',
                    'day' => 2,
                    'time_slot' => 'afternoon',
                    'cost' => 50000,
                    'location' => 'Đèo Mã Pí Lèng',
                ],
            ],
            'is_applied' => false,
        ]);

        $response = $this->actingAs($this->leader)->postJson(route('ajax.trips.ai-copilot.apply-action', [$this->trip, $msg]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue($msg->fresh()->is_applied);

        $this->assertDatabaseHas('activities', [
            'trip_id' => $this->trip->id,
            'title' => 'Cafe Mã Pí Lèng Panorama',
            'day' => 2,
            'time_slot' => 'afternoon',
        ]);
    }

    public function test_cannot_apply_same_action_twice(): void
    {
        $msg = $this->trip->aiChatMessages()->create([
            'user_id' => $this->leader->id,
            'role' => 'assistant',
            'content' => 'Hoạt động đã chạy',
            'suggested_action' => [
                'type' => 'create_activity',
                'params' => [
                    'title' => 'Dạo phố cổ',
                    'day' => 1,
                    'time_slot' => 'evening',
                    'cost' => 0,
                ],
            ],
            'is_applied' => true,
        ]);

        $response = $this->actingAs($this->leader)->postJson(route('ajax.trips.ai-copilot.apply-action', [$this->trip, $msg]));

        $response->assertStatus(422);
    }

    public function test_can_ingest_review_text_into_activities(): void
    {
        $reviewText = "Ngày 1:
        Sáng: Ăn phở gà 50k
        Trưa: Nghỉ ngơi ăn trưa 100k
        Chiều: Đi thăm bảo tàng 40k
        Tối: Uống bia phố cổ 80k";

        $response = $this->actingAs($this->leader)->postJson(route('ajax.trips.ai-copilot.ingest-review', $this->trip), [
            'review_text' => $reviewText,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'activities');
    }

    public function test_can_fetch_weather_alerts(): void
    {
        $response = $this->actingAs($this->leader)->getJson(route('ajax.trips.ai-copilot.weather-alerts', $this->trip));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'forecast' => ['destination', 'current_temp', 'condition', 'has_rain'],
                'conflicts',
            ]);
    }

    public function test_can_fetch_global_ai_messages(): void
    {
        AiChatMessage::create([
            'user_id' => $this->leader->id,
            'trip_id' => null,
            'role' => 'user',
            'content' => 'Gợi ý du lịch Ninh Bình giúp tôi!',
        ]);

        // Trip-scoped message must NOT appear in global messages
        $this->trip->aiChatMessages()->create([
            'user_id' => $this->leader->id,
            'role' => 'user',
            'content' => 'Hà Giang có gì?',
        ]);

        $response = $this->actingAs($this->leader)->getJson(route('ajax.ai-copilot.global-messages'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.content', 'Gợi ý du lịch Ninh Bình giúp tôi!');
    }

    public function test_can_send_global_ai_message(): void
    {
        $response = $this->actingAs($this->leader)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Nên đi du lịch Đà Nẵng mùa nào đẹp nhất?',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('assistant_message.role', 'assistant');

        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $this->leader->id,
            'trip_id' => null,
            'role' => 'assistant',
        ]);
    }

    public function test_ai_copilot_button_is_at_bottom_outside_of_trip(): void
    {
        $response = $this->actingAs($this->leader)->get(route('trips.index'));

        $response->assertOk()
            ->assertSee('bottom-6 sm:bottom-8')
            ->assertDontSee('bottom-[148px] sm:bottom-[164px]')
            ->assertSee('aiCopilotDrawer({ tripId: null })', false);
    }

    public function test_ai_copilot_button_is_elevated_on_trip_show_page(): void
    {
        $response = $this->actingAs($this->leader)->get(route('trips.show', $this->trip));

        $response->assertOk()
            ->assertSee('bottom-[148px] sm:bottom-[164px]')
            ->assertSee('aiCopilotDrawer({ tripId: ' . $this->trip->id . ' })', false);
    }
}

