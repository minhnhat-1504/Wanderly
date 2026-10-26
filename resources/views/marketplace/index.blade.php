@extends('layouts.app')

@section('title', __('Reverse Marketplace — :trip', ['trip' => $trip->name]))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Top Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('trips.show', $trip) }}" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ $trip->name }}
                </a>
                <span>/</span>
                <span class="text-gray-900 font-semibold">{{ __('Marketplace Requests') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                {{ __('Travel Requests & Quotes') }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Post trip requirements and let local partners in :dest bid with best-rate service packages.', ['dest' => $trip->destination]) }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('trips.marketplace.bookings', $trip) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('My Vouchers') }}
            </a>

            @can('manageMarketplace', $trip)
                <a href="{{ route('trips.marketplace.requests.create', $trip) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-md shadow-primary/25 hover:shadow-primary/40 hover:bg-primary/95 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Post New Request') }}
                </a>
            @endcan
        </div>
    </div>

    {{-- Requests Listing --}}
    @if ($requests->isEmpty())
        <div class="text-center py-16 px-4 bg-white rounded-2xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4 text-primary">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">{{ __('No Travel Requests Yet') }}</h2>
            <p class="text-sm text-gray-500 max-w-md mx-auto mt-1 mb-6">
                {{ __('Ready to find local drivers, guides, or room bundles? Post a request and receive competitive proposals.') }}
            </p>
            @can('manageMarketplace', $trip)
                <a href="{{ route('trips.marketplace.requests.create', $trip) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-md hover:bg-primary/95">
                    {{ __('Create First Request') }}
                </a>
            @endcan
        </div>
    @else
        <div class="space-y-4">
            @foreach ($requests as $req)
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2.5">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                {{ $req->status === 'fulfilled' ? 'bg-emerald-100 text-emerald-800' : ($req->status === 'bidding' ? 'bg-amber-100 text-amber-800' : ($req->status === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-800')) }}">
                                {{ __(ucfirst($req->status)) }}
                            </span>
                            <span class="text-xs text-gray-400 font-medium">
                                #{{ $req->id }} • {{ $req->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="font-bold text-gray-900">{{ __('Needed:') }}</span>
                            @foreach ((array) ($req->needs ?? []) as $need)
                                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-medium">{{ __(ucfirst($need)) }}</span>
                            @endforeach
                            @if ($req->pace)
                                <span class="text-gray-400">•</span>
                                <span class="text-gray-600"><strong>{{ __('Pace:') }}</strong> {{ __(ucfirst($req->pace)) }}</span>
                            @endif
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-600"><strong>{{ __('Budget Cap:') }}</strong> {{ number_format((float) $req->max_budget, 0, ',', '.') }} đ</span>
                        </div>

                        @if ($req->notes)
                            <p class="text-xs text-gray-500 line-clamp-1 italic">
                                "{{ $req->notes }}"
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <a href="{{ route('trips.marketplace.compare', [$trip, $req]) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-sm hover:bg-primary/95 transition-all">
                            <span>{{ __('View Proposals') }}</span>
                            <span class="px-1.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-bold">
                                {{ $req->proposals_count }}
                            </span>
                        </a>

                        @if ($req->status === 'open' || $req->status === 'bidding')
                            @can('manageMarketplace', $trip)
                                <form action="{{ route('trips.marketplace.requests.destroy', [$trip, $req]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to cancel this request?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2.5 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors" title="{{ __('Cancel Request') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
