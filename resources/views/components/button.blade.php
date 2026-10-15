@props([
    'variant' => 'primary',
    'type' => 'submit',
    'disabled' => false,
    'loading' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 cursor-pointer';

    $variantClasses = match ($variant) {
        'primary'   => 'bg-primary text-white hover:bg-teal-800 focus:ring-primary/50 shadow-lg shadow-primary/25 hover:shadow-primary/40',
        'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-400/50',
        'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500/50 shadow-lg shadow-red-500/25',
        'outline'   => 'border-2 border-primary text-primary hover:bg-primary hover:text-white focus:ring-primary/50 bg-transparent',
        default     => 'bg-primary text-white hover:bg-teal-800 focus:ring-primary/50',
    };

    $stateClasses = ($disabled || $loading) ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
@endphp

<button
    type="{{ $type }}"
    @if($disabled || $loading) disabled @endif
    {{ $attributes->merge(['class' => trim("$baseClasses $variantClasses $stateClasses")]) }}
>
    @if($loading)
        <svg class="animate-spin -ml-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    {{ $slot }}
</button>
