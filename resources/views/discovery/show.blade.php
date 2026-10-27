@extends('layouts.app')

@section('title', $article->title . ' — ' . __('Vietnam Discovery'))
@section('description', $article->summary ?: Str::limit(strip_tags($article->content), 160))

@section('content')
<div class="max-w-5xl mx-auto space-y-8 pb-20" x-data="{
    addTripModalOpen: false,
    selectedTripId: '{{ $userTrips->first()->id ?? '' }}',
    selectedDay: 1,
    selectedSlot: 'morning',
    isSubmitting: false,
    successMessage: null
}">

    {{-- ================================================================
         1. BREADCRUMB NAVIGATION
    ================================================================= --}}
    <nav class="flex items-center text-xs sm:text-sm font-medium text-gray-500 py-1" aria-label="Breadcrumb">
        <a href="{{ route('discovery.index') }}" class="hover:text-teal-700 transition">{{ __('Vietnam Discovery') }}</a>
        <span class="mx-2 text-gray-300">/</span>
        <a href="{{ route('discovery.index', ['region' => $article->region?->slug]) }}" class="hover:text-teal-700 transition">
            {{ $article->region?->name ?? __('Region') }}
        </a>
        <span class="mx-2 text-gray-300">/</span>
        <span class="text-gray-900 font-semibold truncate">{{ $article->title }}</span>
    </nav>


    {{-- Success Message Alert --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-teal-50 border border-teal-200 text-teal-900 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif


    {{-- ================================================================
         2. HERO ARTICLE HEADER
    ================================================================= --}}
    <div class="rounded-3xl overflow-hidden bg-gray-900 text-white relative shadow-2xl min-h-[360px] sm:min-h-[460px] flex items-end">
        <img
            src="{{ asset($article->featured_image) }}"
            alt="{{ $article->title }}"
            class="absolute inset-0 w-full h-full object-cover object-center"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-900/60 to-transparent"></div>

        <div class="relative z-10 p-6 sm:p-10 space-y-4 max-w-4xl">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-[11px] uppercase font-bold tracking-wider px-3 py-1 rounded-full border shadow-xs backdrop-blur-md {{ $article->category_color_class }}">
                    {{ $article->category_label }}
                </span>
                <span class="bg-black/40 backdrop-blur-md text-white text-[11px] font-bold px-3 py-1 rounded-full border border-white/20">
                    📍 {{ $article->location_name ?: $article->region?->name }}
                </span>
                <span class="bg-black/40 backdrop-blur-md text-white text-[11px] font-medium px-3 py-1 rounded-full border border-white/20">
                    👁️ {{ number_format($article->views_count) }} {{ __('views') }}
                </span>
            </div>

            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight">
                {{ $article->title }}
            </h1>

            @if($article->summary)
                <p class="text-sm sm:text-base text-gray-200 leading-relaxed font-normal max-w-3xl">
                    {{ $article->summary }}
                </p>
            @endif
        </div>
    </div>


    {{-- ================================================================
         3. 1-CLICK "ADD TO CURRENT TRIP" PROMINENT ACTION CARD
    ================================================================= --}}
    <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-900 text-white p-6 sm:p-8 rounded-3xl shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6 border border-teal-700/50">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-emerald-300">
                <span>⚡</span>
                <span>{{ __('1-Click Trip Integration') }}</span>
            </div>
            <h3 class="text-lg sm:text-xl font-black text-white">
                {{ __('Want to experience this on your upcoming journey?') }}
            </h3>
            <p class="text-xs sm:text-sm text-gray-200">
                {{ __('Instantly add :title into your itinerary timeline without manual data entry.', ['title' => $article->title]) }}
            </p>
        </div>

        <div class="shrink-0 flex items-center gap-3">
            @auth
                @if($userTrips->count() > 0)
                    <button
                        type="button"
                        @click="addTripModalOpen = true"
                        class="px-6 py-3.5 rounded-2xl bg-white text-teal-900 font-extrabold text-xs sm:text-sm shadow-xl hover:bg-gray-100 hover:scale-105 active:scale-95 transition flex items-center gap-2"
                    >
                        <span>➕</span>
                        <span>{{ __('Add to Current Trip') }}</span>
                    </button>
                @else
                    <a
                        href="{{ route('trips.create', ['destination' => $article->destination?->name ?? 'Vietnam']) }}"
                        class="px-6 py-3.5 rounded-2xl bg-white text-teal-900 font-extrabold text-xs sm:text-sm shadow-xl hover:bg-gray-100 hover:scale-105 active:scale-95 transition flex items-center gap-2"
                    >
                        <span>🗺️</span>
                        <span>{{ __('Create Trip with this Spot') }}</span>
                    </a>
                @endif
            @else
                <a
                    href="{{ route('login') }}"
                    class="px-6 py-3.5 rounded-2xl bg-white text-teal-900 font-extrabold text-xs sm:text-sm shadow-xl hover:bg-gray-100 hover:scale-105 active:scale-95 transition flex items-center gap-2"
                >
                    <span>🚀</span>
                    <span>{{ __('Sign In to Add to Trip') }}</span>
                </a>
            @endauth
        </div>
    </div>


    {{-- ================================================================
         4. ARTICLE DETAILS & SIDEBAR METADATA
    ================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        {{-- Left 2 Columns: Main Content, Highlights & Gastronomy --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Key Highlights --}}
            @if(!empty($article->highlights))
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-200/90 shadow-sm space-y-4">
                    <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                        <span>⭐</span>
                        <span>{{ __('Key Highlights & Experiences') }}</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($article->highlights as $hl)
                            <div class="flex items-start gap-2.5 p-3 rounded-2xl bg-teal-50/60 border border-teal-100 text-xs text-teal-950 font-medium">
                                <span class="text-teal-700 font-black">✓</span>
                                <span>{{ $hl }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Main Body Content --}}
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-200/90 shadow-sm space-y-6">
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>📖</span>
                    <span>{{ __('About this Destination') }}</span>
                </h2>

                <div class="prose max-w-none text-gray-700 leading-relaxed text-sm space-y-4">
                    {!! nl2br(e($article->content)) !!}
                </div>
            </div>

            {{-- Specialties & Gastronomy --}}
            @if(!empty($article->specialties))
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-200/90 shadow-sm space-y-4">
                    <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                        <span>🍲</span>
                        <span>{{ __('Local Gastronomy & Specialties') }}</span>
                    </h2>

                    <div class="flex flex-wrap gap-2">
                        @foreach($article->specialties as $sp)
                            <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                                <span>🥢</span>
                                <span>{{ $sp }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        {{-- Right 1 Column: Quick Travel Factsheet --}}
        <div class="space-y-6 lg:sticky lg:top-24">
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm space-y-4 text-xs">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                    {{ __('Travel Factsheet') }}
                </h3>

                <div class="space-y-3">
                    <div>
                        <span class="text-gray-400 block font-medium">{{ __('Region') }}</span>
                        <span class="font-extrabold text-gray-900 text-sm">{{ $article->region?->name ?? 'Vietnam' }}</span>
                    </div>

                    @if($article->best_season)
                        <div>
                            <span class="text-gray-400 block font-medium">{{ __('Best Season to Visit') }}</span>
                            <span class="font-bold text-gray-800">{{ $article->best_season }}</span>
                        </div>
                    @endif

                    @if($article->estimated_duration)
                        <div>
                            <span class="text-gray-400 block font-medium">{{ __('Recommended Stay') }}</span>
                            <span class="font-bold text-gray-800">{{ $article->estimated_duration }}</span>
                        </div>
                    @endif

                    <div>
                        <span class="text-gray-400 block font-medium">{{ __('Category') }}</span>
                        <span class="font-bold text-gray-800">{{ $article->category_label }}</span>
                    </div>
                </div>
            </div>

            {{-- Related Destination Articles --}}
            @if($relatedArticles->count() > 0)
                <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm space-y-4">
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                        {{ __('More in :region', ['region' => $article->region?->name]) }}
                    </h3>

                    <div class="space-y-3">
                        @foreach($relatedArticles as $rel)
                            <a href="{{ route('discovery.show', $rel->slug) }}" class="group flex items-center gap-3 hover:bg-gray-50 p-2 rounded-2xl transition">
                                <img src="{{ asset($rel->featured_image) }}" alt="{{ $rel->title }}" class="w-14 h-14 rounded-xl object-cover shrink-0"/>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-gray-900 group-hover:text-teal-700 truncate leading-snug">{{ $rel->title }}</h4>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $rel->category_label }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>


    {{-- ================================================================
         5. MODAL: 1-CLICK "ADD TO CURRENT TRIP"
    ================================================================= --}}
    @auth
        <div
            x-show="addTripModalOpen"
            x-transition
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="addTripModalOpen = false"
            @keydown.escape.window="addTripModalOpen = false"
        >
            <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-gray-200">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">➕</span>
                        <h3 class="text-base font-black text-gray-900">{{ __('Add to Trip Timeline') }}</h3>
                    </div>
                    <button type="button" @click="addTripModalOpen = false" class="text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
                </div>

                <form method="POST" action="{{ route('discovery.add-to-trip', $article) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            {{ __('Select Your Trip') }}
                        </label>
                        <select
                            name="trip_id"
                            required
                            x-model="selectedTripId"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                        >
                            @foreach($userTrips as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }} ({{ $t->destination }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Day Number') }}
                            </label>
                            <input
                                type="number"
                                name="day"
                                min="1"
                                max="60"
                                value="1"
                                required
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Time Slot') }}
                            </label>
                            <select
                                name="time_slot"
                                required
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                            >
                                <option value="morning">{{ __('Morning') }}</option>
                                <option value="midday">{{ __('Midday') }}</option>
                                <option value="afternoon">{{ __('Afternoon') }}</option>
                                <option value="evening">{{ __('Evening') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 rounded-xl text-xs text-gray-600 border border-gray-100">
                        <span class="font-bold text-gray-900">{{ __('Activity Title:') }}</span>
                        <span>Explore: {{ $article->title }}</span>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="addTripModalOpen = false"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-bold text-xs hover:bg-gray-50 transition"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold text-xs shadow-md transition"
                        >
                            ✅ {{ __('Confirm & Add') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endauth

</div>
@endsection
