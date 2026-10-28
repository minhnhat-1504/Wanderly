<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Destination;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Provider;
use App\Models\RoomBooking;
use App\Models\Service;
use App\Models\TravelRequest;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TripDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@wanderly.test')->first() ?? User::factory()->create([
            'name' => 'Demo Traveler',
            'email' => 'demo@wanderly.test',
            'role' => 'traveler',
        ]);

        $startDate = Carbon::today()->addDays(5)->startOfDay();
        $endDate = (clone $startDate)->addDays(3)->endOfDay(); // 4 days, 3 nights

        // 1. Create Demo Trip
        $trip = Trip::updateOrCreate(
            ['name' => '4 Days in Da Lat Pine Paradise'],
            [
                'owner_id' => $demoUser->id,
                'destination' => 'Da Lat',
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'budget' => 10000000.00, // 10,000,000 VND
                'privacy' => 'public',
                'status' => 'planning',
                'share_slug' => 'demo-dalat-4n3d',
                'is_shared' => true,
                'is_share_budget_visible' => true,
            ]
        );

        // 2. Assign leader membership
        TripMember::updateOrCreate(
            ['trip_id' => $trip->id, 'user_id' => $demoUser->id],
            ['role' => 'leader']
        );

        // 3. Clear existing sub-items to prevent duplicate on re-run
        $trip->activities()->delete();
        $trip->roomBookings()->delete();
        $trip->checklistItems()->delete();

        // 4. Create Hotel Room Booking (2,500,000 VND)
        RoomBooking::create([
            'trip_id' => $trip->id,
            'hotel_name' => 'Terracotta Resort & Spa Da Lat',
            'room_type' => 'Lake View Deluxe King Room',
            'check_in' => $startDate->format('Y-m-d'),
            'check_out' => $endDate->format('Y-m-d'),
            'price' => 2500000.00,
            'address' => 'Zone 7.9, Tuyen Lam Lake Tourism Area, Da Lat',
            'notes' => 'Surrounded by pine trees and lakeside tranquility.',
            'is_budget_included' => true,
        ]);

        // 5. Create 8 Activities across 4 days (Total cost: 5,700,000 VND)
        // Total Spent = 2,500,000 + 5,700,000 = 8,200,000 VND (82% - Amber warning threshold!)
        $activities = [
            // Day 1
            [
                'day' => 1,
                'time_slot' => 'midday',
                'start_time' => '11:30',
                'end_time' => '13:00',
                'title' => 'Highland Grilled Chicken & Bamboo Sticky Rice',
                'cost' => 600000.00,
                'category' => 'dining',
                'location' => 'An Thuyen Restaurant',
                'address' => 'Tuyen Lam Lake Area, Da Lat',
                'notes' => 'Signature local grilled chicken specialty.',
                'position' => 1,
            ],
            [
                'day' => 1,
                'time_slot' => 'afternoon',
                'start_time' => '14:30',
                'end_time' => '16:30',
                'title' => 'Truc Lam Zen Monastery & Cable Car Sightseeing',
                'cost' => 250000.00,
                'category' => 'sightseeing',
                'location' => 'Robin Hill Cable Car Station',
                'address' => 'Hoa Cam Tu Cau Street, Ward 3, Da Lat',
                'notes' => 'Panoramic view of the city and pine hills.',
                'position' => 1,
            ],
            // Day 2
            [
                'day' => 2,
                'time_slot' => 'morning',
                'start_time' => '08:00',
                'end_time' => '11:00',
                'title' => 'Alpine Coaster Adventure at Datanla Falls',
                'cost' => 950000.00,
                'category' => 'entertainment',
                'location' => 'Datanla Waterfalls Complex',
                'address' => 'Prenn Pass, Ward 3, Da Lat',
                'notes' => 'Asia longest alpine coaster roller trail.',
                'position' => 1,
            ],
            [
                'day' => 2,
                'time_slot' => 'evening',
                'start_time' => '18:30',
                'end_time' => '21:00',
                'title' => 'Da Lat Night Market Street Food Tour',
                'cost' => 700000.00,
                'category' => 'dining',
                'location' => 'Da Lat Night Market',
                'address' => 'Nguyen Thi Minh Khai Street, Ward 1, Da Lat',
                'notes' => 'Vietnamese pizza (Banh Trang Nuong), hot soy milk & grilled skewers.',
                'position' => 1,
            ],
            // Day 3
            [
                'day' => 3,
                'time_slot' => 'morning',
                'start_time' => '06:00',
                'end_time' => '09:30',
                'title' => 'Sunrise Cloud Hunting at Cau Dat Tea Hills',
                'cost' => 800000.00,
                'category' => 'sightseeing',
                'location' => 'Cau Dat Tea Farm',
                'address' => 'National Route 20, Xuan Truong, Da Lat',
                'notes' => 'Panoramic sunrise above the sea of highland fog.',
                'position' => 1,
            ],
            [
                'day' => 3,
                'time_slot' => 'evening',
                'start_time' => '19:00',
                'end_time' => '21:30',
                'title' => 'Cozy Evening & Warm Coffee at Tung Cafe',
                'cost' => 400000.00,
                'category' => 'cafe',
                'location' => 'Tung Cafe',
                'address' => '6 Hoa Binh Area, Ward 1, Da Lat',
                'notes' => 'Historic cafe playing gentle classical music.',
                'position' => 1,
            ],
            // Day 4
            [
                'day' => 4,
                'time_slot' => 'morning',
                'start_time' => '09:00',
                'end_time' => '11:00',
                'title' => 'Artichoke & Berry Specialties Shopping at L\'angfarm',
                'cost' => 1500000.00,
                'category' => 'shopping',
                'location' => 'L\'angfarm Store',
                'address' => 'Hoa Binh Center, Da Lat',
                'notes' => 'Artichoke tea, dried persimmons and strawberry jams.',
                'position' => 1,
            ],
            [
                'day' => 4,
                'time_slot' => 'afternoon',
                'start_time' => '14:00',
                'end_time' => '15:30',
                'title' => 'Lien Khuong Airport Shuttle Express',
                'cost' => 500000.00,
                'category' => 'other',
                'location' => 'Da Lat Highway Station',
                'address' => 'Duc Trong District, Lam Dong',
                'notes' => 'Private airport transit shuttle.',
                'position' => 1,
            ],
        ];

        foreach ($activities as $act) {
            Activity::create(array_merge($act, [
                'trip_id' => $trip->id,
                'type' => 'activity',
            ]));
        }

        // 6. Create 12 Checklist Items (6 completed = 50% progress)
        $checklist = [
            ['title' => 'ID Card & Driver License', 'category' => 'documents', 'is_completed' => true],
            ['title' => 'Flight & Resort Booking Confirmations', 'category' => 'documents', 'is_completed' => true],
            ['title' => 'Warm Windbreaker & Fleece Jacket', 'category' => 'clothing', 'is_completed' => true],
            ['title' => 'Comfortable Trekking Sneakers', 'category' => 'clothing', 'is_completed' => true],
            ['title' => 'Insect Repellent Spray', 'category' => 'health', 'is_completed' => true],
            ['title' => 'Power Bank 20.000mAh', 'category' => 'electronics', 'is_completed' => true],
            ['title' => 'Camera & Spare Memory Card', 'category' => 'electronics', 'is_completed' => false],
            ['title' => 'Lip Balm & Moisturizing Sunscreen', 'category' => 'personal_care', 'is_completed' => false],
            ['title' => 'Mini Travel Umbrella', 'category' => 'gear', 'is_completed' => false],
            ['title' => 'Thermal Insulated Water Flask', 'category' => 'gear', 'is_completed' => false],
            ['title' => 'Cash for Night Market Food Stalls', 'category' => 'documents', 'is_completed' => false],
            ['title' => 'Personal First Aid Kit & Motion Sickness Pills', 'category' => 'health', 'is_completed' => false],
        ];

        foreach ($checklist as $index => $item) {
            ChecklistItem::create(array_merge($item, [
                'trip_id' => $trip->id,
                'position' => $index + 1,
            ]));
        }

        // 7. Create Demo Provider & Catalog Services (Sprint 3 Reverse Marketplace Demo)
        if (class_exists(Provider::class) && Schema::hasTable('providers')) {
            $providerUser = User::firstOrCreate(
                ['email' => 'provider@wanderly.test'],
                [
                    'name' => 'Dalat Highland Drivers & Tours',
                    'username' => 'dalat_explorer',
                    'password' => bcrypt('password'),
                    'role' => 'provider',
                    'email_verified_at' => now(),
                ]
            );

            $dalat = Destination::where('name', 'LIKE', '%Da Lat%')->first() ?? Destination::first();

            if ($dalat) {
                $provider = Provider::updateOrCreate(
                    ['user_id' => $providerUser->id],
                    [
                        'business_name' => 'Dalat Pine Explorer Travel',
                        'service_type' => 'transport',
                        'destination_id' => $dalat->id,
                        'bio' => 'Professional 7-seater private transfers and curated countryside excursions in Da Lat.',
                        'rating' => 4.9,
                        'phone' => '+84 909 888 777',
                        'address' => '12 Phan Dinh Phung, Ward 2, Da Lat',
                        'is_verified' => true,
                    ]
                );

                if (Schema::hasTable('services')) {
                    Service::updateOrCreate(
                        ['provider_id' => $provider->id, 'name' => 'Private 7-Seater Airport & City Transfer (2 Days)'],
                        [
                            'price' => 1800000.00,
                            'tags' => ['transport', 'car', 'private'],
                            'specs' => ['seats' => 7, 'ac' => true, 'driver' => 'English-speaking', 'weekday_price' => 1500000.00],
                        ]
                    );

                    Service::updateOrCreate(
                        ['provider_id' => $provider->id, 'name' => 'Countryside Pine Forest & Coffee Farm Excursion'],
                        [
                            'price' => 900000.00,
                            'tags' => ['tour', 'sightseeing', 'coffee'],
                            'specs' => ['duration' => 'full-day', 'lunch_included' => true],
                        ]
                    );
                }

                // Create a sample open Travel Request with a Proposal ready to compare
                if (Schema::hasTable('travel_requests')) {
                    $travelRequest = TravelRequest::updateOrCreate(
                        ['trip_id' => $trip->id, 'pace' => 'balanced'],
                        [
                            'needs' => ['transport', 'tour'],
                            'max_budget' => 3500000.00,
                            'status' => 'bidding',
                            'notes' => 'Looking for comfortable private transportation and an authentic coffee plantation day tour.',
                        ]
                    );

                    if (Schema::hasTable('proposals')) {
                        $proposal = Proposal::updateOrCreate(
                            ['request_id' => $travelRequest->id, 'provider_id' => $provider->id],
                            [
                                'total_price' => 2500000.00,
                                'message' => 'Hello! We offer a full 2-day private 7-seater car package and a curated farm tour at a 28% discount for your team.',
                                'valid_until' => Carbon::now()->addDays(7),
                                'status' => 'pending',
                            ]
                        );

                        if (Schema::hasTable('proposal_items')) {
                            ProposalItem::updateOrCreate(
                                ['proposal_id' => $proposal->id, 'name' => 'Private 7-Seater 2-Day Shuttle'],
                                [
                                    'price' => 1600000.00,
                                    'specs' => ['seats' => 7, 'driver' => 'Included'],
                                    'is_accepted' => false,
                                ]
                            );

                            ProposalItem::updateOrCreate(
                                ['proposal_id' => $proposal->id, 'name' => 'Specialty Coffee Tasting & Farm Experience'],
                                [
                                    'price' => 900000.00,
                                    'specs' => ['duration' => 'half-day'],
                                    'is_accepted' => false,
                                ]
                            );
                        }
                    }
                }
            }
        }

        // ══════════════════════════════════════════════════════════════════════
        // 8. Demo Social Posts & In-App Notifications (Sprint 3 - Member 4)
        // ══════════════════════════════════════════════════════════════════════
        if (Schema::hasTable('posts')) {
            $post = Post::updateOrCreate(
                ['title' => 'Our 4-day Da Lat Pine Paradise Itinerary!'],
                [
                    'user_id' => $demoUser->id,
                    'trip_id' => $trip->id,
                    'content' => 'Sharing our complete 4-day, 3-night itinerary in Da Lat! We visited Tuyen Lam Lake, Truc Lam Zen Monastery, and enjoyed specialty coffee in the misty mornings. Feel free to clone this trip to your own account and customize it for your travel buddies!',
                    'likes_count' => 5,
                    'comments_count' => 2,
                ]
            );

            // Add demo comments if comments table exists
            if (Schema::hasTable('comments')) {
                Comment::firstOrCreate(
                    ['post_id' => $post->id, 'content' => 'Such a well-structured plan! How was the lake view at Terracotta?'],
                    ['user_id' => $demoUser->id]
                );

                if (isset($providerUser)) {
                    Comment::firstOrCreate(
                        ['post_id' => $post->id, 'content' => 'Great itinerary! Let us know if you need any extra shuttle services.'],
                        ['user_id' => $providerUser->id]
                    );
                }
            }

            // Seed second community post
            $communityUser = User::where('email', 'admin@wanderly.test')->first() ?? $demoUser;
            Post::updateOrCreate(
                ['title' => 'Top 5 Hidden Cafes in Da Lat for Remote Work & Chill'],
                [
                    'user_id' => $communityUser->id,
                    'trip_id' => null,
                    'content' => 'Da Lat is famous for its chill coffee shops nestled in pine forests. Here are my favorite 5 spots with strong wifi, quiet spaces, and stunning valley views: 1. Cheo Veooo, 2. Thong Oi, 3. In the Forest, 4. Bo Ho Cafe, 5. Tieng To Dong.',
                    'likes_count' => 12,
                    'comments_count' => 4,
                ]
            );
        }

        // Seed In-App Notifications
        if (Schema::hasTable('notifications')) {
            Notification::firstOrCreate(
                [
                    'user_id' => $demoUser->id,
                    'title' => 'New Proposal Received',
                ],
                [
                    'type' => 'proposal',
                    'message' => 'Da Lat Travel Services submitted a competitive proposal for your travel request.',
                    'action_url' => url('/trips/'.$trip->id.'/marketplace'),
                    'read_at' => null,
                ]
            );

            Notification::firstOrCreate(
                [
                    'user_id' => $demoUser->id,
                    'title' => 'Budget Alert',
                ],
                [
                    'type' => 'budget',
                    'message' => 'Your trip "4 Days in Da Lat Pine Paradise" is currently within 65% of allocated budget.',
                    'action_url' => url('/trips/'.$trip->id),
                    'read_at' => now()->subHours(2),
                ]
            );

            Notification::firstOrCreate(
                [
                    'user_id' => $demoUser->id,
                    'title' => 'Welcome to Wanderly Community!',
                ],
                [
                    'type' => 'system',
                    'message' => 'Explore public itineraries and share your travel experiences on the Community Feed.',
                    'action_url' => url('/social'),
                    'read_at' => null,
                ]
            );
        }
    }
}
