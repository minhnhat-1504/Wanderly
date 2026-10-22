<div
    x-data="{
        open: false,
        loading: false,
        items: [],
        progress: { total: 0, completed: 0, percentage: 0 },
        newItemTitle: '',
        newItemCategory: 'clothing',
        activeCategory: 'all',
        applyingTemplate: false,
        tripId: {{ $trip->id }},

        init() {
            window.addEventListener('open-checklist-drawer', () => {
                this.open = true;
                this.fetchChecklist();
            });
        },

        async fetchChecklist() {
            this.loading = true;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/checklist`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.items = data.items;
                    this.progress = data.progress;
                }
            } catch (err) {
                console.error('Failed to load checklist', err);
            } finally {
                this.loading = false;
            }
        },

        async addItem() {
            if (!this.newItemTitle.trim()) return;
            const title = this.newItemTitle.trim();
            const category = this.newItemCategory;
            this.newItemTitle = '';

            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/checklist`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    },
                    body: JSON.stringify({ title, category })
                });
                const data = await res.json();
                if (data.success) {
                    this.items.push(data.item);
                    this.progress = data.progress;
                }
            } catch (err) {
                console.error('Failed to add item', err);
            }
        },

        async toggleItem(item) {
            item.is_completed = !item.is_completed;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/checklist/${item.id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.progress = data.progress;
                }
            } catch (err) {
                item.is_completed = !item.is_completed; // Rollback
                console.error('Failed to toggle item', err);
            }
        },

        async deleteItem(item) {
            if (!confirm('{{ __('Delete this checklist item?') }}')) return;
            const originalItems = [...this.items];
            this.items = this.items.filter(i => i.id !== item.id);

            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/checklist/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.progress = data.progress;
                }
            } catch (err) {
                this.items = originalItems;
                console.error('Failed to delete item', err);
            }
        },

        async applyTemplate(type) {
            if (this.items.length > 0 && !confirm('{{ __('Add preset checklist items to your current trip?') }}')) {
                return;
            }
            this.applyingTemplate = true;

            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/checklist/apply-template`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    },
                    body: JSON.stringify({ template: type })
                });
                const data = await res.json();
                if (data.success) {
                    this.items = data.items;
                    this.progress = data.progress;
                }
            } catch (err) {
                console.error('Failed to apply template', err);
            } finally {
                this.applyingTemplate = false;
            }
        },

        filteredItems() {
            if (this.activeCategory === 'all') return this.items;
            return this.items.filter(i => i.category === this.activeCategory);
        }
    }"
    x-cloak
    x-show="open"
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="slide-over-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-300"
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
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen max-w-md bg-white shadow-2xl flex flex-col"
        >
            {{-- Header --}}
            <div class="p-6 bg-gradient-to-r from-teal-700 to-emerald-800 text-white flex items-center justify-between shrink-0 shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-xl">
                        🧳
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">{{ __('Trip Checklist') }}</h2>
                        <p class="text-xs text-teal-100">{{ __('Pack smart & prepare all essentials') }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="p-2 rounded-xl text-teal-200 hover:text-white hover:bg-white/10 transition-colors"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            {{-- Progress Bar Card --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 shrink-0">
                <div class="flex items-center justify-between text-xs font-semibold text-gray-700 mb-1.5">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :class="progress.percentage === 100 ? 'bg-emerald-500' : 'bg-primary'"></span>
                        {{ __('Preparation Progress') }}
                    </span>
                    <span class="text-primary font-bold" x-text="`${progress.completed}/${progress.total} (${progress.percentage}%)`"></span>
                </div>
                <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden shadow-inner">
                    <div
                        class="h-full bg-gradient-to-r from-teal-500 to-emerald-500 rounded-full transition-all duration-500 ease-out"
                        :style="`width: ${progress.percentage}%`"
                    ></div>
                </div>
            </div>

            {{-- Template Presets --}}
            <div class="px-6 py-3 bg-white border-b border-gray-100 shrink-0">
                <p class="text-xs font-medium text-gray-500 mb-2">{{ __('Quick Templates') }}:</p>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="applyTemplate('beach')"
                        :disabled="applyingTemplate"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition-colors"
                    >
                        <span>🏖️</span>
                        <span>{{ __('Beach') }}</span>
                    </button>
                    <button
                        type="button"
                        @click="applyTemplate('mountain')"
                        :disabled="applyingTemplate"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                    >
                        <span>🏔️</span>
                        <span>{{ __('Highland') }}</span>
                    </button>
                    <button
                        type="button"
                        @click="applyTemplate('city')"
                        :disabled="applyingTemplate"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-colors"
                    >
                        <span>🏙️</span>
                        <span>{{ __('City') }}</span>
                    </button>
                </div>
            </div>

            {{-- Category Filter Pills --}}
            <div class="px-6 py-2.5 bg-gray-50/50 border-b border-gray-100 shrink-0 overflow-x-auto flex items-center gap-1.5 no-scrollbar">
                <button
                    type="button"
                    @click="activeCategory = 'all'"
                    class="px-2.5 py-1 rounded-full text-xs font-medium shrink-0 transition-colors"
                    :class="activeCategory === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-gray-200/70 text-gray-700 hover:bg-gray-300'"
                >
                    {{ __('All') }}
                </button>
                <button
                    type="button"
                    @click="activeCategory = 'clothing'"
                    class="px-2.5 py-1 rounded-full text-xs font-medium shrink-0 transition-colors"
                    :class="activeCategory === 'clothing' ? 'bg-primary text-white shadow-sm' : 'bg-gray-200/70 text-gray-700 hover:bg-gray-300'"
                >
                    👕 {{ __('Clothing') }}
                </button>
                <button
                    type="button"
                    @click="activeCategory = 'gear'"
                    class="px-2.5 py-1 rounded-full text-xs font-medium shrink-0 transition-colors"
                    :class="activeCategory === 'gear' ? 'bg-primary text-white shadow-sm' : 'bg-gray-200/70 text-gray-700 hover:bg-gray-300'"
                >
                    🔦 {{ __('Gear') }}
                </button>
                <button
                    type="button"
                    @click="activeCategory = 'electronics'"
                    class="px-2.5 py-1 rounded-full text-xs font-medium shrink-0 transition-colors"
                    :class="activeCategory === 'electronics' ? 'bg-primary text-white shadow-sm' : 'bg-gray-200/70 text-gray-700 hover:bg-gray-300'"
                >
                    🔌 {{ __('Electronics') }}
                </button>
                <button
                    type="button"
                    @click="activeCategory = 'health'"
                    class="px-2.5 py-1 rounded-full text-xs font-medium shrink-0 transition-colors"
                    :class="activeCategory === 'health' ? 'bg-primary text-white shadow-sm' : 'bg-gray-200/70 text-gray-700 hover:bg-gray-300'"
                >
                    💊 {{ __('Health') }}
                </button>
                <button
                    type="button"
                    @click="activeCategory = 'documents'"
                    class="px-2.5 py-1 rounded-full text-xs font-medium shrink-0 transition-colors"
                    :class="activeCategory === 'documents' ? 'bg-primary text-white shadow-sm' : 'bg-gray-200/70 text-gray-700 hover:bg-gray-300'"
                >
                    📄 {{ __('Documents') }}
                </button>
            </div>

            {{-- Items List --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-2">
                <template x-if="loading">
                    <div class="py-12 text-center text-gray-400">
                        <svg class="animate-spin w-6 h-6 mx-auto mb-2 text-primary" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <p class="text-xs">{{ __('Loading checklist items...') }}</p>
                    </div>
                </template>

                <template x-if="!loading && items.length === 0">
                    <div class="py-12 text-center text-gray-400">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3 text-2xl">
                            📝
                        </div>
                        <p class="text-sm font-semibold text-gray-700">{{ __('No items in your checklist') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Add custom items below or select a template above.') }}</p>
                    </div>
                </template>

                <template x-for="item in filteredItems()" :key="item.id">
                    <div
                        class="group flex items-center justify-between p-3 rounded-xl border transition-all duration-200"
                        :class="item.is_completed ? 'bg-gray-50 border-gray-200 opacity-60' : 'bg-white border-gray-200/80 hover:border-primary/40 shadow-sm'"
                    >
                        <div class="flex items-center gap-3 flex-1 min-w-0 mr-2">
                            <input
                                type="checkbox"
                                :checked="item.is_completed"
                                @change="toggleItem(item)"
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer"
                            >
                            <span
                                class="text-sm font-medium text-gray-800 truncate"
                                :class="{ 'line-through text-gray-400': item.is_completed }"
                                x-text="item.title"
                            ></span>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span
                                class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-gray-100 text-gray-500"
                                x-text="item.category"
                            ></span>
                            <button
                                type="button"
                                @click="deleteItem(item)"
                                class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-600 transition-opacity"
                                title="{{ __('Delete') }}"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer: Add Form --}}
            <div class="p-4 bg-white border-t border-gray-200 shrink-0">
                <form @submit.prevent="addItem()" class="space-y-3">
                    <div class="flex gap-2">
                        <input
                            type="text"
                            x-model="newItemTitle"
                            placeholder="{{ __('Add packing item (e.g., Toothbrush)...') }}"
                            class="flex-1 px-3.5 py-2 rounded-xl border border-gray-300 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        <select
                            x-model="newItemCategory"
                            class="px-2.5 py-2 rounded-xl border border-gray-300 text-xs font-medium text-gray-700 bg-gray-50 focus:outline-none focus:border-primary"
                        >
                            <option value="clothing">{{ __('Clothing') }}</option>
                            <option value="gear">{{ __('Gear') }}</option>
                            <option value="electronics">{{ __('Electronics') }}</option>
                            <option value="health">{{ __('Health') }}</option>
                            <option value="documents">{{ __('Documents') }}</option>
                            <option value="accessories">{{ __('Accessories') }}</option>
                            <option value="personal_care">{{ __('Personal') }}</option>
                        </select>
                    </div>
                    <x-button variant="primary" type="submit" class="w-full text-xs py-2">
                        <span>+ {{ __('Add Item') }}</span>
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</div>
