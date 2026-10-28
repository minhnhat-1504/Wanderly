<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wanderly') }} — {{ __('Smart Travel Planning & Group Expense Platform') }}</title>
    <meta name="description" content="{{ __('Smart itinerary planning, automated group expense splitting with VietQR, and authentic local experiences across 34 official provinces of Vietnam.') }}">

    {{-- Fonts: Be Vietnam Pro (Exact match with Wanderly Dashboard) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    {{-- Styles & Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="font-sans antialiased bg-stone-50 text-gray-900 selection:bg-teal-700 selection:text-white"
    x-data="landingPageState()"
    x-init="initMap()"
>

    {{-- ================================================================
         1. NAVBAR (Exact synchronization with Dashboard Navbar)
    ================================================================= --}}
    @include('layouts.navigation')



    {{-- ================================================================
         2. HERO SECTION — 3D MAP HERO (2 COLUMNS, NO FRAME BORDER)
    ================================================================= --}}
    <section class="relative min-h-[calc(100vh-4rem)] flex items-center overflow-hidden bg-gradient-to-b from-teal-950 via-teal-900 to-gray-950 text-white py-12 lg:py-0">
        {{-- Subtle architectural background grid --}}
        <div class="absolute inset-0 bg-[radial-gradient(#0f766e_1px,transparent_1px)] [background-size:32px_32px] opacity-20 pointer-events-none"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center min-h-[calc(100vh-5rem)] py-8">

                {{-- Left Column: Copy & Actions --}}
                <div class="lg:col-span-5 space-y-6 text-left z-10">
                    {{-- Clean label without pill styling --}}
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-teal-300">
                        <svg class="w-4 h-3 rounded-xs shadow-xs" viewBox="0 0 30 20" fill="none">
                            <rect width="30" height="20" fill="#DA251D"/>
                            <polygon points="15,4 17.5,11.5 11,7 19,7 12.5,11.5" fill="#FFFF00"/>
                        </svg>
                        <span>Vietnam Travel Platform</span>
                    </div>

                    {{-- Solid Color Headline (Matching Dashboard Font) --}}
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight leading-[1.15]">
                        Discover the Timeless Beauty of Vietnam
                    </h1>

                    {{-- Concise Subheading --}}
                    <p class="text-base sm:text-lg text-teal-100/90 leading-relaxed max-w-xl font-normal">
                        Smart itinerary planning, automated group bill splitting with VietQR, and authentic local experiences across 34 official provinces.
                    </p>

                    {{-- 2 CTA Actions (1 Primary + 1 Secondary viền 1px) --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                        @auth
                            <a
                                href="{{ route('trips.create') }}"
                                class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-semibold text-sm shadow-sm transition-colors"
                            >
                                Create New Trip
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-semibold text-sm shadow-sm transition-colors"
                            >
                                Plan Your Trip Free
                            </a>
                        @endauth

                        <a
                            href="#destinations"
                            class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl border border-teal-500/50 hover:border-teal-300 text-teal-100 hover:text-white font-semibold text-sm transition-colors"
                        >
                            Explore Destinations
                        </a>
                    </div>

                    {{-- Real Verified Stats (Figure 2: 3 Items, No Line Wrapping, Removed 4 Khung Giờ) --}}
                    <div class="grid grid-cols-3 gap-6 sm:gap-8 pt-8 border-t border-teal-800/60 max-w-lg">
                        <div>
                            <div class="text-2xl sm:text-3xl font-bold text-white tracking-tight">34</div>
                            <div class="text-xs sm:text-sm text-teal-200/90 mt-1 whitespace-nowrap">Provinces & Cities</div>
                        </div>
                        <div>
                            <div class="text-2xl sm:text-3xl font-bold text-white tracking-tight">6</div>
                            <div class="text-xs sm:text-sm text-teal-200/90 mt-1 whitespace-nowrap">Curated Destinations</div>
                        </div>
                        <div>
                            <div class="text-2xl sm:text-3xl font-bold text-white tracking-tight">VietQR</div>
                            <div class="text-xs sm:text-sm text-teal-200/90 mt-1 whitespace-nowrap">Instant Settlement</div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Three.js 3D Vietnam Map (No Frame, No Square Border) --}}
                <div class="lg:col-span-7 relative h-[520px] sm:h-[620px] lg:h-[700px] w-full flex items-center justify-center">
                    {{-- 3D Map Viewport Container: Square box and borders removed --}}
                    <div
                        id="vietnam-map-container"
                        class="relative w-full h-full bg-transparent overflow-visible"
                    >
                        {{-- Loading Indicator --}}
                        <div
                            x-show="mapLoading"
                            class="absolute inset-0 z-30 flex flex-col items-center justify-center text-teal-200 bg-transparent"
                        >
                            <svg class="w-8 h-8 animate-spin text-teal-400 mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="text-xs tracking-wider">Loading 3D Vietnam Map...</span>
                        </div>

                        {{-- Map Controls Overlay Component --}}
                        @include('partials.home.map-controls')
                    </div>
                </div>

            </div>
        </div>
    </section>


    {{-- ================================================================
         3. ICONIC DESTINATIONS (Underline Tabs, 3 Cols x 2 Rows)
    ================================================================= --}}
    <section id="destinations" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        {{-- Section Header: Left-aligned, no pill badge, no arrows --}}
        <div class="max-w-2xl text-left space-y-2">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">
                Iconic Destinations Across 3 Regions
            </h2>
            <p class="text-base text-gray-600 leading-relaxed">
                Each region boasts its own soul and story. Select a region to focus the 3D map and discover curated local guides.
            </p>

            {{-- Underline Tabs --}}
            <div class="flex items-center gap-6 pt-4 border-b border-gray-200 flex-wrap">
                <button
                    type="button"
                    @click="setRegion('all')"
                    class="pb-3 text-sm font-semibold transition-colors relative"
                    :class="activeRegion === 'all' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-900'"
                >
                    All Destinations (6)
                </button>
                <button
                    type="button"
                    @click="setRegion('north')"
                    class="pb-3 text-sm font-semibold transition-colors relative"
                    :class="activeRegion === 'north' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-900'"
                >
                    Northern Vietnam
                </button>
                <button
                    type="button"
                    @click="setRegion('central')"
                    class="pb-3 text-sm font-semibold transition-colors relative"
                    :class="activeRegion === 'central' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-900'"
                >
                    Central Vietnam
                </button>
                <button
                    type="button"
                    @click="setRegion('south')"
                    class="pb-3 text-sm font-semibold transition-colors relative"
                    :class="activeRegion === 'south' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-900'"
                >
                    Southern Vietnam
                </button>
            </div>
        </div>

        {{-- Destinations Grid (3 cols x 2 rows, card radius 8px, viền 1px) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- 1. HÀ GIANG --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'north'"
                x-transition
                class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between"
            >
                <div class="relative h-56 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hagiang.jpg') }}"
                        alt="Ma Pi Leng Pass and Tu San Canyon in Ha Giang"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-4 right-4 text-white">
                        <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider block">Northern Vietnam</span>
                        <h3 class="text-xl font-bold text-white leading-snug">Ha Giang</h3>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Vietnam's northern frontier renowned for dizzying serpentine passes, the emerald Tu San Canyon along the Nho Que River, and blooming buckwheat terraces.
                    </p>
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="openProvinceInfo('VN-TQ', 'Ha Giang', 'Northern Vietnam', 'Sep – Nov (Golden rice terraces & buckwheat blossoms)', 'Au Tau porridge, Thang Co, Buckwheat cakes', 'Nho Que River boat cruise, Dong Van Sunday market')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 transition-colors"
                        >
                            View Details
                        </button>
                        <a
                            href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hà Giang']) : route('register') }}"
                            class="px-3.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-semibold transition-colors"
                        >
                            Plan Trip
                        </a>
                    </div>
                </div>
            </div>

            {{-- 2. HẠ LONG --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'north'"
                x-transition
                class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between"
            >
                <div class="relative h-56 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hero.jpg') }}"
                        alt="Limestone karsts on emerald waters of Ha Long Bay"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-4 right-4 text-white">
                        <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider block">Northern Vietnam</span>
                        <h3 class="text-xl font-bold text-white leading-snug">Ha Long Bay</h3>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Thousands of towering limestone karsts rising dramatically from emerald waters. Sleep under the stars aboard a cruise and kayak through mystical sea caves.
                    </p>
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="openProvinceInfo('VN-QN', 'Quang Ninh', 'Northern Vietnam', 'Apr – Oct (Clear skies, calm waters & golden sunlight)', 'Hand-pounded squid rolls, Sea mantis shrimp noodles', '5-Star overnight cruise, Sung Sot Cave, Ti Top Island')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 transition-colors"
                        >
                            View Details
                        </button>
                        <a
                            href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hạ Long']) : route('register') }}"
                            class="px-3.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-semibold transition-colors"
                        >
                            Plan Trip
                        </a>
                    </div>
                </div>
            </div>

            {{-- 3. HỘI AN --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'central'"
                x-transition
                class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between"
            >
                <div class="relative h-56 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hoian.jpg') }}"
                        alt="Lantern lit ancient town of Hoi An along Hoai River"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-4 right-4 text-white">
                        <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider block">Central Vietnam</span>
                        <h3 class="text-xl font-bold text-white leading-snug">Hoi An Ancient Town</h3>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        A magical riverside town steeped in centuries of trade heritage, glowing warmly under thousands of colorful silk lanterns every evening along the Hoai River.
                    </p>
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="openProvinceInfo('VN-DN', 'Da Nang', 'Central Vietnam', 'Feb – Jul (Dry season, warm sunshine & festive nights)', 'Cao Lau noodles, Banh Mi Phuong, Ba Buoi chicken rice', 'Lantern floating ceremony, Bay Mau coconut basket boat')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 transition-colors"
                        >
                            View Details
                        </button>
                        <a
                            href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hội An']) : route('register') }}"
                            class="px-3.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-semibold transition-colors"
                        >
                            Plan Trip
                        </a>
                    </div>
                </div>
            </div>

            {{-- 4. ĐÀ NẴNG --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'central'"
                x-transition
                class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between"
            >
                <div class="relative h-56 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/danang.jpg') }}"
                        alt="Dragon Bridge and My Khe Beach in Da Nang"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-4 right-4 text-white">
                        <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider block">Central Vietnam</span>
                        <h3 class="text-xl font-bold text-white leading-snug">Da Nang</h3>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Vietnam's modern coastal hub famous for powdery white sand beaches, the fire-breathing Dragon Bridge, and sacred peaks of Marble Mountains.
                    </p>
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="openProvinceInfo('VN-DN', 'Da Nang', 'Central Vietnam', 'Mar – Aug (Sunny beach weather & Fireworks festival)', 'Mi Quang noodles, Pork rice paper rolls, Fresh seafood', 'Golden Bridge at Ba Na Hills, Son Tra Peninsula')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 transition-colors"
                        >
                            View Details
                        </button>
                        <a
                            href="{{ auth()->check() ? route('trips.create', ['destination' => 'Đà Nẵng']) : route('register') }}"
                            class="px-3.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-semibold transition-colors"
                        >
                            Plan Trip
                        </a>
                    </div>
                </div>
            </div>

            {{-- 5. ĐÀ LẠT --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'central'"
                x-transition
                class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between"
            >
                <div class="relative h-56 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/dalat.jpg') }}"
                        alt="Pine forests and early morning mist around Tuyen Lam Lake in Da Lat"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-4 right-4 text-white">
                        <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider block">Central Vietnam</span>
                        <h3 class="text-xl font-bold text-white leading-snug">Da Lat</h3>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        A refreshing highland sanctuary blessed with cool year-round climate, mist-cloaked pine forests by Tuyen Lam Lake, and hillside cloud-hunting cafes.
                    </p>
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="openProvinceInfo('VN-LD', 'Lam Dong', 'Central Vietnam', 'Oct – Mar (Cherry blossoms, wild sunflowers & early mist)', 'Chicken leaf hotpot, Grilled rice paper pizza, Warm soy milk', 'Cau Dat cloud hunting, SUP paddling on Tuyen Lam Lake')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 transition-colors"
                        >
                            View Details
                        </button>
                        <a
                            href="{{ auth()->check() ? route('trips.create', ['destination' => 'Đà Lạt']) : route('register') }}"
                            class="px-3.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-semibold transition-colors"
                        >
                            Plan Trip
                        </a>
                    </div>
                </div>
            </div>

            {{-- 6. PHÚ QUỐC --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'south'"
                x-transition
                class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between"
            >
                <div class="relative h-56 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/phuquoc.jpg') }}"
                        alt="Crystal clear turquoise waters and coconut palms at Bai Sao, Phu Quoc"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-4 right-4 text-white">
                        <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider block">Southern Vietnam</span>
                        <h3 class="text-xl font-bold text-white leading-snug">Phu Quoc</h3>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Tropical island paradise blessed with powdery white sand at Bai Sao, crystal-clear diving waters, and legendary crimson Southwestern sunsets.
                    </p>
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="openProvinceInfo('VN-AG', 'An Giang', 'Southern Vietnam', 'Nov – Apr (Calm waters, azure skies & gentle breezes)', 'Raw herring salad, Kien Xay stirred noodles, Ham Ninh crab', 'Hon Thom sea cable car, An Thoi coral snorkeling cruise')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 transition-colors"
                        >
                            View Details
                        </button>
                        <a
                            href="{{ auth()->check() ? route('trips.create', ['destination' => 'Phú Quốc']) : route('register') }}"
                            class="px-3.5 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-semibold transition-colors"
                        >
                            Plan Trip
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>


    {{-- ================================================================
         4. VIETNAMESE FLAVORS (3 COLUMNS WITH AUTHENTIC DISH PHOTOS)
    ================================================================= --}}
    <section id="culture" class="py-24 bg-stone-100 border-y border-stone-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            {{-- Header: Left-aligned --}}
            <div class="max-w-2xl text-left space-y-2">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">
                    Flavors of 3 Regions & Culinary Traditions
                </h2>
                <p class="text-base text-gray-600 leading-relaxed">
                    Every dish tells a cultural heritage story, celebrating the authentic culinary craft and seasonal produce of Vietnam.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Card 1: Northern --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="h-48 overflow-hidden">
                            <img
                                src="{{ asset('images/food/food_north.jpg') }}"
                                alt="Hanoi Pho and traditional Bun Cha"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div class="p-6 space-y-3">
                            <h3 class="text-lg font-bold text-gray-900">Northern Flavors — Subtle & Delicate</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Defined by gentle, balanced seasoning that honors the natural purity of fresh ingredients. Clear broths, aromatic herbs, and time-tested recipes.
                            </p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-2">
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Hanoi Pho</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Bun Cha</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Vong Green Rice</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">West Lake Lotus Tea</span>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Central --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="h-48 overflow-hidden">
                            <img
                                src="{{ asset('images/food/food_central.jpg') }}"
                                alt="Bun Bo Hue and golden Mi Quang noodles"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div class="p-6 space-y-3">
                            <h3 class="text-lg font-bold text-gray-900">Central Flavors — Bold & Vibrant Spices</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Famous for fiery chili heat, deep umami notes, and intricate royal imperial heritage from ancient Hue alongside fresh coastal catch.
                            </p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-2">
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Bun Bo Hue</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Mi Quang</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Banh Beo</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Coastal Seafood</span>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Southern --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="h-48 overflow-hidden">
                            <img
                                src="{{ asset('images/food/food_south.jpg') }}"
                                alt="Crispy Banh Xeo sizzling pancake and Mekong delta hotpot"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div class="p-6 space-y-3">
                            <h3 class="text-lg font-bold text-gray-900">Southern Flavors — Generous & Sun-Kissed</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Infused with sweet coconut milk, abundant tropical river fruit, and bountiful seafood harvested across the Mekong Delta.
                            </p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-2">
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Crispy Banh Xeo</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Mekong Hotpot</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Hu Tieu Nam Vang</span>
                            <span class="px-2.5 py-1 rounded bg-stone-100 text-stone-700 text-xs font-medium">Island Herring Salad</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    {{-- ================================================================
         5. WHY EXPLORE WITH WANDERLY (4 COLS WITH UI ILLUSTRATIONS)
    ================================================================= --}}
    <section id="features" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        {{-- Header: Left-aligned --}}
        <div class="max-w-2xl text-left space-y-2">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">
                Why Explore Vietnam With Wanderly?
            </h2>
            <p class="text-base text-gray-600 leading-relaxed">
                Eliminate travel friction whether you are an intrepid solo explorer or traveling with a group of friends.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- Feature 1: 4-Timeslot Timeline --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col justify-between space-y-5">
                <div class="space-y-3">
                    {{-- Mini UI Mockup: 4-Timeslot Timeline --}}
                    <div class="p-3 bg-stone-50 border border-stone-200 rounded-md space-y-1.5 select-none">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-500 pb-1 border-b border-stone-200">
                            <span>Morning</span>
                            <span>Midday</span>
                            <span>Afternoon</span>
                            <span>Evening</span>
                        </div>
                        <div class="h-2 rounded bg-teal-200 overflow-hidden flex">
                            <div class="w-1/4 bg-teal-600"></div>
                            <div class="w-1/4 bg-teal-500"></div>
                            <div class="w-1/4 bg-teal-400"></div>
                            <div class="w-1/4 bg-teal-300"></div>
                        </div>
                        <div class="text-[10px] text-teal-800 font-medium pt-0.5">Non-overlapping auto conflict detection</div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900">4-Timeslot Timeline</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Drag and drop activities seamlessly across Morning, Midday, Afternoon, and Evening with conflict warnings and automatic budget calculations.
                    </p>
                </div>
            </div>

            {{-- Feature 2: Group Split Bill & VietQR --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col justify-between space-y-5">
                <div class="space-y-3">
                    {{-- Mini UI Mockup: Split Bill & VietQR --}}
                    <div class="p-3 bg-stone-50 border border-stone-200 rounded-md space-y-1.5 select-none">
                        <div class="flex items-center justify-between text-[11px] font-semibold">
                            <span class="text-gray-700">Equal & Custom Split</span>
                            <span class="text-emerald-700 font-mono">VietQR 1-Tap</span>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <div class="w-6 h-6 rounded bg-emerald-100 flex items-center justify-center text-[10px] font-bold text-emerald-800">QR</div>
                            <div class="text-[10px] text-gray-500 leading-tight">Greedy min cash flow minimizes cross-transactions</div>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900">Group Split Bill & VietQR</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Min-Cash-Flow algorithm minimizes cross-debts among friends. Scan banking QR codes with auto-filled amounts for 1-tap debt settlement.
                    </p>
                </div>
            </div>

            {{-- Feature 3: Verified Local Marketplace --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col justify-between space-y-5">
                <div class="space-y-3">
                    {{-- Mini UI Mockup: Marketplace --}}
                    <div class="p-3 bg-stone-50 border border-stone-200 rounded-md space-y-1.5 select-none">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700">
                            <span>Verified Local Providers</span>
                            <span class="text-teal-700 text-[10px]">KYC Checked</span>
                        </div>
                        <div class="text-[10px] text-gray-500 pt-0.5">Local drivers · Certified guides · Verified homestays</div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900">Local Marketplace</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Post custom travel requests and receive transparent, competitive bids from verified local drivers, experienced guides, and homestays.
                    </p>
                </div>
            </div>

            {{-- Feature 4: Offline Travel Passport --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col justify-between space-y-5">
                <div class="space-y-3">
                    {{-- Mini UI Mockup: Offline Passport --}}
                    <div class="p-3 bg-stone-50 border border-stone-200 rounded-md space-y-1.5 select-none">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700">
                            <span>Offline Travel Passport</span>
                            <span class="text-emerald-700 text-[10px]">No Network Required</span>
                        </div>
                        <div class="text-[10px] text-gray-500 pt-0.5">Offline vouchers · PDF export · Emergency hotlines</div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900">Offline Travel Passport</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Access full itinerary summaries, static voucher QRs, and emergency hotlines even when you lose cell coverage in remote mountain passes or islands.
                    </p>
                </div>
            </div>

        </div>
    </section>


    {{-- ================================================================
         6. INSPIRATIONAL CTA BANNER (Balanced Layout, Dark Green)
    ================================================================= --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
        <div class="rounded-2xl bg-teal-900 text-white p-8 sm:p-12 lg:p-16 border border-teal-800 shadow-xl">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                {{-- Left Text --}}
                <div class="lg:col-span-8 space-y-3 text-left">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white leading-tight">
                        Ready for Your Next Great Journey Across Vietnam?
                    </h2>
                    <p class="text-base text-teal-100/90 leading-relaxed max-w-2xl font-normal">
                        Create your first itinerary in under 30 seconds. Invite travel buddies, split expenses with VietQR, and experience Vietnam like a local.
                    </p>
                </div>

                {{-- Right Actions --}}
                <div class="lg:col-span-4 flex flex-col sm:flex-row lg:flex-col gap-3 justify-start lg:justify-end">
                    @auth
                        <a
                            href="{{ route('trips.create') }}"
                            class="w-full text-center py-3.5 px-6 rounded-xl bg-white text-teal-900 font-bold text-sm hover:bg-teal-50 transition-colors shadow-xs"
                        >
                            Create New Trip Now
                        </a>
                        <a
                            href="{{ route('trips.index') }}"
                            class="w-full text-center py-3 px-6 rounded-xl border border-teal-600 text-white font-medium text-sm hover:bg-teal-800/80 transition-colors"
                        >
                            View All Trips
                        </a>
                    @else
                        <a
                            href="{{ route('register') }}"
                            class="w-full text-center py-3.5 px-6 rounded-xl bg-white text-teal-900 font-bold text-sm hover:bg-teal-50 transition-colors shadow-xs"
                        >
                            Sign Up Free
                        </a>
                        <a
                            href="{{ route('login') }}"
                            class="w-full text-center py-3 px-6 rounded-xl border border-teal-600 text-white font-medium text-sm hover:bg-teal-800/80 transition-colors"
                        >
                            Already have an account? Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>


    {{-- ================================================================
         7. FOOTER (4 Columns, High Contrast >= 4.5:1, SVG Flag)
    ================================================================= --}}
    <footer class="bg-gray-950 text-gray-300 border-t border-gray-800 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

                {{-- Col 1: Brand & Identity --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5 text-white font-bold text-lg">
                        <div class="w-8 h-8 rounded-lg bg-teal-800 flex items-center justify-center text-teal-100">
                            W
                        </div>
                        <span class="tracking-tight">Wanderly</span>
                    </div>
                    <p class="text-xs text-gray-400 leading-relaxed">
                        Intelligent travel planning platform celebrating the timeless landscapes, hospitable people, and vibrant heritage of Vietnam.
                    </p>
                </div>

                {{-- Col 2: Destinations --}}
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-200">Iconic Destinations</h4>
                    <ul class="space-y-2 text-xs text-gray-400">
                        <li><a href="#destinations" @click="setRegion('north')" class="hover:text-white transition">Ha Giang & Ha Long Bay</a></li>
                        <li><a href="#destinations" @click="setRegion('central')" class="hover:text-white transition">Hoi An, Da Nang & Hue</a></li>
                        <li><a href="#destinations" @click="setRegion('central')" class="hover:text-white transition">Da Lat & Central Highlands</a></li>
                        <li><a href="#destinations" @click="setRegion('south')" class="hover:text-white transition">Phu Quoc & Mekong Delta</a></li>
                    </ul>
                </div>

                {{-- Col 3: Capabilities --}}
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-200">Platform Features</h4>
                    <ul class="space-y-2 text-xs text-gray-400">
                        <li><a href="#features" class="hover:text-white transition">4-Timeslot Timeline</a></li>
                        <li><a href="#features" class="hover:text-white transition">Group Split Bill & VietQR</a></li>
                        <li><a href="#features" class="hover:text-white transition">Verified Local Marketplace</a></li>
                        <li><a href="#features" class="hover:text-white transition">Offline Travel Passport</a></li>
                    </ul>
                </div>

                {{-- Col 4: Community & Legal --}}
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-200">Community & Legal</h4>
                    <ul class="space-y-2 text-xs text-gray-400">
                        <li><a href="{{ route('social.feed') }}" class="hover:text-white transition">Travel Community Feed</a></li>
                        <li><span class="text-gray-500">Privacy Policy</span></li>
                        <li><span class="text-gray-500">Terms of Service</span></li>
                        <li><span class="text-gray-500">Support & Contact</span></li>
                    </ul>
                </div>

            </div>

            {{-- Bottom Row with SVG Flag (No broken emoji) --}}
            <div class="pt-8 border-t border-gray-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-gray-400">
                <div class="flex items-center gap-2">
                    <span>&copy; {{ date('Y') }} Wanderly. All rights reserved.</span>
                    <span class="inline-flex items-center gap-1 text-gray-300">
                        <span>Vietnam</span>
                        <svg class="w-4 h-2.5 rounded-xs inline-block shadow-xs" viewBox="0 0 30 20" fill="none">
                            <rect width="30" height="20" fill="#DA251D"/>
                            <polygon points="15,4 17.5,11.5 11,7 19,7 12.5,11.5" fill="#FFFF00"/>
                        </svg>
                    </span>
                </div>
                <div class="text-gray-500 text-[11px]">
                    Crafted with love for culture, nature, and travelers exploring Vietnam.
                </div>
            </div>
        </div>
    </footer>


    {{-- Slide-over Drawer for Province Details --}}
    @include('partials.home.province-drawer')


    {{-- Page State Management Script (Alpine.js) --}}
    <script>
        function landingPageState() {
            return {
                mobileMenuOpen: false,
                mapLoading: true,
                mapInstance: null,
                activeRegion: 'all',
                drawerOpen: false,
                showCtrlHint: true,
                isFullscreenMap: false,
                currentProvince: {
                    id: '',
                    name: '',
                    regionName: '',
                    note: '',
                    bestSeason: '',
                    specialties: '',
                    highlights: '',
                    planUrl: ''
                },

                initMap() {
                    setTimeout(() => {
                        this.showCtrlHint = false;
                    }, 5000);

                    // Initialize Three.js map after DOM mounts
                    this.$nextTick(() => {
                        const container = document.getElementById('vietnam-map-container');
                        if (container && window.initVietnamMap3D) {
                            try {
                                this.mapInstance = window.initVietnamMap3D({
                                    container: container,
                                    geojsonUrl: '/data/vn-34.geojson',
                                    onSelectProvince: (prov) => {
                                        this.openProvinceDrawer(prov);
                                    },
                                    onRegionChange: (region) => {
                                        this.activeRegion = region;
                                    }
                                });
                                this.mapLoading = false;
                            } catch (err) {
                                console.error('Error starting Vietnam 3D Map:', err);
                                this.mapLoading = false;
                            }
                        } else {
                            this.mapLoading = false;
                        }
                    });

                    // Listen to events from Three.js map
                    window.addEventListener('wanderly-province-selected', (e) => {
                        if (e.detail) {
                            this.openProvinceDrawer(e.detail);
                        }
                    });

                    window.addEventListener('wanderly-destination-clicked', (e) => {
                        if (e.detail) {
                            const dest = e.detail;
                            this.openProvinceInfo(
                                dest.id,
                                dest.name,
                                dest.region === 'north' ? 'Northern Vietnam' : (dest.region === 'central' ? 'Central Vietnam' : 'Southern Vietnam'),
                                '',
                                '',
                                ''
                            );
                        }
                    });
                },

                setRegion(region) {
                    this.activeRegion = region;
                    if (this.mapInstance && typeof this.mapInstance.filterRegion === 'function') {
                        this.mapInstance.filterRegion(region);
                    }
                },

                openProvinceDrawer(prov) {
                    this.currentProvince = {
                        id: prov.id,
                        name: prov.name,
                        regionName: prov.regionName || (prov.region === 'north' ? 'Northern Vietnam' : (prov.region === 'central' ? 'Central Vietnam' : 'Southern Vietnam')),
                        note: prov.note || '',
                        bestSeason: prov.bestSeason || 'Year-round — Pleasant climate and vibrant regional cultural seasons.',
                        specialties: prov.specialties || 'Authentic regional dishes, fresh seasonal produce, and time-honored recipes.',
                        highlights: prov.highlights || 'Natural wonders, historical heritage landmarks, and traditional craft communities.',
                        planUrl: '/trips/create?destination=' + encodeURIComponent(prov.name)
                    };
                    this.drawerOpen = true;
                },

                openProvinceInfo(id, name, regionName, bestSeason, specialties, highlights) {
                    this.currentProvince = {
                        id: id,
                        name: name,
                        regionName: regionName,
                        note: '',
                        bestSeason: bestSeason,
                        specialties: specialties,
                        highlights: highlights,
                        planUrl: '/trips/create?destination=' + encodeURIComponent(name)
                    };
                    this.drawerOpen = true;
                },

                closeDrawer() {
                    this.drawerOpen = false;
                },

                toggleFullscreenMap() {
                    const container = document.getElementById('vietnam-map-container');
                    if (!container) return;

                    if (!this.isFullscreenMap) {
                        if (container.requestFullscreen) {
                            container.requestFullscreen();
                        } else if (container.webkitRequestFullscreen) {
                            container.webkitRequestFullscreen();
                        }
                        this.isFullscreenMap = true;
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        }
                        this.isFullscreenMap = false;
                    }

                    setTimeout(() => {
                        if (this.mapInstance && typeof this.mapInstance.onResize === 'function') {
                            this.mapInstance.onResize();
                        }
                    }, 200);
                }
            };
        }
    </script>
</body>
</html>
