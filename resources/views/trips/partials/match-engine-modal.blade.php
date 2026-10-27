<div
    x-data="{
        open: false,
        tripId: {{ $trip->id }},
        draft: null,
        loading: false,
        applying: false,

        init() {
            window.addEventListener('open-match-engine-modal', () => {
                this.open = true;
                this.fetchDraft();
            });
        },

        async fetchDraft() {
            this.loading = true;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/match-engine/suggest`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.draft) {
                    this.draft = data.draft;
                }
            } catch (err) {
                console.error('Failed to fetch match engine suggestions', err);
            } finally {
                this.loading = false;
            }
        },

        async applyDraft() {
            if (!this.draft) return;
            if (!confirm('{{ __('Apply this suggested itinerary to your timeline? This will populate activities into your days.') }}')) return;

            this.applying = true;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/match-engine/apply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    },
                    body: JSON.stringify({ draft: this.draft })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect || window.location.href;
                }
            } catch (err) {
                console.error('Failed to apply draft', err);
            } finally {
                this.applying = false;
            }
        }
    }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="match-engine-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
        @click="open = false"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-gray-100"
            @click.stop
        >
            {{-- Header --}}
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-teal-50/50 via-emerald-50/30 to-sky-50/30">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900" id="match-engine-title">
                        {{ __('Auto Suggest Draft Itinerary') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('Algorithmic match balancing votes, mood harmony, ratings and zone clustering.') }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="h-8 w-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center text-lg font-bold select-none"
                    aria-label="{{ __('Close') }}"
                >
                    &times;
                </button>
            </div>

            {{-- Summary Bar --}}
            <div x-show="draft" class="px-6 py-3.5 bg-gray-50/80 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1 font-semibold text-gray-700">
                        <span>{{ __('Pace:') }}</span>
                        <span class="uppercase font-bold text-teal-700" x-text="draft?.pace"></span>
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-gray-700">
                        <span>{{ __('Group Mood:') }}</span>
                        <span class="font-bold text-teal-700" x-text="(draft?.group_mood_score ?? 100) + '%'"></span>
                    </span>
                </div>

                <div class="flex items-center gap-2 font-mono">
                    <span class="text-gray-500">{{ __('Est. Cost:') }}</span>
                    <span class="font-bold text-gray-900" x-text="new Intl.NumberFormat().format(draft?.total_cost ?? 0) + ' ₫'"></span>
                    <template x-if="draft?.budget > 0">
                        <span
                            class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                            :class="draft?.is_within_budget ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            x-text="draft?.is_within_budget ? '{{ __('Within Budget') }}' : '{{ __('Over Budget') }}'"
                        ></span>
                    </template>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-6 max-h-[65vh] overflow-y-auto">
                <div x-show="loading" class="py-16 text-center text-xs text-gray-400 space-y-2">
                    <div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p>{{ __('Computing objective match formula & zone clustering...') }}</p>
                </div>

                <div x-show="!loading && draft" class="space-y-6">
                    <template x-for="day in draft?.days" :key="day.day_number">
                        <div class="border border-gray-100 rounded-2xl overflow-hidden bg-white shadow-xs">
                            {{-- Day Header with Zone Clustered --}}
                            <div class="px-4 py-3 bg-gray-50/90 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-gray-900" x-text="'{{ __('Day') }} ' + day.day_number"></span>
                                    <span class="text-gray-300">•</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-teal-50 text-teal-700 border border-teal-100">
                                        <span x-text="day.zone_name"></span>
                                    </span>
                                </div>
                                <span class="text-[11px] text-gray-400 font-medium" x-text="day.activities.length + ' {{ __('spots') }}'"></span>
                            </div>

                            {{-- Activities in Day --}}
                            <div class="divide-y divide-gray-50">
                                <template x-for="act in day.activities" :key="act.title + act.time_slot">
                                    <div class="p-4 space-y-2.5 hover:bg-gray-50/40 transition-colors">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="space-y-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-teal-50 text-teal-800" x-text="act.time_slot"></span>
                                                    <span class="text-xs text-gray-400 capitalize" x-text="act.category"></span>
                                                </div>
                                                <h4 class="text-sm font-bold text-gray-900" x-text="act.title"></h4>
                                                <p class="text-xs text-gray-500 line-clamp-1" x-text="act.location"></p>
                                            </div>

                                            <div class="text-right shrink-0">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/60">
                                                    <span class="text-[10px] mr-0.5">Score:</span>
                                                    <span x-text="act.score_breakdown.final_score"></span>
                                                    <span class="text-[10px] font-normal text-amber-700">/10</span>
                                                </span>
                                                <p class="text-xs font-mono text-gray-600 mt-1" x-text="new Intl.NumberFormat().format(act.cost) + ' ₫'"></p>
                                            </div>
                                        </div>

                                        {{-- Score Breakdown Details --}}
                                        <div class="p-2.5 rounded-xl bg-gray-50 text-[11px] space-y-1.5 border border-gray-100/80">
                                            <p class="text-gray-600 italic">
                                                <span x-text="act.score_breakdown.explanation"></span>
                                            </p>
                                            <div class="flex flex-wrap items-center gap-3 text-gray-500 pt-1 border-t border-gray-200/50">
                                                <span>{{ __('Votes:') }} <strong class="text-gray-800" x-text="act.score_breakdown.vote_score"></strong></span>
                                                <span>{{ __('Mood Match:') }} <strong class="text-gray-800" x-text="act.score_breakdown.mood_match"></strong></span>
                                                <span>{{ __('Rating:') }} <strong class="text-gray-800" x-text="act.score_breakdown.rating_score"></strong></span>
                                                <span>{{ __('Budget Penalty:') }} <strong class="text-gray-800" x-text="act.score_breakdown.cost_penalty"></strong></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <button
                    type="button"
                    @click="fetchDraft()"
                    :disabled="loading"
                    class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-900 rounded-xl hover:bg-gray-200/60 transition"
                >
                    {{ __('Regenerate Draft') }}
                </button>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="open = false"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 rounded-xl"
                    >
                        {{ __('Cancel') }}
                    </button>

                    @can('update', $trip)
                        <button
                            type="button"
                            @click="applyDraft()"
                            :disabled="applying || !draft"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 transition shadow-sm disabled:opacity-50"
                        >
                            <span x-show="!applying">{{ __('Apply to Timeline') }}</span>
                            <span x-show="applying">{{ __('Applying to Timeline...') }}</span>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
