@props([
    'trip',
    'tripDays' => 1,
    'canEdit' => true,
    'allActivities' => null,
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
        originalSlot: null,
        originalDay: null,
        slotChangedMode: false,
        allActivitiesData: @js(($allActivities ?? $trip->activities ?? collect())->keyBy('id')->map(fn($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'day' => (int) $a->day,
            'time_slot' => $a->time_slot,
            'start_time' => $a->start_time ? substr($a->start_time, 0, 5) : '',
            'end_time' => $a->end_time ? substr($a->end_time, 0, 5) : '',
            'cost' => (float) $a->cost,
            'category' => $a->category,
            'location' => $a->location,
            'address' => $a->address,
            'notes' => $a->notes,
        ])),
        slotBounds: {
            morning:   { min: '06:00', max: '11:00', defaultStart: '08:00', defaultEnd: '09:30', label: '06:00 – 11:00' },
            midday:    { min: '11:00', max: '14:00', defaultStart: '11:30', defaultEnd: '13:00', label: '11:00 – 14:00' },
            afternoon: { min: '14:00', max: '18:00', defaultStart: '14:30', defaultEnd: '16:00', label: '14:00 – 18:00' },
            evening:   { min: '18:00', max: '23:59', defaultStart: '18:30', defaultEnd: '20:00', label: '18:00 – 23:59' }
        },
        get isSlotChanging() {
            if (this.mode !== 'edit') return false;
            if (this.slotChangedMode) return true;
            return (this.originalSlot && this.form.time_slot !== this.originalSlot) ||
                   (this.originalDay && Number(this.form.day) !== Number(this.originalDay));
        },
        get currentSlotBounds() {
            return this.slotBounds[this.form.time_slot] || this.slotBounds.morning;
        },
        timeToMins(t) {
            if (!t) return 0;
            const parts = t.split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        },
        minsToTime(m) {
            const h = Math.floor(m / 60).toString().padStart(2, '0');
            const min = (m % 60).toString().padStart(2, '0');
            return `${h}:${min}`;
        },
        get availableIntervals() {
            const day = Number(this.form.day) || 1;
            const slot = this.form.time_slot;
            const bounds = this.slotBounds[slot] || this.slotBounds.morning;
            const slotMin = this.timeToMins(bounds.min);
            const slotMax = this.timeToMins(bounds.max);

            const currentId = this.form.id;
            const allActs = Object.values(this.allActivitiesData || {});

            const occupied = allActs.filter(a => {
                if (currentId && (Number(a.id) === Number(currentId) || String(a.id) === String(currentId))) {
                    return false;
                }
                return Number(a.day) === day && a.time_slot === slot && a.start_time && a.end_time;
            }).map(a => ({
                start: this.timeToMins(String(a.start_time).substring(0, 5)),
                end: this.timeToMins(String(a.end_time).substring(0, 5))
            })).filter(o => o.start < o.end);

            occupied.sort((a, b) => a.start - b.start);

            const merged = [];
            for (const item of occupied) {
                if (!merged.length) {
                    merged.push({ start: item.start, end: item.end });
                } else {
                    const prev = merged[merged.length - 1];
                    if (item.start <= prev.end) {
                        prev.end = Math.max(prev.end, item.end);
                    } else {
                        merged.push({ start: item.start, end: item.end });
                    }
                }
            }

            const freeIntervals = [];
            let cursor = slotMin;

            for (const occ of merged) {
                if (occ.end <= slotMin) continue;
                const occStartClamped = Math.max(occ.start, slotMin);
                const occEndClamped = Math.min(occ.end, slotMax);

                if (occStartClamped > cursor) {
                    const duration = occStartClamped - cursor;
                    if (duration >= 15) {
                        freeIntervals.push({
                            start: this.minsToTime(cursor),
                            end: this.minsToTime(occStartClamped),
                            duration: duration
                        });
                    }
                }
                cursor = Math.max(cursor, occEndClamped);
            }

            if (cursor < slotMax) {
                const duration = slotMax - cursor;
                if (duration >= 15) {
                    freeIntervals.push({
                        start: this.minsToTime(cursor),
                        end: this.minsToTime(slotMax),
                        duration: duration
                    });
                }
            }

            return freeIntervals;
        },
        suggestFreeTime(slot) {
            const intervals = this.availableIntervals;
            if (!intervals || intervals.length === 0) {
                return null;
            }
            const first = intervals[0];
            const startMins = this.timeToMins(first.start);
            const endMins = this.timeToMins(first.end);
            const duration = endMins - startMins;
            const chosenDuration = Math.min(duration, 90);
            return {
                start: first.start,
                end: this.minsToTime(startMins + chosenDuration)
            };
        },
        onTimeSlotChange() {
            const bounds = this.currentSlotBounds;
            const suggestion = this.suggestFreeTime(this.form.time_slot);
            if (suggestion) {
                this.form.start_time = suggestion.start;
                this.form.end_time = suggestion.end;
            } else if (this.isSlotChanging) {
                this.form.start_time = '';
                this.form.end_time = '';
            } else {
                this.form.start_time = bounds.defaultStart;
                this.form.end_time = bounds.defaultEnd;
            }
        },
        form: {
            id: null,
            title: '',
            day: 1,
            time_slot: 'morning',
            start_time: '08:00',
            end_time: '09:30',
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
                this.slotChangedMode = !!e.detail?.slotChanged;

                if (!this.canEdit) {
                    this.authError = '{{ __('Permission Denied: You have view-only access and cannot modify activities on this trip.') }}';
                }

                if (this.mode === 'edit' && e.detail?.activity) {
                    const actId = e.detail.activity.id;
                    const fullAct = this.allActivitiesData[actId] || {};
                    const mergedAct = Object.assign({}, fullAct, e.detail.activity);

                    this.originalSlot = e.detail?.originalSlot || fullAct.time_slot || mergedAct.time_slot;
                    this.originalDay = e.detail?.originalDay || fullAct.day || mergedAct.day;

                    const targetSlot = e.detail?.targetSlot || mergedAct.time_slot;
                    const targetDay = e.detail?.targetDay || mergedAct.day;

                    this.form = Object.assign({}, mergedAct, {
                        day: targetDay,
                        time_slot: targetSlot,
                        start_time: mergedAct.start_time ? String(mergedAct.start_time).substring(0, 5) : '',
                        end_time: mergedAct.end_time ? String(mergedAct.end_time).substring(0, 5) : ''
                    });

                    if (this.isSlotChanging) {
                        const suggestion = this.suggestFreeTime(targetSlot);
                        if (suggestion) {
                            this.form.start_time = suggestion.start;
                            this.form.end_time = suggestion.end;
                        } else {
                            this.form.start_time = '';
                            this.form.end_time = '';
                        }
                    }
                } else {
                    const slot = e.detail?.time_slot || 'morning';
                    const day = e.detail?.day || 1;
                    this.originalSlot = null;
                    this.originalDay = null;
                    this.slotChangedMode = false;
                    this.form = {
                        id: null,
                        title: '',
                        day: day,
                        time_slot: slot,
                        start_time: '',
                        end_time: '',
                        cost: 0,
                        category: 'sightseeing',
                        location: '',
                        address: '',
                        notes: ''
                    };
                    const suggestion = this.suggestFreeTime(slot);
                    if (suggestion) {
                        this.form.start_time = suggestion.start;
                        this.form.end_time = suggestion.end;
                    } else {
                        const bounds = this.slotBounds[slot] || this.slotBounds.morning;
                        this.form.start_time = bounds.defaultStart;
                        this.form.end_time = bounds.defaultEnd;
                    }
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
            this.slotChangedMode = false;
            this.originalSlot = null;
            this.originalDay = null;
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

            // Client-side Time Slot boundary check
            const bounds = this.currentSlotBounds;
            if (!this.form.start_time || !this.form.end_time) {
                this.errors.start_time = ['{{ __('Start time and end time are required.') }}'];
                this.loading = false;
                return;
            }
            if (this.form.start_time >= this.form.end_time) {
                this.errors.end_time = ['{{ __('End time must be after start time.') }}'];
                this.loading = false;
                return;
            }
            if (this.form.start_time < bounds.min || this.form.end_time > bounds.max) {
                this.errors.start_time = [`{{ __('Times must be within') }} ${bounds.label}`];
                this.loading = false;
                return;
            }

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
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border border-teal-200 px-2.5 py-1 rounded-md" x-text="isSlotChanging ? 'Move' : (mode === 'edit' ? 'Edit' : 'Add')"></span>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900" id="activity-modal-title">
                        <span x-text="isSlotChanging ? '{{ __('Move Activity to New Timeslot') }}' : (mode === 'edit' ? '{{ __('Edit Activity') }}' : '{{ __('Add New Activity') }}')"></span>
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
                {{-- Timeslot Changing Callout Banner --}}
                <template x-if="isSlotChanging">
                    <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs space-y-1">
                        <div class="font-bold text-amber-900">
                            {{ __('Moving to a different timeslot') }}
                        </div>
                        <p class="text-amber-800 leading-relaxed">
                            {{ __('Activity') }} "<strong x-text="form.title || '{{ __('This activity') }}'"></strong>" {{ __('is moving to') }} <strong x-text="currentSlotBounds.label"></strong>. {{ __('Other fields are hidden. Please specify the new start & end time from the available slots below.') }}
                        </p>
                    </div>
                </template>

                {{-- Title (Hidden when changing slot) --}}
                <div x-show="!isSlotChanging">
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                        {{ __('Activity Title') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="form.title"
                        :required="!isSlotChanging"
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
                            @change="onTimeSlotChange()"
                            :disabled="!canEdit"
                            class="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed cursor-pointer"
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
                            @change="onTimeSlotChange()"
                            :disabled="!canEdit"
                            class="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <option value="morning">{{ __('Morning (06:00 – 11:00)') }}</option>
                            <option value="midday">{{ __('Midday (11:00 – 14:00)') }}</option>
                            <option value="afternoon">{{ __('Afternoon (14:00 – 18:00)') }}</option>
                            <option value="evening">{{ __('Evening (18:00 – 23:59)') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Start Time & End Time Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Start Time') }} <span class="text-red-500">*</span>
                            <span class="text-[11px] font-normal text-gray-400" x-text="'(' + currentSlotBounds.label + ')'"></span>
                        </label>
                        <input
                            type="time"
                            x-model="form.start_time"
                            required
                            :disabled="!canEdit"
                            :min="currentSlotBounds.min"
                            :max="currentSlotBounds.max"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                            :class="errors.start_time ? 'border-red-400 bg-red-50/20' : 'border-gray-300'"
                        >
                        <template x-if="errors.start_time">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.start_time[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('End Time') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="time"
                            x-model="form.end_time"
                            required
                            :disabled="!canEdit"
                            :min="form.start_time || currentSlotBounds.min"
                            :max="currentSlotBounds.max"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed"
                            :class="errors.end_time ? 'border-red-400 bg-red-50/20' : 'border-gray-300'"
                        >
                        <template x-if="errors.end_time">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.end_time[0]"></p>
                        </template>
                    </div>
                </div>

                {{-- Available Free Time Windows Suggestions --}}
                <div class="p-3 rounded-xl bg-teal-50/70 border border-teal-100 space-y-2">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <span class="text-xs font-bold text-teal-900">
                            {{ __('Available Free Windows:') }}
                        </span>
                        <span class="text-[11px] text-teal-600 font-medium" x-show="availableIntervals.length > 0">
                            {{ __('Click to apply time') }}
                        </span>
                    </div>

                    <template x-if="availableIntervals.length > 0">
                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                            <template x-for="(gap, idx) in availableIntervals" :key="idx">
                                <button
                                    type="button"
                                    @click="form.start_time = gap.start; form.end_time = gap.end"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white text-teal-800 border border-teal-200 hover:border-teal-400 hover:bg-teal-100/50 hover:shadow-xs transition cursor-pointer"
                                    :class="(form.start_time === gap.start && form.end_time === gap.end) ? 'ring-2 ring-teal-600 bg-teal-50' : ''"
                                >
                                    <span x-text="gap.start + ' – ' + gap.end"></span>
                                    <span class="text-[10px] text-teal-600 font-normal" x-text="'(' + gap.duration + 'm)'"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="availableIntervals.length === 0">
                        <div class="text-xs text-amber-800 font-medium">
                            {{ __('This timeslot is completely booked. Please choose another slot or adjust existing activities.') }}
                        </div>
                    </template>
                </div>

                {{-- Cost & Category Row (Hidden when changing slot) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="!isSlotChanging">
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
                            class="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-gray-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 disabled:bg-gray-100 disabled:cursor-not-allowed cursor-pointer"
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

                {{-- Location Name & Address (Hidden when changing slot) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="!isSlotChanging">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1">
                            {{ __('Location / Venue Name') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="form.location"
                            :required="!isSlotChanging"
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

                {{-- Notes (Hidden when changing slot) --}}
                <div x-show="!isSlotChanging">
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
                <div class="pt-3 flex items-center justify-end gap-3 border-t border-gray-100">
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
                        <span x-show="!loading" x-text="isSlotChanging ? '{{ __('Save New Times') }}' : (mode === 'edit' ? '{{ __('Update Activity') }}' : '{{ __('Create Activity') }}')"></span>
                        <span x-show="loading">{{ __('Saving...') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
