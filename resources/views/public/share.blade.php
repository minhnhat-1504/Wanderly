<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $trip->name }} — {{ __('Shared Travel Plan | Wanderly') }}</title>
    <meta name="description" content="{{ __('View the complete itinerary for :name in :destination.', ['name' => $trip->name, 'destination' => $trip->destination]) }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    {{-- Top Public Navigation Bar --}}
    <header class="bg-white/90 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 bg-primary rounded-xl flex items-center justify-center text-white shadow-md shadow-primary/20">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M2 12h20"></path>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight text-gray-900">Wanderly</span>
                </a>
                <span class="text-gray-300">|</span>
                <span class="text-xs sm:text-sm font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                    👁️ {{ __('Read-only Public View') }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span class="hidden sm:inline">{{ __('Print Itinerary') }}</span>
                </button>

                @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-primary text-white hover:bg-teal-800 transition-colors shadow-sm">
                        {{ __('Join Wanderly') }}
                    </a>
                @endguest
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- Hero Header Card --}}
        <div class="relative bg-white rounded-3xl shadow-sm border border-gray-200/80 overflow-hidden">
            @if(!empty($trip->cover))
                @php
                    $coverSrc = str_starts_with($trip->cover, 'http') ? $trip->cover : asset('storage/' . $trip->cover);
                @endphp
                <div class="h-56 sm:h-72 w-full overflow-hidden bg-gray-100 relative">
                    <img src="{{ $coverSrc }}" alt="{{ $trip->name }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
                </div>
            @else
                <div class="h-44 sm:h-56 w-full bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-900 relative overflow-hidden flex items-center justify-center">
                    <div class="text-center text-white/20 select-none">
                        <span class="text-7xl">✈️</span>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
            @endif

            <div class="p-6 sm:p-8 relative">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                📍 {{ $trip->destination }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                ⏱️ {{ __(':count Days', ['count' => $tripDays]) }}
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                            {{ $trip->name }}
                        </h1>
                        <p class="text-sm text-gray-500 flex items-center gap-2">
                            <span>📅 {{ $trip->start_date ? \Carbon\Carbon::parse($trip->start_date)->format('M d, Y') : '' }} – {{ $trip->end_date ? \Carbon\Carbon::parse($trip->end_date)->format('M d, Y') : '' }}</span>
                            <span>•</span>
                            <span>👤 {{ __('Organized by :name', ['name' => $trip->owner->name ?? __('Traveler')]) }}</span>
                        </p>
                    </div>

                    {{-- Public Badge --}}
                    <div class="shrink-0 text-right">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200">
                            🌐 {{ __('Public Itinerary') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget Widget (Only if Visible) --}}
        @if($budgetStatus)
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200/80 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>💰</span>
                            <span>{{ __('Trip Budget Overview') }}</span>
                        </h2>
                        <p class="text-xs text-gray-500">{{ __('Estimated expenditure and allocated resources') }}</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <div>
                            <span class="text-gray-400 block">{{ __('Total Budget') }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ number_format($budgetStatus['budget'], 0, ',', '.') }} VND</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">{{ __('Total Spent') }}</span>
                            <span class="text-sm font-bold text-primary">{{ number_format($budgetStatus['total_spent'], 0, ',', '.') }} VND</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">{{ __('Remaining') }}</span>
                            <span class="text-sm font-bold text-emerald-700">{{ number_format($budgetStatus['remaining_budget'], 0, ',', '.') }} VND</span>
                        </div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="w-full bg-gray-100 h-3 rounded-full overflow-hidden shadow-inner">
                    @php
                        $barColor = $budgetStatus['status'] === 'danger' ? 'bg-red-500' : ($budgetStatus['status'] === 'warning' ? 'bg-amber-500' : 'bg-emerald-500');
                    @endphp
                    <div class="h-full {{ $barColor }} rounded-full transition-all duration-500" style="width: {{ min(100, $budgetStatus['percentage']) }}%"></div>
                </div>
            </div>
        @else
            <div class="bg-gray-100/70 border border-dashed border-gray-300 rounded-2xl p-4 text-center text-xs text-gray-500">
                🔒 {{ __('Budget and cost details are kept private by the organizer.') }}
            </div>
        @endif

        {{-- Itinerary Grid by Day --}}
        <div class="space-y-8" x-data="{ activeDay: 1 }">
            {{-- Day Navigator --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-gray-200">
                @for($d = 1; $d <= $tripDays; $d++)
                    @php
                        $dateForDay = \Carbon\Carbon::parse($trip->start_date)->addDays($d - 1);
                    @endphp
                    <button
                        type="button"
                        @click="activeDay = {{ $d }}"
                        class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold shrink-0 transition-all duration-200 flex items-center gap-2"
                        :class="activeDay === {{ $d }} ? 'bg-primary text-white shadow-md shadow-primary/20 scale-105' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'"
                    >
                        <span>{{ __('Day :num', ['num' => $d]) }}</span>
                        <span class="text-[11px] font-normal opacity-80">({{ $dateForDay->format('M d') }})</span>
                    </button>
                @endfor
            </div>

            {{-- Day Timeline Containers --}}
            @for($d = 1; $d <= $tripDays; $d++)
                <div x-show="activeDay === {{ $d }}" x-transition class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach(['morning' => 'Morning (06:00 – 11:00)', 'midday' => 'Midday (11:00 – 14:00)', 'afternoon' => 'Afternoon (14:00 – 18:00)', 'evening' => 'Evening (18:00 – 23:00)'] as $slotKey => $slotLabel)
                            @php
                                $slotActivities = $timelineByDay[$d][$slotKey] ?? collect();
                            @endphp
                            <div class="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-sm flex flex-col min-h-[350px]">
                                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                        @if($slotKey === 'morning') 🌅
                                        @elseif($slotKey === 'midday') ☀️
                                        @elseif($slotKey === 'afternoon') 🌤️
                                        @else 🌙 @endif
                                        <span>{{ __($slotKey) }}</span>
                                    </h3>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                        {{ $slotActivities->count() }}
                                    </span>
                                </div>

                                <div class="flex-1 space-y-3">
                                    @forelse($slotActivities as $activity)
                                        <div class="p-3.5 rounded-2xl border bg-gray-50/60 border-gray-200 space-y-2 hover:bg-white hover:shadow-md transition-all">
                                            <div class="flex items-start justify-between gap-2">
                                                <h4 class="text-sm font-bold text-gray-900 leading-snug">{{ $activity->title }}</h4>
                                                @if($activity->type === 'room')
                                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 shrink-0">🏨 {{ __('Stay') }}</span>
                                                @else
                                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 border border-teal-200 shrink-0">{{ ucfirst($activity->category) }}</span>
                                                @endif
                                            </div>

                                            @if($activity->start_time)
                                                <p class="text-xs text-gray-500 flex items-center gap-1">
                                                    <span>⏰ {{ substr($activity->start_time, 0, 5) }}</span>
                                                    @if($activity->end_time)
                                                        <span>– {{ substr($activity->end_time, 0, 5) }}</span>
                                                    @endif
                                                </p>
                                            @endif

                                            @if($activity->location)
                                                <p class="text-xs text-gray-600 flex items-center gap-1 truncate" title="{{ $activity->location }}">
                                                    <span>📍</span>
                                                    <span class="truncate">{{ $activity->location }}</span>
                                                </p>
                                            @endif

                                            @if($activity->notes)
                                                <p class="text-xs text-gray-500 bg-white p-2 rounded-xl border border-gray-100 italic">{{ $activity->notes }}</p>
                                            @endif

                                            @if($budgetStatus && $activity->cost > 0)
                                                <p class="text-xs font-bold text-emerald-700 pt-1 border-t border-gray-100 text-right">
                                                    {{ number_format($activity->cost, 0, ',', '.') }} VND
                                                </p>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="h-32 flex flex-col items-center justify-center text-center text-gray-300">
                                            <span class="text-2xl mb-1">💤</span>
                                            <span class="text-xs">{{ __('No planned activities') }}</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endfor
        </div>

        {{-- Checklist Summary Section --}}
        @if(isset($checklistItems) && $checklistItems->isNotEmpty())
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-gray-200/80 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-teal-50 text-primary flex items-center justify-center text-xl">
                            🧳
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ __('Trip Packing & Checklist') }}</h2>
                            <p class="text-xs text-gray-500">{{ __('Essentials prepared for this adventure') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-primary">{{ $checklistProgress['completed'] }}/{{ $checklistProgress['total'] }} ({{ $checklistProgress['percentage'] }}%)</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($checklistItems as $item)
                        <div class="flex items-center justify-between p-3 rounded-2xl border {{ $item->is_completed ? 'bg-gray-50 border-gray-200 opacity-70' : 'bg-white border-gray-200/80 shadow-sm' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] {{ $item->is_completed ? 'bg-emerald-500 text-white' : 'border border-gray-300' }}">
                                    @if($item->is_completed) ✓ @endif
                                </span>
                                <span class="text-xs font-medium text-gray-800 truncate {{ $item->is_completed ? 'line-through text-gray-400' : '' }}">
                                    {{ $item->title }}
                                </span>
                            </div>
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 shrink-0">
                                {{ $item->category }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 py-6 mt-12 text-center text-xs text-gray-500">
        <p>&copy; {{ date('Y') }} Wanderly. {{ __('Built for intelligent collaborative travel planning.') }}</p>
    </footer>

</body>
</html>
