@props([
    'tag',
])

@php
    $lower = strtolower(trim((string) $tag));
    $vibesMap = config('travel.vibes', []);
    $iconName = $vibesMap[$lower] ?? config('travel.default_icon', 'sparkles');
@endphp

<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-100 shadow-sm transition-transform hover:scale-105">
    <x-icon :name="$iconName" class="w-3.5 h-3.5 text-teal-600" />
    <span>{{ $tag }}</span>
</span>
