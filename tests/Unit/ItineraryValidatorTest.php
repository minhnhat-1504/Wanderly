<?php

namespace Tests\Unit;

use App\Services\Itinerary\ItineraryValidator;
use App\Services\Itinerary\PlaceRepository;
use App\Services\Itinerary\ProvinceResolver;
use App\Services\Itinerary\TravelTimeService;
use Tests\TestCase;

class ItineraryValidatorTest extends TestCase
{
    protected ItineraryValidator $validator;

    protected PlaceRepository $placeRepo;

    protected TravelTimeService $travelTime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->placeRepo = new PlaceRepository;
        $resolver = new ProvinceResolver;
        $this->travelTime = new TravelTimeService($this->placeRepo, $resolver);
        $this->validator = new ItineraryValidator($this->placeRepo, $this->travelTime);
    }

    public function test_validates_clean_itinerary_successfully(): void
    {
        $plan = [
            'province_code' => 'danang',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        [
                            'time' => '08:00',
                            'place_id' => 'dn_003', // Bãi biển Mỹ Khê (05:00 - 19:00)
                            'activity' => 'Tắm biển buổi sáng',
                            'duration_min' => 90,
                        ],
                        [
                            'time' => '10:00',
                            'place_id' => 'dn_006', // Bảo tàng Điêu khắc Chăm (07:30 - 17:00)
                            'activity' => 'Tham quan hiện vật Chăm',
                            'duration_min' => 75,
                        ],
                        [
                            'time' => '12:00',
                            'place_id' => 'dn_007', // Chợ Cồn (07:00 - 19:00, lunch)
                            'activity' => 'Ăn trưa đặc sản',
                            'duration_min' => 60,
                        ],
                        [
                            'time' => '14:30',
                            'place_id' => 'dn_004', // Ngũ Hành Sơn (07:00 - 17:30)
                            'activity' => 'Leo núi và ngắm động Huyền Không',
                            'duration_min' => 90,
                        ],
                        [
                            'time' => '18:00',
                            'place_id' => 'dn_020', // Hải sản Bé Mặn (10:00 - 23:00, dinner)
                            'activity' => 'Ăn tối hải sản ven biển',
                            'duration_min' => 90,
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->validator->validate($plan, ['expected_days' => 1, 'month' => 4]);

        $this->assertTrue($result['valid'], 'Errors: '.implode('; ', $result['errors']));
        $this->assertEmpty($result['errors']);
        $this->assertEquals(5, $result['stats']['total_places']);
        $this->assertLessThanOrEqual(180, $result['stats']['total_travel_minutes']);
    }

    public function test_flags_unknown_or_unverified_place_id(): void
    {
        $plan = [
            'province_code' => 'danang',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        ['time' => '08:00', 'place_id' => 'fake_place_999'],
                        ['time' => '12:00', 'place_id' => 'dn_007'],
                        ['time' => '18:00', 'place_id' => 'dn_020'],
                    ],
                ],
            ],
        ];

        $result = $this->validator->validate($plan, ['expected_days' => 1]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('không tồn tại', implode(' ', $result['errors']));
    }

    public function test_flags_duplicate_place_id(): void
    {
        $plan = [
            'province_code' => 'danang',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        ['time' => '08:00', 'place_id' => 'dn_003'],
                        ['time' => '12:00', 'place_id' => 'dn_007'],
                        ['time' => '15:00', 'place_id' => 'dn_003'], // Duplicate
                    ],
                ],
            ],
        ];

        $result = $this->validator->validate($plan, ['expected_days' => 1]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('trùng lặp', implode(' ', $result['errors']));
    }

    public function test_flags_opening_hours_violation(): void
    {
        // Cơm gà Bà Buội dn_025 mở cửa từ 11:00 đến 20:30, xếp lịch lúc 07:30 sáng
        $plan = [
            'province_code' => 'danang',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        ['time' => '07:30', 'place_id' => 'dn_025', 'duration_min' => 60], // Opens at 11:00
                        ['time' => '12:00', 'place_id' => 'dn_007', 'duration_min' => 60],
                        ['time' => '18:00', 'place_id' => 'dn_020', 'duration_min' => 60],
                    ],
                ],
            ],
        ];

        $result = $this->validator->validate($plan, ['expected_days' => 1]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('ngoài giờ mở cửa', implode(' ', $result['errors']));
    }

    public function test_flags_avoid_months_violation(): void
    {
        // Bà Nà Hills dn_001 có avoid_months: [10, 11] (mùa mưa bão miền Trung)
        $plan = [
            'province_code' => 'danang',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        ['time' => '08:00', 'place_id' => 'dn_001', 'duration_min' => 240],
                        ['time' => '13:00', 'place_id' => 'dn_007', 'duration_min' => 60],
                        ['time' => '18:00', 'place_id' => 'dn_008', 'duration_min' => 60],
                    ],
                ],
            ],
        ];

        $result = $this->validator->validate($plan, ['expected_days' => 1, 'month' => 10]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('không thích hợp tham quan vào tháng 10', implode(' ', $result['errors']));
    }

    public function test_flags_heavy_rain_outdoor_safety_violation(): void
    {
        // Outdoor sight scheduled on a day with heavy rain forecast (precipitation >= 15mm)
        $plan = [
            'province_code' => 'danang',
            'days' => [
                [
                    'day' => 1,
                    'items' => [
                        ['time' => '08:00', 'place_id' => 'dn_003'], // Outdoor beach
                        ['time' => '12:00', 'place_id' => 'dn_007'],
                        ['time' => '18:00', 'place_id' => 'dn_008'],
                    ],
                ],
            ],
        ];

        $options = [
            'expected_days' => 1,
            'weather' => [
                'daily' => [
                    [
                        'day' => 1,
                        'precipitation_mm' => 35.0,
                        'wmo_code' => 65,
                    ],
                ],
            ],
        ];

        $result = $this->validator->validate($plan, $options);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('mưa lớn/bão', implode(' ', $result['errors']));
    }
}
