@extends('layouts.app')

@section('title', $trip->name)
@section('description', __('View trip details and itinerary for :name', ['name' => $trip->name]))

@section('content')
<div class="max-w-6xl mx-auto space-y-8">

    {{-- ================================================================
         BREADCRUMBS & TOP ACTIONS
    ================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <nav class="flex items-center text-sm font-medium text-gray-500 space-x-2">
            <a href="{{ route('trips.index') }}" class="hover:text-primary transition-colors duration-200">
                {{ __('Dashboard') }}
            </a>
            <svg class="w-4 h-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-gray-900 font-semibold truncate max-w-xs sm:max-w-md">{{ $trip->name }}</span>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('trips.index') }}">
                <x-button variant="secondary" type="button" class="text-xs sm:text-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>{{ __('Back to Trips') }}</span>
                </x-button>
            </a>

            @can('update', $trip)
                <a href="{{ route('trips.edit', $trip) }}">
                    <x-button variant="primary" type="button" class="text-xs sm:text-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>{{ __('Edit Trip') }}</span>
                    </x-button>
                </a>
            @endcan

            @auth
                <form method="POST" action="{{ route('trips.duplicate', $trip) }}" class="inline">
                    @csrf
                    <x-button variant="outline" type="submit" class="text-xs sm:text-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span>{{ __('Duplicate Trip') }}</span>
                    </x-button>
                </form>
            @endauth


            @can('delete', $trip)
                <form method="POST" action="{{ route('trips.destroy', $trip) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this trip? This action cannot be undone.') }}');" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger" type="submit" class="text-xs sm:text-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span>{{ __('Delete') }}</span>
                    </x-button>
                </form>
            @endcan
        </div>
    </div>

    {{-- ================================================================
         HERO COVER & HEADER CARD
    ================================================================= --}}
    <div class="relative bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
        {{-- Cover Banner --}}
        <div class="h-48 sm:h-64 w-full relative bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-600">
            @if($trip->cover)
                <img src="{{ asset('storage/' . $trip->cover) }}" alt="{{ $trip->name }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
            @else
                <div class="absolute inset-0 bg-gradient-to-br from-teal-800/80 via-teal-600/70 to-emerald-600/80"></div>
                <div class="absolute inset-0 flex items-center justify-center opacity-10">
                    <svg class="w-48 h-48 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M2 12h20"></path>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                </div>
            @endif

            {{-- Floating Badges on Banner --}}
            <div class="absolute top-4 right-4 flex items-center gap-2">
                {{-- Privacy Badge --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-md
                    @if($trip->privacy === 'public') bg-emerald-500/80 text-white
                    @elseif($trip->privacy === 'group') bg-blue-500/80 text-white
                    @else bg-gray-900/70 text-gray-200 @endif">
                    @if($trip->privacy === 'public')
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                        </svg>
                        {{ __('Public') }}
                    @elseif($trip->privacy === 'group')
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        {{ __('Group') }}
                    @else
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        {{ __('Private') }}
                    @endif
                </span>

                {{-- Status Badge --}}
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-md
                    @if($trip->status === 'completed') bg-gray-800/80 text-gray-200
                    @elseif($trip->status === 'ongoing') bg-emerald-500/80 text-white
                    @else bg-amber-500/80 text-white @endif">
                    {{ ucfirst(__($trip->status)) }}
                </span>
            </div>

            {{-- Trip Title & Destination inside hero --}}
            <div class="absolute bottom-4 left-6 right-6 text-white">
                <div class="flex items-center gap-2 text-teal-200 text-sm font-medium mb-1 drop-shadow">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span>{{ $trip->destination }}</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight drop-shadow-md">
                    {{ $trip->name }}
                </h1>
            </div>
        </div>

        {{-- Meta Summary Bar --}}
        <div class="p-6 grid grid-cols-2 sm:grid-cols-4 gap-4 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 bg-white">
            <div class="px-2 pt-2 sm:pt-0">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Duration') }}</span>
                <span class="mt-1 text-lg font-bold text-gray-900">
                    {{ $tripDays }} {{ $tripDays > 1 ? __('Days') : __('Day') }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">
                    {{ $trip->start_date->format('M d') }} – {{ $trip->end_date->format('M d, Y') }}
                </span>
            </div>

            <div class="px-2 pt-2 sm:pt-0 sm:pl-4">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Total Budget') }}</span>
                <span class="mt-1 text-lg font-bold text-teal-700">
                    {{ number_format($trip->budget, 0, ',', '.') }} VND
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ __('Allocated budget') }}</span>
            </div>

            <div class="px-2 pt-2 sm:pt-0 sm:pl-4">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Trip Status') }}</span>
                <span class="mt-1 text-lg font-bold text-gray-900 capitalize">
                    {{ __($trip->status) }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ __('Current Phase') }}</span>
            </div>

            <div class="px-2 pt-2 sm:pt-0 sm:pl-4">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Owner / Leader') }}</span>
                <span class="mt-1 text-lg font-bold text-gray-900 truncate block">
                    {{ $trip->owner->name ?? __('Unknown') }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ $trip->owner->email ?? '' }}</span>
            </div>
        </div>
    </div>

    {{-- ================================================================
         MEMBERS LIST & COLLABORATORS
    ================================================================= --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 space-y-4">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ __('Trip Members & Roles') }}</h2>
                <p class="text-xs text-gray-500">{{ __('Collaborators and their assigned permission levels.') }}</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                {{ $trip->members->count() }} {{ __('Members') }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($trip->members as $member)
                <div class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 bg-gray-50/60 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">
                                {{ $member->user->name ?? __('Member') }}
                                @if($member->user_id === auth()->id())
                                    <span class="text-xs text-primary font-normal">({{ __('You') }})</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 truncate">{{ $member->user->email ?? '' }}</p>
                        </div>
                    </div>

                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        @if($member->role === 'leader') bg-amber-100 text-amber-800 border border-amber-200
                        @elseif($member->role === 'editor') bg-blue-100 text-blue-800 border border-blue-200
                        @else bg-gray-200 text-gray-700 @endif">
                        {{ ucfirst(__($member->role)) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
