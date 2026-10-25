@props([
    'activity',
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

    // Format times if Carbon or string
    $formattedTime = '';
    if ($startTime && $endTime) {
        $startStr = is_string($startTime) ? substr($startTime, 0, 5) : $startTime->format('H:i');
        $endStr = is_string($endTime) ? substr($endTime, 0, 5) : $endTime->format('H:i');
        $formattedTime = "$startStr – $endStr";
    } elseif ($startTime) {
        $formattedTime = is_string($startTime) ? substr($startTime, 0, 5) : $startTime->format('H:i');
    }

    // Category styling & icons
    $categoryConfig = match ($category) {
        'sightseeing' => [
            'label' => __('Sightseeing'),
            'bg'    => 'bg-sky-50 text-sky-700 border-sky-200',
            'dot'   => 'bg-sky-500',
        ],
        'dining' => [
            'label' => __('Dining'),
            'bg'    => 'bg-amber-50 text-amber-700 border-amber-200',
            'dot'   => 'bg-amber-500',
        ],
        'cafe' => [
            'label' => __('Cafe'),
            'bg'    => 'bg-orange-50 text-orange-700 border-orange-200',
            'dot'   => 'bg-orange-500',
        ],
        'shopping' => [
            'label' => __('Shopping'),
            'bg'    => 'bg-rose-50 text-rose-700 border-rose-200',
            'dot'   => 'bg-rose-500',
        ],
        'entertainment' => [
            'label' => __('Entertainment'),
            'bg'    => 'bg-purple-50 text-purple-700 border-purple-200',
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
    class="timeline-activity-card group relative rounded-2xl border p-4 transition-all duration-200 shadow-sm hover:shadow-md select-none
        {{ $isRoom
            ? 'bg-gradient-to-br from-indigo-50/80 to-purple-50/60 border-indigo-200 hover:border-indigo-300'
            : ($isBooking
                ? 'bg-gradient-to-br from-emerald-50/70 to-teal-50/50 border-emerald-200 hover:border-emerald-300'
                : 'bg-white border-gray-200/90 hover:border-teal-300') }}"
    data-id="{{ $id }}"
    data-day="{{ $day }}"
    data-slot="{{ $slot }}"
    data-cost="{{ $cost }}"
    data-type="{{ $type }}"
>
    <div class="flex items-start justify-between gap-3">
        {{-- Left: Drag Handle & Main Content --}}
        <div class="flex items-start gap-2.5 min-w-0 flex-1">
            {{-- Drag Handle --}}
            <button
                type="button"
                class="drag-handle p-1 text-gray-400 hover:text-gray-700 cursor-grab active:cursor-grabbing rounded-lg hover:bg-gray-100 transition-colors mt-0.5 shrink-0"
                title="{{ __('Drag to reorder') }}"
                aria-label="{{ __('Drag handle') }}"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="9" cy="5" r="1"></circle>
                    <circle cx="9" cy="12" r="1"></circle>
                    <circle cx="9" cy="19" r="1"></circle>
                    <circle cx="15" cy="5" r="1"></circle>
                    <circle cx="15" cy="12" r="1"></circle>
                    <circle cx="15" cy="19" r="1"></circle>
                </svg>
            </button>

            <div class="min-w-0 flex-1">
                {{-- Category Tag & Room / Booking Badge --}}
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    @if($isRoom)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                            🏨 {{ __('Accommodation') }}
                        </span>
                    @elseif($isBooking)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            🎟️ {{ __('Booking Voucher') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium border {{ $categoryConfig['bg'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $categoryConfig['dot'] }}"></span>
                            {{ $categoryConfig['label'] }}
                        </span>
                    @endif

                    @if($formattedTime)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-500 bg-gray-50 px-2 py-0.5 rounded-lg border border-gray-200/70">
                            <svg class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            {{ $formattedTime }}
                        </span>
                    @endif

                    @if($isConflict)
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                            ⚠️ {{ __('Time Conflict') }}
                        </span>
                    @endif
                </div>

                {{-- Activity Title --}}
                <h4 class="text-sm font-bold text-gray-900 group-hover:text-primary transition-colors duration-150 truncate">
                    {{ $title }}
                </h4>

                {{-- Location & Address (Strictly No Map API) --}}
                @if($location || $address)
                    <div class="flex items-center gap-1.5 text-xs text-gray-500 mt-1 truncate">
                        <svg class="w-3.5 h-3.5 text-teal-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span class="truncate font-medium">{{ $location ?: $address }}</span>
                    </div>
                @endif

                {{-- Notes if present --}}
                @if($notes)
                    <p class="text-xs text-gray-400 mt-1.5 line-clamp-2 italic">
                        {{ $notes }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Right: Cost Badge & Action Buttons --}}
        <div class="flex flex-col items-end justify-between self-stretch shrink-0 gap-2">
            {{-- Cost Badge --}}
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold {{ $cost > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-600' }}">
                {{ $cost > 0 ? number_format($cost, 0, ',', '.') . ' VND' : __('Free') }}
            </span>

            {{-- Action Buttons (Edit / Delete) --}}
            <div class="flex items-center gap-1 opacity-90 sm:opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                @if(!$isRoom)
                    <button
                        type="button"
                        class="p-1 rounded-lg text-gray-400 hover:text-primary hover:bg-gray-100 transition-colors"
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
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                @endif

                <button
                    type="button"
                    class="p-1 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                    title="{{ __('Delete') }}"
                    @click="$dispatch('delete-activity', { id: {{ $id }}, title: @js($title), type: @js($type) })"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
