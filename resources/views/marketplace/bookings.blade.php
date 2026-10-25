@extends('layouts.app')

@section('title', __('Confirmed Bookings & Vouchers — :trip', ['trip' => $trip->name]))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
    copiedCode: null,
    copyVoucher(code) {
        navigator.clipboard.writeText(code);
        this.copiedCode = code;
        setTimeout(() => this.copiedCode = null, 2500);
    }
}">
    {{-- Header --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('trips.show', $trip) }}" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ $trip->name }}
                </a>
                <span>/</span>
                <span class="text-gray-900 font-semibold">{{ __('Marketplace Bookings') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                {{ __('Confirmed Service Vouchers') }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Official bookings confirmed through Reverse Marketplace with local service partners.') }}
            </p>
        </div>

        <a href="{{ route('trips.marketplace.index', $trip) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            {{ __('Marketplace Requests') }}
        </a>
    </div>

    {{-- Bookings List --}}
    @if ($bookings->isEmpty())
        <div class="text-center py-16 px-4 bg-white rounded-2xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">{{ __('No Confirmed Bookings Yet') }}</h2>
            <p class="text-sm text-gray-500 max-w-md mx-auto mt-1 mb-6">
                {{ __('When you accept a provider proposal or pick items, your vouchers will appear here and automatically sync to your timeline.') }}
            </p>
            <a href="{{ route('trips.marketplace.index', $trip) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-md hover:bg-primary/95">
                {{ __('Explore Marketplace') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($bookings as $booking)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow relative overflow-hidden flex flex-col justify-between">
                    {{-- Decorative Top Right Corner --}}
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-emerald-500/10 via-transparent to-transparent pointer-events-none"></div>

                    <div>
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                    {{ $booking->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ __(ucfirst($booking->status)) }}
                                </span>
                                <h2 class="text-lg font-bold text-gray-900 mt-2">{{ $booking->item_name }}</h2>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ __('Provider:') }} <strong class="text-gray-800">{{ $booking->provider?->business_name ?? __('Local Partner') }}</strong>
                                </p>
                            </div>

                            <div class="text-right">
                                <span class="text-lg font-bold text-gray-900">{{ number_format((float) $booking->price, 0, ',', '.') }}</span>
                                <span class="text-xs text-gray-500 font-semibold">VND</span>
                            </div>
                        </div>

                        {{-- Voucher Code Box --}}
                        <div class="p-3.5 rounded-xl bg-gray-50 border border-dashed border-gray-200 flex items-center justify-between mb-4">
                            <div>
                                <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">{{ __('Voucher Code') }}</div>
                                <div class="text-base font-mono font-bold text-primary tracking-wider mt-0.5 select-all">
                                    {{ $booking->voucher_code }}
                                </div>
                            </div>

                            <button type="button"
                                    @click="copyVoucher('{{ $booking->voucher_code }}')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all flex items-center gap-1.5"
                                    :class="copiedCode === '{{ $booking->voucher_code }}' ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-200'">
                                <span x-show="copiedCode !== '{{ $booking->voucher_code }}'">{{ __('Copy Code') }}</span>
                                <span x-show="copiedCode === '{{ $booking->voucher_code }}'">{{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                        <div>
                            {{ __('Booked on:') }} {{ $booking->created_at->format('d/m/Y') }}
                        </div>

                        <a href="{{ route('marketplace.bookings.show', $booking) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1 text-primary font-semibold hover:underline">
                            {{ __('Open Voucher Pass') }}
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
