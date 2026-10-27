@extends('layouts.app')

@section('title', $trip->name)
@section('description', __('Interactive itinerary and timeline board for :name', ['name' => $trip->name]))

@section('content')
<div class="max-w-7xl mx-auto space-y-8" id="timeline-board" data-trip-id="{{ $trip->id }}">

    {{-- ================================================================
         1. BREADCRUMBS & TOP ACTIONS
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

        <div class="flex items-center gap-2" x-data>
            {{-- Room Drawer Trigger Button --}}
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-room-drawer'))"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors shadow-sm cursor-pointer"
                title="{{ __('Accommodations Drawer') }}"
            >
                <span>🏨</span>
                <span>{{ __('Stay & Rooms') }}</span>
                @if(isset($trip->roomBookings) && $trip->roomBookings->count() > 0)
                    <span class="bg-indigo-600 text-white text-[11px] font-bold px-1.5 py-0.5 rounded-full">
                        {{ $trip->roomBookings->count() }}
                    </span>
                @endif
            </button>

            {{-- Checklist Drawer Trigger Button (D4-02) --}}
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-checklist-drawer'))"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-sm cursor-pointer"
                title="{{ __('Trip Checklist') }}"
            >
                <span>🧳</span>
                <span>{{ __('Checklist') }}</span>
            </button>

            {{-- Public Share Modal Trigger Button (D4-03) --}}
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-share-modal'))"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition-colors shadow-sm cursor-pointer"
                title="{{ __('Share Trip') }}"
            >
                <span>🔗</span>
                <span>{{ __('Share') }}</span>
                @if($trip->is_shared)
                    <span class="w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                @endif
            </button>

            {{-- Members & Group Mood (Sprint 3 - M8) --}}
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-member-modal'))"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition-colors shadow-sm cursor-pointer"
                title="{{ __('Trip Members & Group Mood') }}"
            >
                <span>👥</span>
                <span>{{ __('Members & Mood') }}</span>
            </button>

            {{-- Voting Pool Drawer (Sprint 3 - M8) --}}
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-voting-drawer'))"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-colors shadow-sm cursor-pointer"
                title="{{ __('Vote on Candidate Spots') }}"
            >
                <span>🗳️</span>
                <span>{{ __('Vote Pool') }}</span>
            </button>

            {{-- Auto Suggest Match Engine (Sprint 3 - M9) --}}
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-match-engine-modal'))"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200 transition-colors shadow-sm cursor-pointer"
                title="{{ __('Auto Suggest Draft Itinerary') }}"
            >
                <span>⚡</span>
                <span>{{ __('Match Engine') }}</span>
            </button>

            {{-- In-App & 1-1 Chat Drawer (Sprint 3 - M12) --}}
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-chat-drawer'))"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors shadow-sm cursor-pointer"
                title="{{ __('Trip & Provider Chat') }}"
            >
                <span>💬</span>
                <span>{{ __('Chat') }}</span>
            </button>

            {{-- Reverse Marketplace Button (Sprint 3 - M10) --}}
            <a href="{{ route('trips.marketplace.index', $trip) }}">
                <x-button variant="outline" type="button" class="text-xs sm:text-sm border-primary/40 text-primary hover:bg-primary/5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>{{ __('Marketplace') }}</span>
                </x-button>
            </a>

            @can('update', $trip)
                <a href="{{ route('trips.edit', $trip) }}">
                    <x-button variant="secondary" type="button" class="text-xs sm:text-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>{{ __('Edit') }}</span>
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
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span class="hidden sm:inline">{{ __('Delete') }}</span>
                    </x-button>
                </form>
            @endcan
        </div>
    </div>

    {{-- ================================================================
         2. HERO COVER & HEADER SUMMARY CARD
    ================================================================= --}}
    <div class="relative bg-white rounded-3xl shadow-sm border border-gray-200/80 overflow-hidden">
        {{-- Cover Banner --}}
        <div class="h-44 sm:h-56 w-full relative bg-gradient-to-r from-teal-800 via-teal-700 to-emerald-700">
            @if($trip->cover)
                <img src="{{ asset('storage/' . $trip->cover) }}" alt="{{ $trip->name }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-transparent"></div>
            @else
                <div class="absolute inset-0 bg-gradient-to-br from-teal-900/90 via-teal-700/80 to-emerald-800/80"></div>
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
                        🌐 {{ __('Public') }}
                    @elseif($trip->privacy === 'group')
                        👥 {{ __('Group') }}
                    @else
                        🔒 {{ __('Private') }}
                    @endif
                </span>

                {{-- Status Badge --}}
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-md
                    @if($trip->status === 'completed') bg-gray-800/80 text-gray-200
                    @elseif($trip->status === 'ongoing') bg-emerald-500/80 text-white animate-pulse
                    @else bg-amber-500/80 text-white @endif">
                    {{ ucfirst(__($trip->status)) }}
                </span>
            </div>

            {{-- Trip Title & Destination inside hero --}}
            <div class="absolute bottom-4 left-6 right-6 text-white">
                <div class="flex items-center gap-2 text-teal-200 text-sm font-medium mb-1 drop-shadow">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"></path>
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
        <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-4 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 bg-white">
            <div class="px-2 pt-2 sm:pt-0">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Duration') }}</span>
                <span class="mt-1 text-base sm:text-lg font-bold text-gray-900">
                    {{ $tripDays }} {{ $tripDays > 1 ? __('Days') : __('Day') }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">
                    {{ $trip->start_date ? $trip->start_date->format('M d') : '' }} – {{ $trip->end_date ? $trip->end_date->format('M d, Y') : '' }}
                </span>
            </div>

            <div class="px-2 pt-2 sm:pt-0 sm:pl-4">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Total Budget') }}</span>
                <span class="mt-1 text-base sm:text-lg font-bold text-teal-700">
                    {{ number_format($trip->budget, 0, ',', '.') }} VND
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ __('Allocated budget') }}</span>
            </div>

            <div class="px-2 pt-2 sm:pt-0 sm:pl-4">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Trip Status') }}</span>
                <span class="mt-1 text-base sm:text-lg font-bold text-gray-900 capitalize">
                    {{ __($trip->status) }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ __('Current Phase') }}</span>
            </div>

            <div class="px-2 pt-2 sm:pt-0 sm:pl-4">
                <span class="block text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Owner / Leader') }}</span>
                <span class="mt-1 text-base sm:text-lg font-bold text-gray-900 truncate block">
                    {{ $trip->owner->name ?? __('Unknown') }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5 truncate">{{ $trip->owner->email ?? '' }}</span>
            </div>
        </div>
    </div>

    {{-- ================================================================
         3. TIMELINE BOARD & INTERACTIVE 4 SLOTS (M3)
    ================================================================= --}}
    @php
        $startDate = $trip->start_date ? \Carbon\Carbon::parse($trip->start_date) : now();

        // Group activities if present
        $activitiesGrouped = [];
        if (isset($trip->activities)) {
            foreach ($trip->activities as $act) {
                $activitiesGrouped[$act->day][$act->time_slot][] = $act;
            }
        }
    @endphp

    <div
        x-data="{
            activeDay: 1,
            totalDays: {{ max(1, $tripDays) }},
            savingOrder: false,
            savingTimer: null,
            init() {
                window.addEventListener('timeline-saving', (e) => {
                    this.savingOrder = e.detail?.saving || false;
                    if (this.savingOrder) {
                        clearTimeout(this.savingTimer);
                        this.savingTimer = setTimeout(() => {
                            this.savingOrder = false;
                        }, 2500);
                    }
                });
            }
        }"
        class="space-y-6"
    >
        {{-- Saving Indicator Badge --}}
        <div
            x-show="savingOrder"
            x-cloak
            x-transition
            class="fixed top-20 right-6 z-50 flex items-center gap-2 bg-gray-900/90 text-white text-xs px-3.5 py-2 rounded-xl shadow-lg backdrop-blur-md"
        >
            <svg class="animate-spin h-3.5 w-3.5 text-teal-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span>{{ __('Saving order...') }}</span>
        </div>

        {{-- Day Selector Tabs --}}
        <div class="flex items-center justify-between gap-4 pb-2 border-b border-gray-200 overflow-x-auto">
            <div class="flex items-center gap-2">
                @for($d = 1; $d <= max(1, $tripDays); $d++)
                    @php
                        $currentDayDate = (clone $startDate)->addDays($d - 1);
                    @endphp
                    <button
                        type="button"
                        @click="activeDay = {{ $d }}; $nextTick(() => window.initTimeline && window.initTimeline())"
                        class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all duration-200 shrink-0 flex flex-col items-start border"
                        :class="activeDay === {{ $d }}
                            ? 'bg-primary text-white border-primary shadow-lg shadow-primary/25'
                            : 'bg-white text-gray-700 border-gray-200 hover:border-teal-300 hover:bg-gray-50'"
                    >
                        <span>{{ __('Day :day', ['day' => $d]) }}</span>
                        <span class="text-[11px] font-normal opacity-80" :class="activeDay === {{ $d }} ? 'text-teal-100' : 'text-gray-400'">
                            {{ $currentDayDate->format('D, M d') }}
                        </span>
                    </button>
                @endfor
            </div>

            {{-- Quick Add Activity Button --}}
            <button
                type="button"
                @click="$dispatch('open-activity-modal', { mode: 'create', day: activeDay, time_slot: 'morning' })"
                class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold bg-primary text-white hover:bg-teal-800 transition-colors shadow-md shadow-primary/20"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>{{ __('Add Activity') }}</span>
            </button>
        </div>

        {{-- 4 Time Slots Timeline Grid for Active Day --}}
        @for($d = 1; $d <= max(1, $tripDays); $d++)
            <div
                x-show="activeDay === {{ $d }}"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-6"
            >
                {{-- Day Banner Info --}}
                <div class="flex items-center justify-between bg-teal-50/70 border border-teal-100 px-5 py-3 rounded-2xl">
                    <div class="flex items-center gap-2 text-teal-900 text-sm font-bold">
                        <span>🗓️</span>
                        <span>{{ __('Day :day Itinerary', ['day' => $d]) }}</span>
                        <span class="text-xs font-normal text-teal-700">({{ (clone $startDate)->addDays($d - 1)->format('l, F d, Y') }})</span>
                    </div>

                    <div class="text-xs text-teal-800 font-semibold flex items-center gap-1.5">
                        <span class="text-teal-600">{{ __('Day Total:') }}</span>
                        <span data-day-total-display="{{ $d }}" class="font-bold text-teal-950">0 VND</span>
                    </div>
                </div>

                {{-- 4 Time Slots --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                    {{-- Slot 1: MORNING (06:00 – 11:00) --}}
                    <div class="flex flex-col bg-gray-50/90 rounded-3xl border border-gray-200/80 p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🌅</span>
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                    {{ __('Morning') }}
                                </h3>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-400">06:00 – 11:00</span>
                        </div>

                        {{-- Slot Container for SortableJS --}}
                        <div
                            class="timeline-slot-container flex-1 min-h-[140px] space-y-2.5 p-1"
                            data-day="{{ $d }}"
                            data-slot="morning"
                        >
                            @php $morningActs = $activitiesGrouped[$d]['morning'] ?? []; @endphp
                            @foreach($morningActs as $act)
                                <x-activity-card :activity="$act" />
                            @endforeach

                            <div class="slot-empty-placeholder {{ count($morningActs) > 0 ? 'hidden' : 'flex' }} flex-col items-center justify-center p-6 border-2 border-dashed border-gray-200 rounded-2xl text-center text-gray-400 hover:border-teal-300 hover:text-primary transition-colors cursor-pointer group"
                                 @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'morning' })">
                                <span class="text-lg group-hover:scale-110 transition-transform">+</span>
                                <span class="text-xs font-medium mt-1">{{ __('Drop or click to add') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Slot 2: MIDDAY (11:00 – 14:00) --}}
                    <div class="flex flex-col bg-gray-50/90 rounded-3xl border border-gray-200/80 p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                            <div class="flex items-center gap-2">
                                <span class="text-base">☀️</span>
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                    {{ __('Midday') }}
                                </h3>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-400">11:00 – 14:00</span>
                        </div>

                        <div
                            class="timeline-slot-container flex-1 min-h-[140px] space-y-2.5 p-1"
                            data-day="{{ $d }}"
                            data-slot="midday"
                        >
                            @php $middayActs = $activitiesGrouped[$d]['midday'] ?? []; @endphp
                            @foreach($middayActs as $act)
                                <x-activity-card :activity="$act" />
                            @endforeach

                            <div class="slot-empty-placeholder {{ count($middayActs) > 0 ? 'hidden' : 'flex' }} flex-col items-center justify-center p-6 border-2 border-dashed border-gray-200 rounded-2xl text-center text-gray-400 hover:border-teal-300 hover:text-primary transition-colors cursor-pointer group"
                                 @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'midday' })">
                                <span class="text-lg group-hover:scale-110 transition-transform">+</span>
                                <span class="text-xs font-medium mt-1">{{ __('Drop or click to add') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Slot 3: AFTERNOON (14:00 – 18:00) --}}
                    <div class="flex flex-col bg-gray-50/90 rounded-3xl border border-gray-200/80 p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🌇</span>
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                    {{ __('Afternoon') }}
                                </h3>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-400">14:00 – 18:00</span>
                        </div>

                        <div
                            class="timeline-slot-container flex-1 min-h-[140px] space-y-2.5 p-1"
                            data-day="{{ $d }}"
                            data-slot="afternoon"
                        >
                            @php $afternoonActs = $activitiesGrouped[$d]['afternoon'] ?? []; @endphp
                            @foreach($afternoonActs as $act)
                                <x-activity-card :activity="$act" />
                            @endforeach

                            <div class="slot-empty-placeholder {{ count($afternoonActs) > 0 ? 'hidden' : 'flex' }} flex-col items-center justify-center p-6 border-2 border-dashed border-gray-200 rounded-2xl text-center text-gray-400 hover:border-teal-300 hover:text-primary transition-colors cursor-pointer group"
                                 @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'afternoon' })">
                                <span class="text-lg group-hover:scale-110 transition-transform">+</span>
                                <span class="text-xs font-medium mt-1">{{ __('Drop or click to add') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Slot 4: EVENING (18:00 – 23:00) --}}
                    <div class="flex flex-col bg-gray-50/90 rounded-3xl border border-gray-200/80 p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🌙</span>
                                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                    {{ __('Evening') }}
                                </h3>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-400">18:00 – 23:00</span>
                        </div>

                        <div
                            class="timeline-slot-container flex-1 min-h-[140px] space-y-2.5 p-1"
                            data-day="{{ $d }}"
                            data-slot="evening"
                        >
                            @php $eveningActs = $activitiesGrouped[$d]['evening'] ?? []; @endphp
                            @foreach($eveningActs as $act)
                                <x-activity-card :activity="$act" />
                            @endforeach

                            <div class="slot-empty-placeholder {{ count($eveningActs) > 0 ? 'hidden' : 'flex' }} flex-col items-center justify-center p-6 border-2 border-dashed border-gray-200 rounded-2xl text-center text-gray-400 hover:border-teal-300 hover:text-primary transition-colors cursor-pointer group"
                                 @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'evening' })">
                                <span class="text-lg group-hover:scale-110 transition-transform">+</span>
                                <span class="text-xs font-medium mt-1">{{ __('Drop or click to add') }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @endfor
    </div>

    {{-- ================================================================
         4. MEMBERS LIST & COLLABORATORS
    ================================================================= --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200/80 p-6 space-y-4">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ __('Trip Members & Roles') }}</h2>
                <p class="text-xs text-gray-500">{{ __('Collaborators and their assigned permission levels.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                    {{ $trip->members->count() }} {{ __('Members') }}
                </span>
                <button
                    type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-member-modal'))"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                >
                    <span>+</span>
                    <span>{{ __('Invite / Manage') }}</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($trip->members as $member)
                <div class="flex items-center justify-between p-3.5 rounded-2xl border border-gray-100 bg-gray-50/60 hover:bg-gray-50 transition-colors">
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

    {{-- ================================================================
         5. PARTIALS & MODALS INCLUSION (D3-03, D3-04, D3-05)
    ================================================================= --}}
    {{-- Activity CRUD Modal --}}
    @include('trips.partials.activity-modal', ['trip' => $trip, 'tripDays' => $tripDays])

    {{-- Room Drawer --}}
    @include('trips.partials.room-drawer', ['trip' => $trip])

    {{-- Live Realtime Budget Widget --}}
    @include('trips.partials.budget-widget', ['trip' => $trip])

    {{-- Budget Override Warning Modal --}}
    @include('trips.partials.budget-override-modal')

    {{-- Checklist Drawer (D4-02) --}}
    @include('trips.partials.checklist-drawer', ['trip' => $trip])

    {{-- Public Share Modal (D4-03) --}}
    @include('trips.partials.share-modal', ['trip' => $trip])

    {{-- Member & Group Mood Modal (Sprint 3 - M8) --}}
    @include('trips.partials.member-modal', ['trip' => $trip])

    {{-- Voting Drawer (Sprint 3 - M8) --}}
    @include('trips.partials.voting-drawer', ['trip' => $trip])

    {{-- Match Engine Modal (Sprint 3 - M9) --}}
    @include('trips.partials.match-engine-modal', ['trip' => $trip])

    {{-- Chat Drawer (Sprint 3 - D3-05) --}}
    <x-chat-drawer :trip="$trip" />

</div>
@endsection
