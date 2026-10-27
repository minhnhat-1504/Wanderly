<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wanderly') }} — {{ __('Discover & Experience Vietnam') }}</title>
    <meta name="description" content="{{ __('Discover the timeless beauty of Vietnam with Wanderly. Smart itinerary planning, automatic group expense splitting, and trusted local service booking.') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Styles & Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 selection:bg-teal-600 selection:text-white" x-data="{ mobileMenuOpen: false }">

    {{-- ================================================================
         1. STICKY NAVBAR
    ================================================================= --}}
    <nav class="bg-white/85 backdrop-blur-md border-b border-gray-200/80 sticky top-0 z-50 transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 sm:h-20 items-center">

                {{-- Left: Logo --}}
                <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0 group">
                    <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center shadow-lg shadow-teal-900/20 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M2 12h20"></path>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight block leading-none">Wanderly</span>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-teal-700 block mt-0.5">{{ __('Explore Vietnam') }}</span>
                    </div>
                </a>

                {{-- Center: Navigation Links (Desktop) --}}
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="#destinations" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all">
                        {{ __('Destinations') }}
                    </a>
                    <a href="#culture" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all">
                        {{ __('Cuisine & Culture') }}
                    </a>
                    <a href="#features" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all">
                        {{ __('Features') }}
                    </a>
                    <a href="{{ route('social.feed') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all flex items-center gap-1.5">
                        <span>📸</span>
                        <span>{{ __('Community') }}</span>
                    </a>
                </div>

                {{-- Right: Auth Buttons --}}
                <div class="hidden sm:flex items-center gap-3">
                    @auth
                        <a href="{{ route('trips.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-bold shadow-md shadow-teal-900/20 hover:bg-teal-800 hover:shadow-lg transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                            </svg>
                            <span>{{ __('Dashboard') }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 hover:text-teal-700 hover:bg-gray-100 transition">
                            {{ __('Sign In') }}
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-bold shadow-md shadow-teal-900/20 hover:bg-teal-800 hover:shadow-lg transition-all duration-200">
                            <span>{{ __('Get Started Free') }}</span>
                            <span class="text-base">&rarr;</span>
                        </a>
                    @endauth
                </div>

                {{-- Mobile Menu Button --}}
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none"
                    aria-label="Toggle mobile menu"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Menu Dropdown --}}
        <div x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden bg-white border-b border-gray-200 px-4 pt-2 pb-4 space-y-2">
            <a href="#destinations" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-teal-50 hover:text-teal-700">
                {{ __('Destinations') }}
            </a>
            <a href="#culture" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-teal-50 hover:text-teal-700">
                {{ __('Cuisine & Culture') }}
            </a>
            <a href="#features" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-teal-50 hover:text-teal-700">
                {{ __('Features') }}
            </a>
            <a href="{{ route('social.feed') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-teal-50 hover:text-teal-700">
                📸 {{ __('Travel Community') }}
            </a>
            <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
                @auth
                    <a href="{{ route('trips.index') }}" class="w-full text-center py-2.5 rounded-xl bg-primary text-white font-bold text-sm shadow">
                        {{ __('Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full text-center py-2 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm">
                        {{ __('Sign In') }}
                    </a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 rounded-xl bg-primary text-white font-bold text-sm shadow">
                        {{ __('Get Started Free') }}
                    </a>
                @endauth
            </div>
        </div>
    </nav>


    {{-- ================================================================
         2. HERO SECTION — DISCOVER VIETNAM
    ================================================================= --}}
    <section class="relative min-h-[580px] lg:min-h-[680px] flex items-center justify-center overflow-hidden bg-gray-900 text-white">
        {{-- Background Image with Depth --}}
        <img
            src="{{ asset('images/destinations/hero.jpg') }}"
            alt="Ha Long Bay, Vietnam"
            class="absolute inset-0 w-full h-full object-cover object-center scale-105 transition-transform duration-1000 ease-out"
        />

        {{-- Gradient Overlay for Contrast & Readability --}}
        <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-900/60 to-gray-900/40"></div>
        <div class="absolute inset-0 bg-radial-at-c from-transparent via-transparent to-black/50"></div>

        {{-- Hero Content --}}
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center space-y-6">
            {{-- Flag Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/25 text-white text-xs sm:text-sm font-semibold shadow-xl animate-fade-in">
                <span>🇻🇳</span>
                <span class="tracking-wide uppercase font-bold">{{ __('Discover Vietnam — S-Shaped Wonder') }}</span>
            </div>

            {{-- Main Headline --}}
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight max-w-4xl mx-auto drop-shadow-md">
                Discover the Timeless Beauty of <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-amber-300">Vietnam</span>
            </h1>

            {{-- Subheading --}}
            <p class="text-base sm:text-xl text-gray-200 max-w-3xl mx-auto leading-relaxed font-normal drop-shadow">
                From the majestic peaks of Ma Pi Leng and lantern-lit alleyways of Hoi An to the emerald waters of Phu Quoc. Wanderly empowers your journey with smart planning, automated group bill splitting, and authentic local experiences.
            </p>

            {{-- CTA Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                @auth
                    <a
                        href="{{ route('trips.create') }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold text-base shadow-xl shadow-teal-900/40 hover:from-emerald-600 hover:to-teal-700 hover:scale-105 active:scale-95 transition-all duration-200"
                    >
                        <span>🗺️ {{ __('Create New Trip') }}</span>
                        <span class="text-lg">&rarr;</span>
                    </a>
                @else
                    <a
                        href="{{ route('register') }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold text-base shadow-xl shadow-teal-900/40 hover:from-emerald-600 hover:to-teal-700 hover:scale-105 active:scale-95 transition-all duration-200"
                    >
                        <span>🚀 {{ __('Plan Your Trip Free') }}</span>
                        <span class="text-lg">&rarr;</span>
                    </a>
                @endauth

                <a
                    href="#destinations"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/30 text-white font-semibold text-base transition-all duration-200"
                >
                    <span>{{ __('Explore Regions') }}</span>
                    <span class="text-sm">&darr;</span>
                </a>
            </div>

            {{-- Trust Metrics --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 pt-10 border-t border-white/15 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">63</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Provinces & Cities') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">100+</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Curated Destinations') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">1-Click</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Drag & Drop Timeline') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">VietQR</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Instant Debt Settlement') }}</div>
                </div>
            </div>
        </div>
    </section>


    {{-- ================================================================
         3. EXPLORE ICONIC DESTINATIONS (REGION FILTER)
    ================================================================= --}}
    <section id="destinations" class="py-16 sm:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12" x-data="{ activeRegion: 'all' }">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full">
                {{ __('Heritage & Natural Wonders') }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                {{ __('Iconic Destinations Across 3 Regions') }}
            </h2>
            <p class="text-sm sm:text-base text-gray-500">
                {{ __('Each region boasts its own soul and story. Select a region to uncover stunning landscapes, rich traditions, and local secrets.') }}
            </p>

            {{-- Region Filter Pills --}}
            <div class="inline-flex p-1.5 rounded-2xl bg-gray-100 border border-gray-200 gap-1.5 mt-4 flex-wrap justify-center">
                <button
                    type="button"
                    @click="activeRegion = 'all'"
                    :class="activeRegion === 'all' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('All Regions (6)') }}
                </button>
                <button
                    type="button"
                    @click="activeRegion = 'north'"
                    :class="activeRegion === 'north' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('Northern Vietnam') }}
                </button>
                <button
                    type="button"
                    @click="activeRegion = 'central'"
                    :class="activeRegion === 'central' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('Central Vietnam') }}
                </button>
                <button
                    type="button"
                    @click="activeRegion = 'south'"
                    :class="activeRegion === 'south' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('Southern & Highlands') }}
                </button>
            </div>
        </div>

        {{-- Destinations Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            {{-- 1. HA GIANG (NORTH) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'north'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hagiang.jpg') }}"
                        alt="Ha Giang, Vietnam"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-sky-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Northern Vietnam') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Ha Giang</h3>
                        <p class="text-xs text-gray-200 mt-0.5">{{ __('Dong Van Karst Plateau & Ma Pi Leng Pass') }}</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __("Vietnam's northern frontier renowned for dizzying serpentine passes, the emerald Tu San Canyon along the Nho Que River, and blooming buckwheat flowers across terraced hills.") }}
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Best Season:') }}</span>
                            <span class="text-gray-500">{{ __('Sep – Nov (Golden rice terraces & Buckwheat flowers)') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Specialties:') }}</span>
                            <span class="text-gray-500">{{ __('Au Tau porridge, Thang Co, Buckwheat cakes') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Highlights:') }}</span>
                            <span class="text-gray-500">{{ __('Nho Que River boat cruise, Dong Van Sunday market') }}</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hà Giang']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Plan Ha Giang Trip') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 2. HA LONG (NORTH) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'north'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hero.jpg') }}"
                        alt="Ha Long Bay"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-sky-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Northern Vietnam') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Ha Long Bay</h3>
                        <p class="text-xs text-gray-200 mt-0.5">{{ __('UNESCO World Natural Heritage (Quang Ninh)') }}</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __('Thousands of towering limestone karsts rising dramatically from turquoise waters. Sleep under the stars aboard a luxury cruise and kayak through mystical sea caves.') }}
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Best Season:') }}</span>
                            <span class="text-gray-500">{{ __('Apr – Oct (Clear skies, calm waters & golden sunlight)') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Specialties:') }}</span>
                            <span class="text-gray-500">{{ __('Hand-pounded squid rolls, Sea mantis shrimp noodles') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Highlights:') }}</span>
                            <span class="text-gray-500">{{ __('5-Star overnight cruise, Sung Sot Cave, Ti Top Island') }}</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hạ Long']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Plan Ha Long Trip') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 3. HOI AN (CENTRAL) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'central'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hoian.jpg') }}"
                        alt="Hoi An Ancient Town"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-amber-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Central Vietnam') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Hoi An Ancient Town</h3>
                        <p class="text-xs text-gray-200 mt-0.5">{{ __('UNESCO World Cultural Heritage (Quang Nam)') }}</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __('A magical riverside town steeped in centuries of trade history, glowing warmly under thousands of colorful silk lanterns every evening along the Hoai River.') }}
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Best Season:') }}</span>
                            <span class="text-gray-500">{{ __('Feb – Jul (Dry season, warm sunshine & festive nights)') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Specialties:') }}</span>
                            <span class="text-gray-500">{{ __('Cao Lau noodles, Banh Mi Phuong, Ba Buoi chicken rice') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Highlights:') }}</span>
                            <span class="text-gray-500">{{ __('Lantern floating ceremony, Bay Mau coconut basket boat') }}</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hội An']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Plan Hoi An Trip') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 4. DA NANG (CENTRAL) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'central'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/danang.jpg') }}"
                        alt="Da Nang City"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-amber-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Central Vietnam') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Da Nang</h3>
                        <p class="text-xs text-gray-200 mt-0.5">{{ __('City of Iconic Bridges & My Khe Coastline') }}</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __("Vietnam's most livable modern coastal metropolis, famous for miles of powdery white sand, the fire-breathing Dragon Bridge, and sacred Marble Mountains.") }}
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Best Season:') }}</span>
                            <span class="text-gray-500">{{ __('Mar – Aug (Sunny beach season & International Fireworks Festival)') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Specialties:') }}</span>
                            <span class="text-gray-500">{{ __('Mi Quang frog noodles, Pork rice paper rolls, Be Man seafood') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Highlights:') }}</span>
                            <span class="text-gray-500">{{ __('Son Tra Peninsula langurs, Golden Bridge at Ba Na Hills') }}</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Đà Nẵng']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Plan Da Nang Trip') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 5. DA LAT (HIGHLANDS) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'south'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/dalat.jpg') }}"
                        alt="Da Lat City"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-emerald-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Central Highlands') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Da Lat</h3>
                        <p class="text-xs text-gray-200 mt-0.5">{{ __('City of Eternal Spring & Pine Forests (Lam Dong)') }}</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __('A refreshing highland sanctuary blessed with cool year-round climate, mist-cloaked pine forests by Tuyen Lam Lake, and charming hillside cloud-hunting cafes.') }}
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Best Season:') }}</span>
                            <span class="text-gray-500">{{ __('Oct – Mar (Cherry blossoms, wild sunflowers & early mist)') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Specialties:') }}</span>
                            <span class="text-gray-500">{{ __('Chicken leaf hotpot, Grilled rice paper pizza, Warm soy milk') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Highlights:') }}</span>
                            <span class="text-gray-500">{{ __('Cau Dat cloud hunting, SUP paddling on Tuyen Lam Lake') }}</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Đà Lạt']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Plan Da Lat Trip') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 6. PHU QUOC (SOUTH) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'south'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/phuquoc.jpg') }}"
                        alt="Phu Quoc Pearl Island"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-emerald-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Southern Vietnam') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Phu Quoc Pearl Island</h3>
                        <p class="text-xs text-gray-200 mt-0.5">{{ __('Tropical Island Paradise (Kien Giang)') }}</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __('Crystal-clear turquoise waters, silky soft sand at Bai Sao, and legendary fiery crimson sunsets across the Southwestern sea horizon.') }}
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Best Season:') }}</span>
                            <span class="text-gray-500">{{ __('Nov – Apr (Calm waters, gentle breezes & azure skies)') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Specialties:') }}</span>
                            <span class="text-gray-500">{{ __('Raw herring salad, Kien Xay stirred noodles, Ham Ninh crab') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Highlights:') }}</span>
                            <span class="text-gray-500">{{ __('Hon Thom sea cable car, An Thoi coral snorkeling cruise') }}</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Phú Quốc']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Plan Phu Quoc Trip') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

        </div>
    </section>


    {{-- ================================================================
         4. VIETNAMESE CUISINE & CULTURE (3 REGIONS)
    ================================================================= --}}
    <section id="culture" class="py-16 bg-gradient-to-b from-gray-50 via-teal-50/40 to-gray-50 border-y border-teal-100/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-teal-800 bg-white border border-teal-200 px-3 py-1 rounded-full shadow-xs">
                    {{ __('Culture & Gastronomy') }}
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                    {{ __('Flavors of 3 Regions & Authentic Traditions') }}
                </h2>
                <p class="text-sm sm:text-base text-gray-500">
                    {{ __('Every dish tells a cultural heritage tale, every festival echoes the thousand-year spirit of Vietnam.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                {{-- Card 1: Northern --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100/80 shadow-sm space-y-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-2xl font-bold">
                        🥢
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">{{ __('Northern Flavors — Subtle & Delicate') }}</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __('Characterized by harmonious, gentle flavors that celebrate the natural purity of fresh ingredients. Savor a steaming bowl of Hanoi Pho on a crisp morning, charcoal-grilled Bun Cha, and fragrant West Lake lotus tea.') }}
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Hanoi Pho</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Vong Village Green Rice</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Bun Thang</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">West Lake Lotus Tea</span>
                    </div>
                </div>

                {{-- Card 2: Central --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100/80 shadow-sm space-y-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-800 flex items-center justify-center text-2xl font-bold">
                        🌶️
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">{{ __('Central Flavors — Bold & Vibrant Spices') }}</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __('Defined by fiery chili heat, deep umami seasoning, and the intricate elegance of royal imperial heritage. Indulge in aromatic Bun Bo Hue, golden turmeric Mi Quang noodles, and delicate savory rice cakes.') }}
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Bun Bo Hue</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Mi Quang</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Banh Beo</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Fresh Coastline Seafood</span>
                    </div>
                </div>

                {{-- Card 3: Southern --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100/80 shadow-sm space-y-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-bold">
                        🥥
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">{{ __('Southern Flavors — Generous & Sun-Kissed') }}</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        {{ __('Infused with rich coconut cream, sun-ripened tropical orchard fruits, and bountiful seafood from the Mekong Delta. Relish bubbling fermented fish hotpot, giant crispy sizzling pancakes (Banh Xeo), and fresh island herring salad.') }}
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Mekong Hotpot</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Crispy Banh Xeo</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Hu Tieu Nam Vang</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Island Herring Salad</span>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ================================================================
         5. SMART WANDERLY CAPABILITIES FOR VIETNAM TRAVEL
    ================================================================= --}}
    <section id="features" class="py-16 sm:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full">
                {{ __('Smart Travel Ecosystem') }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                {{ __('Why Explore Vietnam With Wanderly?') }}
            </h2>
            <p class="text-sm sm:text-base text-gray-500">
                {{ __('Eliminate travel friction whether you are an intrepid solo explorer or traveling with a group of friends.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Feature 1 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl">
                    ⏱️
                </div>
                <h4 class="font-bold text-gray-900 text-base">{{ __('4-Timeslot Timeline') }}</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    {{ __('Drag and drop activities seamlessly across Morning, Afternoon, Evening, and Night. Built-in conflict warnings and instant budget calculation.') }}
                </p>
            </div>

            {{-- Feature 2 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl">
                    💸
                </div>
                <h4 class="font-bold text-gray-900 text-base">{{ __('Group Split Bill & VietQR') }}</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    {{ __('Min-Cash-Flow algorithm minimizes cross-debts among friends. Scan banking QR codes with auto-filled amounts and memo for 1-tap settlement.') }}
                </p>
            </div>

            {{-- Feature 3 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-800 flex items-center justify-center text-xl">
                    🏪
                </div>
                <h4 class="font-bold text-gray-900 text-base">{{ __('Island & Local Marketplace') }}</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    {{ __('Post custom travel requests and receive competitive bids from verified local drivers, experienced guides, and authentic homestays.') }}
                </p>
            </div>

            {{-- Feature 4 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl">
                    📶
                </div>
                <h4 class="font-bold text-gray-900 text-base">{{ __('Offline Travel Passport') }}</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    {{ __('Access your full itinerary summary, static voucher QRs, and emergency hotlines even when you lose cell coverage in remote mountain passes or islands.') }}
                </p>
            </div>
        </div>
    </section>


    {{-- ================================================================
         6. INSPIRATIONAL CTA BANNER
    ================================================================= --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-900 text-white p-8 sm:p-14 shadow-2xl">
            <div class="absolute -right-10 -bottom-10 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-3xl space-y-4">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-400 bg-white/10 px-3 py-1 rounded-full border border-white/15">
                    {{ __('Get Started Today') }}
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                    {{ __('Ready for Your Next Great Journey Across Vietnam?') }}
                </h2>
                <p class="text-sm sm:text-base text-gray-300 leading-relaxed max-w-2xl">
                    {{ __('Create your first itinerary in under 30 seconds. Invite travel buddies, discover authentic local spots, and capture unforgettable memories.') }}
                </p>

                <div class="pt-4 flex flex-wrap gap-4 items-center">
                    @auth
                        <a href="{{ route('trips.create') }}" class="px-8 py-3.5 rounded-2xl bg-white text-teal-900 font-extrabold text-sm shadow-xl hover:bg-gray-100 hover:scale-105 active:scale-95 transition-all">
                            🗺️ {{ __('Create New Trip Now') }}
                        </a>
                        <a href="{{ route('trips.index') }}" class="px-6 py-3.5 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/20 text-white font-bold text-sm transition-all">
                            {{ __('View All Trips') }} &rarr;
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="px-8 py-3.5 rounded-2xl bg-white text-teal-900 font-extrabold text-sm shadow-xl hover:bg-gray-100 hover:scale-105 active:scale-95 transition-all">
                            🚀 {{ __('Sign Up Free') }}
                        </a>
                        <a href="{{ route('login') }}" class="px-6 py-3.5 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/20 text-white font-bold text-sm transition-all">
                            {{ __('Already have an account? Sign In') }} &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>


    {{-- ================================================================
         7. FOOTER
    ================================================================= --}}
    <footer class="bg-gray-900 text-gray-400 border-t border-gray-800 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 pb-8 border-b border-gray-800">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-white font-black text-xl">
                        <span class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-sm">W</span>
                        <span>Wanderly</span>
                    </div>
                    <p class="text-xs text-gray-400 max-w-md">
                        {{ __('Intelligent travel planning platform celebrating the timeless landscapes, hospitable people, and vibrant heritage of Vietnam.') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-4 text-xs font-semibold">
                    <a href="#destinations" class="hover:text-white transition">{{ __('Destinations') }}</a>
                    <a href="#culture" class="hover:text-white transition">{{ __('Cuisine & Culture') }}</a>
                    <a href="#features" class="hover:text-white transition">{{ __('Features') }}</a>
                    <a href="{{ route('social.feed') }}" class="hover:text-white transition">{{ __('Community') }}</a>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 text-gray-500 text-[11px]">
                <p>&copy; {{ date('Y') }} Wanderly. {{ __('All rights reserved. Proudly showcasing Vietnam') }} 🇻🇳</p>
                <p class="flex items-center gap-1">
                    <span>{{ __('Crafted with love for nature, culture, and travelers worldwide.') }}</span>
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
