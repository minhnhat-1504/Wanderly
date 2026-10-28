{{-- Province Detail Slide-over Drawer --}}
<div
    x-cloak
    x-show="drawerOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="province-drawer-title"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="closeDrawer()"
>
    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity"
        @click="closeDrawer()"
    ></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div
            x-show="drawerOpen"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen max-w-md bg-white border-l border-gray-200 shadow-2xl flex flex-col justify-between"
        >
            {{-- Header --}}
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center justify-between">
                    <div>
                        <span
                            class="inline-block text-[11px] font-semibold text-primary bg-primary/10 px-2.5 py-0.5 rounded-lg"
                            x-text="currentProvince.regionName || 'Vietnam'"
                        ></span>
                        <h3 id="province-drawer-title" class="text-2xl font-bold text-gray-900 mt-1.5 tracking-tight" x-text="currentProvince.name"></h3>
                    </div>
                    <button
                        type="button"
                        @click="closeDrawer()"
                        class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition focus:outline-none focus:ring-2 focus:ring-primary"
                        aria-label="Close province details"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <template x-if="currentProvince.note">
                    <p class="text-xs text-gray-500 mt-2" x-text="'Administrative unit: ' + currentProvince.note"></p>
                </template>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto space-y-6 flex-1 text-sm text-gray-600 leading-relaxed">
                {{-- Dynamic Province Info --}}
                <div class="space-y-4">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Best Season</h4>
                        <p class="text-gray-900 font-medium" x-text="currentProvince.bestSeason || 'Year-round — Pleasant weather and vibrant local cultural seasons.'"></p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Local Specialties</h4>
                        <p class="text-gray-900 font-medium" x-text="currentProvince.specialties || 'Authentic regional dishes, fresh seasonal produce, and time-honored recipes.'"></p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Highlights & POIs</h4>
                        <p class="text-gray-900 font-medium" x-text="currentProvince.highlights || 'Natural wonders, historical heritage landmarks, and traditional craft communities.'"></p>
                    </div>
                </div>

                <div class="rounded-xl border border-teal-100 bg-teal-50/50 p-4 space-y-2">
                    <h5 class="font-bold text-teal-900 text-xs uppercase tracking-wider">Smart Travel Planning</h5>
                    <p class="text-xs text-teal-800 leading-normal">
                        Wanderly automatically arranges your 4-timeslot itinerary, calculates group budgets, and connects you with verified local hosts in <span class="font-bold" x-text="currentProvince.name"></span>.
                    </p>
                </div>
            </div>

            {{-- Footer Action --}}
            <div class="p-6 border-t border-gray-100 bg-gray-50">
                <a
                    :href="currentProvince.planUrl || '/trips/create'"
                    class="w-full flex items-center justify-center py-3.5 px-6 rounded-xl bg-primary hover:bg-teal-800 text-white font-semibold text-sm shadow-sm transition-colors"
                >
                    <span x-text="'Plan ' + (currentProvince.name || '') + ' Trip'"></span>
                </a>
            </div>
        </div>
    </div>
</div>
