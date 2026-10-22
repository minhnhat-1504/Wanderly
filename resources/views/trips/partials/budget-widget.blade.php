@props([
    'trip',
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
        minimized: false,
        drawerOpen: false,
        warningShown80: false,
        warningShown95: false,

        init() {
            // Listen for drawer toggle to gracefully hide widget
            window.addEventListener('drawer-toggled', (e) => {
                this.drawerOpen = e.detail?.open || false;
            });

            // Listen for timeline or room updates
            window.addEventListener('budget-recalculate', () => this.recalculateFromDOM());
            window.addEventListener('timeline-updated', () => this.recalculateFromDOM());
            window.addEventListener('activity-saved', () => this.recalculateFromDOM());

            // Initial calculation from DOM cards if present
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

            // Trigger threshold toasts if crossed
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

        formatMoney(num) {
            return new Intl.NumberFormat('vi-VN').format(Math.max(0, num)) + ' VND';
        }
    }"
    x-show="!drawerOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-6 right-6 z-30 max-w-sm w-full transition-all duration-300"
>
    {{-- Widget Card --}}
    <div class="bg-white/95 backdrop-blur-md rounded-2xl border shadow-xl p-4 transition-all duration-300"
         :class="statusColor === 'red' ? 'border-red-300 ring-2 ring-red-500/20 shadow-red-500/10' : (statusColor === 'amber' ? 'border-amber-300 shadow-amber-500/10' : 'border-gray-200/90 shadow-gray-900/10')">

        {{-- Header row --}}
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full"
                      :class="statusColor === 'red' ? 'bg-red-500 animate-ping' : (statusColor === 'amber' ? 'bg-amber-500' : 'bg-emerald-500')"></span>
                <span class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    {{ __('Live Budget') }}
                </span>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                      :class="statusColor === 'red' ? 'bg-red-100 text-red-800' : (statusColor === 'amber' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')"
                      x-text="percentage + '%'">
                </span>
            </div>

            <button
                type="button"
                @click="minimized = !minimized"
                class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100"
                :title="minimized ? '{{ __('Expand') }}' : '{{ __('Minimize') }}'"
            >
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': minimized }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
        </div>

        {{-- Expandable Content --}}
        <div x-show="!minimized" x-collapse class="pt-3 space-y-3">
            {{-- Metrics Grid --}}
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-gray-50 p-2.5 rounded-xl">
                    <span class="text-gray-500 block text-[11px]">{{ __('Spent') }}</span>
                    <span class="font-bold text-gray-900 text-sm truncate block" x-text="formatMoney(spent)"></span>
                </div>

                <div class="p-2.5 rounded-xl" :class="remaining < 0 ? 'bg-red-50 text-red-900' : 'bg-emerald-50 text-emerald-900'">
                    <span class="block text-[11px]" :class="remaining < 0 ? 'text-red-600' : 'text-emerald-600'">
                        <span x-text="remaining < 0 ? '{{ __('Exceeded') }}' : '{{ __('Remaining') }}'"></span>
                    </span>
                    <span class="font-bold text-sm truncate block" x-text="formatMoney(Math.abs(remaining))"></span>
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

                <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1">
                    <span>{{ __('Total:') }} <strong class="text-gray-700" x-text="formatMoney(budget)"></strong></span>
                    <span x-text="statusColor === 'red' ? '⚠️ Over 95%' : (statusColor === 'amber' ? '⚡ 80-95%' : '✓ Safe')"></span>
                </div>
            </div>
        </div>
    </div>
</div>
