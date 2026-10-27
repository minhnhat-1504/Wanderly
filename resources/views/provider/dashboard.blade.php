@extends('layouts.app')

@section('title', __('Provider Dashboard — :biz', ['biz' => $provider->business_name]))

@section('content')
<div class="space-y-8" x-data="providerDashboard({{ json_encode($stats['revenue_chart']) }})">

    {{-- ================================================================
         1. HEADER & ACTIONS
    ================================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary mb-1">
                <span>🏢 {{ __('Partner Portal') }}</span>
                <span>•</span>
                <span class="inline-flex items-center gap-1 text-gray-500 font-normal">
                    📍 {{ $provider->destination?->name ?? __('Unknown') }}
                </span>
                @if($provider->is_verified)
                    <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full text-[11px] font-bold border border-emerald-200">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        {{ __('Verified Partner') }}
                    </span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                {{ $provider->business_name }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Overview of your service bookings, revenue growth, customer inquiries and scheduling.') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('provider.calendar') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-gray-200 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                <span>📅</span>
                <span>{{ __('Manage Calendar') }}</span>
            </a>
            <a href="{{ route('provider.services.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-gray-200 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                <span>📦</span>
                <span>{{ __('Catalog Services') }} ({{ $stats['total_services'] }})</span>
            </a>
            <a href="{{ route('provider.bookings.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-primary text-white text-xs sm:text-sm font-semibold hover:bg-teal-800 transition-colors shadow-md shadow-primary/20">
                <span>🎫</span>
                <span>{{ __('All Bookings') }}</span>
            </a>
        </div>
    </div>

    {{-- ================================================================
         2. KPI SUMMARY CARDS (4 CARDS)
    ================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- Card 1: Monthly Revenue --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('This Month Revenue') }}</span>
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">💰</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-3">
                {{ number_format($stats['monthly_revenue'], 0, ',', '.') }} <span class="text-sm font-semibold text-gray-500">VND</span>
            </p>
            <p class="text-xs text-gray-500 mt-1">
                {{ __('Lifetime Total:') }} <span class="font-semibold text-gray-700">{{ number_format($stats['total_revenue'], 0, ',', '.') }} VND</span>
            </p>
        </div>

        {{-- Card 2: Today's Bookings --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('New Bookings Today') }}</span>
                <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-base">⚡</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-3">
                {{ $stats['today_bookings'] }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                {{ __('Active pipeline:') }} <span class="font-semibold text-gray-700">{{ $stats['status_breakdown']['confirmed'] + $stats['status_breakdown']['ongoing'] }} {{ __('trips') }}</span>
            </p>
        </div>

        {{-- Card 3: Occupancy Rate (30 Days) --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('30-Day Occupancy') }}</span>
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">📊</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-3">
                {{ $stats['occupancy_rate'] }}%
            </p>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ min(100, $stats['occupancy_rate']) }}%"></div>
            </div>
        </div>

        {{-- Card 4: Provider Rating --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Customer Rating') }}</span>
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">⭐</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-3 flex items-center gap-1.5">
                <span>{{ number_format($stats['rating'], 1) }}</span>
                <span class="text-xs font-normal text-gray-400">/ 5.0</span>
            </p>
            <p class="text-xs text-gray-500 mt-1">
                {{ __('Based on verified traveler reviews') }}
            </p>
        </div>

    </div>

    {{-- ================================================================
         3. REVENUE CHART (CHART.JS)
    ================================================================= --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h2 class="text-base font-bold text-gray-900">
                    {{ __('Revenue Performance (Last 6 Months)') }}
                </h2>
                <p class="text-xs text-gray-500">
                    {{ __('Total confirmed and ongoing bookings revenue trend over time') }}
                </p>
            </div>
            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-100">
                {{ __('Auto-calculated from confirmed bookings') }}
            </span>
        </div>

        <div class="relative h-64 w-full">
            <canvas id="providerRevenueChart"></canvas>
        </div>
    </div>

    {{-- ================================================================
         4. TWO-COLUMN WORKFLOW: UPCOMING BOOKINGS & OPEN REQUESTS
    ================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- Column 1: Upcoming Bookings Waiting Service --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>🎫</span>
                        <span>{{ __('Upcoming Bookings') }}</span>
                    </h2>
                    <a href="{{ route('provider.bookings.index') }}" class="text-xs font-semibold text-primary hover:underline">
                        {{ __('View All') }} &rarr;
                    </a>
                </div>

                @if($stats['upcoming_bookings']->isEmpty())
                    <div class="text-center py-10 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        <span class="text-3xl">🌴</span>
                        <p class="text-sm font-semibold text-gray-700 mt-2">{{ __('No upcoming bookings scheduled') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Traveler reservations will appear here once accepted.') }}</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($stats['upcoming_bookings'] as $booking)
                            <div class="p-3.5 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/50 transition-all flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ $booking->item_name }}</p>
                                        <span class="shrink-0 text-[10px] font-mono font-bold bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded">
                                            {{ $booking->voucher_code }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate mt-0.5">
                                        {{ __('Client:') }} <span class="font-medium text-gray-700">{{ $booking->trip?->owner?->name ?? __('Traveler') }}</span>
                                        •
                                        <span>{{ $booking->trip?->start_date?->format('d M') }} - {{ $booking->trip?->end_date?->format('d M Y') }}</span>
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold text-emerald-600">
                                        {{ number_format($booking->price, 0, ',', '.') }} VND
                                    </p>
                                    <a href="{{ route('provider.bookings.show', $booking) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline mt-0.5">
                                        <span>💬 {{ __('Chat / Details') }}</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Column 2: Open Travel Requests to Bid On --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>📢</span>
                        <span>{{ __('Open Travel Requests in :dest', ['dest' => $provider->destination?->name ?? '']) }}</span>
                    </h2>
                </div>

                @if($stats['open_requests']->isEmpty())
                    <div class="text-center py-10 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        <span class="text-3xl">✅</span>
                        <p class="text-sm font-semibold text-gray-700 mt-2">{{ __('All matched requests have been responded to') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('New traveler requests at your destination will appear automatically.') }}</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($stats['open_requests'] as $request)
                            <div class="p-3.5 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/50 transition-all flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate">
                                        {{ $request->trip?->name ?? __('Travel Request') }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5 text-xs text-gray-500 mt-1">
                                        <span class="text-gray-600">Budget: <strong class="text-gray-900">{{ number_format($request->max_budget, 0, ',', '.') }} VND</strong></span>
                                        <span>•</span>
                                        <span>{{ $request->trip?->start_date?->format('d M') }} ({{ $request->proposals_count }} {{ __('proposals') }})</span>
                                    </div>
                                    @if(!empty($request->needs))
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach((array)$request->needs as $need)
                                                <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                                                    {{ $need }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="shrink-0">
                                    <a href="{{ route('marketplace.proposals.create', $request) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-white text-xs font-semibold transition-colors">
                                        <span>📝</span>
                                        <span>{{ __('Submit Proposal') }}</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
function providerDashboard(chartData) {
    return {
        chartData: chartData,
        init() {
            this.$nextTick(() => {
                this.renderChart();
            });
        },
        renderChart() {
            const ctx = document.getElementById('providerRevenueChart');
            if (!ctx || !window.Chart) return;

            new window.Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.chartData.labels,
                    datasets: [{
                        label: '{{ __('Confirmed Revenue (VND)') }}',
                        data: this.chartData.values,
                        backgroundColor: 'rgba(13, 148, 136, 0.75)',
                        borderColor: 'rgb(13, 148, 136)',
                        borderWidth: 1.5,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VND';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                }
                            },
                            grid: { color: 'rgba(0, 0, 0, 0.04)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endpush
@endsection
