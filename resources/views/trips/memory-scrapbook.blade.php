@extends('layouts.app')

@section('title', __('Memory Scrapbook — :name', ['name' => $trip->name]))
@section('description', __('Relive your best travel moments, photo timeline, and journey milestones.'))

@section('content')
<div class="max-w-6xl mx-auto space-y-8 pb-20" x-data="{
    shareModalOpen: false,
    selectedPhoto: null
}">

    {{-- ================================================================
         1. BREADCRUMB & TOP ACTIONS
    ================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200">
        <nav class="flex items-center text-xs sm:text-sm font-medium text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('trips.index') }}" class="hover:text-teal-700 transition">{{ __('Dashboard') }}</a>
            <span class="mx-2 text-gray-300">/</span>
            <a href="{{ route('trips.show', $trip) }}" class="hover:text-teal-700 transition truncate max-w-[200px]">{{ $trip->name }}</a>
            <span class="mx-2 text-gray-300">/</span>
            <span class="text-gray-900 font-semibold">{{ __('Reverse Memory Scrapbook') }}</span>
        </nav>

        <div class="flex items-center gap-2.5 flex-wrap">
            {{-- Share to Community Feed Button --}}
            <button
                type="button"
                @click="shareModalOpen = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white text-xs font-bold shadow-md shadow-teal-900/20 hover:scale-105 active:scale-95 transition"
            >
                <span>📸</span>
                <span>{{ __('Share to Community Feed') }}</span>
            </button>

            {{-- Print / Save Album --}}
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold border border-gray-300 shadow-xs transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>{{ __('Print Album') }}</span>
            </button>
        </div>
    </div>


    {{-- ================================================================
         2. VINTAGE SCRAPBOOK ALBUM COVER
    ================================================================= --}}
    <div class="rounded-3xl overflow-hidden bg-gradient-to-br from-amber-900 via-stone-900 to-teal-950 text-white p-8 sm:p-12 shadow-2xl relative border-4 border-amber-900/30">
        <div class="absolute -right-12 -bottom-12 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl space-y-4">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-500/20 border border-amber-400/30 text-amber-300 text-xs font-extrabold uppercase tracking-widest">
                <span>📖</span>
                <span>{{ __('Travel Diary & Photo Scrapbook') }}</span>
            </div>

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white leading-tight">
                {{ $trip->name }}
            </h1>

            <p class="text-sm sm:text-base text-amber-100/90 leading-relaxed">
                {{ __('A living retrospective album automatically woven from your group chat captures and completed journey milestones across :dest.', ['dest' => $trip->destination]) }}
            </p>

            {{-- Stat Pills --}}
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <span class="px-3.5 py-1.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-white flex items-center gap-1.5">
                    <span>🗓️</span> {{ $scrapbook['stats']['duration_days'] }} {{ __('Days') }} ({{ $scrapbook['trip']['date_range_formatted'] }})
                </span>
                <span class="px-3.5 py-1.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-white flex items-center gap-1.5">
                    <span>📷</span> {{ $scrapbook['stats']['total_photos'] }} {{ __('Photos Captured') }}
                </span>
                <span class="px-3.5 py-1.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-white flex items-center gap-1.5">
                    <span>⭐</span> {{ $scrapbook['stats']['total_milestones'] }} {{ __('Milestones') }}
                </span>
                <span class="px-3.5 py-1.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-white flex items-center gap-1.5">
                    <span>👥</span> {{ $scrapbook['stats']['participants_count'] }} {{ __('Travelers') }}
                </span>
            </div>
        </div>
    </div>


    {{-- ================================================================
         3. CHRONOLOGICAL SCRAPBOOK TIMELINE (DAY 1 ➔ DAY N)
    ================================================================= --}}
    <div class="space-y-12">
        @foreach($scrapbook['days'] as $day)
            <div class="space-y-6">
                {{-- Day Banner Header --}}
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-800 text-amber-100 flex items-center justify-center font-black text-lg shadow-md shrink-0 border border-amber-700">
                        D{{ $day['day'] }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl sm:text-2xl font-black text-gray-900">{{ __('Day :day', ['day' => $day['day']]) }}: {{ $day['theme'] }}</h2>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-500 font-medium mt-0.5">{{ $day['date_formatted'] }}</p>
                    </div>
                    <div class="h-0.5 flex-1 bg-gradient-to-r from-amber-200 to-transparent ml-4 hidden sm:block"></div>
                </div>

                {{-- Memories Grid (Polaroids & Milestones) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($day['memories'] as $memory)
                        @if($memory['type'] === 'chat_photo')
                            {{-- Polaroid Card --}}
                            <div
                                @click="selectedPhoto = '{{ $memory['image_url'] }}'"
                                class="group bg-white p-4 pb-6 rounded-2xl shadow-md border border-gray-200/80 hover:shadow-2xl hover:-translate-y-1 hover:rotate-1 transition-all duration-300 cursor-pointer flex flex-col justify-between"
                            >
                                <div class="relative aspect-4/3 rounded-xl overflow-hidden bg-gray-100 border border-gray-100">
                                    <img
                                        src="{{ $memory['image_url'] }}"
                                        alt="{{ $memory['caption'] }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy"
                                    />
                                    <span class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                        {{ $memory['time_formatted'] }}
                                    </span>
                                    @if(!empty($memory['is_curated']))
                                        <span class="absolute top-2 left-2 bg-amber-600/90 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            ✨ {{ __('Moments') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="pt-3 space-y-1.5">
                                    <p class="text-xs text-gray-800 font-medium leading-relaxed italic line-clamp-2">
                                        "{{ $memory['caption'] }}"
                                    </p>
                                    <div class="flex items-center justify-between text-[11px] text-gray-500 pt-1 border-t border-gray-100">
                                        <span class="font-bold text-gray-700">📸 {{ $memory['author_name'] }}</span>
                                        <span class="text-teal-700 font-bold group-hover:underline">{{ __('View Full') }} &rarr;</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Milestone Card --}}
                            <div class="bg-gradient-to-br from-amber-50/70 via-stone-50 to-white p-5 rounded-2xl border border-amber-200/70 shadow-sm flex flex-col justify-between space-y-3">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-amber-200/80 text-amber-900">
                                            {{ $memory['time_slot'] }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-700">{{ $memory['cost_formatted'] }}</span>
                                    </div>
                                    <h4 class="text-sm font-extrabold text-gray-900 leading-snug">{{ $memory['title'] }}</h4>
                                    @if($memory['location'])
                                        <p class="text-xs text-gray-500 flex items-center gap-1">
                                            <span>📍</span>
                                            <span>{{ $memory['location'] }}</span>
                                        </p>
                                    @endif
                                    @if($memory['notes'])
                                        <p class="text-xs text-gray-600 bg-white/80 p-2.5 rounded-xl border border-amber-100 italic">
                                            {{ $memory['notes'] }}
                                        </p>
                                    @endif
                                </div>

                                <div class="pt-2 border-t border-amber-200/50 flex items-center gap-1.5 text-[11px] text-amber-800 font-bold">
                                    <span>✅</span>
                                    <span>{{ __('Journey Milestone Completed') }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>


    {{-- ================================================================
         4. LIGHTBOX MODAL FOR PHOTO EXPANSION
    ================================================================= --}}
    <div
        x-show="selectedPhoto"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md"
        @click.self="selectedPhoto = null"
        @keydown.escape.window="selectedPhoto = null"
    >
        <div class="relative max-w-4xl max-h-[90vh] bg-white rounded-3xl overflow-hidden shadow-2xl p-2">
            <button
                type="button"
                @click="selectedPhoto = null"
                class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-black/60 hover:bg-black text-white flex items-center justify-center text-lg transition"
            >
                &times;
            </button>
            <img :src="selectedPhoto" alt="Full size photo" class="max-h-[82vh] w-auto mx-auto rounded-2xl object-contain"/>
        </div>
    </div>


    {{-- ================================================================
         5. SHARE TO COMMUNITY FEED MODAL
    ================================================================= --}}
    <div
        x-show="shareModalOpen"
        x-transition
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        @click.self="shareModalOpen = false"
        @keydown.escape.window="shareModalOpen = false"
    >
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-gray-200">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">📸</span>
                    <h3 class="text-lg font-black text-gray-900">{{ __('Share Album to Community') }}</h3>
                </div>
                <button type="button" @click="shareModalOpen = false" class="text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('social.posts.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="trip_id" value="{{ $trip->id }}">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Post Title') }}</label>
                    <input
                        type="text"
                        name="title"
                        required
                        value="{{ __('Unforgettable moments from our journey to :dest!', ['dest' => $trip->destination]) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Memory Highlights') }}</label>
                    <textarea
                        name="content"
                        rows="4"
                        required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    >{{ __('Just wrapped up an incredible :days-day journey across :dest with Wanderly. Checked off :milestones milestones and captured unforgettable memories!', ['days' => $scrapbook['stats']['duration_days'], 'dest' => $trip->destination, 'milestones' => $scrapbook['stats']['total_milestones']]) }}</textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="shareModalOpen = false"
                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-bold text-xs hover:bg-gray-50 transition"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold text-xs shadow-md transition"
                    >
                        🚀 {{ __('Publish to Social Feed') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
