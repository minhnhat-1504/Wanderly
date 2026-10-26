<div
    x-data="{
        open: false,
        tripId: {{ $trip->id }},
        filter: 'all', // 'all', 'leaderboard'
        candidates: [],
        loading: false,

        init() {
            window.addEventListener('open-voting-drawer', () => {
                this.open = true;
                this.loadCandidates();
            });
        },

        async loadCandidates() {
            this.loading = true;
            try {
                const endpoint = this.filter === 'leaderboard'
                    ? `/ajax/trips/${this.tripId}/votes/leaderboard`
                    : `/ajax/trips/${this.tripId}/candidates`;

                const res = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.candidates = data.candidates || data.leaderboard || [];
                }
            } catch (err) {
                console.error('Failed to load voting candidates', err);
            } finally {
                this.loading = false;
            }
        },

        async vote(candidate, val) {
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/votes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        votable_type: candidate.votable_type,
                        votable_id: candidate.votable_id,
                        value: val
                    })
                });
                const data = await res.json();
                if (data.success && data.candidate) {
                    // Update candidate in local array
                    const idx = this.candidates.findIndex(c => c.key === candidate.key);
                    if (idx > -1) {
                        this.candidates[idx] = data.candidate;
                    }
                }
            } catch (err) {
                console.error('Failed to vote', err);
            }
        }
    }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
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

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div
            x-show="open"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen max-w-md bg-white shadow-2xl flex flex-col"
            @click.stop
        >
            {{-- Drawer Header --}}
            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-amber-50/50 to-orange-50/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-100/80 text-amber-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
                        🗳️
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ __('Candidate Voting Pool') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Vote +1 or -1 on destination highlights.') }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Filter Tabs --}}
            <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2 bg-gray-50/50">
                <button
                    type="button"
                    @click="filter = 'all'; loadCandidates()"
                    :class="filter === 'all' ? 'bg-white shadow-xs text-primary font-bold' : 'text-gray-600 hover:text-gray-900'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all border border-gray-200/60"
                >
                    {{ __('All Candidates') }}
                </button>
                <button
                    type="button"
                    @click="filter = 'leaderboard'; loadCandidates()"
                    :class="filter === 'leaderboard' ? 'bg-white shadow-xs text-primary font-bold' : 'text-gray-600 hover:text-gray-900'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all border border-gray-200/60 flex items-center gap-1.5"
                >
                    <span>🏆</span>
                    <span>{{ __('Top Leaderboard') }}</span>
                </button>
            </div>

            {{-- Candidate Cards List --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-3.5">
                <div x-show="loading" class="py-12 text-center text-xs text-gray-400">
                    {{ __('Loading candidate recommendations...') }}
                </div>

                <template x-for="item in candidates" :key="item.key">
                    <div class="p-4 rounded-2xl border border-gray-100 bg-white hover:border-gray-200 hover:shadow-xs transition-all space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider"
                                        :class="item.type === 'poi' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'"
                                        x-text="item.type"
                                    ></span>
                                    <span class="text-xs text-gray-400 capitalize" x-text="item.category"></span>
                                </div>
                                <h4 class="text-sm font-bold text-gray-900 truncate" x-text="item.name"></h4>
                                <p class="text-xs text-gray-500 line-clamp-1" x-text="item.zone"></p>
                            </div>

                            {{-- Rating & Cost --}}
                            <div class="text-right shrink-0 space-y-1">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                                    <span>★</span>
                                    <span x-text="item.rating"></span>
                                </span>
                                <p class="text-xs font-mono font-medium text-gray-600" x-text="new Intl.NumberFormat().format(item.cost) + ' ₫'"></p>
                            </div>
                        </div>

                        {{-- Voting Action Bar --}}
                        <div class="pt-2 border-t border-gray-50 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-medium text-gray-400">{{ __('Net Score:') }}</span>
                                <span
                                    class="text-xs font-bold px-2 py-0.5 rounded-md"
                                    :class="item.score > 0 ? 'bg-emerald-50 text-emerald-700' : (item.score < 0 ? 'bg-rose-50 text-rose-700' : 'bg-gray-100 text-gray-600')"
                                    x-text="(item.score > 0 ? '+' : '') + item.score"
                                ></span>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- Upvote (+1) --}}
                                <button
                                    type="button"
                                    @click="vote(item, 1)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border"
                                    :class="item.user_vote === 1
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                        : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-emerald-50 hover:text-emerald-700'"
                                >
                                    <span>👍</span>
                                    <span x-text="item.upvotes"></span>
                                </button>

                                {{-- Downvote (-1) --}}
                                <button
                                    type="button"
                                    @click="vote(item, -1)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border"
                                    :class="item.user_vote === -1
                                        ? 'bg-rose-600 text-white border-rose-600 shadow-xs'
                                        : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-rose-50 hover:text-rose-700'"
                                >
                                    <span>👎</span>
                                    <span x-text="item.downvotes"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="!loading && candidates.length === 0" class="py-12 text-center text-xs text-gray-400">
                    {{ __('No candidates available yet for this destination.') }}
                </div>
            </div>
        </div>
    </div>
</div>
