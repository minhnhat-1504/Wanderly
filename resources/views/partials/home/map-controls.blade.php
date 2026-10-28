{{-- Map Floating Controls (Section H.1 & H.8) --}}
<div class="absolute bottom-4 right-4 z-20 flex flex-col items-end gap-2 pointer-events-none select-none">
    {{-- Zoom Hint Notice --}}
    <div
        x-show="showCtrlHint"
        x-transition
        class="bg-gray-950/85 text-teal-200 border border-teal-500/30 text-[11px] px-2.5 py-1 rounded-md backdrop-blur-md shadow-md pointer-events-auto"
    >
        Scroll: Hold Ctrl to zoom map
    </div>

    {{-- Control Button Stack --}}
    <div class="flex items-center gap-1.5 p-1.5 rounded-xl bg-gray-950/80 border border-teal-500/30 backdrop-blur-md shadow-xl pointer-events-auto">
        {{-- Zoom In --}}
        <button
            type="button"
            @click="mapInstance && mapInstance.zoom(-2)"
            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-200 hover:text-white hover:bg-teal-800/60 focus:outline-none focus:ring-2 focus:ring-teal-400 transition"
            aria-label="Zoom In"
            title="Zoom In (+)"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </button>

        {{-- Zoom Out --}}
        <button
            type="button"
            @click="mapInstance && mapInstance.zoom(2)"
            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-200 hover:text-white hover:bg-teal-800/60 focus:outline-none focus:ring-2 focus:ring-teal-400 transition"
            aria-label="Zoom Out"
            title="Zoom Out (-)"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
            </svg>
        </button>

        {{-- Divider --}}
        <div class="w-px h-5 bg-teal-800/50"></div>

        {{-- Reset View --}}
        <button
            type="button"
            @click="mapInstance && mapInstance.resetView(); setRegion('all')"
            class="h-9 px-2.5 flex items-center justify-center gap-1.5 rounded-lg text-xs font-semibold text-gray-200 hover:text-white hover:bg-teal-800/60 focus:outline-none focus:ring-2 focus:ring-teal-400 transition"
            aria-label="Reset overview map"
            title="Reset overview (0)"
        >
            <svg class="w-3.5 h-3.5 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            <span>Reset</span>
        </button>

        {{-- Fullscreen Overlay Toggle --}}
        <button
            type="button"
            @click="toggleFullscreenMap()"
            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-200 hover:text-white hover:bg-teal-800/60 focus:outline-none focus:ring-2 focus:ring-teal-400 transition"
            :aria-label="isFullscreenMap ? 'Exit expanded map' : 'Expand full map'"
            title="Expand Map"
        >
            <svg x-show="!isFullscreenMap" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
            </svg>
            <svg x-show="isFullscreenMap" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
            </svg>
        </button>
    </div>
</div>
