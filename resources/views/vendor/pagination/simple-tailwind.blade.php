@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between py-3">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-gray-300 bg-gray-50 border border-gray-200 cursor-not-allowed select-none">
                &larr; {{ __('Previous') }}
            </span>
        @else
            <a
                href="{{ $paginator->previousPageUrl() }}"
                rel="prev"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition shadow-xs"
            >
                &larr; {{ __('Previous') }}
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a
                href="{{ $paginator->nextPageUrl() }}"
                rel="next"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition shadow-xs"
            >
                {{ __('Next') }} &rarr;
            </a>
        @else
            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-gray-300 bg-gray-50 border border-gray-200 cursor-not-allowed select-none">
                {{ __('Next') }} &rarr;
            </span>
        @endif
    </nav>
@endif
