@extends('layouts.app')

@section('title', $trip->name)
@section('description', __('Interactive itinerary and timeline board for :name', ['name' => $trip->name]))

@php
    $currentUser = auth()->user();
    $membership = $currentUser ? $trip->members->firstWhere('user_id', $currentUser->id) : null;
    $isOwner = $currentUser && ($trip->owner_id === $currentUser->id);
    $userRole = $isOwner ? 'owner' : ($membership ? $membership->role : null);
    $isViewer = ($userRole === 'viewer') || (!$isOwner && !$membership && $trip->privacy === 'public');
    $canEdit = $isOwner || in_array($userRole, ['leader', 'editor']);
@endphp

@section('content')
<div x-data class="w-full max-w-[1280px] mx-auto space-y-6" id="timeline-board" data-trip-id="{{ $trip->id }}" data-can-edit="{{ $canEdit ? 'true' : 'false' }}">

    {{-- ================================================================
         1. BREADCRUMB (Single clean row)
    ================================================================= --}}
    <nav class="flex items-center text-xs sm:text-sm font-medium text-gray-500 py-1" aria-label="Breadcrumb">
        <a href="{{ route('trips.index') }}" class="hover:text-teal-700 transition">
            {{ __('Dashboard') }}
        </a>
        <span class="mx-2 text-gray-300">/</span>
        <span class="text-gray-900 font-semibold truncate">{{ $trip->name }}</span>
    </nav>

    {{-- View-Only Banner for Viewers & Non-editors --}}
    @if(!$canEdit)
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-2xl flex items-center justify-between text-xs sm:text-sm shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="font-bold uppercase tracking-wider text-[11px] bg-amber-200 text-amber-900 px-2 py-0.5 rounded-md">{{ __('View Only') }}</span>
                <span>{{ __('You are viewing this trip in read-only mode. Only Leaders and Editors can add, edit, or delete activities.') }}</span>
            </div>
            <span class="text-[11px] font-semibold text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-full">{{ ucfirst(__($userRole ?? 'Viewer')) }}</span>
        </div>
    @endif

    {{-- ================================================================
         2. TRIP HEADER
    ================================================================= --}}
    <div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm p-4 sm:p-6 space-y-4">
        {{-- Row 1: Title + Badges & Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            {{-- Left: H1 Title & Badges --}}
            <div class="flex flex-wrap items-center gap-2.5 min-w-0">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-gray-900 tracking-tight truncate">
                    {{ $trip->name }}
                </h1>

                {{-- Privacy Badge --}}
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-md
                    @if($trip->privacy === 'public') bg-emerald-50 text-emerald-800 border border-emerald-200
                    @elseif($trip->privacy === 'group') bg-blue-50 text-blue-800 border border-blue-200
                    @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                    {{ ucfirst(__($trip->privacy)) }}
                </span>

                {{-- Status Badge --}}
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-md
                    @if($trip->status === 'completed') bg-gray-100 text-gray-700 border border-gray-200
                    @elseif($trip->status === 'ongoing') bg-teal-50 text-teal-800 border border-teal-200
                    @else bg-amber-50 text-amber-800 border border-amber-200 @endif">
                    {{ ucfirst(__($trip->status)) }}
                </span>
            </div>

            {{-- Right: Actions (Share, Edit, Dropdown Menu) --}}
            <div class="flex items-center gap-2 shrink-0" x-data="{ menuOpen: false }">
                {{-- Primary Action: Share --}}
                <button
                    type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-share-modal'))"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold bg-teal-700 text-white hover:bg-teal-800 transition shadow-sm"
                >
                    {{ __('Share') }}
                </button>

                {{-- Secondary Action: Edit --}}
                @can('update', $trip)
                    <a
                        href="{{ route('trips.edit', $trip) }}"
                        class="px-3.5 py-2 rounded-lg text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 transition"
                    >
                        {{ __('Edit') }}
                    </a>
                @endcan

                {{-- Dropdown Menu "⋯" --}}
                <div class="relative" @click.outside="menuOpen = false">
                    <button
                        type="button"
                        @click="menuOpen = !menuOpen"
                        class="h-9 w-9 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-bold transition flex items-center justify-center tracking-widest select-none"
                        aria-label="{{ __('More options') }}"
                        :aria-expanded="menuOpen"
                    >
                        ⋯
                    </button>

                    <div
                        x-show="menuOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-48 rounded-xl bg-white border border-gray-200 shadow-lg py-1.5 z-50 text-xs sm:text-sm"
                    >
                        @auth
                            <form method="POST" action="{{ route('trips.duplicate', $trip) }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-teal-700 transition font-medium">
                                    {{ __('Duplicate Trip') }}
                                </button>
                            </form>
                        @endauth

                        <a href="{{ route('trips.offline-passport', $trip) }}" class="flex items-center gap-2 px-4 py-2 text-teal-800 hover:bg-teal-50 transition font-semibold">
                            <span>📶</span>
                            <span>{{ __('Offline Passport') }}</span>
                        </a>

                        <a href="{{ route('trips.memory-scrapbook', $trip) }}" class="flex items-center gap-2 px-4 py-2 text-amber-800 hover:bg-amber-50 transition font-semibold">
                            <span>📖</span>
                            <span>{{ __('Memory Scrapbook') }}</span>
                        </a>

                        <a href="{{ route('trips.export-pdf', $trip) }}" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-teal-700 transition font-medium">
                            <span>📄</span>
                            <span>{{ __('Export PDF') }}</span>
                        </a>

                        <a href="{{ route('trips.marketplace.index', $trip) }}" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-teal-700 transition font-medium">
                            <span>🏪</span>
                            <span>{{ __('Marketplace') }}</span>
                        </a>

                        <button
                            type="button"
                            @click="window.dispatchEvent(new CustomEvent('open-settlement-drawer')); menuOpen = false"
                            class="w-full text-left px-4 py-2 text-teal-700 hover:bg-teal-50 transition font-medium"
                        >
                            {{ __('Debt Settlement & VietQR') }}
                        </button>

                        @can('delete', $trip)
                            <div class="border-t border-gray-100 my-1"></div>
                            <form method="POST" action="{{ route('trips.destroy', $trip) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this trip? This action cannot be undone.') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 transition font-medium">
                                    {{ __('Delete Trip') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Location & Compact Cover Banner (max-height 160px, NO globe icon) --}}
        <div class="space-y-2">
            <div class="text-xs sm:text-sm font-semibold text-gray-500">
                <span>{{ __('Location:') }}</span>
                <span class="text-gray-900 font-bold ml-1">{{ $trip->destination }}</span>
            </div>

            <div class="h-28 sm:h-36 max-h-[160px] w-full rounded-xl overflow-hidden relative shadow-inner">
                @if($trip->cover)
                    <img src="{{ asset('storage/' . $trip->cover) }}" alt="{{ $trip->name }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"></div>
                @else
                    <div class="w-full h-full bg-gradient-to-r from-teal-800 via-teal-700 to-emerald-800"></div>
                @endif
            </div>
        </div>

        {{-- Row 3: Stat Strip (4 boxes: 4 cols desktop, 2x2 tablet, 1 col mobile) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
            {{-- Stat 1: Duration --}}
            <div class="bg-gray-50/80 rounded-xl p-3 border border-gray-100">
                <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Duration') }}</span>
                <span class="mt-1 text-sm sm:text-base font-bold text-gray-900 block">
                    {{ $tripDays }} {{ $tripDays === 1 ? __('day') : __('days') }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5 truncate">
                    {{ $trip->start_date ? $trip->start_date->format('M d') : '' }} – {{ $trip->end_date ? $trip->end_date->format('M d, Y') : '' }}
                </span>
            </div>

            {{-- Stat 2: Total Budget --}}
            <div class="bg-gray-50/80 rounded-xl p-3 border border-gray-100">
                <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Total Budget') }}</span>
                <span class="mt-1 text-sm sm:text-base font-bold text-teal-700 block">
                    {{ number_format($trip->budget, 0, ',', '.') }} ₫
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ __('Allocated budget') }}</span>
            </div>

            {{-- Stat 3: Status --}}
            <div class="bg-gray-50/80 rounded-xl p-3 border border-gray-100">
                <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Trip Status') }}</span>
                <span class="mt-1 text-sm sm:text-base font-bold text-gray-900 capitalize block">
                    {{ ucfirst(__($trip->status)) }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ __('Current Phase') }}</span>
            </div>

            {{-- Stat 4: Owner / Leader --}}
            <div class="bg-gray-50/80 rounded-xl p-3 border border-gray-100">
                <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Owner / Leader') }}</span>
                <span class="mt-1 text-sm sm:text-base font-bold text-gray-900 truncate block">
                    {{ $trip->owner->name ?? __('Unknown') }}
                </span>
                <span class="block text-xs text-gray-500 mt-0.5 truncate block">{{ $trip->owner->email ?? '' }}</span>
            </div>
        </div>
    </div>

    {{-- ================================================================
         3. TAB NAVIGATION (Underline style, brand teal accent, no icons)
    ================================================================= --}}
    <nav x-data class="border-b border-gray-200 overflow-x-auto scrollbar-none flex items-center gap-1 sm:gap-2 text-xs sm:text-sm font-medium" aria-label="Trip tabs">
        <button
            type="button"
            class="py-3 px-3.5 sm:px-4 text-teal-700 font-bold border-b-2 border-teal-700 -mb-px shrink-0 transition"
        >
            {{ __('Itinerary') }}
        </button>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-room-drawer'))"
            class="py-3 px-3.5 sm:px-4 text-gray-500 hover:text-teal-700 transition font-medium border-b-2 border-transparent -mb-px shrink-0 flex items-center gap-1.5"
        >
            <span>{{ __('Stay & Rooms') }}</span>
            @if(isset($trip->roomBookings) && $trip->roomBookings->count() > 0)
                <span class="bg-gray-100 text-gray-700 text-[11px] font-semibold px-2 py-0.5 rounded-full">
                    {{ $trip->roomBookings->count() }}
                </span>
            @endif
        </button>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-checklist-drawer'))"
            class="py-3 px-3.5 sm:px-4 text-gray-500 hover:text-teal-700 transition font-medium border-b-2 border-transparent -mb-px shrink-0"
        >
            {{ __('Checklist') }}
        </button>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-member-modal'))"
            class="py-3 px-3.5 sm:px-4 text-gray-500 hover:text-teal-700 transition font-medium border-b-2 border-transparent -mb-px shrink-0"
        >
            {{ __('Members & Mood') }}
        </button>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-voting-drawer'))"
            class="py-3 px-3.5 sm:px-4 text-gray-500 hover:text-teal-700 transition font-medium border-b-2 border-transparent -mb-px shrink-0"
        >
            {{ __('Vote Pool') }}
        </button>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-match-engine-modal'))"
            class="py-3 px-3.5 sm:px-4 text-gray-500 hover:text-teal-700 transition font-medium border-b-2 border-transparent -mb-px shrink-0"
        >
            {{ __('Match Engine') }}
        </button>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-chat-drawer'))"
            onclick="window.dispatchEvent(new CustomEvent('open-chat-drawer'))"
            class="py-3 px-3.5 sm:px-4 text-gray-500 hover:text-teal-700 transition font-medium border-b-2 border-transparent -mb-px shrink-0"
        >
            {{ __('Chat') }}
        </button>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-settlement-drawer'))"
            class="py-3 px-3.5 sm:px-4 text-teal-700 hover:text-teal-900 transition font-bold border-b-2 border-teal-600 -mb-px shrink-0 flex items-center gap-1.5"
            title="{{ __('Min-Cash-Flow Debt Settlement & VietQR') }}"
        >
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ __('Split & VietQR') }}
        </button>
    </nav>

    {{-- ================================================================
         4. 2-COLUMN LAYOUT (Main: 8 cols, Sidebar: 4 cols)
    ================================================================= --}}
    @php
        $startDate = $trip->start_date ? \Carbon\Carbon::parse($trip->start_date) : now();

        $activitiesGrouped = [];
        if (isset($trip->activities)) {
            $sortedActs = $trip->activities->sort(function ($a, $b) {
                if ($a->start_time && $b->start_time) {
                    return strcmp((string) $a->start_time, (string) $b->start_time);
                }
                if ($a->start_time) {
                    return -1;
                }
                if ($b->start_time) {
                    return 1;
                }

                return $a->position <=> $b->position;
            });
            foreach ($sortedActs as $act) {
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
        class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
    >
        {{-- Saving indicator --}}
        <div
            x-show="savingOrder"
            x-cloak
            x-transition
            class="fixed top-20 right-6 z-50 bg-gray-900/90 text-white text-xs px-3.5 py-2 rounded-xl shadow-lg backdrop-blur-md"
        >
            <span>{{ __('Saving order...') }}</span>
        </div>

        {{-- ============================================================
             MAIN COLUMN (8 cols on desktop)
        ============================================================ --}}
        <div class="col-span-1 lg:col-span-8 space-y-6 min-w-0">

            {{-- Compact Live Budget Card for Mobile only (<1024px) --}}
            <div class="block lg:hidden">
                @include('trips.partials.budget-widget', ['trip' => $trip, 'isMobile' => true])
            </div>

            {{-- Day Selector Chips --}}
            <div class="flex items-center justify-between gap-3 overflow-x-auto scrollbar-none pb-1">
                <div class="flex items-center gap-2 shrink-0">
                    @for($d = 1; $d <= max(1, $tripDays); $d++)
                        @php
                            $currentDayDate = (clone $startDate)->addDays($d - 1);
                        @endphp
                        <button
                            type="button"
                            @click="activeDay = {{ $d }}; $nextTick(() => window.initTimeline && window.initTimeline())"
                            class="px-3.5 py-2 rounded-lg text-xs font-semibold transition shrink-0 border"
                            :class="activeDay === {{ $d }}
                                ? 'bg-teal-700 text-white border-teal-700 shadow-sm'
                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                        >
                            Day {{ $d }} · {{ $currentDayDate->format('D d/m') }}
                        </button>
                    @endfor
                </div>

                {{-- Add Activity Button (Only for users with edit permission) --}}
                @if($canEdit)
                    <button
                        type="button"
                        @click="$dispatch('open-activity-modal', { mode: 'create', day: activeDay, time_slot: 'morning' })"
                        class="shrink-0 px-3.5 py-2 rounded-lg text-xs font-semibold bg-teal-700 text-white hover:bg-teal-800 transition shadow-sm"
                    >
                        + {{ __('Add Activity') }}
                    </button>
                @else
                    <span class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200 select-none">
                        {{ __('View Only') }}
                    </span>
                @endif
            </div>

            {{-- Active Day Timeline --}}
            @for($d = 1; $d <= max(1, $tripDays); $d++)
                <div
                    x-show="activeDay === {{ $d }}"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="space-y-6"
                >
                    {{-- Subheader: Day Date + Day Total Cost (No repeated 'Day 1 Itinerary') --}}
                    <div class="flex items-center justify-between text-xs text-gray-500 py-1.5 border-b border-gray-100">
                        <span class="font-medium text-gray-700">{{ (clone $startDate)->addDays($d - 1)->format('l, F d, Y') }}</span>
                        <span>{{ __('Day Total:') }} <strong data-day-total-display="{{ $d }}" class="text-teal-900 font-bold ml-1">0 ₫</strong></span>
                    </div>

                    {{-- Vertical Timeline: 4 Slots --}}
                    <div class="space-y-6">

                        {{-- Slot 1: MORNING (06:00 – 11:00) --}}
                        <div class="flex flex-col md:flex-row md:items-start gap-4">
                            <div class="w-full md:w-36 shrink-0 flex md:flex-col items-center md:items-start justify-between md:justify-start gap-1 pt-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-600 ring-4 ring-teal-50 shrink-0"></span>
                                    <span class="text-xs font-bold text-gray-900 uppercase tracking-wider">{{ __('Morning') }}</span>
                                </div>
                                <span class="text-xs font-medium text-gray-400 pl-4 md:pl-4">06:00 – 11:00</span>
                            </div>

                            <div class="flex-1 min-w-0" x-data="{ expanded: false }">
                                <div
                                    class="timeline-slot-container space-y-3 min-h-[56px]"
                                    data-day="{{ $d }}"
                                    data-slot="morning"
                                >
                                    @php
                                        $morningActs = $activitiesGrouped[$d]['morning'] ?? [];
                                        $countMorning = count($morningActs);
                                    @endphp
                                    @foreach($morningActs as $idx => $act)
                                        <x-activity-card
                                            :activity="$act"
                                            :can-edit="$canEdit"
                                            x-show="expanded || {{ $idx < 2 ? 'true' : 'false' }}"
                                            x-cloak="{{ $idx >= 2 ? 'true' : 'false' }}"
                                        />
                                    @endforeach

                                    @if($canEdit)
                                        <div
                                            class="slot-empty-placeholder {{ $countMorning > 0 ? 'hidden' : 'flex' }} items-center justify-center h-12 border border-dashed border-gray-300 rounded-xl text-xs font-medium text-gray-500 hover:border-teal-600 hover:text-teal-700 hover:bg-teal-50/20 transition cursor-pointer"
                                            @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'morning' })"
                                        >
                                            <span>+ {{ __('Add Activity') }}</span>
                                        </div>
                                    @else
                                        <div
                                            class="slot-empty-placeholder {{ $countMorning > 0 ? 'hidden' : 'flex' }} items-center justify-center h-10 border border-dashed border-gray-200 rounded-xl text-xs text-gray-400 select-none"
                                        >
                                            <span>{{ __('No activities scheduled') }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Show All / Hide Button & Quick Add --}}
                                @if($countMorning > 0)
                                    <div class="mt-2.5 flex items-center justify-between gap-2 flex-wrap">
                                        @if($countMorning > 2)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200/80 transition"
                                            >
                                                <span x-show="!expanded">{{ __('Show all (:count activities)', ['count' => $countMorning]) }} &darr;</span>
                                                <span x-show="expanded" x-cloak>{{ __('Hide (Show 2 only)') }} &uarr;</span>
                                            </button>
                                        @else
                                            <div></div>
                                        @endif

                                        @if($canEdit)
                                            <button
                                                type="button"
                                                @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'morning' })"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-900 hover:underline py-1 px-2 rounded-md hover:bg-teal-50/50 transition"
                                            >
                                                <span>+ {{ __('Add Activity') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Slot 2: MIDDAY (11:00 – 14:00) --}}
                        <div class="flex flex-col md:flex-row md:items-start gap-4">
                            <div class="w-full md:w-36 shrink-0 flex md:flex-col items-center md:items-start justify-between md:justify-start gap-1 pt-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-600 ring-4 ring-teal-50 shrink-0"></span>
                                    <span class="text-xs font-bold text-gray-900 uppercase tracking-wider">{{ __('Midday') }}</span>
                                </div>
                                <span class="text-xs font-medium text-gray-400 pl-4 md:pl-4">11:00 – 14:00</span>
                            </div>

                            <div class="flex-1 min-w-0" x-data="{ expanded: false }">
                                <div
                                    class="timeline-slot-container space-y-3 min-h-[56px]"
                                    data-day="{{ $d }}"
                                    data-slot="midday"
                                >
                                    @php
                                        $middayActs = $activitiesGrouped[$d]['midday'] ?? [];
                                        $countMidday = count($middayActs);
                                    @endphp
                                    @foreach($middayActs as $idx => $act)
                                        <x-activity-card
                                            :activity="$act"
                                            :can-edit="$canEdit"
                                            x-show="expanded || {{ $idx < 2 ? 'true' : 'false' }}"
                                            x-cloak="{{ $idx >= 2 ? 'true' : 'false' }}"
                                        />
                                    @endforeach

                                    @if($canEdit)
                                        <div
                                            class="slot-empty-placeholder {{ $countMidday > 0 ? 'hidden' : 'flex' }} items-center justify-center h-12 border border-dashed border-gray-300 rounded-xl text-xs font-medium text-gray-500 hover:border-teal-600 hover:text-teal-700 hover:bg-teal-50/20 transition cursor-pointer"
                                            @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'midday' })"
                                        >
                                            <span>+ {{ __('Add Activity') }}</span>
                                        </div>
                                    @else
                                        <div
                                            class="slot-empty-placeholder {{ $countMidday > 0 ? 'hidden' : 'flex' }} items-center justify-center h-10 border border-dashed border-gray-200 rounded-xl text-xs text-gray-400 select-none"
                                        >
                                            <span>{{ __('No activities scheduled') }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Show All / Hide Button & Quick Add --}}
                                @if($countMidday > 0)
                                    <div class="mt-2.5 flex items-center justify-between gap-2 flex-wrap">
                                        @if($countMidday > 2)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200/80 transition"
                                            >
                                                <span x-show="!expanded">{{ __('Show all (:count activities)', ['count' => $countMidday]) }} &darr;</span>
                                                <span x-show="expanded" x-cloak>{{ __('Hide (Show 2 only)') }} &uarr;</span>
                                            </button>
                                        @else
                                            <div></div>
                                        @endif

                                        @if($canEdit)
                                            <button
                                                type="button"
                                                @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'midday' })"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-900 hover:underline py-1 px-2 rounded-md hover:bg-teal-50/50 transition"
                                            >
                                                <span>+ {{ __('Add Activity') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Slot 3: AFTERNOON (14:00 – 18:00) --}}
                        <div class="flex flex-col md:flex-row md:items-start gap-4">
                            <div class="w-full md:w-36 shrink-0 flex md:flex-col items-center md:items-start justify-between md:justify-start gap-1 pt-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-600 ring-4 ring-teal-50 shrink-0"></span>
                                    <span class="text-xs font-bold text-gray-900 uppercase tracking-wider">{{ __('Afternoon') }}</span>
                                </div>
                                <span class="text-xs font-medium text-gray-400 pl-4 md:pl-4">14:00 – 18:00</span>
                            </div>

                            <div class="flex-1 min-w-0" x-data="{ expanded: false }">
                                <div
                                    class="timeline-slot-container space-y-3 min-h-[56px]"
                                    data-day="{{ $d }}"
                                    data-slot="afternoon"
                                >
                                    @php
                                        $afternoonActs = $activitiesGrouped[$d]['afternoon'] ?? [];
                                        $countAfternoon = count($afternoonActs);
                                    @endphp
                                    @foreach($afternoonActs as $idx => $act)
                                        <x-activity-card
                                            :activity="$act"
                                            :can-edit="$canEdit"
                                            x-show="expanded || {{ $idx < 2 ? 'true' : 'false' }}"
                                            x-cloak="{{ $idx >= 2 ? 'true' : 'false' }}"
                                        />
                                    @endforeach

                                    @if($canEdit)
                                        <div
                                            class="slot-empty-placeholder {{ $countAfternoon > 0 ? 'hidden' : 'flex' }} items-center justify-center h-12 border border-dashed border-gray-300 rounded-xl text-xs font-medium text-gray-500 hover:border-teal-600 hover:text-teal-700 hover:bg-teal-50/20 transition cursor-pointer"
                                            @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'afternoon' })"
                                        >
                                            <span>+ {{ __('Add Activity') }}</span>
                                        </div>
                                    @else
                                        <div
                                            class="slot-empty-placeholder {{ $countAfternoon > 0 ? 'hidden' : 'flex' }} items-center justify-center h-10 border border-dashed border-gray-200 rounded-xl text-xs text-gray-400 select-none"
                                        >
                                            <span>{{ __('No activities scheduled') }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Show All / Hide Button & Quick Add --}}
                                @if($countAfternoon > 0)
                                    <div class="mt-2.5 flex items-center justify-between gap-2 flex-wrap">
                                        @if($countAfternoon > 2)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200/80 transition"
                                            >
                                                <span x-show="!expanded">{{ __('Show all (:count activities)', ['count' => $countAfternoon]) }} &darr;</span>
                                                <span x-show="expanded" x-cloak>{{ __('Hide (Show 2 only)') }} &uarr;</span>
                                            </button>
                                        @else
                                            <div></div>
                                        @endif

                                        @if($canEdit)
                                            <button
                                                type="button"
                                                @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'afternoon' })"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-900 hover:underline py-1 px-2 rounded-md hover:bg-teal-50/50 transition"
                                            >
                                                <span>+ {{ __('Add Activity') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Slot 4: EVENING (18:00 – 23:00) --}}
                        <div class="flex flex-col md:flex-row md:items-start gap-4">
                            <div class="w-full md:w-36 shrink-0 flex md:flex-col items-center md:items-start justify-between md:justify-start gap-1 pt-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-600 ring-4 ring-teal-50 shrink-0"></span>
                                    <span class="text-xs font-bold text-gray-900 uppercase tracking-wider">{{ __('Evening') }}</span>
                                </div>
                                <span class="text-xs font-medium text-gray-400 pl-4 md:pl-4">18:00 – 23:00</span>
                            </div>

                            <div class="flex-1 min-w-0" x-data="{ expanded: false }">
                                <div
                                    class="timeline-slot-container space-y-3 min-h-[56px]"
                                    data-day="{{ $d }}"
                                    data-slot="evening"
                                >
                                    @php
                                        $eveningActs = $activitiesGrouped[$d]['evening'] ?? [];
                                        $countEvening = count($eveningActs);
                                    @endphp
                                    @foreach($eveningActs as $idx => $act)
                                        <x-activity-card
                                            :activity="$act"
                                            :can-edit="$canEdit"
                                            x-show="expanded || {{ $idx < 2 ? 'true' : 'false' }}"
                                            x-cloak="{{ $idx >= 2 ? 'true' : 'false' }}"
                                        />
                                    @endforeach

                                    @if($canEdit)
                                        <div
                                            class="slot-empty-placeholder {{ $countEvening > 0 ? 'hidden' : 'flex' }} items-center justify-center h-12 border border-dashed border-gray-300 rounded-xl text-xs font-medium text-gray-500 hover:border-teal-600 hover:text-teal-700 hover:bg-teal-50/20 transition cursor-pointer"
                                            @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'evening' })"
                                        >
                                            <span>+ {{ __('Add Activity') }}</span>
                                        </div>
                                    @else
                                        <div
                                            class="slot-empty-placeholder {{ $countEvening > 0 ? 'hidden' : 'flex' }} items-center justify-center h-10 border border-dashed border-gray-200 rounded-xl text-xs text-gray-400 select-none"
                                        >
                                            <span>{{ __('No activities scheduled') }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Show All / Hide Button & Quick Add --}}
                                @if($countEvening > 0)
                                    <div class="mt-2.5 flex items-center justify-between gap-2 flex-wrap">
                                        @if($countEvening > 2)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200/80 transition"
                                            >
                                                <span x-show="!expanded">{{ __('Show all (:count activities)', ['count' => $countEvening]) }} &darr;</span>
                                                <span x-show="expanded" x-cloak>{{ __('Hide (Show 2 only)') }} &uarr;</span>
                                            </button>
                                        @else
                                            <div></div>
                                        @endif

                                        @if($canEdit)
                                            <button
                                                type="button"
                                                @click="$dispatch('open-activity-modal', { mode: 'create', day: {{ $d }}, time_slot: 'evening' })"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-900 hover:underline py-1 px-2 rounded-md hover:bg-teal-50/50 transition"
                                            >
                                                <span>+ {{ __('Add Activity') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            @endfor
        </div>

        {{-- ============================================================
             SIDEBAR COLUMN (4 cols on desktop, sticky)
        ============================================================ --}}
        <div class="col-span-1 lg:col-span-4 space-y-6 min-w-0 lg:sticky lg:top-20">

            {{-- Live Budget Card (Desktop flow) --}}
            <div class="hidden lg:block">
                @include('trips.partials.budget-widget', ['trip' => $trip, 'isMobile' => false])
            </div>

            {{-- Trip Members Card (Replaces huge section at bottom) --}}
            @include('trips.partials.members-card', ['trip' => $trip])

        </div>
    </div>

    {{-- ================================================================
         FLOATING ACTION BUBBLES (Quick Actions: Marketplace & Trip Chat)
         Vertical stack on right side of screen - accessible without scrolling
    ================================================================= --}}
    <div x-data class="fixed right-5 sm:right-6 bottom-6 sm:bottom-8 z-40 flex flex-col items-end gap-3.5 select-none pointer-events-auto">
        {{-- 1. Marketplace Bubble --}}
        <a
            href="{{ route('trips.marketplace.index', $trip) }}"
            class="group relative flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-white text-teal-700 shadow-lg shadow-teal-950/10 border border-teal-100 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800 hover:shadow-xl hover:shadow-teal-950/20 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 focus:outline-none cursor-pointer"
            title="{{ __('Marketplace') }}"
            aria-label="{{ __('Marketplace') }}"
        >
            {{-- Marketplace Storefront SVG Icon --}}
            <svg class="w-5 h-5 sm:w-6 sm:h-6 pointer-events-none transition-transform duration-200 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>

            {{-- Floating Tooltip Pill (Left Side) --}}
            <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-xl bg-gray-900/90 text-white text-xs font-semibold whitespace-nowrap shadow-xl backdrop-blur-md opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none translate-x-2 group-hover:translate-x-0 flex items-center gap-1.5">
                <span>🏪</span>
                <span>{{ __('Marketplace') }}</span>
            </span>
        </a>

        {{-- 2. Trip Chat Bubble --}}
        <button
            type="button"
            id="trip-chat-floating-btn"
            @click="window.dispatchEvent(new CustomEvent('open-chat-drawer'))"
            onclick="window.dispatchEvent(new CustomEvent('open-chat-drawer'))"
            class="group relative flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-tr from-teal-800 via-teal-600 to-emerald-500 text-white shadow-xl shadow-teal-900/30 hover:shadow-2xl hover:shadow-teal-900/40 hover:-translate-y-0.5 active:translate-y-0 hover:brightness-105 transition-all duration-200 border-2 border-white/25 focus:outline-none cursor-pointer"
            title="{{ __('Trip Chat') }}"
            aria-label="{{ __('Trip Chat') }}"
        >
            {{-- Chat Bubble SVG Icon --}}
            <svg class="w-6 h-6 sm:w-6.5 sm:h-6.5 pointer-events-none transition-transform duration-200 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>

            {{-- Pulsing Indicator / Ping effect --}}
            <span class="absolute top-0 right-0 -mt-0.5 -mr-0.5 flex h-3.5 w-3.5 pointer-events-none">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-400 border-2 border-white"></span>
            </span>

            {{-- Floating Tooltip Pill (Left Side) --}}
            <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-xl bg-gray-900/90 text-white text-xs font-semibold whitespace-nowrap shadow-xl backdrop-blur-md opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none translate-x-2 group-hover:translate-x-0 flex items-center gap-1.5">
                <span>💬</span>
                <span>{{ __('Trip Chat') }}</span>
            </span>
        </button>
    </div>

    {{-- ================================================================
         5. MODALS & DRAWERS (Preserved 100% logic and functionality)
    ================================================================= --}}
    @include('trips.partials.activity-modal', ['trip' => $trip, 'tripDays' => $tripDays, 'canEdit' => $canEdit, 'allActivities' => $trip->activities])
    @include('trips.partials.room-drawer', ['trip' => $trip, 'canEdit' => $canEdit])
    @include('trips.partials.budget-override-modal')
    @include('trips.partials.checklist-drawer', ['trip' => $trip, 'canEdit' => $canEdit])
    @include('trips.partials.share-modal', ['trip' => $trip])
    @include('trips.partials.member-modal', ['trip' => $trip, 'canEdit' => $canEdit])
    @include('trips.partials.voting-drawer', ['trip' => $trip])
    @include('trips.partials.match-engine-modal', ['trip' => $trip])
    <x-chat-drawer :trip="$trip" />
    @include('trips.partials.split-bill-modal', ['trip' => $trip])
    @include('trips.partials.settlement-drawer', ['trip' => $trip])

    {{-- Global Activity Delete Listener with Permission Check & Notifications --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('delete-activity', async (e) => {
                const { id, title } = e.detail || {};
                if (!id) return;

                const timelineBoard = document.getElementById('timeline-board');
                const canEdit = timelineBoard?.dataset?.canEdit !== 'false';

                if (!canEdit) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            type: 'error',
                            message: '{{ __('Permission denied: You have view-only access and cannot delete activities.') }}'
                        }
                    }));
                    return;
                }

                if (!confirm('{{ __('Are you sure you want to delete this activity?') }}' + (title ? ` "${title}"?` : ''))) {
                    return;
                }

                const tripId = timelineBoard?.dataset?.tripId;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const res = await fetch(`/ajax/trips/${tripId}/activities/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (res.status === 403) {
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: {
                                type: 'error',
                                message: '{{ __('Permission denied: You have view-only access and cannot delete activities.') }}'
                            }
                        }));
                        return;
                    }

                    if (!res.ok) {
                        throw new Error('Delete failed');
                    }

                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            type: 'success',
                            message: '{{ __('Activity removed successfully.') }}'
                        }
                    }));

                    const cardEl = document.querySelector(`.timeline-activity-card[data-id="${id}"]`);
                    if (cardEl) {
                        const container = cardEl.closest('.timeline-slot-container');
                        cardEl.remove();
                        if (container) {
                            const remaining = container.querySelectorAll('.timeline-activity-card').length;
                            const emptyEl = container.querySelector('.slot-empty-placeholder');
                            if (emptyEl) {
                                emptyEl.style.display = remaining > 0 ? 'none' : 'flex';
                            }
                        }
                    }

                    window.dispatchEvent(new CustomEvent('budget-recalculate'));
                } catch (err) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            type: 'error',
                            message: '{{ __('Could not delete activity.') }}'
                        }
                    }));
                }
            });
        });
    </script>

</div>
@endsection
