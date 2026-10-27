<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Message;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProviderPortalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('providers') || ! Schema::hasTable('messages')) {
            return;
        }

        $provider = Provider::with('user')->first();
        $trip = Trip::with(['owner', 'members.user', 'bookings.provider'])->first();

        if (! $provider || ! $trip) {
            return;
        }

        // 1. Seed blocked schedule dates for demo provider
        $tomorrow = Carbon::tomorrow();
        ProviderSchedule::updateOrCreate(
            ['provider_id' => $provider->id, 'blocked_date' => $tomorrow->toDateString()],
            ['reason' => 'Vehicle routine maintenance & fleet inspection']
        );

        $nextWeek = Carbon::today()->addDays(8);
        ProviderSchedule::updateOrCreate(
            ['provider_id' => $provider->id, 'blocked_date' => $nextWeek->toDateString()],
            ['reason' => 'Annual company training']
        );

        // 2. Seed Trip Group Chat Messages
        $leader = $trip->owner;
        $editorUser = User::where('email', 'admin@wanderly.test')->first() ?? $leader;

        Message::firstOrCreate(
            [
                'trip_id' => $trip->id,
                'booking_id' => null,
                'content' => 'Hey everyone! Excited for our upcoming Da Lat adventure. Please check the itinerary and suggest any extra stops.',
            ],
            [
                'sender_id' => $leader->id,
                'receiver_id' => null,
                'attachment' => null,
                'is_read' => true,
                'created_at' => Carbon::now()->subHours(5),
            ]
        );

        Message::firstOrCreate(
            [
                'trip_id' => $trip->id,
                'booking_id' => null,
                'content' => 'Looks amazing! I voted +1 for Tuyen Lam Lake and the Coffee Farm tour. Don\'t forget warm jackets for misty evenings!',
            ],
            [
                'sender_id' => $editorUser->id,
                'receiver_id' => null,
                'attachment' => null,
                'is_read' => true,
                'created_at' => Carbon::now()->subHours(3),
            ]
        );

        // 3. Seed Direct 1-1 Booking Chat Messages (if a booking exists)
        $booking = $trip->bookings()->first();

        if ($booking && $booking->provider) {
            $providerUser = $booking->provider->user;

            Message::firstOrCreate(
                [
                    'trip_id' => $trip->id,
                    'booking_id' => $booking->id,
                    'content' => 'Hello! We have confirmed your shuttle reservation (Voucher: '.$booking->voucher_code.'). Please let us know your flight arrival time at Lien Khuong Airport.',
                ],
                [
                    'sender_id' => $providerUser->id,
                    'receiver_id' => $leader->id,
                    'attachment' => null,
                    'is_read' => true,
                    'created_at' => Carbon::now()->subHours(2),
                ]
            );

            Message::firstOrCreate(
                [
                    'trip_id' => $trip->id,
                    'booking_id' => $booking->id,
                    'content' => 'Hi! Our flight touches down at 10:15 AM. We will have 4 carry-on suitcases and 2 large bags.',
                ],
                [
                    'sender_id' => $leader->id,
                    'receiver_id' => $providerUser->id,
                    'attachment' => null,
                    'is_read' => false,
                    'created_at' => Carbon::now()->subMinutes(45),
                ]
            );
        }
    }
}
