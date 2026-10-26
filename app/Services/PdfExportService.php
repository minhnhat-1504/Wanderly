<?php

namespace App\Services;

use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PdfExportService
{
    /**
     * Generate a comprehensive PDF itinerary document for the given trip.
     */
    public function generate(Trip $trip): DomPDF
    {
        $data = $this->collectTripData($trip);

        $pdf = Pdf::loadView('trips.pdf.export', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Collect all trip data needed for the PDF template.
     *
     * @return array<string, mixed>
     */
    protected function collectTripData(Trip $trip): array
    {
        // Load eager relations
        $trip->load([
            'owner',
            'members.user',
            'activities',
            'roomBookings',
        ]);

        if (Schema::hasTable('checklist_items')) {
            $trip->load('checklistItems');
        }

        // Organize activities by day and time_slot
        $activityDays = $this->organizeActivitiesByDay($trip);

        // Budget summary
        $totalSpent = $trip->activities->sum('cost') ?? 0;
        $roomCost = $trip->roomBookings
            ->where('include_in_budget', true)
            ->sum('price_per_night') ?? 0;
        $totalCost = $totalSpent + $roomCost;
        $remaining = (float) $trip->budget - $totalCost;

        // Trip duration
        $startDate = Carbon::parse($trip->start_date);
        $endDate = Carbon::parse($trip->end_date);
        $tripDays = $startDate->diffInDays($endDate) + 1;

        return [
            'trip' => $trip,
            'activityDays' => $activityDays,
            'tripDays' => $tripDays,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalBudget' => (float) $trip->budget,
            'totalCost' => $totalCost,
            'remaining' => $remaining,
            'budgetPercentage' => $trip->budget > 0 ? min(100, round(($totalCost / $trip->budget) * 100)) : 0,
            'members' => $trip->members->load('user'),
            'checklistItems' => Schema::hasTable('checklist_items') ? $trip->checklistItems ?? collect() : collect(),
            'roomBookings' => $trip->roomBookings,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Organize activities into a structured [day => [slot => [activities]]] array.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function organizeActivitiesByDay(Trip $trip): array
    {
        $startDate = Carbon::parse($trip->start_date);
        $endDate = Carbon::parse($trip->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $days = [];
        $timeSlots = ['morning', 'midday', 'afternoon', 'evening'];

        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $startDate->copy()->addDays($day - 1);
            $dayActivities = $trip->activities->where('day', $day)->sortBy('position');

            $slots = [];
            foreach ($timeSlots as $slot) {
                $slots[$slot] = $dayActivities->where('time_slot', $slot)->values();
            }

            $days[$day] = [
                'date' => $date,
                'label' => __('Day :n — :date', ['n' => $day, 'date' => $date->format('D, d M Y')]),
                'slots' => $slots,
                'total_cost' => $dayActivities->sum('cost'),
            ];
        }

        return $days;
    }
}
