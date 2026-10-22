<?php

namespace App\Services;

use App\Models\ChecklistItem;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

class ChecklistService
{
    /**
     * Get all checklist items for a trip.
     *
     * @return Collection<int, ChecklistItem>
     */
    public function getItems(Trip $trip): Collection
    {
        return $trip->checklistItems()->orderBy('position')->get();
    }

    /**
     * Create a new checklist item.
     *
     * @param  array<string, mixed>  $data
     */
    public function createItem(Trip $trip, array $data): ChecklistItem
    {
        $maxPosition = (int) $trip->checklistItems()->max('position');

        return $trip->checklistItems()->create([
            'title' => $data['title'],
            'category' => $data['category'] ?? 'general',
            'is_completed' => false,
            'position' => $maxPosition + 1,
        ]);
    }

    /**
     * Update an existing checklist item.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(ChecklistItem $item, array $data): ChecklistItem
    {
        $item->update($data);

        return $item;
    }

    /**
     * Delete a checklist item.
     */
    public function deleteItem(ChecklistItem $item): bool
    {
        return (bool) $item->delete();
    }

    /**
     * Toggle completion status of a checklist item.
     */
    public function toggleItem(ChecklistItem $item): ChecklistItem
    {
        $item->is_completed = ! $item->is_completed;
        $item->save();

        return $item;
    }

    /**
     * Calculate completion progress for a trip's checklist.
     *
     * @return array{total: int, completed: int, percentage: int}
     */
    public function calculateProgress(Trip $trip): array
    {
        $total = $trip->checklistItems()->count();
        $completed = $trip->checklistItems()->where('is_completed', true)->count();
        $percentage = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $percentage,
        ];
    }

    /**
     * Apply a preset checklist template based on destination type.
     *
     * @return Collection<int, ChecklistItem>
     */
    public function applyTemplate(Trip $trip, string $templateType): Collection
    {
        $templates = [
            'beach' => [
                ['title' => 'Swimwear & Beach Towel', 'category' => 'clothing'],
                ['title' => 'Sunscreen SPF 50+', 'category' => 'personal_care'],
                ['title' => 'UV Sunglasses', 'category' => 'accessories'],
                ['title' => 'Waterproof Phone Pouch', 'category' => 'electronics'],
                ['title' => 'Wide-brim Beach Hat', 'category' => 'clothing'],
                ['title' => 'Flip Flops / Sand Sandals', 'category' => 'clothing'],
                ['title' => 'Snorkeling Mask', 'category' => 'gear'],
                ['title' => 'Aloe Vera Soothing Gel', 'category' => 'personal_care'],
            ],
            'mountain' => [
                ['title' => 'Warm Windbreaker Jacket', 'category' => 'clothing'],
                ['title' => 'Trekking & Hiking Shoes', 'category' => 'clothing'],
                ['title' => 'Raincoat / Poncho', 'category' => 'gear'],
                ['title' => 'Insect Repellent Spray', 'category' => 'health'],
                ['title' => 'Flashlight / Headlamp', 'category' => 'gear'],
                ['title' => 'Mini First Aid Kit', 'category' => 'health'],
                ['title' => 'Insulated Water Bottle', 'category' => 'gear'],
                ['title' => 'Thermal Socks & Gloves', 'category' => 'clothing'],
            ],
            'city' => [
                ['title' => 'Comfortable Walking Sneakers', 'category' => 'clothing'],
                ['title' => 'Power Bank & Charging Cables', 'category' => 'electronics'],
                ['title' => 'Compact Folding Umbrella', 'category' => 'accessories'],
                ['title' => 'ID Card / Passport & Copies', 'category' => 'documents'],
                ['title' => 'Hand Sanitizer & Masks', 'category' => 'health'],
                ['title' => 'Daypack Backpack', 'category' => 'gear'],
                ['title' => 'Local Cash & Credit Cards', 'category' => 'documents'],
                ['title' => 'Noise-canceling Earphones', 'category' => 'electronics'],
            ],
        ];

        $itemsToInsert = $templates[$templateType] ?? $templates['city'];
        $maxPosition = (int) $trip->checklistItems()->max('position');

        foreach ($itemsToInsert as $index => $itemData) {
            $trip->checklistItems()->create([
                'title' => $itemData['title'],
                'category' => $itemData['category'],
                'is_completed' => false,
                'position' => $maxPosition + $index + 1,
            ]);
        }

        return $trip->checklistItems()->orderBy('position')->get();
    }
}
