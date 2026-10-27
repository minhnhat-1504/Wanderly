@props([
    'stats' => [],
])

@php
    $items = [
        [
            'label' => __('Trips'),
            'value' => $stats['public_trips_count'] ?? 0,
            'icon' => 'map',
        ],
        [
            'label' => __('Destinations'),
            'value' => $stats['destinations_count'] ?? 0,
            'icon' => 'map-pin',
        ],
        [
            'label' => __('Posts'),
            'value' => $stats['posts_count'] ?? 0,
            'icon' => 'camera',
        ],
        [
            'label' => __('Clones'),
            'value' => $stats['clones_count'] ?? 0,
            'icon' => 'copy',
        ],
        [
            'label' => __('Likes'),
            'value' => $stats['likes_count'] ?? 0,
            'icon' => 'heart',
        ],
    ];
@endphp

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8">
    <div class="grid grid-cols-2 md:grid-cols-5 divide-y md:divide-y-0 md:divide-x divide-gray-100">
        @foreach($items as $item)
            <div class="p-4 sm:p-5 flex items-center gap-3.5 hover:bg-gray-50/50 transition-colors">
                <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center shrink-0 ring-1 ring-teal-100/70">
                    <x-icon :name="$item['icon']" class="w-5 h-5 text-teal-600" />
                </div>
                <div class="min-w-0">
                    <div class="text-xl sm:text-2xl font-bold tracking-tight {{ $item['value'] > 0 ? 'text-gray-900' : 'text-slate-400' }}">
                        {{ number_format($item['value']) }}
                    </div>
                    <div class="text-xs text-slate-500 font-medium truncate">
                        {{ $item['label'] }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
