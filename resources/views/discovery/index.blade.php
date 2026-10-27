@extends('layouts.app')

@section('title', __('Vietnam Travel Discovery Portal'))
@section('description', __('Explore iconic destinations, vibrant local cultures, and seasonal gastronomy across Northern, Central, and Southern Vietnam.'))

@section('content')
<div class="space-y-12 pb-20">

    {{-- ================================================================
         1. HERO DISCOVERY BANNER
    ================================================================= --}}
    <div class="relative rounded-3xl overflow-hidden bg-gray-900 text-white min-h-[380px] sm:min-h-[440px] flex items-center justify-center p-6 sm:p-12 shadow-2xl">
        <img
            src="{{ asset('images/destinations/hero.jpg') }}"
            alt="Vietnam Discovery"
            class="absolute inset-0 w-full h-full object-cover object-center opacity-40 scale-105"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-900/70 to-teal-950/40"></div>

        <div class="relative z-10 max-w-4xl mx-auto text-center space-y-5">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs sm:text-sm font-bold text-white shadow-lg">
                <span>🇻🇳</span>
                <span class="uppercase tracking-widest text-[11px]">{{ __('Vietnam Tourism Portal • Khám Phá Việt Nam') }}</span>
            </div>

            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                {{ __('Explore the Wonders of Vietnam') }}
            </h1>

            <p class="text-sm sm:text-base text-gray-200 max-w-2xl mx-auto leading-relaxed">
                {{ __('Curated regional guides, seasonal recommendations, and authentic culinary journeys from the northern frontiers to southern islands.') }}
            </p>

            {{-- Search Bar --}}
            <form method="GET" action="{{ route('discovery.index') }}" class="max-w-2xl mx-auto pt-2">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search destinations, foods (e.g. Ha Giang, Bun Bo, Pho, Cave)...') }}"
                        class="w-full pl-12 pr-28 py-3.5 rounded-2xl bg-white/95 text-gray-900 placeholder-gray-500 text-sm shadow-xl focus:bg-white focus:outline-none focus:ring-4 focus:ring-teal-500/30"
                    />

                    <button
                        type="submit"
                        class="absolute right-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold text-xs shadow transition"
                    >
                        {{ __('Search') }}
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ================================================================
         2. REGION CARDS (3 MIỀN & TÂY NGUYÊN)
    ================================================================= --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">{{ __('Browse by Region') }}</h2>
                <p class="text-xs sm:text-sm text-gray-500">{{ __('Filter destinations across Vietnam’s distinct cultural landscapes.') }}</p>
            </div>
            @if(request('region') || request('category') || request('search'))
                <a href="{{ route('discovery.index') }}" class="text-xs font-bold text-teal-700 hover:underline">
                    {{ __('Clear all filters') }} &times;
                </a>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($regions as $r)
                <a
                    href="{{ route('discovery.index', array_merge(request()->query(), ['region' => $r->slug])) }}"
                    class="group relative rounded-2xl overflow-hidden aspect-4/3 sm:aspect-16/10 border {{ request('region') === $r->slug ? 'ring-4 ring-teal-600 shadow-lg' : 'border-gray-200/80 shadow-sm' }} hover:shadow-xl transition-all duration-300"
                >
                    <img
                        src="{{ asset($r->cover_image) }}"
                        alt="{{ $r->name }}"
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-950/85 via-gray-900/30 to-transparent"></div>

                    <div class="absolute bottom-3 left-3 right-3 text-white">
                        <h3 class="text-sm sm:text-base font-extrabold leading-tight">{{ $r->name }}</h3>
                        <p class="text-[11px] text-gray-300 font-medium mt-0.5">{{ $r->published_articles_count }} {{ __('Articles') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>


    {{-- ================================================================
         3. CATEGORY FILTER PILLS
    ================================================================= --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-2 text-xs font-bold">
        @php
            $cats = [
                'all' => ['label' => __('All Categories'), 'icon' => '🌏'],
                'nature' => ['label' => __('Nature & Wonders'), 'icon' => '🏞️'],
                'culture' => ['label' => __('Culture & Heritage'), 'icon' => '🏮'],
                'cuisine' => ['label' => __('Gastronomy & Bites'), 'icon' => '🥢'],
                'festival' => ['label' => __('Festivals & Events'), 'icon' => '🎆'],
            ];
            $currentCat = request('category', 'all');
        @endphp

        @foreach($cats as $cKey => $cVal)
            <a
                href="{{ route('discovery.index', array_merge(request()->query(), ['category' => $cKey === 'all' ? null : $cKey])) }}"
                class="px-4 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap {{ ($currentCat === $cKey || ($cKey === 'all' && !request('category'))) ? 'bg-primary text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100' }}"
            >
                <span>{{ $cVal['icon'] }}</span>
                <span>{{ $cVal['label'] }}</span>
            </a>
        @endforeach
    </div>


    {{-- ================================================================
         4. ARTICLES GRID
    ================================================================= --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">
                {{ __('Curated Articles & Guides') }} ({{ $articles->total() }})
            </h2>
            <span class="text-xs text-gray-500 font-medium">
                {{ __('Showing page :current of :last', ['current' => $articles->currentPage(), 'last' => $articles->lastPage()]) }}
            </span>
        </div>

        @if($articles->count() === 0)
            <div class="bg-white rounded-3xl p-12 text-center space-y-4 border border-gray-200 shadow-sm">
                <div class="text-4xl">🔍</div>
                <h3 class="text-lg font-bold text-gray-900">{{ __('No Destination Articles Found') }}</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto">
                    {{ __('No matching articles found for your search query. Try removing filters or searching for popular locations like Ha Giang, Hoi An, or Da Lat.') }}
                </p>
                <a href="{{ route('discovery.index') }}" class="inline-flex px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs shadow hover:bg-teal-800 transition">
                    {{ __('Reset Filters') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($articles as $art)
                    <div class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                        {{-- Image Header --}}
                        <div class="relative h-56 overflow-hidden bg-gray-100">
                            <img
                                src="{{ asset($art->featured_image) }}"
                                alt="{{ $art->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-950/80 via-transparent to-transparent"></div>

                            {{-- Category Badge --}}
                            <span class="absolute top-4 left-4 text-[10px] uppercase font-bold tracking-wider px-2.5 py-1 rounded-full border shadow-xs backdrop-blur-md {{ $art->category_color_class }}">
                                {{ $art->category_label }}
                            </span>

                            <div class="absolute bottom-3 left-4 right-4 text-white">
                                <span class="text-[11px] font-bold text-teal-300 block">📍 {{ $art->location_name ?: $art->region?->name }}</span>
                                <h3 class="text-lg font-extrabold leading-snug line-clamp-1 mt-0.5">{{ $art->title }}</h3>
                            </div>
                        </div>

                        {{-- Body Content --}}
                        <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                            <p class="text-xs sm:text-sm text-gray-600 line-clamp-3 leading-relaxed">
                                {{ $art->summary ?: Str::limit(strip_tags(\Illuminate\Support\Str::markdown($art->content)), 160) }}
                            </p>

                            <div class="space-y-2 pt-2 border-t border-gray-100 text-xs text-gray-600">
                                @if($art->best_season)
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm">🗓️</span>
                                        <span class="font-bold text-gray-900">{{ __('Best Season:') }}</span>
                                        <span class="text-gray-600 truncate">{{ $art->best_season }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1">
                                    <span>👁️ {{ number_format($art->views_count) }} {{ __('views') }}</span>
                                    <span>⏱️ {{ $art->estimated_duration ?: '2–3 Days' }}</span>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="pt-2 flex items-center gap-2">
                                <a
                                    href="{{ route('discovery.show', $art->slug) }}"
                                    class="flex-1 text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs"
                                >
                                    <span>{{ __('Explore Guide') }}</span>
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="pt-6 border-t border-gray-100">
                {{ $articles->links() }}
            </div>
        @endif
    </div>


    {{-- ================================================================
         5. CURATED SAMPLE ITINERARIES
    ================================================================= --}}
    <div class="pt-8 space-y-6">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full">
                {{ __('Signature Travel Itineraries') }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mt-2">
                {{ __('Handcrafted Vietnam Journey Plans') }}
            </h2>
            <p class="text-xs sm:text-sm text-gray-500">
                {{ __('Expertly paced itineraries designed for seamless travel across Vietnam.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($itineraries as $itin)
                <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm p-6 hover:shadow-lg transition space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2.5 py-0.5 rounded-md bg-teal-100 text-teal-800">
                                    {{ $itin['region'] }}
                                </span>
                                <h3 class="text-lg font-black text-gray-900 mt-1.5">{{ $itin['title'] }}</h3>
                                <p class="text-xs text-gray-500">{{ $itin['duration'] }} • {{ $itin['difficulty'] }}</p>
                            </div>
                            <div class="w-16 h-16 rounded-2xl overflow-hidden shrink-0 shadow-xs border border-gray-100">
                                <img src="{{ asset($itin['cover_image']) }}" alt="{{ $itin['title'] }}" class="w-full h-full object-cover"/>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed">{{ $itin['overview'] }}</p>

                        <div class="space-y-1.5 pt-2">
                            <span class="text-[11px] font-bold text-gray-900 block">{{ __('Route Highlights:') }}</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($itin['highlights'] as $hl)
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-[11px] font-medium">
                                        ✓ {{ $hl }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <a
                            href="{{ auth()->check() ? route('trips.create', ['destination' => $itin['destination']]) : route('register') }}"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700 hover:text-teal-900 hover:underline"
                        >
                            <span>🗺️ {{ __('Create trip with this route') }}</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
