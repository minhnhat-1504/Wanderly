@props([
    'name' => 'default',
    'maxWidth' => 'md',
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm'  => 'sm:max-w-sm',
        'md'  => 'sm:max-w-md',
        'lg'  => 'sm:max-w-lg',
        'xl'  => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        default => 'sm:max-w-md',
    };
@endphp

<div
    x-data="{ open: false }"
    @open-modal-{{ $name }}.window="open = true"
    @close-modal-{{ $name }}.window="open = false"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[60] overflow-y-auto"
    aria-labelledby="modal-title-{{ $name }}"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
    ></div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.stop
            class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full {{ $maxWidthClass }}"
        >
            {{-- Header --}}
            @if(isset($title))
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900" id="modal-title-{{ $name }}">
                        {{ $title }}
                    </h3>
                    <button @click="open = false"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors duration-150"
                            aria-label="{{ __('Close') }}">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            @endif

            {{-- Body --}}
            <div class="px-6 py-5">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if(isset($footer))
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
