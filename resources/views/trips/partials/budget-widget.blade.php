@props([
    'trip',
    'isMobile' => false,
])

@php
    $initialBudget = (float) ($trip->budget ?? 0);
    $initialSpent = (float) ($trip->spent ?? 0);
    $initialRemaining = max(0, $initialBudget - $initialSpent);
    $initialPercent = $initialBudget > 0 ? min(round(($initialSpent / $initialBudget) * 100), 100) : 0;
@endphp

<div
    x-data="{
        tripId: {{ $trip->id }},
        budget: {{ $initialBudget }},
        spent: {{ $initialSpent }},
        remaining: {{ $initialRemaining }},
        percentage: {{ $initialPercent }},
        minimized: {{ $isMobile ? 'true' : 'false' }},
        warningShown80: false,
        warningShown95: false,

        init() {
            window.addEventListener('budget-recalculate', () => this.recalculateFromDOM());
            window.addEventListener('timeline-updated', () => this.recalculateFromDOM());
            window.addEventListener('activity-saved', () => this.recalculateFromDOM());

            this.$nextTick(() => this.recalculateFromDOM());
        },

        recalculateFromDOM() {
            let totalActivityCost = 0;
            document.querySelectorAll('.timeline-activity-card').forEach((card) => {
                totalActivityCost += parseFloat(card.dataset.cost || 0);
            });

            this.spent = totalActivityCost;
            this.remaining = this.budget - this.spent;
            this.percentage = this.budget > 0 ? Math.round((this.spent / this.budget) * 100) : 0;

            if (this.percentage >= 95 && !this.warningShown95) {
                this.warningShown95 = true;
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        type: 'error',
                        message: 'Danger: You have utilized over 95% of your trip budget!'
                    }
                }));
            } else if (this.percentage >= 80 && this.percentage < 95 && !this.warningShown80) {
                this.warningShown80 = true;
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        type: 'warning',
                        message: 'Warning: 80% of your travel budget has been reached.'
                    }
                }));
            }
        },

        get statusColor() {
            if (this.percentage < 80) return 'emerald';
            if (this.percentage <= 95) return 'amber';
            return 'red';
        },

        get statusText() {
            if (this.percentage < 80) return 'Safe';
            if (this.percentage <= 95) return 'Warning';
            return 'Over Budget';
        },

        formatMoney(num) {
            return new Intl.NumberFormat('vi-VN').format(Math.max(0, num)) + ' ₫';
        }
    }"
    class="w-full transition-all duration-200"
>
    {{-- Card Container (in-flow, no floating!) --}}
    <div
        class="bg-white rounded-2xl border shadow-sm p-4 transition-all duration-200"
        :class="statusColor === 'red' ? 'border-red-300 ring-1 ring-red-200' : (statusColor === 'amber' ? 'border-amber-300' : 'border-gray-200/90')"
    >
        {{-- Header row --}}
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span
                    class="w-2.5 h-2.5 rounded-full"
                    :class="statusColor === 'red' ? 'bg-red-500 animate-pulse' : (statusColor === 'amber' ? 'bg-amber-500' : 'bg-emerald-500')"
                ></span>
                <span class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    {{ __('Live Budget') }}
                </span>
                <span
                    class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                    :class="statusColor === 'red' ? 'bg-red-100 text-red-800' : (statusColor === 'amber' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')"
                    x-text="percentage + '%'"
                >
                </span>
            </div>

            <button
                type="button"
                @click="minimized = !minimized"
                class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-2 py-1 rounded-md hover:bg-gray-100 transition"
                :title="minimized ? '{{ __('Expand') }}' : '{{ __('Collapse') }}'"
                x-text="minimized ? '{{ __('Expand') }}' : '{{ __('Collapse') }}'"
            >
            </button>
        </div>

        {{-- Expandable Content --}}
        <div x-show="!minimized" x-collapse class="pt-3 space-y-3">
            {{-- Metrics Grid --}}
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                    <span class="text-gray-500 block text-[11px] font-medium">{{ __('Spent') }}</span>
                    <span class="font-bold text-gray-900 text-sm truncate block mt-0.5" x-text="formatMoney(spent)"></span>
                </div>

                <div
                    class="p-2.5 rounded-xl border"
                    :class="remaining < 0 ? 'bg-red-50 text-red-900 border-red-200' : 'bg-emerald-50 text-emerald-900 border-emerald-200'"
                >
                    <span
                        class="block text-[11px] font-medium"
                        :class="remaining < 0 ? 'text-red-700' : 'text-emerald-700'"
                        x-text="remaining < 0 ? '{{ __('Exceeded') }}' : '{{ __('Remaining') }}'"
                    ></span>
                    <span class="font-bold text-sm truncate block mt-0.5" x-text="formatMoney(Math.abs(remaining))"></span>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div>
                <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="statusColor === 'red' ? 'bg-red-500' : (statusColor === 'amber' ? 'bg-amber-500' : 'bg-emerald-500')"
                        :style="'width: ' + Math.min(percentage, 100) + '%'"
                    ></div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-gray-500 mt-1.5 font-medium">
                    <span>{{ __('Total:') }} <strong class="text-gray-800" x-text="formatMoney(budget)"></strong></span>
                    <span
                        class="font-semibold"
                        :class="statusColor === 'red' ? 'text-red-600' : (statusColor === 'amber' ? 'text-amber-600' : 'text-emerald-600')"
                        x-text="statusText"
                    ></span>
                </div>
            </div>
        </div>
    </div>
</div>
