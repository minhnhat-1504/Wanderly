<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedItineraryAiCopilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_copilot_generates_verified_itinerary_action_card(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Gợi ý lịch trình du lịch Đà Nẵng 3 ngày',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $assistantMsg = $response->json('assistant_message');
        $this->assertNotEmpty($assistantMsg);
        $this->assertNotEmpty($assistantMsg['content']);

        $action = $assistantMsg['suggested_action'];
        $this->assertNotNull($action, 'Expected suggested_action to be present.');
        $this->assertEquals('create_trip_itinerary', $action['action']);

        $params = $action['params'];
        $this->assertEquals('danang', $params['province']['code']);
        $this->assertCount(3, $params['days']);
        $this->assertNotEmpty($params['attributions']['map_data']);
        $this->assertNotEmpty($params['disclaimer']);

        // Assert all places in days have verified=true and google_maps_url
        foreach ($params['days'] as $day) {
            $this->assertGreaterThanOrEqual(3, count($day['items']));
            foreach ($day['items'] as $item) {
                $this->assertTrue($item['place']['verified']);
                $this->assertStringStartsWith('https://www.google.com/maps/search/', $item['place']['google_maps_url']);
            }
        }
    }

    public function test_can_create_concrete_trip_and_activities_from_itinerary(): void
    {
        $user = User::factory()->create();

        $payload = [
            'province' => [
                'code' => 'danang',
                'name' => 'Đà Nẵng',
            ],
            'start_date' => '2026-11-01',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        [
                            'time' => '08:30',
                            'duration_min' => 90,
                            'activity' => 'Tắm biển Mỹ Khê',
                            'note' => 'Đi dạo bãi cát trắng',
                            'place' => [
                                'id' => 'dn_003',
                                'name_vi' => 'Bãi biển Mỹ Khê',
                                'category' => 'beach',
                                'ticket_price_vnd' => 0,
                                'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=16.0594,108.2467',
                                'opening_hours' => ['open' => '05:00', 'close' => '19:00'],
                            ],
                        ],
                        [
                            'time' => '12:00',
                            'duration_min' => 60,
                            'activity' => 'Ăn trưa Chợ Cồn',
                            'note' => 'Thưởng thức ẩm thực đường phố',
                            'place' => [
                                'id' => 'dn_007',
                                'name_vi' => 'Chợ Cồn',
                                'category' => 'market',
                                'ticket_price_vnd' => 0,
                                'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=16.0682,108.2163',
                                'opening_hours' => ['open' => '07:00', 'close' => '19:00'],
                            ],
                        ],
                        [
                            'time' => '18:30',
                            'duration_min' => 90,
                            'activity' => 'Ăn tối hải sản Bé Mặn',
                            'note' => 'Hải sản tươi sống',
                            'place' => [
                                'id' => 'dn_020',
                                'name_vi' => 'Hải sản Bé Mặn',
                                'category' => 'food',
                                'ticket_price_vnd' => 250000,
                                'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=16.0745,108.2488',
                                'opening_hours' => ['open' => '10:00', 'close' => '23:00'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.create-trip'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $tripId = $response->json('trip_id');
        $this->assertNotNull($tripId);

        $trip = Trip::find($tripId);
        $this->assertNotNull($trip);
        $this->assertEquals($user->id, $trip->owner_id);
        $this->assertEquals('Đà Nẵng', $trip->destination);
        $this->assertEquals('2026-11-01', $trip->start_date->toDateString());

        // Assert activities were created
        $this->assertCount(3, $trip->activities);
        $firstAct = $trip->activities()->where('position', 0)->first();
        $this->assertStringStartsWith('08:30', (string) $firstAct->start_time);
        $this->assertStringStartsWith('10:00', (string) $firstAct->end_time);
        $this->assertEquals(0, (float) $firstAct->cost);

        $thirdAct = $trip->activities()->where('position', 2)->first();
        $this->assertEquals(250000, (float) $thirdAct->cost);
    }

    public function test_asks_clarification_on_unknown_province_input(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Lập lịch trình du lịch tại Xứ Sở Thần Tiên 3 ngày',
        ]);

        $response->assertStatus(200);
        $assistantMsg = $response->json('assistant_message');
        $this->assertNotEmpty($assistantMsg['content']);
    }

    public function test_can_clear_global_chat_history(): void
    {
        $user = User::factory()->create();

        // Send a message first
        $this->actingAs($user)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Xin chào AI Travel Assistant',
        ]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $user->id,
            'trip_id' => null,
        ]);

        // Clear history
        $response = $this->actingAs($user)->deleteJson(route('ajax.ai-copilot.clear-global-messages'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('ai_chat_messages', [
            'user_id' => $user->id,
            'trip_id' => null,
        ]);
    }

    public function test_ai_copilot_parses_vietnamese_word_days_and_generates_exact_duration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Hãy gợi ý lịch trình hai ngày tại Đà Nẵng',
        ]);

        $response->assertStatus(200);
        $action = $response->json('assistant_message.suggested_action');
        $this->assertNotNull($action);
        $this->assertEquals('create_trip_itinerary', $action['action']);
        // Verify EXACTLY 2 days are generated!
        $this->assertCount(2, $action['params']['days']);
        $this->assertEquals(2, $action['params']['summary']['total_days']);
    }

    public function test_create_trip_from_itinerary_fills_formatted_ticket_prices_correctly(): void
    {
        $user = User::factory()->create();

        $payload = [
            'province' => [
                'code' => 'danang',
                'name' => 'Đà Nẵng',
            ],
            'start_date' => '2026-11-01',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        [
                            'time' => '08:30',
                            'duration_min' => 180,
                            'activity' => 'Sun World Bà Nà Hills',
                            'note' => 'Cáp treo lên đỉnh núi',
                            'place' => [
                                'id' => 'dn_001',
                                'name_vi' => 'Sun World Bà Nà Hills & Cầu Vàng',
                                'category' => 'sight',
                                'ticket_price_vnd' => '450.000 VNĐ',
                                'price_vnd' => 450000,
                            ],
                        ],
                        [
                            'time' => '15:00',
                            'duration_min' => 60,
                            'activity' => 'Bảo tàng Điêu khắc Chăm',
                            'place' => [
                                'id' => 'dn_006',
                                'name_vi' => 'Bảo tàng Điêu khắc Chăm',
                                'category' => 'culture',
                                'ticket_price_vnd' => '60.000 VNĐ',
                            ],
                        ],
                        [
                            'time' => '17:00',
                            'duration_min' => 45,
                            'activity' => 'Cầu Rồng Đà Nẵng',
                            'place' => [
                                'id' => 'dn_002',
                                'name_vi' => 'Cầu Rồng Đà Nẵng',
                                'category' => 'sight',
                                'ticket_price_vnd' => 'Miễn phí',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.create-trip'), $payload);

        $response->assertStatus(200);
        $tripId = $response->json('trip_id');
        $trip = Trip::find($tripId);
        $this->assertNotNull($trip);

        $activities = $trip->activities()->orderBy('position')->get();
        $this->assertCount(3, $activities);

        // 1. Sun World Bà Nà Hills: 450000 VND
        $this->assertEquals(450000.0, (float) $activities[0]->cost);
        // 2. Bảo tàng Chăm: parsed from string "60.000 VNĐ" -> 60000 VND
        $this->assertEquals(60000.0, (float) $activities[1]->cost);
        // 3. Cầu Rồng: "Miễn phí" -> 0 VND
        $this->assertEquals(0.0, (float) $activities[2]->cost);
    }

    public function test_create_trip_from_itinerary_clamps_morning_activities_before_11_am_and_prevents_overlap(): void
    {
        $user = User::factory()->create();

        $payload = [
            'province' => [
                'code' => 'danang',
                'name' => 'Đà Nẵng',
            ],
            'start_date' => '2026-11-01',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        [
                            'time' => '08:30',
                            'duration_min' => 180, // Would bleed to 11:30 without clamping
                            'activity' => 'Công viên Suối khoáng nóng Núi Thần Tài',
                            'place' => [
                                'id' => 'dn_011',
                                'name_vi' => 'Công viên Suối khoáng nóng Núi Thần Tài',
                                'category' => 'entertainment',
                            ],
                        ],
                        [
                            'time' => '10:30', // Overlaps with 08:30 - 11:30 without shift
                            'duration_min' => 60,
                            'activity' => 'Chợ Cồn - Thiên đường ẩm thực Đà Nẵng',
                            'place' => [
                                'id' => 'dn_007',
                                'name_vi' => 'Chợ Cồn - Thiên đường ẩm thực Đà Nẵng',
                                'category' => 'food',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.create-trip'), $payload);
        $response->assertStatus(200);

        $tripId = $response->json('trip_id');
        $trip = Trip::find($tripId);
        $activities = $trip->activities()->orderBy('position')->get();

        $this->assertCount(2, $activities);

        $act1 = $activities[0];
        $act2 = $activities[1];

        // Act 1 must be in morning and end at or before 11:00
        $this->assertEquals('morning', $act1->time_slot);
        $this->assertStringStartsWith('08:30', (string) $act1->start_time);
        $this->assertStringStartsWith('11:00', (string) $act1->end_time);

        // Act 2 must NOT overlap; it shifts to 11:00 and goes into midday (11:00 - 14:00)
        $this->assertEquals('midday', $act2->time_slot);
        $this->assertStringStartsWith('11:00', (string) $act2->start_time);
        $this->assertStringStartsWith('12:00', (string) $act2->end_time);
    }

    public function test_ai_copilot_parses_budget_and_propagates_to_trip_creation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Lên lịch trình du lịch Đà Nẵng 3 ngày với ngân sách 5 triệu',
        ]);

        $response->assertStatus(200);
        $action = $response->json('assistant_message.suggested_action');
        $this->assertNotNull($action);

        $summary = $action['params']['summary'];
        $this->assertEquals(5000000, (float) $summary['budget']);
        $this->assertEquals(5000000, (float) $summary['requested_budget']);
        $this->assertEquals('5.000.000 VNĐ', $summary['budget_formatted']);

        // Now create trip from this itinerary
        $createResponse = $this->actingAs($user)->postJson(route('ajax.ai-copilot.create-trip'), [
            'province' => $action['params']['province'],
            'days' => $action['params']['days'],
            'budget' => $summary['budget'],
            'summary' => $summary,
        ]);

        $createResponse->assertStatus(200);
        $tripId = $createResponse->json('trip_id');
        $trip = Trip::find($tripId);
        $this->assertNotNull($trip);
        $this->assertEquals(5000000.0, (float) $trip->budget);
    }

    public function test_ai_copilot_parses_relaxed_pace_and_schedules_fewer_places_per_day(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Lập lịch trình Đà Nẵng 2 ngày phong cách chill, thong thả',
        ]);

        $response->assertStatus(200);
        $action = $response->json('assistant_message.suggested_action');
        $this->assertNotNull($action);

        $params = $action['params'];
        $this->assertEquals('relaxed', $params['summary']['pace']);

        // In relaxed mode, each day has exactly 4 items (1 morning + lunch + 1 afternoon + dinner)
        foreach ($params['days'] as $day) {
            $this->assertCount(4, $day['items']);
        }
    }

    public function test_ai_copilot_answers_factual_place_query_with_verified_address_and_hours(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ajax.ai-copilot.global-chat'), [
            'message' => 'Chợ Hàn Đà Nẵng địa chỉ ở đâu và mở cửa lúc mấy giờ?',
        ]);

        $response->assertStatus(200);
        $msg = $response->json('assistant_message');

        // Verify factual query does not trigger full itinerary action card
        $this->assertNull($msg['suggested_action']);

        // Verify response contains exact verified address from danang.json
        $this->assertStringContainsString('119 Trần Phú', $msg['content']);
        $this->assertStringContainsString('Hải Châu', $msg['content']);
    }
}

