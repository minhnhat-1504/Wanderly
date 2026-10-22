@props([
    'trip',
])

<div
    x-data="{
        open: false,
        showAddForm: false,
        loading: false,
        tripId: {{ $trip->id }},
        rooms: [],
        form: {
            hotel_name: '',
            room_type: '',
            check_in: '',
            check_out: '',
            price: 0,
            address: '',
            notes: '',
            is_budget_included: true
        },
        init() {
            window.addEventListener('open-room-drawer', () => {
                window.dispatchEvent(new CustomEvent('close-activity-modal'));
                this.open = true;
                window.dispatchEvent(new CustomEvent('drawer-toggled', { detail: { open: true } }));
                this.fetchRooms();
            });

            window.addEventListener('close-room-drawer', () => {
                this.closeDrawer();
            });
        },
        closeDrawer() {
            this.open = false;
            window.dispatchEvent(new CustomEvent('drawer-toggled', { detail: { open: false } }));
        },
        async fetchRooms() {
            // Can be populated from server or initial data
            try {
                const res = await fetch(`/trips/${this.tripId}/rooms`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.rooms = data.rooms || [];
                }
            } catch (e) {
                // Ignore if endpoint not yet deployed
            }
        },
        async toggleBudget(room) {
            const previousState = room.is_budget_included;
            room.is_budget_included = !previousState;

            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');

            try {
                const res = await fetch(`/ajax/rooms/${room.id}/toggle-budget`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ is_budget_included: room.is_budget_included })
                });

                if (!res.ok) {
                    throw new Error('Toggle failed');
                }

                const data = await res.json();

                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        type: 'success',
                        message: room.is_budget_included
                            ? 'Room cost included in budget'
                            : 'Room cost excluded from budget'
                    }
                }));

                // Broadcast budget update
                window.dispatchEvent(new CustomEvent('budget-recalculate', { detail: data }));

            } catch (err) {
                room.is_budget_included = previousState; // Rollback
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: 'Could not update room budget setting.' }
                }));
            }
        },
        async addRoom() {
            this.loading = true;
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');

            try {
                const res = await fetch(`/trips/${this.tripId}/rooms`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.form)
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.room) {
                        this.rooms.push(data.room);
                    }
                    this.showAddForm = false;
                    this.form = {
                        hotel_name: '',
                        room_type: '',
                        check_in: '',
                        check_out: '',
                        price: 0,
                        address: '',
                        notes: '',
                        is_budget_included: true
                    };
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { type: 'success', message: 'Room booking added successfully!' }
                    }));
                    window.dispatchEvent(new CustomEvent('budget-recalculate'));
                    setTimeout(() => window.location.reload(), 300);
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { type: 'error', message: 'Error adding room.' }
                }));
            } finally {
                this.loading = false;
            }
        }
    }"
    x-show="open"
    x-cloak
    style="display: none;"
    @keydown.escape.window="closeDrawer()"
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="room-drawer-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeDrawer()"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm"
    ></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        {{-- Drawer Panel --}}
        <div
            x-show="open"
            x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen max-w-md bg-white shadow-2xl flex flex-col"
        >
            {{-- Header --}}
            <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-indigo-50/60 to-purple-50/60">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-600/20">
                        🏨
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900" id="room-drawer-title">
                            {{ __('Accommodations') }}
                        </h2>
                        <p class="text-xs text-gray-500">
                            {{ __('Manage booked hotels, resorts and stays.') }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="closeDrawer()"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-white transition-colors"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            {{-- Body Content --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-6">

                {{-- Action: Toggle Add Form --}}
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                        {{ __('Booked Rooms') }}
                    </span>

                    <button
                        type="button"
                        @click="showAddForm = !showAddForm"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-xl transition-colors"
                    >
                        <span x-text="showAddForm ? '✕ {{ __('Cancel') }}' : '+ {{ __('Add Room') }}'"></span>
                    </button>
                </div>

                {{-- Quick Add Room Form --}}
                <div
                    x-show="showAddForm"
                    x-transition
                    class="bg-gray-50 rounded-2xl p-4 border border-gray-200/80 space-y-3"
                >
                    <h3 class="text-xs font-bold text-gray-900 uppercase">
                        {{ __('New Accommodation') }}
                    </h3>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Hotel / Resort Name') }} *</label>
                        <input type="text" x-model="form.hotel_name" required placeholder="{{ __('e.g., Terracotta Resort Da Lat') }}" class="w-full px-3 py-2 text-sm bg-white rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Room Type') }}</label>
                            <input type="text" x-model="form.room_type" placeholder="{{ __('e.g., Deluxe Villa') }}" class="w-full px-3 py-2 text-sm bg-white rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Price (VND)') }}</label>
                            <input type="number" min="0" step="10000" x-model.number="form.price" placeholder="0" class="w-full px-3 py-2 text-sm bg-white rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Check-in') }} *</label>
                            <input type="date" x-model="form.check_in" required class="w-full px-3 py-2 text-sm bg-white rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Check-out') }} *</label>
                            <input type="date" x-model="form.check_out" required class="w-full px-3 py-2 text-sm bg-white rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Address') }}</label>
                        <input type="text" x-model="form.address" placeholder="{{ __('e.g., Tuyen Lam Lake Zone') }}" class="w-full px-3 py-2 text-sm bg-white rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>

                    {{-- Toggle Budget --}}
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-xs text-gray-600 font-medium">{{ __('Include in Trip Budget') }}</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.is_budget_included" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <button
                        type="button"
                        @click="addRoom()"
                        :disabled="loading"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors flex items-center justify-center gap-1.5 shadow-md shadow-indigo-600/20"
                    >
                        <span>{{ __('Save Accommodation') }}</span>
                    </button>
                </div>

                {{-- Rooms List --}}
                <div class="space-y-3">
                    @forelse($trip->roomBookings ?? [] as $room)
                        <div
                            class="bg-white rounded-2xl border border-indigo-100 p-4 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden"
                            x-data="{ included: {{ $room->is_budget_included ? 'true' : 'false' }} }"
                        >
                            {{-- Top decorative strip --}}
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>

                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">{{ $room->hotel_name }}</h4>
                                    @if($room->room_type)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $room->room_type }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">
                                        📅 {{ $room->check_in ? \Carbon\Carbon::parse($room->check_in)->format('M d') : '' }} – {{ $room->check_out ? \Carbon\Carbon::parse($room->check_out)->format('M d, Y') : '' }}
                                    </p>
                                    @if($room->address)
                                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                            <span>📍</span> {{ $room->address }}
                                        </p>
                                    @endif
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="inline-block text-xs font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-xl border border-indigo-200">
                                        {{ number_format($room->price, 0, ',', '.') }} VND
                                    </span>
                                </div>
                            </div>

                            {{-- Bottom Toggle switch --}}
                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                                <span class="text-gray-500 font-medium">{{ __('Count in Budget') }}</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        x-model="included"
                                        @change="toggleBudget({{ $room->id }})"
                                        class="sr-only peer"
                                    >
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 px-4 text-center bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                            <span class="text-3xl">🏨</span>
                            <p class="text-sm font-semibold text-gray-700 mt-2">{{ __('No accommodations added') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Add your booked hotel or resort to keep all travel plans in one place.') }}</p>
                        </div>
                    @endforelse
                </div>

            </div>

            {{-- Footer --}}
            <div class="p-4 border-t border-gray-100 bg-gray-50 text-right">
                <x-button variant="secondary" type="button" @click="closeDrawer()">
                    {{ __('Close') }}
                </x-button>
            </div>
        </div>
    </div>
</div>
