@php
    $toastTypes = [
        'success' => [
            'bg'     => 'bg-emerald-50',
            'border' => 'border-emerald-200',
            'iconBg' => 'bg-emerald-100',
            'icon'   => 'text-emerald-600',
            'text'   => 'text-emerald-800',
            'path'   => 'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z',
        ],
        'error' => [
            'bg'     => 'bg-red-50',
            'border' => 'border-red-200',
            'iconBg' => 'bg-red-100',
            'icon'   => 'text-red-600',
            'text'   => 'text-red-800',
            'path'   => 'M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z',
        ],
        'warning' => [
            'bg'     => 'bg-amber-50',
            'border' => 'border-amber-200',
            'iconBg' => 'bg-amber-100',
            'icon'   => 'text-amber-600',
            'text'   => 'text-amber-800',
            'path'   => 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
        ],
        'info' => [
            'bg'     => 'bg-blue-50',
            'border' => 'border-blue-200',
            'iconBg' => 'bg-blue-100',
            'icon'   => 'text-blue-600',
            'text'   => 'text-blue-800',
            'path'   => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z',
        ],
    ];
@endphp

<div class="fixed top-4 right-4 z-[100] space-y-3 w-full max-w-sm pointer-events-none" id="toast-container">
    @foreach($toastTypes as $type => $config)
        @if(session($type))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, 3000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                class="{{ $config['bg'] }} border {{ $config['border'] }} rounded-xl shadow-lg p-4 flex items-start gap-3 pointer-events-auto"
                role="alert"
            >
                {{-- Icon --}}
                <div class="shrink-0 w-8 h-8 {{ $config['iconBg'] }} rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $config['icon'] }}" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="{{ $config['path'] }}" clip-rule="evenodd"/>
                    </svg>
                </div>

                {{-- Message --}}
                <p class="text-sm font-medium {{ $config['text'] }} flex-1 pt-1">
                    {{ session($type) }}
                </p>

                {{-- Close Button --}}
                <button @click="show = false"
                        class="shrink-0 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-white/80 transition-colors duration-150 pointer-events-auto"
                        aria-label="{{ __('Close notification') }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        @endif
    @endforeach
</div>
