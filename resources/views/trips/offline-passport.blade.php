@extends('layouts.app')

@section('title', __('Offline Travel Passport — :name', ['name' => $trip->name]))
@section('description', __('Access your itinerary, static QR vouchers, and emergency hotlines offline without internet.'))

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-16" x-data="{
    activeTab: 'vouchers',
    isOnline: navigator.onLine,
    cachedSuccess: false,
    copyToast: false,
    copyCode(code) {
        navigator.clipboard.writeText(code);
        this.copyToast = true;
        setTimeout(() => this.copyToast = false, 2000);
    },
    saveOffline() {
        try {
            const data = @json($snapshot);
            localStorage.setItem('wanderly_passport_{{ $trip->id }}', JSON.stringify(data));
            this.cachedSuccess = true;
            setTimeout(() => this.cachedSuccess = false, 3000);
        } catch(e) {
            alert('Failed to cache in localStorage');
        }
    }
}" x-init="
    saveOffline();
    window.addEventListener('online', () => isOnline = true);
    window.addEventListener('offline', () => isOnline = false);
">

    {{-- ================================================================
         1. BREADCRUMB & HEADER BAR
    ================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-gray-200">
        <nav class="flex items-center text-xs sm:text-sm font-medium text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('trips.index') }}" class="hover:text-teal-700 transition">{{ __('Dashboard') }}</a>
            <span class="mx-2 text-gray-300">/</span>
            <a href="{{ route('trips.show', $trip) }}" class="hover:text-teal-700 transition truncate max-w-[200px]">{{ $trip->name }}</a>
            <span class="mx-2 text-gray-300">/</span>
            <span class="text-gray-900 font-semibold">{{ __('Offline Travel Passport') }}</span>
        </nav>

        {{-- Status and Top Actions --}}
        <div class="flex items-center gap-2.5 flex-wrap">
            {{-- Online / Offline live badge --}}
            <div id="network-status-indicator"
                 :class="isOnline ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-300'"
                 class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-colors shadow-xs">
                <span id="network-status-dot"
                      :class="isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"
                      class="w-2 h-2 rounded-full"></span>
                <span id="network-status-text" x-text="isOnline ? '{{ __('Online • Live Sync') }}' : '{{ __('Offline Mode Active') }}'">
                    {{ __('Online • Live Sync') }}
                </span>
            </div>

            {{-- Save Offline button --}}
            <button
                type="button"
                @click="saveOffline()"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-bold border border-teal-200 transition shadow-xs"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>{{ __('Sync to Device') }}</span>
            </button>

            {{-- Print / Save PDF --}}
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold border border-gray-200 transition shadow-xs"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>{{ __('Print / PDF') }}</span>
            </button>
        </div>
    </div>


    {{-- Cache Toast Notification --}}
    <div x-show="cachedSuccess" x-transition x-cloak class="p-3 bg-teal-900 text-white text-xs font-semibold rounded-2xl flex items-center justify-between shadow-xl">
        <div class="flex items-center gap-2">
            <span>✅</span>
            <span>{{ __('Passport cached locally in browser storage. Ready for offline access when cell connection is lost!') }}</span>
        </div>
        <button @click="cachedSuccess = false" class="text-gray-300 hover:text-white">&times;</button>
    </div>

    {{-- Copy Code Toast --}}
    <div x-show="copyToast" x-transition x-cloak class="fixed bottom-6 right-6 z-50 p-3 bg-gray-900 text-white text-xs font-bold rounded-xl shadow-2xl flex items-center gap-2">
        <span>📋</span>
        <span>{{ __('Voucher code copied to clipboard!') }}</span>
    </div>

    {{-- Offline Notice Banner --}}
    <div id="offline-banner" :class="isOnline ? 'hidden' : ''" class="bg-amber-500 text-amber-950 px-4 py-3 rounded-2xl text-xs sm:text-sm font-semibold flex items-center gap-3 shadow-md">
        <span class="text-lg">📶</span>
        <div class="flex-1">
            <span class="font-black">{{ __('Offline Mode Active:') }}</span>
            <span>{{ __('You are disconnected from internet. All itinerary items, static voucher QR codes, and emergency hotlines are fully accessible from local cache.') }}</span>
        </div>
    </div>


    {{-- ================================================================
         2. DIGITAL PASSPORT HERO BADGE
    ================================================================= --}}
    <div class="rounded-3xl overflow-hidden bg-gradient-to-r from-slate-900 via-teal-950 to-emerald-950 text-white p-6 sm:p-8 shadow-xl border border-teal-800/40 relative">
        <div class="absolute -right-8 -bottom-8 w-64 h-64 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-bold text-teal-300">
                    <span>🇻🇳</span>
                    <span class="uppercase tracking-widest text-[11px]">{{ __('Official Travel Passport • Wanderly') }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                    {{ $snapshot['trip']['name'] }}
                </h1>
                <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-gray-300">
                    <span class="flex items-center gap-1.5 font-medium">
                        📍 <span>{{ $snapshot['trip']['destination'] }}</span>
                    </span>
                    <span class="flex items-center gap-1.5 font-medium">
                        🗓️ <span>{{ $snapshot['trip']['date_range_formatted'] }} ({{ $snapshot['trip']['duration_days'] }} {{ __('Days') }})</span>
                    </span>
                    <span class="flex items-center gap-1.5 font-medium">
                        💰 <span>{{ $snapshot['trip']['budget_formatted'] }}</span>
                    </span>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center shrink-0 min-w-[160px]">
                <div class="text-[11px] uppercase tracking-wider text-teal-300 font-bold">{{ __('Snapshot Synced') }}</div>
                <div class="text-sm font-extrabold text-white mt-0.5">{{ $snapshot['generated_at_formatted'] }}</div>
                <div class="text-[10px] text-gray-400 mt-1">{{ __('Trip ID: #:id', ['id' => $trip->id]) }}</div>
            </div>
        </div>
    </div>


    {{-- ================================================================
         3. INTERACTIVE NAVIGATION TABS
    ================================================================= --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-gray-200 text-sm font-bold">
        <button
            type="button"
            @click="activeTab = 'vouchers'"
            :class="activeTab === 'vouchers' ? 'border-primary text-teal-800 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
            class="px-4 py-2.5 rounded-xl border-b-2 transition flex items-center gap-2 whitespace-nowrap"
        >
            <span>🎫</span>
            <span>{{ __('Vouchers & QR Codes') }} ({{ count($snapshot['vouchers']) }})</span>
        </button>

        <button
            type="button"
            @click="activeTab = 'itinerary'"
            :class="activeTab === 'itinerary' ? 'border-primary text-teal-800 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
            class="px-4 py-2.5 rounded-xl border-b-2 transition flex items-center gap-2 whitespace-nowrap"
        >
            <span>🗓️</span>
            <span>{{ __('Itinerary Timeline') }} ({{ count($snapshot['timeline']) }} {{ __('Days') }})</span>
        </button>

        <button
            type="button"
            @click="activeTab = 'emergency'"
            :class="activeTab === 'emergency' ? 'border-primary text-teal-800 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
            class="px-4 py-2.5 rounded-xl border-b-2 transition flex items-center gap-2 whitespace-nowrap"
        >
            <span>🚨</span>
            <span>{{ __('Emergency Hotlines') }}</span>
        </button>

        <button
            type="button"
            @click="activeTab = 'checklist'"
            :class="activeTab === 'checklist' ? 'border-primary text-teal-800 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
            class="px-4 py-2.5 rounded-xl border-b-2 transition flex items-center gap-2 whitespace-nowrap"
        >
            <span>🎒</span>
            <span>{{ __('Packing List') }} ({{ count($snapshot['checklist']) }})</span>
        </button>
    </div>


    {{-- ================================================================
         TAB 1: VOUCHERS & QR CODES
    ================================================================= --}}
    <div x-show="activeTab === 'vouchers'" x-transition class="space-y-6">
        @if(count($snapshot['vouchers']) === 0)
            <div class="bg-white rounded-3xl p-10 border border-gray-200 text-center space-y-3 shadow-sm">
                <div class="text-4xl">🎫</div>
                <h3 class="text-base font-bold text-gray-900">{{ __('No Service Bookings or Vouchers Yet') }}</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto">
                    {{ __('When you accept proposals from local providers in the Marketplace, your offline QR vouchers will be securely stored here.') }}
                </p>
                <a href="{{ route('trips.marketplace.index', $trip) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow hover:bg-teal-800 transition">
                    <span>🏪 {{ __('Explore Marketplace') }}</span>
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($snapshot['vouchers'] as $voucher)
                    <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm p-6 flex flex-col justify-between space-y-4 hover:shadow-md transition">
                        {{-- Voucher Top --}}
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-teal-800 bg-teal-50 border border-teal-200 px-2.5 py-0.5 rounded-md">
                                    {{ ucfirst($voucher['status']) }}
                                </span>
                                <h3 class="text-lg font-extrabold text-gray-900 mt-2">{{ $voucher['item_name'] }}</h3>
                                <p class="text-xs text-gray-500">{{ $voucher['provider_name'] }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-base font-black text-gray-900">{{ $voucher['price_formatted'] }}</span>
                            </div>
                        </div>

                        {{-- QR Code Display --}}
                        <div class="bg-gray-50 rounded-2xl p-4 flex flex-col sm:flex-row items-center gap-4 border border-gray-100">
                            <div class="w-32 h-32 bg-white rounded-xl p-2 shadow-xs border border-gray-200 shrink-0 flex items-center justify-center">
                                <img
                                    src="{{ $voucher['qr_image_url'] }}"
                                    alt="Voucher QR Code"
                                    class="w-full h-full object-contain"
                                    loading="lazy"
                                />
                            </div>
                            <div class="space-y-1.5 text-xs text-gray-600 flex-1">
                                <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                    <span>Code:</span>
                                    <code class="px-2 py-0.5 bg-gray-200 rounded text-teal-900 font-mono text-[11px]">{{ $voucher['voucher_code'] }}</code>
                                    <button
                                        type="button"
                                        @click="copyCode('{{ $voucher['voucher_code'] }}')"
                                        class="text-teal-700 hover:text-teal-900 text-[11px] font-bold underline"
                                    >
                                        {{ __('Copy') }}
                                    </button>
                                </div>
                                <p class="text-[11px] text-gray-500">📍 {{ $voucher['provider_address'] }}</p>
                                <p class="text-[11px] text-gray-500">📞 {{ $voucher['provider_phone'] }}</p>
                                <p class="text-[10px] text-gray-400 italic">{{ __('Present this QR code to the service operator upon check-in.') }}</p>
                            </div>
                        </div>

                        @if(!empty($voucher['notes']))
                            <div class="text-[11px] text-gray-600 bg-amber-50/60 border border-amber-200/60 p-2.5 rounded-xl">
                                <span class="font-bold text-amber-900">{{ __('Booking Notes:') }}</span> {{ $voucher['notes'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>


    {{-- ================================================================
         TAB 2: ITINERARY TIMELINE
    ================================================================= --}}
    <div x-show="activeTab === 'itinerary'" x-transition class="space-y-6">
        @foreach($snapshot['timeline'] as $dayData)
            <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-teal-100 text-teal-800 font-black text-sm flex items-center justify-center">
                            D{{ $dayData['day'] }}
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900">{{ __('Day :day', ['day' => $dayData['day']]) }}</h3>
                            <p class="text-xs text-gray-500">{{ $dayData['date_formatted'] }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                        {{ $dayData['activities_count'] }} {{ __('Activities') }}
                    </span>
                </div>

                @if($dayData['activities_count'] === 0)
                    <p class="text-xs text-gray-400 italic py-2">{{ __('No scheduled activities recorded for this day.') }}</p>
                @else
                    <div class="space-y-2.5">
                        @foreach($dayData['activities'] as $act)
                            <div class="p-3.5 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold uppercase tracking-wider text-[10px] px-2 py-0.5 rounded-md
                                            @if($act['time_slot'] === 'morning') bg-amber-100 text-amber-800
                                            @elseif($act['time_slot'] === 'afternoon') bg-sky-100 text-sky-800
                                            @elseif($act['time_slot'] === 'evening') bg-indigo-100 text-indigo-800
                                            @else bg-purple-100 text-purple-800 @endif">
                                            {{ $act['time_slot_label'] }}
                                        </span>
                                        <h4 class="font-bold text-gray-900 text-sm">{{ $act['title'] }}</h4>
                                    </div>
                                    @if($act['address'])
                                        <p class="text-gray-500 text-[11px] flex items-center gap-1">
                                            <span>📍</span>
                                            <span>{{ $act['address'] }}</span>
                                        </p>
                                    @endif
                                    @if($act['notes'])
                                        <p class="text-gray-500 text-[11px] italic">{{ $act['notes'] }}</p>
                                    @endif
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="font-bold text-gray-800">{{ $act['cost_formatted'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>


    {{-- ================================================================
         TAB 3: EMERGENCY HOTLINES
    ================================================================= --}}
    <div x-show="activeTab === 'emergency'" x-transition class="space-y-6">
        {{-- National Hotlines --}}
        <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm p-6 space-y-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full">
                    {{ __('Vietnam National Emergency Hotlines') }}
                </span>
                <h3 class="text-lg font-black text-gray-900 mt-2">{{ __('1-Tap Emergency Direct Dialing') }}</h3>
                <p class="text-xs text-gray-500">{{ __('Toll-free emergency numbers accessible nationwide across all telecommunications carriers in Vietnam.') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($snapshot['emergency']['national'] as $hotline)
                    <div class="p-4 rounded-2xl border border-gray-200 bg-gray-50/70 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold text-gray-900">{{ $hotline['name'] }}</div>
                            <div class="text-xl font-black text-rose-600 mt-0.5">{{ $hotline['number'] }}</div>
                        </div>
                        <a
                            href="tel:{{ $hotline['number'] }}"
                            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs shadow-md transition flex items-center gap-1.5 shrink-0"
                        >
                            <span>📞</span>
                            <span>{{ __('Call') }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Trip Leader Contact --}}
        <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm p-6 space-y-4">
            <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                <span>👤</span>
                <span>{{ __('Trip Leader & Group Organizer') }}</span>
            </h3>

            <div class="p-4 rounded-2xl bg-teal-50 border border-teal-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div>
                    <h4 class="font-extrabold text-gray-900 text-sm">{{ $snapshot['emergency']['leader']['name'] }}</h4>
                    <p class="text-teal-800 mt-0.5">Email: {{ $snapshot['emergency']['leader']['email'] ?? 'N/A' }}</p>
                    <p class="text-teal-800">Phone: {{ $snapshot['emergency']['leader']['phone'] }}</p>
                </div>
                @if(!empty($snapshot['emergency']['leader']['phone']) && $snapshot['emergency']['leader']['phone'] !== 'Not provided')
                    <a href="tel:{{ $snapshot['emergency']['leader']['phone'] }}" class="px-4 py-2 rounded-xl bg-primary text-white font-bold shadow text-xs shrink-0 self-start sm:self-auto">
                        📞 {{ __('Call Leader') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Booked Providers Hotlines --}}
        @if(count($snapshot['emergency']['providers']) > 0)
            <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm p-6 space-y-4">
                <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                    <span>🏪</span>
                    <span>{{ __('Local Booked Service Hotlines') }}</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($snapshot['emergency']['providers'] as $prov)
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-between gap-3 text-xs">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">{{ $prov['name'] }}</h4>
                                <p class="text-gray-500">{{ $prov['service'] }}</p>
                                <p class="text-gray-600 font-semibold mt-1">📞 {{ $prov['phone'] }}</p>
                            </div>
                            <a href="tel:{{ $prov['phone'] }}" class="px-4 py-2 rounded-xl bg-gray-900 text-white font-bold text-xs shrink-0">
                                📞 {{ __('Call') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>


    {{-- ================================================================
         TAB 4: PACKING CHECKLIST
    ================================================================= --}}
    <div x-show="activeTab === 'checklist'" x-transition class="space-y-6">
        <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">{{ __('Essential Travel Packing List') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('Check off items as you pack. Progress is stored locally on this device.') }}</p>
                </div>
            </div>

            @if(count($snapshot['checklist']) === 0)
                <p class="text-xs text-gray-400 italic py-2">{{ __('No packing items recorded yet.') }}</p>
            @else
                <div class="space-y-2">
                    @foreach($snapshot['checklist'] as $item)
                        <label class="flex items-center gap-3 p-3 rounded-2xl bg-gray-50 border border-gray-100 hover:bg-teal-50/50 cursor-pointer transition text-xs">
                            <input
                                type="checkbox"
                                {{ $item['is_completed'] ? 'checked' : '' }}
                                class="w-4 h-4 rounded text-primary focus:ring-teal-500 border-gray-300"
                            />
                            <span class="font-medium text-gray-800">{{ $item['title'] }}</span>
                            <span class="ml-auto text-[10px] uppercase font-bold text-gray-400 bg-gray-200/70 px-2 py-0.5 rounded">
                                {{ $item['category'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
