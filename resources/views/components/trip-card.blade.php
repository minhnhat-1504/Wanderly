@props([
    'trip',
])

@php
    use Carbon\Carbon;

    // Date and countdown calculation
    $today = Carbon::today();
    $startDate = $trip->start_date ? Carbon::parse($trip->start_date)->startOfDay() : null;
    $endDate = $trip->end_date ? Carbon::parse($trip->end_date)->endOfDay() : null;

    if ($startDate && $today->lt($startDate)) {
        $daysLeft = (int) $today->diffInDays($startDate, false);
        $countdownLabel = __(':days days left', ['days' => max(1, $daysLeft)]);
        $countdownBadgeClass = 'bg-emerald-600/90 text-white backdrop-blur-md shadow-emerald-900/20';
        $countdownDotClass = 'bg-white';
    } elseif ($startDate && $endDate && $today->lte($endDate)) {
        $countdownLabel = __('Ongoing');
        $countdownBadgeClass = 'bg-amber-500/95 text-white backdrop-blur-md shadow-amber-900/20';
        $countdownDotClass = 'bg-white animate-ping';
    } else {
        $countdownLabel = __('Completed');
        $countdownBadgeClass = 'bg-gray-800/85 text-gray-200 backdrop-blur-md';
        $countdownDotClass = 'bg-gray-400';
    }

    // Budget calculation (spent fallback to 0 if not present)
    $budget = (float) ($trip->budget ?? 0);
    $spent = (float) ($trip->spent ?? 0);
    $percentage = $budget > 0 ? round(($spent / $budget) * 100) : 0;
    $percentageCapped = min(max($percentage, 0), 100);

    if ($percentage < 80) {
        $budgetBarColor = 'bg-emerald-500';
        $budgetBadgeColor = 'text-emerald-700 bg-emerald-50 border-emerald-200';
    } elseif ($percentage <= 95) {
        $budgetBarColor = 'bg-amber-500';
        $budgetBadgeColor = 'text-amber-700 bg-amber-50 border-amber-200';
    } else {
        $budgetBarColor = 'bg-red-500';
        $budgetBadgeColor = 'text-red-700 bg-red-50 border-red-200';
    }

    // Privacy configuration
    $privacyConfig = match ($trip->privacy) {
        'public' => [
            'label' => __('Public'),
            'class' => 'bg-sky-50 text-sky-700 border-sky-200',
        ],
        'group' => [
            'label' => __('Group'),
            'class' => 'bg-purple-50 text-purple-700 border-purple-200',
        ],
        default => [
            'label' => __('Private'),
            'class' => 'bg-gray-100 text-gray-700 border-gray-200',
        ],
    };

    // Status configuration
    $statusConfig = match ($trip->status) {
        'ongoing' => [
            'label' => __('Ongoing'),
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
        ],
        'completed' => [
            'label' => __('Completed'),
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        ],
        default => [
            'label' => __('Planning'),
            'class' => 'bg-teal-50 text-teal-700 border-teal-200',
        ],
    };

    // Formatted dates
    $formattedStartDate = $startDate ? $startDate->format('M d, Y') : '';
    $formattedEndDate = $endDate ? $endDate->format('M d, Y') : '';

    // Route fallback
    $tripUrl = Route::has('trips.show') ? route('trips.show', $trip) : url('/trips/' . $trip->id);
@endphp

<article class="group bg-white rounded-2xl border border-gray-200/80 hover:border-primary/40 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col overflow-hidden">
    {{-- Card Cover Image Area --}}
    <div class="relative h-48 w-full overflow-hidden bg-gray-100">
        @if(!empty($trip->cover))
            @php
                $coverSrc = str_starts_with($trip->cover, 'http') ? $trip->cover : asset('storage/' . $trip->cover);
            @endphp
            <img
                src="{{ $coverSrc }}"
                alt="{{ $trip->name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
            >
        @else
            {{-- Fallback Gradient Background --}}
            <div class="w-full h-full bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-900 flex items-center justify-center relative overflow-hidden group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="text-center p-4 relative z-10">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center text-white mb-2 shadow-inner">
                        <svg class="w-6 h-6 text-teal-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-teal-100/90 uppercase tracking-wider">{{ $trip->destination }}</p>
                </div>
            </div>
        @endif

        {{-- Countdown Badge (Top Right) --}}
        <div class="absolute top-3 right-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold shadow-md {{ $countdownBadgeClass }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $countdownDotClass }}"></span>
                {{ $countdownLabel }}
            </span>
        </div>
    </div>

    {{-- Card Body --}}
    <div class="p-5 flex-1 flex flex-col justify-between">
        <div>
            {{-- Trip Title --}}
            <h3 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors duration-200 line-clamp-1">
                <a href="{{ $tripUrl }}" class="focus:outline-none">
                    {{ $trip->name }}
                </a>
            </h3>

            {{-- Destination --}}
            <div class="flex items-center gap-1.5 text-sm text-gray-600 mt-1.5">
                <svg class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span class="truncate font-medium">{{ $trip->destination }}</span>
            </div>

            {{-- Date Range --}}
            @if($formattedStartDate && $formattedEndDate)
                <div class="flex items-center gap-1.5 text-xs text-gray-400 mt-2">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>{{ $formattedStartDate }} — {{ $formattedEndDate }}</span>
                </div>
            @endif

            {{-- Budget Progress Section --}}
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-gray-500 font-medium">{{ __('Budget') }}</span>
                    <span class="font-semibold text-gray-800">
                        {{ number_format($budget, 0, ',', '.') }} VND
                    </span>
                </div>

                {{-- Progress Bar --}}
                <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div
                        class="h-2 rounded-full transition-all duration-500 {{ $budgetBarColor }}"
                        style="width: {{ $percentageCapped }}%;"
                    ></div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1">
                    <span>{{ __('Used: :percent%', ['percent' => $percentage]) }}</span>
                    @if($spent > 0)
                        <span>{{ number_format($spent, 0, ',', '.') }} VND</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card Footer: Status & Privacy Tags --}}
        <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
            {{-- Status Badge --}}
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border {{ $statusConfig['class'] }}">
                {{ $statusConfig['label'] }}
            </span>

            {{-- Privacy Badge --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border {{ $privacyConfig['class'] }}">
                @if($trip->privacy === 'public')
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                @elseif($trip->privacy === 'group')
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                @else
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                @endif
                {{ $privacyConfig['label'] }}
            </span>
        </div>
    </div>
</article>
