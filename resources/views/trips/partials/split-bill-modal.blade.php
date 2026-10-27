<div
    x-data="{
        open: false,
        tripId: {{ $trip->id }},
        activityId: null,
        activityTitle: '',
        activityCost: 0,
        payerId: null,
        splitType: 'equal', // 'equal' or 'custom'
        selectedMembers: [],
        customAmounts: {},
        allMembers: [],
        loading: false,
        saving: false,
        errorMessage: '',

        init() {
            window.addEventListener('open-split-bill-modal', (e) => {
                this.activityId = e.detail.activityId;
                this.activityTitle = e.detail.activityTitle || '{{ __('Activity') }}';
                this.activityCost = parseFloat(e.detail.activityCost || 0);
                this.open = true;
                this.errorMessage = '';
                this.loadActivitySplit();
            });
        },

        async loadActivitySplit() {
            this.loading = true;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/activities/${this.activityId}/split`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.allMembers = data.trip_members || [];
                    const details = data.split_details || {};
                    this.payerId = details.payer_id || (this.allMembers[0] ? this.allMembers[0].id : null);
                    
                    if (details.participants && details.participants.length > 0) {
                        this.selectedMembers = details.participants.map(p => p.user_id);
                        this.splitType = details.is_custom ? 'custom' : 'equal';
                        this.customAmounts = {};
                        details.participants.forEach(p => {
                            if (p.custom_amount !== null) {
                                this.customAmounts[p.user_id] = p.custom_amount;
                            }
                        });
                    } else {
                        // Default: all members participate, equal split
                        this.selectedMembers = this.allMembers.map(m => m.id);
                        this.splitType = 'equal';
                        this.customAmounts = {};
                    }
                }
            } catch (err) {
                console.error('Failed to load activity split details', err);
            } finally {
                this.loading = false;
            }
        },

        selectAll() {
            this.selectedMembers = this.allMembers.map(m => m.id);
        },

        deselectAll() {
            this.selectedMembers = [];
        },

        toggleMember(id) {
            const index = this.selectedMembers.indexOf(id);
            if (index > -1) {
                this.selectedMembers.splice(index, 1);
            } else {
                this.selectedMembers.push(id);
            }
        },

        get equalShare() {
            if (this.selectedMembers.length === 0 || this.activityCost <= 0) return 0;
            return Math.round(this.activityCost / this.selectedMembers.length);
        },

        get customTotal() {
            let sum = 0;
            this.selectedMembers.forEach(id => {
                sum += parseFloat(this.customAmounts[id] || 0);
            });
            return Math.round(sum * 100) / 100;
        },

        async saveSplit() {
            if (this.selectedMembers.length === 0) {
                this.errorMessage = '{{ __('Please select at least one participant.') }}';
                return;
            }

            if (this.splitType === 'custom') {
                const diff = Math.abs(this.customTotal - this.activityCost);
                if (diff > 1) { // 1 VND tolerance
                    this.errorMessage = `{{ __('Total allocated amount (:allocated) does not match activity cost (:cost).') }}`
                        .replace(':allocated', this.customTotal.toLocaleString('vi-VN') + ' VND')
                        .replace(':cost', this.activityCost.toLocaleString('vi-VN') + ' VND');
                    return;
                }
            }

            this.saving = true;
            this.errorMessage = '';

            try {
                const payload = {
                    payer_id: this.payerId,
                    user_ids: this.selectedMembers,
                    custom_amounts: this.splitType === 'custom' ? this.customAmounts : null
                };

                const res = await fetch(`/ajax/trips/${this.tripId}/activities/${this.activityId}/split`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    this.open = false;
                    window.dispatchEvent(new CustomEvent('activity-split-updated', {
                        detail: {
                            activityId: this.activityId,
                            splitDetails: data.split_details
                        }
                    }));
                } else {
                    this.errorMessage = data.message || '{{ __('Failed to update split-bill.') }}';
                }
            } catch (err) {
                console.error('Error saving split', err);
                this.errorMessage = '{{ __('A network error occurred.') }}';
            } finally {
                this.saving = false;
            }
        }
    }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="split-bill-modal-title"
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
            @click.away="open = false"
        >
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold tracking-tight" id="split-bill-modal-title">
                            {{ __('Split Bill for Activity') }}
                        </h3>
                        <p class="text-xs text-teal-100 mt-0.5" x-text="activityTitle"></p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-teal-200 hover:text-white hover:bg-white/10 transition"
                        @click="open = false"
                    >
                        <span class="sr-only">{{ __('Close') }}</span>
                        ✕
                    </button>
                </div>
                <div class="mt-3 flex items-center justify-between bg-white/10 rounded-xl px-4 py-2 text-xs">
                    <span>{{ __('Total Cost') }}:</span>
                    <span class="font-bold text-sm tracking-wide" x-text="activityCost.toLocaleString('vi-VN') + ' VND'"></span>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-5 space-y-5">
                {{-- Error Alert --}}
                <div x-show="errorMessage" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700" x-text="errorMessage"></div>

                {{-- Loading Spinner --}}
                <div x-show="loading" class="text-center py-8 text-gray-500 text-sm">
                    {{ __('Loading participants...') }}
                </div>

                <div x-show="!loading" class="space-y-4">
                    {{-- 1. Select Who Paid First --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            {{ __('Paid in advance by') }}
                        </label>
                        <select
                            x-model="payerId"
                            class="w-full text-sm rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 py-2"
                        >
                            <option value="">{{ __('-- Select who paid upfront --') }}</option>
                            <template x-for="member in allMembers" :key="'payer-' + member.id">
                                <option :value="member.id" x-text="member.name + (member.is_owner ? ' (Leader)' : '')"></option>
                            </template>
                        </select>
                    </div>

                    {{-- 2. Split Mode Tabs --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            {{ __('Split Method') }}
                        </label>
                        <div class="grid grid-cols-2 gap-2 bg-gray-100 p-1 rounded-xl text-xs font-medium">
                            <button
                                type="button"
                                @click="splitType = 'equal'"
                                :class="splitType === 'equal' ? 'bg-white shadow text-teal-700 font-bold' : 'text-gray-600 hover:text-gray-900'"
                                class="py-1.5 rounded-lg transition text-center"
                            >
                                {{ __('Equal Split') }}
                            </button>
                            <button
                                type="button"
                                @click="splitType = 'custom'"
                                :class="splitType === 'custom' ? 'bg-white shadow text-teal-700 font-bold' : 'text-gray-600 hover:text-gray-900'"
                                class="py-1.5 rounded-lg transition text-center"
                            >
                                {{ __('Custom Amounts') }}
                            </button>
                        </div>
                    </div>

                    {{-- 3. Participants Checklist --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                {{ __('Participating Members') }} (<span x-text="selectedMembers.length"></span>)
                            </label>
                            <div class="text-xs space-x-2">
                                <button type="button" @click="selectAll()" class="text-teal-600 hover:underline font-medium">
                                    {{ __('Select All') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="deselectAll()" class="text-gray-500 hover:underline">
                                    {{ __('Deselect') }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                            <template x-for="member in allMembers" :key="'member-' + member.id">
                                <div class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200/80 hover:bg-gray-50 transition">
                                    <label class="flex items-center space-x-3 cursor-pointer flex-1">
                                        <input
                                            type="checkbox"
                                            :value="member.id"
                                            :checked="selectedMembers.includes(member.id)"
                                            @change="toggleMember(member.id)"
                                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                        >
                                        <div class="flex items-center space-x-2">
                                            <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-xs">
                                                <span x-text="member.name.charAt(0).toUpperCase()"></span>
                                            </div>
                                            <span class="text-sm font-medium text-gray-800" x-text="member.name"></span>
                                        </div>
                                    </label>

                                    {{-- Amount Preview / Input --}}
                                    <div class="shrink-0 text-right">
                                        <template x-if="selectedMembers.includes(member.id) && splitType === 'equal'">
                                            <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-2 py-1 rounded-md" x-text="equalShare.toLocaleString('vi-VN') + ' đ'"></span>
                                        </template>

                                        <template x-if="selectedMembers.includes(member.id) && splitType === 'custom'">
                                            <div class="flex items-center space-x-1">
                                                <input
                                                    type="number"
                                                    x-model="customAmounts[member.id]"
                                                    placeholder="0"
                                                    class="w-24 text-right text-xs rounded-lg border-gray-300 py-1 px-1.5 focus:border-teal-500 focus:ring-teal-500"
                                                >
                                                <span class="text-xs text-gray-400">đ</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Custom Total Status --}}
                    <template x-if="splitType === 'custom'">
                        <div class="flex justify-between items-center text-xs p-2.5 rounded-xl" :class="Math.abs(customTotal - activityCost) <= 1 ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'">
                            <span>{{ __('Total Allocated') }}: <strong x-text="customTotal.toLocaleString('vi-VN') + ' đ'"></strong></span>
                            <span>{{ __('Required') }}: <strong x-text="activityCost.toLocaleString('vi-VN') + ' đ'"></strong></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 rounded-b-2xl border-t border-gray-100">
                <button
                    type="button"
                    class="rounded-xl px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition"
                    @click="open = false"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="button"
                    class="rounded-xl bg-teal-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500 transition disabled:opacity-50"
                    :disabled="saving || loading || selectedMembers.length === 0"
                    @click="saveSplit()"
                >
                    <span x-show="!saving">{{ __('Save Split') }}</span>
                    <span x-show="saving">{{ __('Saving...') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
