@props([
    'activity',
    'canEdit' => true,
])

@php
    $id = is_object($activity) ? $activity->id : $activity['id'];
    $day = is_object($activity) ? ($activity->day ?? 1) : ($activity['day'] ?? 1);
    $slot = is_object($activity) ? ($activity->time_slot ?? 'morning') : ($activity['time_slot'] ?? 'morning');
    $title = is_object($activity) ? $activity->title : $activity['title'];
    $cost = (float) (is_object($activity) ? ($activity->cost ?? 0) : ($activity['cost'] ?? 0));
    $category = is_object($activity) ? ($activity->category ?? 'other') : ($activity['category'] ?? 'other');
    $location = is_object($activity) ? ($activity->location ?? '') : ($activity['location'] ?? '');
    $address = is_object($activity) ? ($activity->address ?? '') : ($activity['address'] ?? '');
    $startTime = is_object($activity) ? ($activity->start_time ?? null) : ($activity['start_time'] ?? null);
    $endTime = is_object($activity) ? ($activity->end_time ?? null) : ($activity['end_time'] ?? null);
    $notes = is_object($activity) ? ($activity->notes ?? '') : ($activity['notes'] ?? '');
    $type = is_object($activity) ? ($activity->type ?? 'activity') : ($activity['type'] ?? 'activity');
    $isConflict = is_object($activity) ? ($activity->is_conflict ?? false) : ($activity['is_conflict'] ?? false);

    // Format times
    $formattedTime = '';
    if ($startTime && $endTime) {
        $startStr = is_string($startTime) ? substr($startTime, 0, 5) : $startTime->format('H:i');
        $endStr = is_string($endTime) ? substr($endTime, 0, 5) : $endTime->format('H:i');
        $formattedTime = "$startStr – $endStr";
    } elseif ($startTime) {
        $formattedTime = is_string($startTime) ? substr($startTime, 0, 5) : $startTime->format('H:i');
    }

    // Category styling & labels
    $categoryConfig = match ($category) {
        'sightseeing' => [
            'label' => __('Sightseeing'),
            'bg'    => 'bg-sky-50 text-sky-800 border-sky-200',
            'dot'   => 'bg-sky-500',
        ],
        'dining' => [
            'label' => __('Dining'),
            'bg'    => 'bg-amber-50 text-amber-800 border-amber-200',
            'dot'   => 'bg-amber-500',
        ],
        'cafe' => [
            'label' => __('Cafe'),
            'bg'    => 'bg-orange-50 text-orange-800 border-orange-200',
            'dot'   => 'bg-orange-500',
        ],
        'shopping' => [
            'label' => __('Shopping'),
            'bg'    => 'bg-rose-50 text-rose-800 border-rose-200',
            'dot'   => 'bg-rose-500',
        ],
        'entertainment' => [
            'label' => __('Entertainment'),
            'bg'    => 'bg-purple-50 text-purple-800 border-purple-200',
            'dot'   => 'bg-purple-500',
        ],
        default => [
            'label' => __('Other'),
            'bg'    => 'bg-gray-100 text-gray-700 border-gray-200',
            'dot'   => 'bg-gray-500',
        ],
    };

    $isRoom = ($type === 'room');
    $isBooking = ($type === 'booking');
@endphp

<div
    class="timeline-activity-card group relative w-full bg-white rounded-xl border border-gray-200/90 p-3.5 sm:p-4 transition-all duration-200 shadow-sm hover:shadow-md hover:border-teal-300 select-none {{ $isRoom ? 'bg-indigo-50/30 border-indigo-200' : ($isBooking ? 'bg-emerald-50/30 border-emerald-200' : '') }}"
    data-id="{{ $id }}"
    data-day="{{ $day }}"
    data-slot="{{ $slot }}"
    data-cost="{{ $cost }}"
    data-type="{{ $type }}"
>
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 min-w-0">
        {{-- Left: Drag Handle + Time Block + Title + Address + Notes --}}
        <div class="flex items-start gap-3 min-w-0 flex-1">
            {{-- Drag Handle (pure CSS/typography, no icon!) --}}
            @if($canEdit)
                <button
                    type="button"
                    class="drag-handle px-1.5 py-1 text-gray-400 hover:text-gray-700 cursor-grab active:cursor-grabbing rounded hover:bg-gray-100 transition shrink-0 mt-0.5 font-mono text-xs tracking-tighter"
                    title="{{ __('Drag to reorder') }}"
                    aria-label="{{ __('Drag handle') }}"
                >
                    :::
                </button>
            @endif

            {{-- Main Text Details (min-width: 0 prevents flex overflow) --}}
            <div class="min-w-0 flex-1 space-y-1">
                {{-- Badges / Time Row --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if($formattedTime)
                        <span class="inline-flex items-center text-xs font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded-md">
                            {{ $formattedTime }}
                        </span>
                    @endif

                    @if($isRoom)
                        <span class="inline-flex items-center text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md">
                            {{ __('Accommodation') }}
                        </span>
                    @elseif($isBooking)
                        <span class="inline-flex items-center text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                            {{ __('Voucher') }}
                        </span>
                    @endif

                    @if($isConflict)
                        <span class="inline-flex items-center text-[11px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md">
                            {{ __('Time Conflict') }}
                        </span>
                    @endif
                </div>

                {{-- Activity Title: Max 2 lines (line-clamp-2), NOT truncated to 1 short line --}}
                <h4 class="text-sm font-bold text-gray-900 group-hover:text-teal-700 transition leading-snug line-clamp-2">
                    {{ $title }}
                </h4>

                {{-- Location / Address: 1 line with tooltip --}}
                @if($location || $address)
                    <p class="text-xs text-gray-500 truncate" title="{{ $location ?: $address }}">
                        <span class="text-gray-400 font-medium">{{ __('Location:') }}</span> {{ $location ?: $address }}
                    </p>
                @endif

                {{-- Notes / Description: 2 lines with line-clamp --}}
                @if($notes)
                    <p class="text-xs text-gray-500 line-clamp-2 italic leading-relaxed pt-0.5">
                        {{ $notes }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Right: Category Chip + Cost Badge + Hover Actions --}}
        <div class="flex sm:flex-col items-end justify-between sm:justify-start gap-2 shrink-0 sm:min-w-[130px] pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100">
            <div class="flex flex-wrap items-center sm:flex-col sm:items-end gap-1.5">
                {{-- Category Chip --}}
                @if(!$isRoom && !$isBooking)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium border {{ $categoryConfig['bg'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $categoryConfig['dot'] }}"></span>
                        {{ $categoryConfig['label'] }}
                    </span>
                @endif

                {{-- Cost Badge --}}
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold {{ $cost > 0 ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-gray-100 text-gray-600' }}">
                    {{ $cost > 0 ? number_format($cost, 0, ',', '.') . ' ₫' : __('Free') }}
                </span>
            </div>

            {{-- Hover Actions: Edit & Delete (clean text buttons, only for editors) --}}
            @if($canEdit)
                <div class="flex items-center gap-1 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity duration-150 pt-1">
                    @if(!$isRoom)
                        <button
                            type="button"
                            class="px-2 py-1 text-xs font-semibold text-gray-600 hover:text-teal-700 hover:bg-gray-100 rounded transition"
                            title="{{ __('Edit Activity') }}"
                            @click="$dispatch('open-activity-modal', {
                                mode: 'edit',
                                activity: {
                                    id: {{ $id }},
                                    title: @js($title),
                                    day: {{ $day }},
                                    time_slot: @js($slot),
                                    start_time: @js($startTime),
                                    end_time: @js($endTime),
                                    cost: {{ $cost }},
                                    category: @js($category),
                                    location: @js($location),
                                    address: @js($address),
                                    notes: @js($notes)
                                }
                            })"
                        >
                            {{ __('Edit') }}
                        </button>
                    @endif

                    <button
                        type="button"
                        class="px-2 py-1 text-xs font-semibold text-red-600 hover:text-red-700 hover:bg-red-50 rounded transition"
                        title="{{ __('Delete') }}"
                        @click="$dispatch('delete-activity', { id: {{ $id }}, title: @js($title), type: @js($type) })"
                    >
                        {{ __('Delete') }}
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
