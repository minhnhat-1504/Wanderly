@props([
    'user',
    'size' => 'md', // sm, md, lg, xl
    'class' => '',
])

@php
    $dimensions = match($size) {
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-16 h-16 text-lg',
        'xl' => 'w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 text-2xl md:text-3xl',
        default => 'w-10 h-10 text-sm',
    };
@endphp

<div class="relative inline-block shrink-0">
    @if($user && $user->avatar)
        <img src="{{ $user->avatar_url }}"
             alt="{{ $user->name }}"
             loading="lazy"
             class="{{ $dimensions }} rounded-2xl object-cover ring-4 ring-white shadow-md {{ $class }}">
    @elseif($user)
        <div class="{{ $dimensions }} rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-teal-700 flex items-center justify-center text-white font-bold ring-4 ring-white shadow-md {{ $class }}">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
    @else
        <div class="{{ $dimensions }} rounded-2xl bg-gray-100 flex items-center justify-center text-gray-400 ring-4 ring-white {{ $class }}">
            <x-icon name="user" class="w-1/2 h-1/2 text-gray-400" />
        </div>
    @endif
</div>
