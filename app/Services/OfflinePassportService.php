<?php

namespace App\Services;

use App\Models\Trip;
use Carbon\Carbon;

class OfflinePassportService
{
    /**
     * Generate an offline snapshot data bundle for the given trip.
     * Contains compact itinerary, static QR voucher payloads, emergency hotlines, and checklist items.
     *
     * @return array<string, mixed>
     */
    public function generateOfflineSnapshot(Trip $trip): array
    {
        $trip->loadMissing([
            'owner',
            'members.user',
            'activities',
            'bookings.provider',
            'checklistItems',
        ]);

        $startDate = $trip->start_date ? Carbon::parse($trip->start_date) : Carbon::today();
        $endDate = $trip->end_date ? Carbon::parse($trip->end_date) : $startDate->copy();
        $durationDays = max(1, $startDate->diffInDays($endDate) + 1);

        // Build days timeline
        $timeline = [];
        $activitiesByDay = $trip->activities->groupBy('day');

        for ($day = 1; $day <= $durationDays; $day++) {
            $currentDate = $startDate->copy()->addDays($day - 1);
            $dayActivities = $activitiesByDay->get($day, collect())->sortBy('position');

            $activitiesData = $dayActivities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'time_slot' => $activity->time_slot,
                    'time_slot_label' => ucfirst($activity->time_slot ?? 'morning'),
                    'cost' => (float) $activity->cost,
                    'cost_formatted' => number_format((float) $activity->cost, 0, ',', '.').' ₫',
                    'address' => $activity->address ?: $activity->location,
                    'notes' => $activity->notes,
                    'is_completed' => (bool) ($activity->is_completed ?? false),
                ];
            })->values()->all();

            $timeline[] = [
                'day' => $day,
                'date' => $currentDate->format('Y-m-d'),
                'date_formatted' => $currentDate->format('D, M j, Y'),
                'activities_count' => count($activitiesData),
                'activities' => $activitiesData,
            ];
        }

        // Build vouchers list with static QR data
        $vouchers = $trip->bookings->map(function ($booking) use ($trip) {
            $voucherCode = $booking->voucher_code ?: sprintf('WND-%d-%04d', $trip->id, $booking->id);
            $qrData = sprintf('WND:VOUCHER:%s:TRIP:%d:PRICE:%.0f', $voucherCode, $trip->id, (float) $booking->price);

            return [
                'id' => $booking->id,
                'voucher_code' => $voucherCode,
                'item_name' => $booking->item_name,
                'provider_name' => $booking->provider?->business_name ?? 'Local Verified Partner',
                'provider_phone' => $booking->provider?->phone ?? 'Hotline available on booking',
                'provider_address' => $booking->provider?->address ?? $trip->destination,
                'price' => (float) $booking->price,
                'price_formatted' => number_format((float) $booking->price, 0, ',', '.').' ₫',
                'status' => $booking->status ?? 'confirmed',
                'notes' => $booking->notes,
                'qr_payload' => $qrData,
                'qr_image_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data='.urlencode($qrData),
            ];
        })->values()->all();

        // Build unique provider hotlines
        $providerHotlines = [];
        foreach ($trip->bookings as $booking) {
            if ($booking->provider && $booking->provider->phone) {
                $providerHotlines[$booking->provider->business_name] = [
                    'name' => $booking->provider->business_name,
                    'phone' => $booking->provider->phone,
                    'service' => $booking->provider->service_type ?? 'Service Partner',
                    'address' => $booking->provider->address,
                ];
            }
        }

        // Emergency hotlines
        $emergencyContacts = [
            'national' => [
                ['name' => 'National Police (Cảnh sát)', 'number' => '113', 'type' => 'police'],
                ['name' => 'Fire & Rescue (Cứu hỏa)', 'number' => '114', 'type' => 'fire'],
                ['name' => 'Ambulance (Cấp cứu y tế)', 'number' => '115', 'type' => 'medical'],
                ['name' => 'Search & Rescue (Cứu nạn quốc gia)', 'number' => '112', 'type' => 'rescue'],
                ['name' => 'Vietnam Tourism Hotline', 'number' => '1800599920', 'type' => 'tourism'],
            ],
            'leader' => [
                'name' => $trip->owner?->name ?? 'Trip Leader',
                'email' => $trip->owner?->email ?? null,
                'phone' => $trip->owner?->phone ?? 'Not provided',
            ],
            'providers' => array_values($providerHotlines),
        ];

        // Packing Checklist
        $checklist = $trip->checklistItems->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'is_completed' => (bool) ($item->is_completed ?? false),
                'category' => $item->category ?? 'General',
            ];
        })->values()->all();

        return [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'destination' => $trip->destination,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'date_range_formatted' => $startDate->format('M j').' – '.$endDate->format('M j, Y'),
                'duration_days' => $durationDays,
                'budget' => (float) $trip->budget,
                'budget_formatted' => number_format((float) $trip->budget, 0, ',', '.').' ₫',
                'status' => $trip->status ?? 'active',
                'cover_image' => $trip->cover,
            ],
            'timeline' => $timeline,
            'vouchers' => $vouchers,
            'emergency' => $emergencyContacts,
            'checklist' => $checklist,
            'generated_at' => now()->toIso8601String(),
            'generated_at_formatted' => now()->format('D, M j, Y H:i:s'),
        ];
    }
}
