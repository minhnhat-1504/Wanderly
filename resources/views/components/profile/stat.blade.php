@props([
    'label',
    'value',
    'icon' => null,
])

<div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 text-center flex flex-col items-center justify-center">
    @if($icon)
        <div class="text-2xl mb-1.5">{{ $icon }}</div>
    @endif
    <div class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
        {{ number_format($value) }}
    </div>
    <div class="text-xs sm:text-sm font-medium text-gray-500 mt-1 uppercase tracking-wider">
        {{ $label }}
    </div>
</div>
