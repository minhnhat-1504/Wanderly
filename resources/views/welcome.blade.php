<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wanderly') }} — {{ __('Quảng Bá & Khám Phá Du Lịch Việt Nam') }}</title>
    <meta name="description" content="{{ __('Khám phá vẻ đẹp bất tận của du lịch Việt Nam cùng Wanderly. Lên lịch trình thông minh, chia tiền nhóm tự động và kết nối đối tác bản địa uy tín.') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

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
                        <span class="text-[10px] uppercase font-bold tracking-wider text-teal-700 block mt-0.5">Du lịch Việt Nam</span>
                    </div>
                </a>

                {{-- Center: Navigation Links (Desktop) --}}
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="#destinations" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all">
                        {{ __('Điểm đến 3 Miền') }}
                    </a>
                    <a href="#culture" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all">
                        {{ __('Ẩm thực & Văn hóa') }}
                    </a>
                    <a href="#features" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all">
                        {{ __('Tính năng Nổi bật') }}
                    </a>
                    <a href="{{ route('social.feed') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:text-teal-700 hover:bg-teal-50 transition-all flex items-center gap-1.5">
                        <span>📸</span>
                        <span>{{ __('Cộng đồng') }}</span>
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
                            <span>{{ __('Vào Dashboard') }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 hover:text-teal-700 hover:bg-gray-100 transition">
                            {{ __('Đăng nhập') }}
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-bold shadow-md shadow-teal-900/20 hover:bg-teal-800 hover:shadow-lg transition-all duration-200">
                            <span>{{ __('Bắt đầu miễn phí') }}</span>
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
                {{ __('Điểm đến 3 Miền') }}
            </a>
            <a href="#culture" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-teal-50 hover:text-teal-700">
                {{ __('Ẩm thực & Văn hóa') }}
            </a>
            <a href="#features" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-teal-50 hover:text-teal-700">
                {{ __('Tính năng Nổi bật') }}
            </a>
            <a href="{{ route('social.feed') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-teal-50 hover:text-teal-700">
                📸 {{ __('Cộng đồng du lịch') }}
            </a>
            <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
                @auth
                    <a href="{{ route('trips.index') }}" class="w-full text-center py-2.5 rounded-xl bg-primary text-white font-bold text-sm shadow">
                        {{ __('Vào Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full text-center py-2 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm">
                        {{ __('Đăng nhập') }}
                    </a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 rounded-xl bg-primary text-white font-bold text-sm shadow">
                        {{ __('Bắt đầu miễn phí') }}
                    </a>
                @endauth
            </div>
        </div>
    </nav>


    {{-- ================================================================
         2. HERO SECTION — QUẢNG BÁ DU LỊCH VIỆT NAM
    ================================================================= --}}
    <section class="relative min-h-[580px] lg:min-h-[680px] flex items-center justify-center overflow-hidden bg-gray-900 text-white">
        {{-- Background Image with Parallax / Depth --}}
        <img
            src="{{ asset('images/destinations/hero.jpg') }}"
            alt="Vịnh Hạ Long, Việt Nam"
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
                <span class="tracking-wide uppercase font-bold">{{ __('Tự Hào Du Lịch Việt Nam — Vietnam Discovery') }}</span>
            </div>

            {{-- Main Headline --}}
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight max-w-4xl mx-auto drop-shadow-md">
                Khám Phá Vẻ Đẹp Bất Tận Của <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-amber-300">Đất Nước Hình Chữ S</span>
            </h1>

            {{-- Subheading --}}
            <p class="text-base sm:text-xl text-gray-200 max-w-3xl mx-auto leading-relaxed font-normal drop-shadow">
                Từ đỉnh đèo Mã Pí Lèng hùng vĩ, phố cổ Hội An rực rỡ đèn lồng đến biển ngọc Phú Quốc hoang sơ. Wanderly đồng hành cùng bạn lên lịch trình thông minh, chia tiền nhóm tự động và kết nối dịch vụ địa phương.
            </p>

            {{-- CTA Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                @auth
                    <a
                        href="{{ route('trips.create') }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold text-base shadow-xl shadow-teal-900/40 hover:from-emerald-600 hover:to-teal-700 hover:scale-105 active:scale-95 transition-all duration-200"
                    >
                        <span>🗺️ {{ __('Tạo Chuyến Đi Mới') }}</span>
                        <span class="text-lg">&rarr;</span>
                    </a>
                @else
                    <a
                        href="{{ route('register') }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold text-base shadow-xl shadow-teal-900/40 hover:from-emerald-600 hover:to-teal-700 hover:scale-105 active:scale-95 transition-all duration-200"
                    >
                        <span>🚀 {{ __('Lên Lịch Trình Miễn Phí') }}</span>
                        <span class="text-lg">&rarr;</span>
                    </a>
                @endauth

                <a
                    href="#destinations"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/30 text-white font-semibold text-base transition-all duration-200"
                >
                    <span>{{ __('Khám phá 3 miền') }}</span>
                    <span class="text-sm">&darr;</span>
                </a>
            </div>

            {{-- Trust Metrics --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 pt-10 border-t border-white/15 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">63</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Tỉnh Thành Việt Nam') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">100+</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Điểm đến Tuyển chọn') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">1-Click</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Lịch trình Kéo — Thả') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">VietQR</div>
                    <div class="text-xs sm:text-sm text-gray-300 font-medium mt-0.5">{{ __('Tất toán Nợ 1 Chạm') }}</div>
                </div>
            </div>
        </div>
    </section>


    {{-- ================================================================
         3. KHÁM PHÁ ĐIỂM ĐẾN 3 MIỀN (INTERACTIVE REGION FILTER)
    ================================================================= --}}
    <section id="destinations" class="py-16 sm:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12" x-data="{ activeRegion: 'all' }">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full">
                {{ __('Hành Trình Di Sản & Kỳ Quan') }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                Điểm Đến Tiêu Biểu Bắc — Trung — Nam
            </h2>
            <p class="text-sm sm:text-base text-gray-500">
                Mỗi vùng đất một phong vị riêng. Hãy chọn miền đất bạn yêu thích để bắt đầu hành trình khám phá thiên nhiên và văn hóa đặc sắc.
            </p>

            {{-- Region Filter Pills --}}
            <div class="inline-flex p-1.5 rounded-2xl bg-gray-100 border border-gray-200 gap-1.5 mt-4 flex-wrap justify-center">
                <button
                    type="button"
                    @click="activeRegion = 'all'"
                    :class="activeRegion === 'all' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('Tất cả (6)') }}
                </button>
                <button
                    type="button"
                    @click="activeRegion = 'north'"
                    :class="activeRegion === 'north' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('Miền Bắc') }}
                </button>
                <button
                    type="button"
                    @click="activeRegion = 'central'"
                    :class="activeRegion === 'central' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('Miền Trung') }}
                </button>
                <button
                    type="button"
                    @click="activeRegion = 'south'"
                    :class="activeRegion === 'south' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                >
                    {{ __('Miền Nam & Tây Nguyên') }}
                </button>
            </div>
        </div>

        {{-- Destinations Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            {{-- 1. HÀ GIANG (NORTH) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'north'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hagiang.jpg') }}"
                        alt="Hà Giang"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-sky-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Miền Bắc') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Hà Giang</h3>
                        <p class="text-xs text-gray-200 mt-0.5">Cao nguyên đá Đồng Văn & Đèo Mã Pí Lèng</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Nơi địa đầu Tổ quốc với những cung đèo ngoạn mục, hẻm Tu Sản xanh ngọc bích của dòng sông Nho Quế và mùa hoa tam giác mạch rực rỡ khắp sườn đồi.
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Mùa đẹp nhất:') }}</span>
                            <span class="text-gray-500">Tháng 9 – 11 (Lúa chín & Hoa tam giác mạch)</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Đặc sản:') }}</span>
                            <span class="text-gray-500">Cháo ấu tẩu, Thắng cố, Bánh tam giác mạch</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Trải nghiệm:') }}</span>
                            <span class="text-gray-500">Đi thuyền sông Nho Quế, Chợ phiên Đồng Văn</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hà Giang']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Lên lịch trình Hà Giang') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 2. HẠ LONG (NORTH) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'north'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hero.jpg') }}"
                        alt="Vịnh Hạ Long"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-sky-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Miền Bắc') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Vịnh Hạ Long</h3>
                        <p class="text-xs text-gray-200 mt-0.5">Kỳ quan Thiên nhiên Thế giới (Quảng Ninh)</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Hàng ngàn đảo đá vôi kỳ vĩ nhấp nhô trên mặt biển xanh ngọc. Trải nghiệm ngủ đêm trên du thuyền sang trọng và chèo kayak qua hang luồn kỳ bí.
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Mùa đẹp nhất:') }}</span>
                            <span class="text-gray-500">Tháng 4 – 10 (Trời trong, biển êm, nắng vàng)</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Đặc sản:') }}</span>
                            <span class="text-gray-500">Chả mực giã tay, Sam biển, Bún bề bề</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Trải nghiệm:') }}</span>
                            <span class="text-gray-500">Du thuyền 5 sao, Hang Sửng Sốt, Đảo Ti Tốp</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hạ Long']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Lên lịch trình Hạ Long') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 3. HỘI AN (CENTRAL) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'central'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/hoian.jpg') }}"
                        alt="Phố cổ Hội An"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-amber-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Miền Trung') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Phố Cổ Hội An</h3>
                        <p class="text-xs text-gray-200 mt-0.5">Di sản Văn hóa Thế giới UNESCO (Quảng Nam)</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Phố cổ rêu phong bên dòng sông Hoài thơ mộng, lung linh muôn sắc đèn lồng mỗi đêm rằm. Thiên đường ẩm thực đường phố níu chân mọi lữ khách phương xa.
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Mùa đẹp nhất:') }}</span>
                            <span class="text-gray-500">Tháng 2 – 7 (Thời tiết khô ráo, nắng ấm)</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Đặc sản:') }}</span>
                            <span class="text-gray-500">Cao lầu, Bánh mì Phượng, Cơm gà bà Buội</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Trải nghiệm:') }}</span>
                            <span class="text-gray-500">Thả đèn hoa đăng, Đi thuyền thúng Rừng dừa Bảy Mẫu</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Hội An']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Lên lịch trình Hội An') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 4. ĐÀ NẴNG (CENTRAL) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'central'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/danang.jpg') }}"
                        alt="Đà Nẵng"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-amber-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Miền Trung') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Đà Nẵng</h3>
                        <p class="text-xs text-gray-200 mt-0.5">Thành phố của những cây cầu & Bãi biển Mỹ Khê</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Thành phố biển hiện đại đáng sống bậc nhất với bãi cát dài mịn màng, Cầu Rồng phun lửa ấn tượng và danh thắng Ngũ Hành Sơn huyền bí.
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Mùa đẹp nhất:') }}</span>
                            <span class="text-gray-500">Tháng 3 – 8 (Mùa tắm biển & Lễ hội pháo hoa)</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Đặc sản:') }}</span>
                            <span class="text-gray-500">Mì Quảng ếch, Bánh tráng cuốn thịt heo, Hải sản Bé Mặn</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Trải nghiệm:') }}</span>
                            <span class="text-gray-500">Bán đảo Sơn Trà ngắm Voọc, Cầu Vàng Bà Nà Hills</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Đà Nẵng']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Lên lịch trình Đà Nẵng') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 5. ĐÀ LẠT (SOUTH / HIGHLANDS) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'south'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/dalat.jpg') }}"
                        alt="Đà Lạt"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-emerald-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Tây Nguyên') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Đà Lạt</h3>
                        <p class="text-xs text-gray-200 mt-0.5">Xứ sở ngàn hoa sương mờ (Lâm Đồng)</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Khí hậu cao nguyên se lạnh quanh năm, những đồi thông thơ mộng bên hồ Tuyền Lâm và hàng trăm quán cafe chill ngắm biển mây sớm mai.
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Mùa đẹp nhất:') }}</span>
                            <span class="text-gray-500">Tháng 10 – 3 (Mùa hoa mai anh đào, dã quỳ, mây sớm)</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Đặc sản:') }}</span>
                            <span class="text-gray-500">Lẩu gà lá é, Bánh tráng nướng, Sữa đậu nành nóng</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Trải nghiệm:') }}</span>
                            <span class="text-gray-500">Săn mây Cầu Đất, Chèo SUP hồ Tuyền Lâm, Cắm trại rừng thông</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Đà Lạt']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Lên lịch trình Đà Lạt') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- 6. PHÚ QUỐC (SOUTH) --}}
            <div
                x-show="activeRegion === 'all' || activeRegion === 'south'"
                x-transition
                class="group bg-white rounded-3xl border border-gray-200/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col"
            >
                <div class="relative h-60 overflow-hidden">
                    <img
                        src="{{ asset('images/destinations/phuquoc.jpg') }}"
                        alt="Đảo Ngọc Phú Quốc"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <span class="absolute top-4 left-4 bg-emerald-900/80 backdrop-blur-md text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-white/20">
                        {{ __('Miền Nam') }}
                    </span>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-xl font-extrabold leading-tight">Đảo Ngọc Phú Quốc</h3>
                        <p class="text-xs text-gray-200 mt-0.5">Thiên đường biển đảo nhiệt đới (Kiên Giang)</p>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Làn nước trong xanh màu ngọc bích, bãi cát trắng mịn như kem tại Bãi Sao và những buổi chiều hoàng hôn rực lửa trên biển Tây Nam.
                    </p>

                    <div class="space-y-2 border-t border-gray-100 pt-3 text-xs">
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🗓️</span>
                            <span class="font-semibold text-gray-900">{{ __('Mùa đẹp nhất:') }}</span>
                            <span class="text-gray-500">Tháng 11 – 4 (Mùa biển êm, sóng nhẹ, trời xanh ngắt)</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">🍲</span>
                            <span class="font-semibold text-gray-900">{{ __('Đặc sản:') }}</span>
                            <span class="text-gray-500">Gỏi cá trích, Bún quậy Kiến Xây, Ghẹ Hàm Ninh</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700">
                            <span class="text-sm">⭐</span>
                            <span class="font-semibold text-gray-900">{{ __('Trải nghiệm:') }}</span>
                            <span class="text-gray-500">Cáp treo Hòn Thơm vượt biển, Lặn ngắm san hô An Thới</span>
                        </div>
                    </div>

                    <a
                        href="{{ auth()->check() ? route('trips.create', ['destination' => 'Phú Quốc']) : route('register') }}"
                        class="w-full text-center py-2.5 rounded-xl bg-teal-50 text-teal-800 hover:bg-primary hover:text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>{{ __('Lên lịch trình Phú Quốc') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

        </div>
    </section>


    {{-- ================================================================
         4. TINH HOA ẨM THỰC & VĂN HÓA VIỆT NAM (CULTURE & CUISINE)
    ================================================================= --}}
    <section id="culture" class="py-16 bg-gradient-to-b from-gray-50 via-teal-50/40 to-gray-50 border-y border-teal-100/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-teal-800 bg-white border border-teal-200 px-3 py-1 rounded-full shadow-xs">
                    {{ __('Bản Sắc Văn Hóa & Ẩm Thực') }}
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                    Hương Vị Ba Miền & Trải Nghiệm Bản Địa
                </h2>
                <p class="text-sm sm:text-base text-gray-500">
                    Mỗi món ăn là một câu chuyện văn hóa, mỗi lễ hội là một nhịp thở nghìn năm của đất Việt.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                {{-- Card 1: Miền Bắc --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100/80 shadow-sm space-y-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-2xl font-bold">
                        🥢
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Ẩm Thực Đất Bắc — Tinh Tế & Thanh Nhã</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Đặc trưng bởi hương vị thanh tao, hài hòa, tôn trọng độ ngọt tự nhiên của nguyên liệu. Thưởng thức bát Phở bò Hà Nội nghi ngút khói sáng mùa đông, Bún chả nướng than hoa thơm lừng và chè sen nhãn lồng thanh mát.
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Phở Hà Nội</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Cốm Làng Vòng</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Bún thang</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Trà sen Tây Hồ</span>
                    </div>
                </div>

                {{-- Card 2: Miền Trung --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100/80 shadow-sm space-y-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-800 flex items-center justify-center text-2xl font-bold">
                        🌶️
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Hương Vị Xứ Quảng & Cố Đô — Đậm Đà Cay Nồng</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Nổi bật với vị cay nồng của ớt, màu sắc rực rỡ và nghệ thuật ẩm thực cung đình cầu kỳ. Một tô Bún bò Huế thơm lừng mùi mắm ruốc, đĩa Mì Quảng ếch vàng ươm hay chén chè bột lọc heo quay độc đáo.
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Bún bò Huế</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Mì Quảng</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Bánh bèo chén</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Hải sản tươi sống</span>
                    </div>
                </div>

                {{-- Card 3: Miền Nam --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100/80 shadow-sm space-y-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-bold">
                        🥥
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Miền Nam & Sông Nước — Phóng Khoáng & Đậm Tình</h3>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                        Hương vị ngọt bùi của nước cốt dừa, phong vị tươi ngon hào sảng của miệt vườn cây trái và hải sản biển đảo. Nồi lẩu mắm miền Tây thơm nức mũi, bánh xèo vàng rụm cuốn rau rừng và gỏi cá trích Phú Quốc.
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Lẩu mắm</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Bánh xèo miền Tây</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Hủ tiếu Nam Vang</span>
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium">Gỏi cá trích</span>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ================================================================
         5. TÍNH NĂNG ĐỘT PHÁ CỦA WANDERLY CHO DU LỊCH VIỆT NAM
    ================================================================= --}}
    <section id="features" class="py-16 sm:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full">
                {{ __('Hệ Sinh Thái Thông Minh') }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                Tại Sao Chọn Wanderly Để Khám Phá Việt Nam?
            </h2>
            <p class="text-sm sm:text-base text-gray-500">
                Giải quyết trọn vẹn những bất tiện khi đi du lịch tự túc hoặc theo nhóm đông người.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Feature 1 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl">
                    ⏱️
                </div>
                <h4 class="font-bold text-gray-900 text-base">Lịch Trình 4 Khung Giờ</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Kéo-thả hoạt động mượt mà vào Sáng - Trưa - Chiều - Tối. Hệ thống tự động cảnh báo trùng giờ và tính ngân sách tức thì.
                </p>
            </div>

            {{-- Feature 2 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl">
                    💸
                </div>
                <h4 class="font-bold text-gray-900 text-base">Chia Tiền Nhóm & VietQR</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Thuật toán Min-Cash-Flow rút gọn nợ chéo tối đa. Quét mã QR ngân hàng tự động điền số tiền và nội dung, sạch nợ 100%.
                </p>
            </div>

            {{-- Feature 3 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-800 flex items-center justify-center text-xl">
                    🏪
                </div>
                <h4 class="font-bold text-gray-900 text-base">Sàn Dịch Vụ Đảo</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Đăng yêu cầu du lịch và nhận báo giá cạnh tranh từ các nhà xe, hướng dẫn viên, khách sạn bản địa uy tín.
                </p>
            </div>

            {{-- Feature 4 --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-200/90 shadow-sm hover:shadow-lg transition space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl">
                    📶
                </div>
                <h4 class="font-bold text-gray-900 text-base">Hộ Chiếu Ngoại Tuyến</h4>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Xem trọn vẹn tóm tắt lịch trình, voucher và số điện thoại khẩn cấp ngay cả khi mất sóng 4G ở vùng đèo núi, hải đảo.
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
                    {{ __('Bắt đầu trải nghiệm ngay') }}
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                    Bạn Đã Sẵn Sàng Cho Chuyến Hành Trình Tiếp Theo Khắp Việt Nam?
                </h2>
                <p class="text-sm sm:text-base text-gray-300 leading-relaxed max-w-2xl">
                    Tạo chuyến đi đầu tiên chỉ trong 30 giây. Thêm bạn bè cùng bàn bạc, chọn dịch vụ bản địa và lưu giữ những khoảnh khắc đẹp nhất.
                </p>

                <div class="pt-4 flex flex-wrap gap-4 items-center">
                    @auth
                        <a href="{{ route('trips.create') }}" class="px-8 py-3.5 rounded-2xl bg-white text-teal-900 font-extrabold text-sm shadow-xl hover:bg-gray-100 hover:scale-105 active:scale-95 transition-all">
                            🗺️ {{ __('Tạo Chuyến Đi Mới Ngay') }}
                        </a>
                        <a href="{{ route('trips.index') }}" class="px-6 py-3.5 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/20 text-white font-bold text-sm transition-all">
                            {{ __('Xem Danh Sách Chuyến Đi') }} &rarr;
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="px-8 py-3.5 rounded-2xl bg-white text-teal-900 font-extrabold text-sm shadow-xl hover:bg-gray-100 hover:scale-105 active:scale-95 transition-all">
                            🚀 {{ __('Đăng Ký Miễn Phí') }}
                        </a>
                        <a href="{{ route('login') }}" class="px-6 py-3.5 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/20 text-white font-bold text-sm transition-all">
                            {{ __('Đã có tài khoản? Đăng nhập') }} &rarr;
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
                        Nền tảng lập kế hoạch du lịch thông minh, tôn vinh và lan tỏa vẻ đẹp thiên nhiên, con người cùng tinh hoa văn hóa Việt Nam.
                    </p>
                </div>

                <div class="flex flex-wrap gap-4 text-xs font-semibold">
                    <a href="#destinations" class="hover:text-white transition">{{ __('Điểm đến') }}</a>
                    <a href="#culture" class="hover:text-white transition">{{ __('Văn hóa ẩm thực') }}</a>
                    <a href="#features" class="hover:text-white transition">{{ __('Tính năng') }}</a>
                    <a href="{{ route('social.feed') }}" class="hover:text-white transition">{{ __('Cộng đồng') }}</a>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 text-gray-500 text-[11px]">
                <p>&copy; {{ date('Y') }} Wanderly. All rights reserved. Tự hào du lịch Việt Nam 🇻🇳</p>
                <p class="flex items-center gap-1">
                    <span>Phát triển vì tình yêu thiên nhiên & văn hóa đất Việt</span>
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
