@extends('layouts.app')

@section('title', __('Bookings & Vouchers — :biz', ['biz' => $provider->business_name]))

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <a href="{{ route('provider.dashboard') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                    <span>🏢</span>
                    <span>{{ $provider->business_name }}</span>
                </a>
                <span>/</span>
                <span class="text-gray-900 font-bold">{{ __('Client Bookings') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                {{ __('Confirmed Bookings & Customer Vouchers') }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Track fulfillment, verify customer voucher codes upon arrival, and chat directly with trip leaders.') }}
            </p>
        </div>
    </div>

    {{-- Bookings Table --}}
    @if($bookings->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-gray-200">
            <span class="text-4xl">🎫</span>
            <h3 class="text-base font-bold text-gray-900 mt-3">{{ __('No bookings received yet') }}</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                {{ __('When travelers accept your marketplace proposals, confirmed bookings and vouchers will appear here.') }}
            </p>
            <div class="mt-6">
                <a href="{{ route('provider.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    {{ __('Back to Dashboard') }}
                </a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700">
                    <thead class="bg-gray-50/70 border-b border-gray-100 text-[11px] uppercase font-bold text-gray-400 tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 sm:px-6">{{ __('Voucher Code') }}</th>
                            <th class="py-3.5 px-4">{{ __('Service Item') }}</th>
                            <th class="py-3.5 px-4">{{ __('Client / Trip') }}</th>
                            <th class="py-3.5 px-4">{{ __('Travel Dates') }}</th>
                            <th class="py-3.5 px-4">{{ __('Price (VND)') }}</th>
                            <th class="py-3.5 px-4">{{ __('Status') }}</th>
                            <th class="py-3.5 px-4 sm:px-6 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($bookings as $booking)
                            @php
                                $channel = $channels->get($booking->id);
                                $unread = $channel['unread'] ?? 0;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-4 sm:px-6 font-mono font-bold text-gray-900">
                                    {{ $booking->voucher_code }}
                                </td>
                                <td class="py-4 px-4 font-semibold text-gray-900">
                                    {{ $booking->item_name }}
                                </td>
                                <td class="py-4 px-4">
                                    <p class="font-medium text-gray-900">{{ $booking->trip?->owner?->name ?? __('Traveler') }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $booking->trip?->name }}</p>
                                </td>
                                <td class="py-4 px-4 text-xs text-gray-600">
                                    @if($booking->trip?->start_date && $booking->trip?->end_date)
                                        {{ $booking->trip->start_date->format('d M') }} - {{ $booking->trip->end_date->format('d M Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-4 px-4 font-bold text-emerald-600">
                                    {{ number_format($booking->price, 0, ',', '.') }} VND
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                        @if($booking->status === 'confirmed') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif($booking->status === 'ongoing') bg-blue-50 text-blue-700 border border-blue-200
                                        @elseif($booking->status === 'completed') bg-gray-100 text-gray-700
                                        @else bg-red-50 text-red-700 @endif">
                                        {{ ucfirst(__($booking->status)) }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 sm:px-6 text-right">
                                    <a href="{{ route('provider.bookings.show', $booking) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-white text-xs font-semibold transition-colors">
                                        <span>💬</span>
                                        <span>{{ __('Chat with Traveler') }}</span>
                                        @if($unread > 0)
                                            <span class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full">
                                                {{ $unread }}
                                            </span>
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($bookings->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
