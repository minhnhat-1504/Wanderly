@props([
    'name',
    'class' => 'w-4 h-4',
])

@php
    $cleanedName = str_replace('_', '-', strtolower(trim((string) $name)));
@endphp

@switch($cleanedName)
    @case('map')
        <x-lucide-map class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('map-pin')
        <x-lucide-map-pin class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('camera')
        <x-lucide-camera class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('copy')
        <x-lucide-copy class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('compass')
        <x-lucide-compass class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('bookmark')
        <x-lucide-bookmark class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('briefcase')
        <x-lucide-briefcase class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('layout-grid')
        <x-lucide-layout-grid class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('calendar')
    @case('calendar-days')
        <x-lucide-calendar class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('link')
        <x-lucide-link class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('heart')
        <x-lucide-heart class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('badge-check')
    @case('check-circle')
        <x-lucide-badge-check class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('store')
        <x-lucide-store class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('pencil')
    @case('edit')
        <x-lucide-pencil class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('share-2')
    @case('share')
        <x-lucide-share-2 class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('message-circle')
    @case('message')
        <x-lucide-message-circle class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('lock')
        <x-lucide-lock class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('utensils')
        <x-lucide-utensils class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('mountain')
        <x-lucide-mountain class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('palmtree')
    @case('tree-palm')
        <x-lucide-palmtree class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('sun')
        <x-lucide-sun class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('landmark')
        <x-lucide-landmark class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('music')
        <x-lucide-music class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('coffee')
        <x-lucide-coffee class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('trees')
    @case('tree-pine')
        <x-lucide-trees class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('shopping-bag')
        <x-lucide-shopping-bag class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('wallet')
        <x-lucide-wallet class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('handshake')
        <x-lucide-handshake class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('shield')
        <x-lucide-shield class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('bell')
        <x-lucide-bell class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('alert-triangle')
        <x-lucide-alert-triangle class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('globe')
        <x-lucide-globe class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('trash')
    @case('trash-2')
        <x-lucide-trash-2 class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('user')
        <x-lucide-user class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('users')
        <x-lucide-users class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('check')
        <x-lucide-check class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('x')
        <x-lucide-x class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('folder')
        <x-lucide-folder class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
    @case('sparkles')
    @default
        <x-lucide-sparkles class="{{ $class }}" aria-hidden="true" {{ $attributes }} />
        @break
@endswitch
