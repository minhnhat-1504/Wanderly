@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-3">
        {{-- Results Summary (Left) --}}
        <div class="text-xs sm:text-sm text-gray-500 font-medium order-2 sm:order-1">
            <span>{{ __('Showing') }}</span>
            <span class="font-bold text-gray-900">{{ $paginator->firstItem() ?? 0 }}</span>
            <span>{{ __('to') }}</span>
            <span class="font-bold text-gray-900">{{ $paginator->lastItem() ?? 0 }}</span>
            <span>{{ __('of') }}</span>
            <span class="font-bold text-gray-900">{{ $paginator->total() }}</span>
            <span>{{ __('results') }}</span>
        </div>

        {{-- Page Buttons Pill (Right) --}}
        <div class="inline-flex items-center gap-1 p-1 bg-white rounded-2xl border border-gray-200/90 shadow-xs order-1 sm:order-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-gray-300 cursor-not-allowed select-none" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-gray-600 hover:text-teal-800 hover:bg-teal-50 transition font-semibold"
                    aria-label="{{ __('pagination.previous') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center w-9 h-9 text-xs text-gray-400 font-bold select-none">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                aria-current="page"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white font-extrabold text-xs shadow-md shadow-teal-900/20 select-none"
                            >
                                {{ $page }}
                            </span>
                        @else
                            <a
                                href="{{ $url }}"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-gray-700 hover:text-teal-800 hover:bg-teal-50 font-bold text-xs transition"
                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                            >
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-gray-600 hover:text-teal-800 hover:bg-teal-50 transition font-semibold"
                    aria-label="{{ __('pagination.next') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-gray-300 cursor-not-allowed select-none" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
