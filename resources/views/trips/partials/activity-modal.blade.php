@props([
    'trip',
    'tripDays' => 1,
])

<div
    x-data="{
        open: false,
        mode: 'create',
        tripId: {{ $trip->id }},
        loading: false,
        errors: {},
        form: {
            id: null,
            title: '',
            day: 1,
            time_slot: 'morning',
            start_time: '',
            end_time: '',
            cost: 0,
            category: 'sightseeing',
            location: '',
            address: '',
            notes: ''
        },
        init() {
            window.addEventListener('open-activity-modal', (e) => {
                window.dispatchEvent(new CustomEvent('close-room-drawer'));
                this.errors = {};
                this.mode = e.detail?.mode || 'create';
                if (this.mode === 'edit' && e.detail?.activity) {
                    this.form = Object.assign({}, e.detail.activity);
                } else {
                    this.form = {
                        id: null,
                        title: '',
                        day: e.detail?.day || 1,
                        time_slot: e.detail?.time_slot || 'morning',
                        start_time: '',
                        end_time: '',
                        cost: 0,
                        category: 'sightseeing',
                        location: '',
                        address: '',
                        notes: ''
                    };
                }
                this.open = true;
            });

            window.addEventListener('close-activity-modal', () => {
                this.closeModal();
            });
        },
        closeModal() {
            this.open = false;
            this.errors = {};
        },
        async submitForm() {
            this.loading = true;
            this.errors = {};

            const isEdit = (this.mode === 'edit' && this.form.id);
            const url = isEdit
                ? `/trips/${this.tripId}/activities/${this.form.id}`
                : `/trips/${this.tripId}/activities`;
            const method = isEdit ? 'PUT' : 'POST';

            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.status === 422) {
                    this.errors = data.errors || {};
                    this.loading = false;
                    return;
                }

                if (!response.ok) {
                    throw new Error(data.message || 'Server error occurred');
                }

                // Success
                this.closeModal();

                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        type: 'success',
                        message: isEdit ? 'Activity updated successfully!' : 'Activity added successfully!'
                    }
                }));

                // Broadcast event for dynamic update
                window.dispatchEvent(new CustomEvent('activity-saved', {
                    detail: {
                        mode: this.mode,
                        activity: data.activity || this.form
                    }
                }));

                // Reload or re-render
                setTimeout(() => window.location.reload(), 300);

            } catch (err) {
                console.error('[ActivityModal] Submit error:', err);
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        type: 'error',
                        message: err.message || 'Could not save activity.'
                    }
                }));
            } finally {
                this.loading = false;
            }
        }
    }"
    x-show="open"
    x-cloak
    style="display: none;"
    @keydown.escape.window="closeModal()"
    class="fixed inset-0 z-[60] overflow-y-auto"
    aria-labelledby="activity-modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeModal()"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
    ></div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            @click.stop
            class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-xl max-h-[90vh] flex flex-col"
        >
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/70">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <span x-text="mode === 'edit' ? '✏️' : '➕'"></span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900" id="activity-modal-title">
                        <span x-text="mode === 'edit' ? '{{ __('Edit Activity') }}' : '{{ __('Add New Activity') }}'"></span>
                    </h3>
                </div>

                <button
                    type="button"
                    @click="closeModal()"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            {{-- Modal Body Form --}}
            <form @submit.prevent="submitForm()" class="overflow-y-auto p-6 space-y-4 flex-1">
                {{-- Title --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('Activity Title') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="form.title"
                        required
                        placeholder="{{ __('e.g., Morning Coffee at Túi Mơ To') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        :class="errors.title ? 'border-red-400' : 'border-gray-300'"
                    >
                    <template x-if="errors.title">
                        <p class="text-xs text-red-600 mt-1" x-text="errors.title[0]"></p>
                    </template>
                </div>

                {{-- Day & Time Slot Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Day') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            x-model.number="form.day"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                            @for($d = 1; $d <= max(1, $tripDays); $d++)
                                <option value="{{ $d }}">{{ __('Day :day', ['day' => $d]) }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Time Slot') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            x-model="form.time_slot"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                            <option value="morning">🌅 {{ __('Morning (06:00 – 11:00)') }}</option>
                            <option value="midday">☀️ {{ __('Midday (11:00 – 14:00)') }}</option>
                            <option value="afternoon">🌇 {{ __('Afternoon (14:00 – 18:00)') }}</option>
                            <option value="evening">🌙 {{ __('Evening (18:00 – 23:00)') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Start Time & End Time --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Start Time') }}
                        </label>
                        <input
                            type="time"
                            x-model="form.start_time"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('End Time') }}
                        </label>
                        <input
                            type="time"
                            x-model="form.end_time"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                    </div>
                </div>

                {{-- Cost & Category Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Estimated Cost (VND)') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            min="0"
                            step="1000"
                            x-model.number="form.cost"
                            placeholder="0"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            :class="errors.cost ? 'border-red-400' : 'border-gray-300'"
                        >
                        <template x-if="errors.cost">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.cost[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Category') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            x-model="form.category"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                            <option value="sightseeing">🏛️ {{ __('Sightseeing') }}</option>
                            <option value="dining">🍜 {{ __('Dining / Food') }}</option>
                            <option value="cafe">☕ {{ __('Cafe / Chill') }}</option>
                            <option value="shopping">🛍️ {{ __('Shopping') }}</option>
                            <option value="entertainment">🎟️ {{ __('Entertainment') }}</option>
                            <option value="other">📌 {{ __('Other') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Location Name & Address (Strictly No Map API) --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Location / Venue Name') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="form.location"
                            required
                            placeholder="{{ __('e.g., Tuyen Lam Lake') }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Text Address / Landmark') }}
                        </label>
                        <input
                            type="text"
                            x-model="form.address"
                            placeholder="{{ __('e.g., Ward 4, Da Lat City') }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('Personal Notes / Tips') }}
                    </label>
                    <textarea
                        x-model="form.notes"
                        rows="2"
                        placeholder="{{ __('e.g., Book tickets in advance, bring warm jackets...') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    ></textarea>
                </div>

                {{-- Footer Actions inside form --}}
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <x-button
                        variant="secondary"
                        type="button"
                        @click="closeModal()"
                    >
                        {{ __('Cancel') }}
                    </x-button>

                    <x-button
                        variant="primary"
                        type="submit"
                        ::disabled="loading"
                    >
                        <template x-if="loading">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('Saving...') }}
                            </span>
                        </template>
                        <template x-if="!loading">
                            <span x-text="mode === 'edit' ? '{{ __('Update Activity') }}' : '{{ __('Add Activity') }}'"></span>
                        </template>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
