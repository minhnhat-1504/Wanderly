@extends('layouts.app')

@section('title', __('Voucher :code — :item', ['code' => $booking->voucher_code, 'item' => $booking->item_name]))

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-12">
    {{-- Back action --}}
    <div class="mb-6 flex items-center justify-between no-print">
        <a href="{{ route('trips.marketplace.bookings', $booking->trip) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('Back to Bookings') }}
        </a>

        <button onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-semibold text-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            {{ __('Print / Save Pass') }}
        </button>
    </div>

    {{-- Official Voucher Boarding Pass Card --}}
    <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
        {{-- Card Header --}}
        <div class="bg-primary p-8 text-white relative">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold tracking-tight">Wanderly</span>
                    <span class="text-xs uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded-full font-semibold">{{ __('Service Pass') }}</span>
                </div>
                <div class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-100 text-xs font-bold border border-emerald-400/30">
                    {{ __(ucfirst($booking->status)) }}
                </div>
            </div>

            <h1 class="text-2xl font-bold tracking-tight">{{ $booking->item_name }}</h1>
            <p class="text-sm text-white/80 mt-1">
                {{ $booking->trip?->destination }} • {{ $booking->trip?->name }}
            </p>
        </div>

        {{-- Voucher Code Ticket Notch --}}
        <div class="relative bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent px-8 py-6 border-b border-dashed border-gray-200">
            <div class="text-xs text-gray-500 uppercase font-semibold tracking-wider">{{ __('Authorized Voucher Code') }}</div>
            <div class="text-2xl sm:text-3xl font-mono font-extrabold text-primary tracking-widest mt-1 select-all">
                {{ $booking->voucher_code }}
            </div>
            <p class="text-xs text-gray-500 mt-1">
                {{ __('Present this code or QR to the service provider upon arrival.') }}
            </p>
        </div>

        {{-- Details Grid --}}
        <div class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-6 text-sm">
                <div>
                    <div class="text-xs text-gray-400 font-semibold uppercase">{{ __('Service Provider') }}</div>
                    <div class="text-base font-bold text-gray-900 mt-0.5">{{ $booking->provider?->business_name ?? __('Local Provider') }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $booking->provider?->address }}</div>
                    @if ($booking->provider?->phone)
                        <div class="text-xs text-primary font-medium mt-1">📞 {{ $booking->provider->phone }}</div>
                    @endif
                </div>

                <div>
                    <div class="text-xs text-gray-400 font-semibold uppercase">{{ __('Booking Value') }}</div>
                    <div class="text-xl font-bold text-gray-900 mt-0.5">{{ number_format((float) $booking->price, 0, ',', '.') }} VND</div>
                    <div class="text-xs text-emerald-600 font-medium mt-0.5">✓ {{ __('Paid / Confirmed') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 text-sm pt-4 border-t border-gray-100">
                <div>
                    <div class="text-xs text-gray-400 font-semibold uppercase">{{ __('Issue Date') }}</div>
                    <div class="text-sm font-semibold text-gray-800 mt-0.5">{{ $booking->created_at->format('d/m/Y H:i') }}</div>
                </div>

                <div>
                    <div class="text-xs text-gray-400 font-semibold uppercase">{{ __('Trip Duration') }}</div>
                    <div class="text-sm font-semibold text-gray-800 mt-0.5">
                        {{ $booking->trip?->start_date?->format('d/m/Y') }} – {{ $booking->trip?->end_date?->format('d/m/Y') }}
                    </div>
                </div>
            </div>

            @if ($booking->notes)
                <div class="pt-4 border-t border-gray-100 text-xs text-gray-500">
                    <strong class="text-gray-700">{{ __('Notes:') }}</strong> {{ $booking->notes }}
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="p-6 bg-gray-50 border-t border-gray-100 text-center text-xs text-gray-400">
            {{ __('Issued by Wanderly Reverse Marketplace. For support, contact your trip leader or the local partner.') }}
        </div>
    </div>
</div>
@endsection
