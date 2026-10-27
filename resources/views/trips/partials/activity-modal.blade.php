@props([
    'trip',
    'tripDays' => 1,
    'canEdit' => true,
])

<div
    x-data="{
        open: false,
        mode: 'create',
        tripId: {{ $trip->id }},
        canEdit: {{ $canEdit ? 'true' : 'false' }},
        loading: false,
        authError: '',
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
                this.authError = '';
                this.mode = e.detail?.mode || 'create';

                if (!this.canEdit) {
                    this.authError = '{{ __('Permission Denied: You have view-only access and cannot modify activities on this trip.') }}';
                }

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
            this.authError = '';
        },
        async submitForm() {
            if (!this.canEdit) {
                this.authError = '{{ __('Permission Denied: You have view-only access and cannot modify activities on this trip.') }}';
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        type: 'error',
                        message: this.authError
                    }
                }));
                return;
            }

            this.loading = true;
            this.errors = {};
            this.authError = '';

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

                if (response.status === 403) {
                    const data = await response.json().catch(() => ({}));
                    const msg = data.message || '{{ __('Permission Denied: You have view-only access and cannot make changes.') }}';
                    this.authError = msg;
                    this.loading = false;
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            type: 'error',
                            message: msg
                        }
                    }));
                    return;
                }

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

                window.dispatchEvent(new CustomEvent('activity-saved', {
                    detail: {
                        mode: this.mode,
                        activity: data.activity || this.form
                    }
                }));

                setTimeout(() => window.location.reload(), 300);

            } catch (err) {
                console.error('[ActivityModal] Submit error:', err);
                const msg = err.message || 'Could not save activity.';
                this.authError = msg;
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        type: 'error',
                        message: msg
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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeModal()"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
    ></div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            @click.stop
            class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-xl max-h-[90vh] flex flex-col border border-gray-100"
        >
            {{-- Modal Header (Zero icons/emojis) --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/70">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-2.5 py-1 rounded-md" x-text="mode === 'edit' ? 'Edit' : 'Add'"></span>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900" id="activity-modal-title">
                        <span x-text="mode === 'edit' ? '{{ __('Edit Activity') }}' : '{{ __('Add New Activity') }}'"></span>
                    </h3>
                </div>

                <button
                    type="button"
                    @click="closeModal()"
                    class="h-8 w-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center text-lg font-bold select-none"
                    aria-label="{{ __('Close modal') }}"
                >
                    &times;
                </button>
            </div>

            {{-- Permission Error Banner --}}
            <template x-if="authError">
                <div class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs font-semibold">
                    <span x-text="authError"></span>
                </div>
            </template>

            {{-- Modal Body Form --}}
            <form @submit.prevent="submitForm()" class="overflow-y-auto p-6 space-y-4 flex-1">
                {{-- Title --}}
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                        {{ __('Activity Title') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="form.title"
                        required
                        :disabled="!canEdit"
                        placeholder="{{ __('e.g., Morning Coffee at Tu Mo To') }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        :class="errors.title ? 'border-red-400' : 'border-gray-300'"
                    >
                    <template x-if="errors.title">
                        <p class="text-xs text-red-600 mt-1" x-text="errors.title[0]"></p>
                    </template>
                </div>

                {{-- Day & Time Slot Row (Zero icons/emojis) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Day') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            x-model.number="form.day"
                            :disabled="!canEdit"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                            @for($d = 1; $d <= max(1, $tripDays); $d++)
                                <option value="{{ $d }}">{{ __('Day :day', ['day' => $d]) }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Time Slot') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            x-model="form.time_slot"
                            :disabled="!canEdit"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                            <option value="morning">{{ __('Morning (06:00 – 11:00)') }}</option>
                            <option value="midday">{{ __('Midday (11:00 – 14:00)') }}</option>
                            <option value="afternoon">{{ __('Afternoon (14:00 – 18:00)') }}</option>
                            <option value="evening">{{ __('Evening (18:00 – 23:00)') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Start Time & End Time --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Start Time') }}
                        </label>
                        <input
                            type="time"
                            x-model="form.start_time"
                            :disabled="!canEdit"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('End Time') }}
                        </label>
                        <input
                            type="time"
                            x-model="form.end_time"
                            :disabled="!canEdit"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                    </div>
                </div>

                {{-- Cost & Category Row (Zero icons/emojis) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Estimated Cost (VND)') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            min="0"
                            step="1000"
                            x-model.number="form.cost"
                            :disabled="!canEdit"
                            placeholder="0"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                            :class="errors.cost ? 'border-red-400' : 'border-gray-300'"
                        >
                        <template x-if="errors.cost">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.cost[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Category') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            x-model="form.category"
                            :disabled="!canEdit"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                            <option value="sightseeing">{{ __('Sightseeing') }}</option>
                            <option value="dining">{{ __('Dining / Food') }}</option>
                            <option value="cafe">{{ __('Cafe / Chill') }}</option>
                            <option value="shopping">{{ __('Shopping') }}</option>
                            <option value="entertainment">{{ __('Entertainment') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Location Name & Address --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Location / Venue Name') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="form.location"
                            required
                            :disabled="!canEdit"
                            placeholder="{{ __('e.g., Tuyen Lam Lake') }}"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Text Address / Landmark') }}
                        </label>
                        <input
                            type="text"
                            x-model="form.address"
                            :disabled="!canEdit"
                            placeholder="{{ __('e.g., Ward 4, Da Lat City') }}"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                        {{ __('Personal Notes / Tips') }}
                    </label>
                    <textarea
                        x-model="form.notes"
                        rows="2"
                        :disabled="!canEdit"
                        placeholder="{{ __('e.g., Book tickets in advance, bring warm jackets...') }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    ></textarea>
                </div>

                {{-- Actions --}}
                <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="px-4 py-2.5 rounded-xl border border-gray-200 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                    >
                        {{ __('Cancel') }}
                    </button>

                    <button
                        type="submit"
                        :disabled="loading || !canEdit"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!loading" x-text="mode === 'edit' ? '{{ __('Update Activity') }}' : '{{ __('Create Activity') }}'"></span>
                        <span x-show="loading">{{ __('Saving...') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
