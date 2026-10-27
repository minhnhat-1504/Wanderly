@extends('layouts.app')

@section('title', __('Dashboard'))
@section('description', __('Manage and view all your travel itineraries with Wanderly.'))

@section('content')
<div class="space-y-8">

    {{-- ================================================================
         HEADER SECTION
    ================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-200/80">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                {{ __('Welcome back, :name', ['name' => auth()->user()->name ?? __('Traveler')]) }}
            </h1>
            <p class="text-sm sm:text-base text-gray-500 mt-1">
                {{ __('Plan, organize, and explore your upcoming journeys with ease.') }}
            </p>
        </div>

        <div class="shrink-0">
            <a href="{{ Route::has('trips.create') ? route('trips.create') : url('/trips/create') }}">
                <x-button variant="primary" type="button" class="shadow-md">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>{{ __('Create New Trip') }}</span>
                </x-button>
            </a>
        </div>
    </div>

    {{-- ================================================================
         SEARCH & FILTER BAR
    ================================================================= --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <form method="GET" action="{{ url('/trips') }}" class="relative flex-1 max-w-lg">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>

                <input
                    type="text"
                    name="search"
                    id="search-input"
                    value="{{ request('search') }}"
                    placeholder="{{ __('Search trips or destinations...') }}"
                    class="block w-full pl-10 pr-20 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-900 placeholder-gray-400 shadow-sm transition duration-200 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm"
                >

                @if(request('search'))
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                        <a
                            href="{{ url('/trips') }}"
                            class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            title="{{ __('Clear search') }}"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </form>

        {{-- Trip Count or Search Indicator --}}
        @if(isset($trips) && method_exists($trips, 'total'))
            <div class="text-sm text-gray-500 text-right sm:text-left self-center">
                {{ __('Showing :count trips', ['count' => $trips->total()]) }}
            </div>
        @elseif(isset($trips) && is_countable($trips))
            <div class="text-sm text-gray-500 text-right sm:text-left self-center">
                {{ __('Showing :count trips', ['count' => count($trips)]) }}
            </div>
        @endif
    </div>

    {{-- ================================================================
         TRIPS GRID & EMPTY STATE
    ================================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($trips ?? [] as $trip)
            <x-trip-card :trip="$trip" />
        @empty
            <div class="col-span-full py-16 px-6 text-center bg-white rounded-3xl border border-dashed border-gray-300 shadow-sm">
                {{-- Empty State Illustration --}}
                <div class="w-20 h-20 mx-auto rounded-3xl bg-teal-50 border border-teal-100 flex items-center justify-center text-primary mb-5 shadow-inner">
                    <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.2c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.1z"></path>
                    </svg>
                </div>

                @if(request('search'))
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        {{ __('No trips found') }}
                    </h2>
                    <p class="text-gray-500 max-w-md mx-auto mb-6 text-sm">
                        {{ __('We couldn\'t find any trips matching ":query". Try searching with a different keyword or create a new trip.', ['query' => request('search')]) }}
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <a href="{{ url('/trips') }}">
                            <x-button variant="secondary" type="button">
                                {{ __('Clear Search') }}
                            </x-button>
                        </a>
                        <a href="{{ Route::has('trips.create') ? route('trips.create') : url('/trips/create') }}">
                            <x-button variant="primary" type="button">
                                {{ __('Create New Trip') }}
                            </x-button>
                        </a>
                    </div>
                @else
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        {{ __("You haven't created any trips yet") }}
                    </h2>
                    <p class="text-gray-500 max-w-md mx-auto mb-6 text-sm">
                        {{ __('Start planning your first adventure! Choose a destination, set your budget, and build unforgettable memories.') }}
                    </p>
                    <a href="{{ Route::has('trips.create') ? route('trips.create') : url('/trips/create') }}">
                        <x-button variant="primary" type="button">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>{{ __('Create New Trip') }}</span>
                        </x-button>
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- ================================================================
         PAGINATION
    ================================================================= --}}
    @if(isset($trips) && method_exists($trips, 'hasPages') && $trips->hasPages())
        <div class="pt-6 border-t border-gray-200">
            {{ $trips->withQueryString()->links() }}
        </div>
    @endif

</div>
@endsection
