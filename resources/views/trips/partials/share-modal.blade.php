<div
    x-data="{
        open: false,
        tripId: {{ $trip->id }},
        isShared: {{ $trip->is_shared ? 'true' : 'false' }},
        shareSlug: '{{ $trip->share_slug ?? '' }}',
        shareUrl: '{{ $trip->share_slug ? url('/s/' . $trip->share_slug) : '' }}',
        budgetVisible: {{ $trip->is_share_budget_visible ? 'true' : 'false' }},
        copied: false,
        loading: false,

        init() {
            window.addEventListener('open-share-modal', () => {
                this.open = true;
            });
        },

        async toggleShare() {
            this.loading = true;
            try {
                const res = await fetch(`/trips/${this.tripId}/share/toggle`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.isShared = data.is_shared;
                    this.shareSlug = data.share_slug;
                    this.shareUrl = data.share_url;
                }
            } catch (err) {
                console.error('Failed to toggle share', err);
            } finally {
                this.loading = false;
            }
        },

        async toggleBudget() {
            try {
                const res = await fetch(`/trips/${this.tripId}/share/budget`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.budgetVisible = data.is_share_budget_visible;
                }
            } catch (err) {
                console.error('Failed to toggle budget visibility', err);
            }
        },

        async revokeLink() {
            if (!confirm('Revoking will immediately invalidate the current public link. Anyone with the old link will lose access. Continue?')) {
                return;
            }
            this.loading = true;
            try {
                const res = await fetch(`/trips/${this.tripId}/share/revoke`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.shareSlug = data.share_slug;
                    this.shareUrl = data.share_url;
                }
            } catch (err) {
                console.error('Failed to revoke link', err);
            } finally {
                this.loading = false;
            }
        },

        copyToClipboard() {
            if (!this.shareUrl) return;
            navigator.clipboard.writeText(this.shareUrl).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
            });
        }
    }"
    x-cloak
    x-show="open"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
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
            class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100"
        >
            {{-- Header --}}
            <div class="px-6 py-5 bg-gradient-to-r from-teal-700 to-emerald-800 text-white flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">{{ __('Share Trip Publicly') }}</h3>
                    <p class="text-xs text-teal-100">{{ __('Allow friends or viewers to view your itinerary without logging in') }}</p>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="h-8 w-8 rounded-lg text-teal-200 hover:text-white hover:bg-white/10 transition flex items-center justify-center text-lg font-bold select-none"
                    aria-label="{{ __('Close') }}"
                >
                    &times;
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-6">
                {{-- Master Switch: Enable Sharing --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-200/80">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __('Public Share Link') }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <span x-show="isShared" class="text-teal-700 font-medium">{{ __('Active (Anyone with link can view)') }}</span>
                            <span x-show="!isShared" class="text-gray-400">{{ __('Disabled (Access is blocked)') }}</span>
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="toggleShare()"
                        :disabled="loading"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="isShared ? 'bg-teal-700' : 'bg-gray-300'"
                    >
                        <span
                            aria-hidden="true"
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="isShared ? 'translate-x-5' : 'translate-x-0'"
                        ></span>
                    </button>
                </div>

                {{-- Link Display & Copy --}}
                <div x-show="isShared" x-transition class="space-y-2">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        {{ __('Shareable URL') }}
                    </label>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            readonly
                            :value="shareUrl"
                            class="block w-full px-3.5 py-2.5 rounded-xl border border-gray-300 bg-gray-50 text-gray-700 text-xs font-mono select-all focus:outline-none"
                        >
                        <button
                            type="button"
                            @click="copyToClipboard()"
                            class="shrink-0 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200"
                            :class="copied ? 'bg-emerald-600 text-white' : 'bg-teal-700 text-white hover:bg-teal-800'"
                        >
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied">{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Privacy & Security Options --}}
                <div x-show="isShared" x-transition class="space-y-4 pt-4 border-t border-gray-100">
                    {{-- Budget Visibility Toggle --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-800">{{ __('Show Budget to Public') }}</p>
                            <p class="text-[11px] text-gray-500">{{ __('When disabled, total budget and costs will be hidden on public view') }}</p>
                        </div>
                        <button
                            type="button"
                            @click="toggleBudget()"
                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                            :class="budgetVisible ? 'bg-primary' : 'bg-gray-300'"
                        >
                            <span
                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200"
                                :class="budgetVisible ? 'translate-x-4' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>

                    {{-- Revoke Link Button --}}
                    <div class="flex items-center justify-between pt-2">
                        <div>
                            <p class="text-xs font-semibold text-red-600">{{ __('Revoke Link') }}</p>
                            <p class="text-[11px] text-gray-500">{{ __('Regenerate link to stop previous viewers from accessing') }}</p>
                        </div>
                        <button
                            type="button"
                            @click="revokeLink()"
                            :disabled="loading"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 transition-colors"
                        >
                            {{ __('Revoke & Refresh') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 flex justify-end">
                <x-button variant="secondary" type="button" @click="open = false" class="text-xs">
                    {{ __('Close') }}
                </x-button>
            </div>
        </div>
    </div>
</div>
