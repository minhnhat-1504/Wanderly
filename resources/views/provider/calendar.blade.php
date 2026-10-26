@extends('layouts.app')

@section('title', __('Availability Calendar — :biz', ['biz' => $provider->business_name]))

@section('content')
<div class="space-y-6" x-data="providerCalendar({
    currentMonth: '{{ $month->format('Y-m') }}',
    blockedDates: {{ json_encode($blockedDates) }},
    bookedDates: {{ json_encode($bookedDates) }}
})">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <a href="{{ route('provider.dashboard') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                    <span>🏢</span>
                    <span>{{ $provider->business_name }}</span>
                </a>
                <span>/</span>
                <span class="text-gray-900 font-bold">{{ __('Availability Calendar') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                {{ __('Service Schedule & Blocked Dates') }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Click any date to quickly block or re-open availability. Blocked dates prevent automatic request matching.') }}
            </p>
        </div>

        {{-- Month Navigator --}}
        <div class="flex items-center gap-2">
            @php
                $prevMonth = $month->copy()->subMonth()->format('Y-m');
                $nextMonth = $month->copy()->addMonth()->format('Y-m');
            @endphp
            <a href="{{ route('provider.calendar', ['month' => $prevMonth]) }}" class="p-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors" title="{{ __('Previous Month') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <span class="text-base font-bold text-gray-900 px-3 py-1 bg-white border border-gray-200 rounded-xl shadow-sm">
                {{ $month->format('F Y') }}
            </span>
            <a href="{{ route('provider.calendar', ['month' => $nextMonth]) }}" class="p-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors" title="{{ __('Next Month') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
            <a href="{{ route('provider.calendar') }}" class="ml-2 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-700 transition-colors">
                {{ __('Today') }}
            </a>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-gray-600 bg-white p-3.5 rounded-2xl border border-gray-100">
        <span class="text-gray-400 font-bold uppercase tracking-wider text-[11px]">{{ __('Legend:') }}</span>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-md bg-white border-2 border-emerald-500"></span>
            <span>{{ __('Available (Open for requests)') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-md bg-red-100 border-2 border-red-500"></span>
            <span>{{ __('Blocked by you') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-md bg-indigo-100 border-2 border-indigo-500"></span>
            <span>{{ __('Booked trip active') }}</span>
        </div>
        <div class="flex items-center gap-1.5 text-gray-400">
            <span class="w-3.5 h-3.5 rounded-md bg-gray-100 border border-gray-200"></span>
            <span>{{ __('Past date') }}</span>
        </div>
    </div>

    {{-- Calendar Grid --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6">
        @php
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            $startDayOfWeek = ($startOfMonth->dayOfWeekIso - 1); // 0 (Mon) to 6 (Sun)
            $daysInMonth = $month->daysInMonth;
            $today = \Carbon\Carbon::today();
        @endphp

        {{-- Weekday Header --}}
        <div class="grid grid-cols-7 gap-2 mb-2 text-center text-xs font-bold uppercase tracking-wider text-gray-400">
            <div>{{ __('Mon') }}</div>
            <div>{{ __('Tue') }}</div>
            <div>{{ __('Wed') }}</div>
            <div>{{ __('Thu') }}</div>
            <div>{{ __('Fri') }}</div>
            <div class="text-primary">{{ __('Sat') }}</div>
            <div class="text-red-500">{{ __('Sun') }}</div>
        </div>

        {{-- Days Grid --}}
        <div class="grid grid-cols-7 gap-2">
            {{-- Empty leading cells --}}
            @for($i = 0; $i < $startDayOfWeek; $i++)
                <div class="h-24 rounded-xl bg-gray-50/40 border border-transparent"></div>
            @endfor

            {{-- Month days --}}
            @for($dayNum = 1; $dayNum <= $daysInMonth; $dayNum++)
                @php
                    $dateObj = $month->copy()->day($dayNum);
                    $dateStr = $dateObj->toDateString();
                    $isPast = $dateObj->lt($today);
                    $isToday = $dateObj->isSameDay($today);
                @endphp

                <div
                    @if(!$isPast)
                        @click="toggleDate('{{ $dateStr }}')"
                    @endif
                    :class="{
                        'bg-red-50 border-red-300 ring-2 ring-red-200': isBlocked('{{ $dateStr }}'),
                        'bg-indigo-50 border-indigo-300': isBooked('{{ $dateStr }}') && !isBlocked('{{ $dateStr }}'),
                        'bg-white border-gray-200 hover:border-primary hover:shadow-sm': !isBlocked('{{ $dateStr }}') && !isBooked('{{ $dateStr }}') && !{{ $isPast ? 'true' : 'false' }},
                        'bg-gray-50/70 border-gray-100 opacity-60 cursor-not-allowed': {{ $isPast ? 'true' : 'false' }},
                        'cursor-pointer': !{{ $isPast ? 'true' : 'false' }}
                    }"
                    class="h-24 p-2.5 rounded-xl border flex flex-col justify-between transition-all duration-150 relative group"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold {{ $isToday ? 'w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs' : 'text-gray-800' }}">
                            {{ $dayNum }}
                        </span>

                        <template x-if="isBlocked('{{ $dateStr }}')">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-red-700 bg-red-100 px-1.5 py-0.5 rounded">
                                {{ __('Blocked') }}
                            </span>
                        </template>

                        <template x-if="isBooked('{{ $dateStr }}') && !isBlocked('{{ $dateStr }}')">
                            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-1.5 py-0.5 rounded" x-text="bookedDates['{{ $dateStr }}'] + ' {{ __('Booked') }}'"></span>
                        </template>
                    </div>

                    <div class="text-[11px] truncate">
                        <template x-if="isBlocked('{{ $dateStr }}')">
                            <p class="text-red-600 truncate font-medium" x-text="blockedDates['{{ $dateStr }}'] || '{{ __('Day off / Busy') }}'"></p>
                        </template>
                        <template x-if="!isBlocked('{{ $dateStr }}') && !isBooked('{{ $dateStr }}') && !{{ $isPast ? 'true' : 'false' }}">
                            <p class="text-gray-400 group-hover:text-primary transition-colors text-[10px]">{{ __('Click to block') }}</p>
                        </template>
                    </div>
                </div>
            @endfor
        </div>
    </div>

</div>

@push('scripts')
<script>
function providerCalendar(config) {
    return {
        currentMonth: config.currentMonth,
        blockedDates: config.blockedDates || {},
        bookedDates: config.bookedDates || {},
        loading: false,

        isBlocked(date) {
            return date in this.blockedDates;
        },

        isBooked(date) {
            return date in this.bookedDates && this.bookedDates[date] > 0;
        },

        async toggleDate(date) {
            if (this.loading) return;
            this.loading = true;

            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            const isCurrentlyBlocked = this.isBlocked(date);

            try {
                if (isCurrentlyBlocked) {
                    // Unblock
                    const res = await fetch('{{ route('ajax.provider.calendar.unblock') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ date: date })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        delete this.blockedDates[date];
                        this.blockedDates = { ...this.blockedDates };
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: data.message } }));
                    } else {
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: data.message || 'Error' } }));
                    }
                } else {
                    // Block
                    const reason = prompt('{{ __('Reason for blocking this day (optional):') }}', 'Day off / Fleet maintenance');
                    if (reason === null) {
                        this.loading = false;
                        return; // User cancelled prompt
                    }

                    const res = await fetch('{{ route('ajax.provider.calendar.block') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ date: date, reason: reason })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.blockedDates[date] = reason;
                        this.blockedDates = { ...this.blockedDates };
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: data.message } }));
                    } else {
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: data.message || 'Error' } }));
                    }
                }
            } catch (err) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: 'Network error' } }));
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
