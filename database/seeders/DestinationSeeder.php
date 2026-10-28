<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Poi;
use App\Models\Zone;
use App\Support\VietnamProvinces;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $destinationsData = [
            [
                'name' => 'Da Lat',
                'description' => 'The City of Eternal Spring known for pine forests, cool highland climate, and romantic coffee shops.',
                'zones' => [
                    [
                        'name' => 'City Center (Trung tam Pho)',
                        'pois' => [
                            [
                                'name' => 'Lam Vien Square',
                                'address' => 'Tran Quoc Toan Street, Ward 1, Da Lat',
                                'category' => 'Sightseeing',
                                'typical_cost' => 0.00,
                            ],
                            [
                                'name' => 'Da Lat Night Market',
                                'address' => 'Nguyen Thi Minh Khai Street, Ward 1, Da Lat',
                                'category' => 'Dining',
                                'typical_cost' => 100000.00,
                            ],
                            [
                                'name' => 'Coi Xay Gio Bakery',
                                'address' => '19 Hoa Binh Area, Ward 1, Da Lat',
                                'category' => 'Dining',
                                'typical_cost' => 35000.00,
                            ],
                            [
                                'name' => 'Tung Cafe',
                                'address' => '6 Hoa Binh Area, Ward 1, Da Lat',
                                'category' => 'Cafe',
                                'typical_cost' => 40000.00,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Tuyen Lam Lake Area (Khu Ho Tuyen Lam)',
                        'pois' => [
                            [
                                'name' => 'Truc Lam Zen Monastery',
                                'address' => 'Hoa Cam Tu Cau Street, Ward 3, Da Lat',
                                'category' => 'Sightseeing',
                                'typical_cost' => 0.00,
                            ],
                            [
                                'name' => 'Da Lat Clay Tunnel',
                                'address' => 'Ward 4, Da Lat',
                                'category' => 'Sightseeing',
                                'typical_cost' => 120000.00,
                            ],
                            [
                                'name' => 'Tui Mo To Coffee Shop',
                                'address' => 'Alley 31 Sao Nam, Ward 11, Da Lat',
                                'category' => 'Cafe',
                                'typical_cost' => 65000.00,
                            ],
                            [
                                'name' => 'Rung Thong Mo Farm & Bistro',
                                'address' => 'Hoa Hong Street, Ward 4, Da Lat',
                                'category' => 'Dining',
                                'typical_cost' => 250000.00,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Da Nang',
                'description' => 'A vibrant coastal city famous for long sandy beaches, Dragon Bridge, and fresh seafood.',
                'zones' => [
                    [
                        'name' => 'My Khe Beach Area (Khu Bai bien My Khe)',
                        'pois' => [
                            [
                                'name' => 'My Khe Beach',
                                'address' => 'Vo Nguyen Giap Street, Phuoc My, Da Nang',
                                'category' => 'Sightseeing',
                                'typical_cost' => 0.00,
                            ],
                            [
                                'name' => 'Be Man Seafood Restaurant',
                                'address' => 'Lot 11 Vo Nguyen Giap Street, Man Thai, Da Nang',
                                'category' => 'Dining',
                                'typical_cost' => 300000.00,
                            ],
                            [
                                'name' => 'The Espresso Station Da Nang',
                                'address' => '12 An Thuong 2, My An, Da Nang',
                                'category' => 'Cafe',
                                'typical_cost' => 55000.00,
                            ],
                            [
                                'name' => 'Son Tra Night Market',
                                'address' => 'Mai Hac De Street, An Hai Trung, Da Nang',
                                'category' => 'Dining',
                                'typical_cost' => 80000.00,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Son Tra Peninsula & Marble Mountains',
                        'pois' => [
                            [
                                'name' => 'Linh Ung Pagoda Bai But',
                                'address' => 'Son Tra Peninsula, Tho Quang, Da Nang',
                                'category' => 'Sightseeing',
                                'typical_cost' => 0.00,
                            ],
                            [
                                'name' => 'Ban Co Peak (Chessboard Peak)',
                                'address' => 'Tho Quang, Son Tra, Da Nang',
                                'category' => 'Sightseeing',
                                'typical_cost' => 0.00,
                            ],
                            [
                                'name' => 'Marble Mountains Scenic Spot',
                                'address' => '81 Huyen Tran Cong Chua, Hoa Hai, Da Nang',
                                'category' => 'Sightseeing',
                                'typical_cost' => 40000.00,
                            ],
                            [
                                'name' => 'Banh Xeo Ba Duong',
                                'address' => 'K280/23 Hoang Dieu, Binh Hien, Da Nang',
                                'category' => 'Dining',
                                'typical_cost' => 70000.00,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Phu Quoc',
                'description' => 'The Pearl Island offering tropical waters, pristine sunsets, and delicious local cuisine.',
                'zones' => [
                    [
                        'name' => 'Duong Dong Town (Thi tran Duong Dong)',
                        'pois' => [
                            [
                                'name' => 'Phu Quoc Night Market',
                                'address' => '6 Bach Dang Street, Zone 1, Duong Dong, Phu Quoc',
                                'category' => 'Dining',
                                'typical_cost' => 150000.00,
                            ],
                            [
                                'name' => 'Dinh Cau Temple',
                                'address' => 'Zone 2, Duong Dong, Phu Quoc',
                                'category' => 'Sightseeing',
                                'typical_cost' => 0.00,
                            ],
                            [
                                'name' => 'Kien Xay Stirred Noodle (Bun Quay)',
                                'address' => '28 Bach Dang Street, Duong Dong, Phu Quoc',
                                'category' => 'Dining',
                                'typical_cost' => 65000.00,
                            ],
                            [
                                'name' => 'Chuon Chuon Bistro & Skybar',
                                'address' => 'Sao Mai Hill, 69 Tran Hung Dao, Phu Quoc',
                                'category' => 'Cafe',
                                'typical_cost' => 95000.00,
                            ],
                        ],
                    ],
                    [
                        'name' => 'South Island & An Thoi (Nam Dao - An Thoi)',
                        'pois' => [
                            [
                                'name' => 'Sao Beach (Bai Sao)',
                                'address' => 'Hamlet 4, An Thoi, Phu Quoc',
                                'category' => 'Sightseeing',
                                'typical_cost' => 50000.00,
                            ],
                            [
                                'name' => 'Hon Thom Cable Car Sun World',
                                'address' => 'Bai Dat Do, An Thoi, Phu Quoc',
                                'category' => 'Sightseeing',
                                'typical_cost' => 600000.00,
                            ],
                            [
                                'name' => 'Phu Quoc Prison Historical Monument',
                                'address' => '350 Nguyen Van Cu Street, An Thoi, Phu Quoc',
                                'category' => 'Sightseeing',
                                'typical_cost' => 0.00,
                            ],
                            [
                                'name' => 'Ham Ninh Fishing Village Seafood',
                                'address' => 'Ham Ninh Pier, Ham Ninh, Phu Quoc',
                                'category' => 'Dining',
                                'typical_cost' => 250000.00,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($destinationsData as $destData) {
            $destination = Destination::updateOrCreate(
                ['name' => $destData['name']],
                ['description' => $destData['description']]
            );

            foreach ($destData['zones'] as $zoneData) {
                $zone = Zone::updateOrCreate(
                    [
                        'destination_id' => $destination->id,
                        'name' => $zoneData['name'],
                    ]
                );

                foreach ($zoneData['pois'] as $poiData) {
                    Poi::updateOrCreate(
                        [
                            'zone_id' => $zone->id,
                            'name' => $poiData['name'],
                        ],
                        [
                            'address' => $poiData['address'],
                            'category' => $poiData['category'],
                            'typical_cost' => $poiData['typical_cost'],
                        ]
                    );
                }
            }
        }

        // Seed all 34 official administrative provinces of Vietnam
        foreach (VietnamProvinces::PROVINCES as $province) {
            Destination::updateOrCreate(
                ['name' => $province['name']],
                [
                    'description' => $province['note'].' ('.$province['region'].')',
                ]
            );
        }
    }
}
