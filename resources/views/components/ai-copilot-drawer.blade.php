@props(['trip' => null])

@php
    $isTrip = ($trip instanceof \App\Models\Trip && $trip->exists);
    // Double check that we are not leaking a loop variable if on a non-trip route
    if ($isTrip && !request()->route('trip') && !request()->routeIs('trips.show')) {
        $isTrip = false;
        $trip = null;
    }
    $tripIdVal = $isTrip ? $trip->id : 'null';
    $isGlobal = !$isTrip;
    // Only elevate the floating button if we are on a page with other floating action bubbles (trips.show)
    $hasFloatingStack = $isTrip && request()->routeIs('trips.show');
@endphp

<div x-data="aiCopilotDrawer({ tripId: {{ $tripIdVal }} })"
     x-init="init()"
     @keydown.escape.window="if (isOpen) isOpen = false"
     class="relative z-50">

    <!-- Floating Trigger Button (Top of Floating Stack when in trip, bottom when global) -->
    <div x-show="!isOpen"
         x-transition:enter="transition-all ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-75"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition-all ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-75"
         class="fixed right-5 sm:right-6 {{ $hasFloatingStack ? 'bottom-[148px] sm:bottom-[164px]' : 'bottom-6 sm:bottom-8' }} z-40 select-none pointer-events-auto"
         x-cloak>
        <button @click="openDrawer()"
                type="button"
                id="ai-copilot-floating-btn"
                class="group relative flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-tr from-amber-500 via-teal-600 to-emerald-500 text-white shadow-xl shadow-teal-900/30 hover:shadow-2xl hover:shadow-teal-900/40 hover:-translate-y-0.5 active:translate-y-0 hover:brightness-110 transition-all duration-200 border-2 border-white/40 focus:outline-none cursor-pointer"
                title="{{ $isGlobal ? __('Wanderly AI Travel Assistant') : __('Wanderly AI Co-pilot') }}"
                aria-label="{{ $isGlobal ? __('Wanderly AI Travel Assistant') : __('Wanderly AI Co-pilot') }}">
            
            <x-icon name="sparkles" class="w-6 h-6 sm:w-6.5 sm:h-6.5 text-amber-200 pointer-events-none transition-transform duration-200 group-hover:scale-110" />

            <!-- Weather Alert Badge -->
            <template x-if="weatherAlertsCount > 0">
                <span class="absolute -top-1 -right-1 px-1.5 py-0.5 text-[10px] font-extrabold bg-amber-400 text-amber-950 border-2 border-white rounded-full shadow-sm" x-text="weatherAlertsCount"></span>
            </template>
            <template x-if="weatherAlertsCount === 0">
                <span class="absolute top-0 right-0 -mt-0.5 -mr-0.5 flex h-3.5 w-3.5 pointer-events-none">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-300 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-300 border-2 border-white"></span>
                </span>
            </template>

            <!-- Floating Tooltip Pill (Left Side) -->
            <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-xl bg-gray-900/90 text-white text-xs font-semibold whitespace-nowrap shadow-xl backdrop-blur-md opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none translate-x-2 group-hover:translate-x-0 flex items-center gap-1.5">
                <span>✨</span>
                <span>{{ $isGlobal ? __('AI Travel Assistant') : __('AI Co-pilot') }}</span>
            </span>
        </button>
    </div>

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[65]"
         style="display: none;"
         x-cloak></div>

    <!-- Sliding Drawer Panel -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 max-w-full flex pl-10 z-[70]"
         style="display: none;"
         x-cloak>
        
        <div class="w-screen max-w-md bg-white dark:bg-slate-900 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
            
            <!-- Header -->
            <div class="p-4 bg-gradient-to-r from-teal-700 via-teal-800 to-slate-900 text-white flex items-center justify-between shadow-md">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-white/10 rounded-xl backdrop-blur-md">
                        <x-icon name="bot" class="w-6 h-6 text-teal-300" />
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h3 class="font-bold text-base tracking-tight text-white">
                                {{ $isGlobal ? __('Wanderly Travel Assistant') : __('Wanderly AI Co-pilot') }}
                            </h3>
                            <span class="px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-teal-500/30 text-teal-200 border border-teal-400/30 rounded">
                                {{ $isGlobal ? 'Global AI' : 'Trip Mode' }}
                            </span>
                        </div>
                        <p class="text-xs text-teal-100/80">
                            {{ $isGlobal ? __('Tư vấn du lịch 34 tỉnh thành Việt Nam') : __('Group mediator & adaptive action assistant') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Clear History Button -->
                    <button @click="clearHistory()" 
                            type="button"
                            :disabled="isClearing || messages.length === 0"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-rose-200 hover:text-white bg-rose-500/25 hover:bg-rose-600/60 border border-rose-400/40 rounded-lg transition-all cursor-pointer disabled:opacity-35 disabled:hover:bg-rose-500/25 disabled:hover:text-rose-200 disabled:cursor-not-allowed shadow-sm"
                            title="{{ __('Xóa toàn bộ lịch sử trò chuyện') }}"
                            aria-label="{{ __('Xóa toàn bộ lịch sử trò chuyện') }}">
                        <span x-show="!isClearing" class="inline-flex items-center gap-1">
                            <x-icon name="trash" class="w-3.5 h-3.5" />
                            <span>{{ __('Xóa lịch sử') }}</span>
                        </span>
                        <span x-show="isClearing" class="inline-flex items-center gap-1" x-cloak>
                            <span class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin inline-block"></span>
                            <span>{{ __('Đang xóa...') }}</span>
                        </span>
                    </button>

                    <!-- Close Button -->
                    <button @click="isOpen = false" class="p-1.5 text-white/70 hover:text-white rounded-lg hover:bg-white/10 transition-colors cursor-pointer" title="{{ __('Đóng') }}">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Weather Alert Bar (Conditional) -->
            <template x-if="weatherForecast">
                <div class="px-4 py-2 bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800/40 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-medium">
                        <x-icon name="cloud-rain" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        <span x-text="weatherForecast.destination + ': ' + weatherForecast.condition + ' (' + weatherForecast.current_temp + '°C)'"></span>
                    </div>
                    <template x-if="weatherAlertsCount > 0">
                        <span class="text-rose-600 dark:text-rose-400 font-bold" x-text="weatherAlertsCount + ' cảnh báo mưa'"></span>
                    </template>
                </div>
            </template>

            <!-- Quick Action Prompts Bar -->
            <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex gap-2 overflow-x-auto no-scrollbar text-xs">
                <!-- Clear History Chip in Action Bar when messages exist -->
                <template x-if="messages.length > 0">
                    <button @click="clearHistory()"
                            type="button"
                            class="whitespace-nowrap px-2.5 py-1 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 rounded-full hover:bg-rose-100 dark:hover:bg-rose-900/60 transition-colors shadow-sm flex items-center gap-1 font-medium">
                        <x-icon name="trash" class="w-3.5 h-3.5" />
                        <span>{{ __('Xóa lịch sử chat') }}</span>
                    </button>
                </template>

                @if($isGlobal)
                    <button @click="quickPrompt('Gợi ý điểm du lịch Việt Nam hấp dẫn nhất mùa này')"
                            class="whitespace-nowrap px-2.5 py-1 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors shadow-sm">
                        🌸 {{ __('Đi đâu mùa này?') }}
                    </button>
                    <button @click="quickPrompt('Lịch trình gợi ý du lịch Đà Lạt 3 ngày 2 đêm')"
                            class="whitespace-nowrap px-2.5 py-1 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors shadow-sm">
                        🌲 {{ __('Gợi ý Đà Lạt 3N2Đ') }}
                    </button>
                    <button @click="quickPrompt('Món ăn ngon và đặc sản nhất định phải thử ở Đà Nẵng')"
                            class="whitespace-nowrap px-2.5 py-1 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors shadow-sm">
                        🍜 {{ __('Ẩm thực Đà Nẵng') }}
                    </button>
                @else
                    <button @click="quickPrompt('Hòa giải: Người thích trekking, người thích cafe ngắm cảnh')"
                            class="whitespace-nowrap px-2.5 py-1 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors shadow-sm">
                        🤝 {{ __('Hòa giải bất đồng') }}
                    </button>
                    <button @click="isReviewModalOpen = true"
                            class="whitespace-nowrap px-2.5 py-1 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors shadow-sm">
                        📝 {{ __('Nhập bài review') }}
                    </button>
                    <button @click="quickPrompt('Gợi ý hoạt động trong nhà nếu trời mưa')"
                            class="whitespace-nowrap px-2.5 py-1 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors shadow-sm">
                        🌧️ {{ __('Ứng phó thời tiết') }}
                    </button>
                @endif
            </div>

            <!-- Messages Stream Area -->
            <div x-ref="messagesContainer" class="flex-1 p-4 overflow-y-auto space-y-4">
                
                <!-- Initial Welcome Message -->
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-700 dark:text-teal-300 flex-shrink-0">
                        <x-icon name="bot" class="w-4 h-4" />
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-800 p-3.5 rounded-2xl rounded-tl-sm text-sm text-slate-800 dark:text-slate-200 max-w-[85%] space-y-2">
                        @if($isGlobal)
                            <p>{{ __('Xin chào! Mình là Wanderly AI Travel Assistant. Mình có thể tư vấn du lịch, gợi ý địa điểm vui chơi, món ăn đặc sản tại 34 tỉnh thành Việt Nam. Hãy hỏi mình bất kỳ điều gì!') }}</p>
                        @else
                            <p>{{ __('Xin chào! Mình là Wanderly AI Co-pilot cho chuyến đi này. Mình nắm rõ thông tin lịch trình, ngân sách và sở thích từng thành viên. Hãy hỏi mình hoặc nhờ mình hòa giải ý kiến nhóm!') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Clear thread indicator inside chat stream -->
                <template x-if="messages.length > 0">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 px-2 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <span x-text="messages.length + ' tin nhắn trong cuộc trò chuyện'"></span>
                        <button @click="clearHistory()" type="button" class="text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 hover:underline inline-flex items-center gap-1 font-medium cursor-pointer">
                            <x-icon name="trash" class="w-3 h-3" />
                            <span>{{ __('Xóa lịch sử') }}</span>
                        </button>
                    </div>
                </template>

                <!-- Chat Messages History Loop -->
                <template x-for="msg in messages" :key="msg.id">
                    <div class="flex gap-3" :class="msg.role === 'user' ? 'flex-row-reverse' : ''">
                        <!-- Avatar -->
                        <template x-if="msg.role === 'user'">
                            <div class="w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center text-xs font-semibold flex-shrink-0">
                                <span>You</span>
                            </div>
                        </template>
                        <template x-if="msg.role !== 'user'">
                            <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-700 dark:text-teal-300 flex-shrink-0">
                                <x-icon name="bot" class="w-4 h-4" />
                            </div>
                        </template>

                        <!-- Content Bubble & Action Card -->
                        <div class="space-y-2 max-w-[85%]">
                            <div class="p-3.5 rounded-2xl text-sm"
                                 :class="msg.role === 'user' 
                                    ? 'bg-teal-600 text-white rounded-tr-sm' 
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-tl-sm shadow-sm'">
                                <p class="whitespace-pre-line leading-relaxed" x-text="msg.content"></p>
                            </div>

                            <!-- Verified Trip Itinerary Card (Sprint 4 Step 7) -->
                            <template x-if="msg.suggested_action && (msg.suggested_action.action === 'create_trip_itinerary' || msg.suggested_action.type === 'create_trip_itinerary') && msg.suggested_action.params">
                                <div class="p-3.5 bg-gradient-to-br from-teal-50/90 via-emerald-50/50 to-white dark:from-slate-800 dark:via-slate-850 dark:to-teal-950/40 border border-teal-200 dark:border-teal-700/60 rounded-2xl shadow-md space-y-3">
                                    
                                    <!-- Header Badge & Meta -->
                                    <div class="flex items-center justify-between border-b border-teal-100 dark:border-teal-900/50 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="p-1.5 bg-teal-600 text-white rounded-lg shadow-sm">
                                                <x-icon name="map-pin" class="w-4 h-4" />
                                            </span>
                                            <div>
                                                <h4 class="font-extrabold text-slate-900 dark:text-white text-sm" x-text="msg.suggested_action.params.province ? msg.suggested_action.params.province.name : 'Lịch trình du lịch'"></h4>
                                                <div class="flex items-center gap-1.5 text-[11px] text-teal-700 dark:text-teal-300 font-semibold">
                                                    <span x-text="(msg.suggested_action.params.summary ? msg.suggested_action.params.summary.total_days : '3') + ' ngày'"></span>
                                                    <span>•</span>
                                                    <span x-text="(msg.suggested_action.params.summary ? msg.suggested_action.params.summary.total_places : '0') + ' điểm đến đã xác minh'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/40">
                                            <span>✓</span>
                                            <span>Đã kiểm chứng</span>
                                        </span>
                                    </div>

                                    <!-- Weather summary callout -->
                                    <template x-if="msg.suggested_action.params.weather_summary && msg.suggested_action.params.weather_summary.general_advice">
                                        <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-900 dark:text-amber-200 flex items-start gap-2">
                                            <span class="text-base leading-none">🌤️</span>
                                            <span class="leading-relaxed" x-text="msg.suggested_action.params.weather_summary.general_advice"></span>
                                        </div>
                                    </template>

                                    <!-- Day by Day Accordion / List -->
                                    <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                                        <template x-for="day in (msg.suggested_action.params.days || [])" :key="day.day">
                                            <div class="bg-white/95 dark:bg-slate-900/90 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-3 space-y-2.5">
                                                
                                                <!-- Day Header -->
                                                <div class="flex items-center justify-between text-xs font-bold text-slate-800 dark:text-slate-100 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="px-2 py-0.5 bg-teal-100 dark:bg-teal-900/60 text-teal-800 dark:text-teal-200 rounded font-extrabold text-[11px]" x-text="'Ngày ' + day.day"></span>
                                                        <template x-if="day.weather">
                                                            <span class="text-slate-500 dark:text-slate-400 font-medium" x-text="'• ' + day.weather.condition + ' (' + day.weather.temp_min + '°C - ' + day.weather.temp_max + '°C)'"></span>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Items in Day -->
                                                <div class="space-y-2">
                                                    <template x-for="(item, idx) in (day.items || [])" :key="idx">
                                                        <div class="space-y-1.5">
                                                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs flex items-start justify-between gap-2">
                                                                <div class="space-y-1 flex-1">
                                                                    <div class="flex items-center gap-1.5">
                                                                        <span class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded font-mono text-[10px] font-bold" x-text="item.time"></span>
                                                                        <span class="font-bold text-slate-900 dark:text-slate-100" x-text="item.place ? item.place.name_vi : item.activity"></span>
                                                                    </div>

                                                                    <!-- Exact Verified Street Address -->
                                                                    <template x-if="item.place && item.place.address">
                                                                        <div class="flex items-start gap-1 text-[11px] text-slate-600 dark:text-slate-300">
                                                                            <span class="text-teal-600 dark:text-teal-400 font-bold">📍</span>
                                                                            <span class="line-clamp-1" x-text="item.place.address"></span>
                                                                        </div>
                                                                    </template>
                                                                    
                                                                    <!-- Meta Badges: hours, price, indoor -->
                                                                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                                                                        <template x-if="item.place && item.place.opening_hours && typeof item.place.opening_hours === 'object'">
                                                                            <span class="inline-flex items-center gap-0.5 text-slate-600 dark:text-slate-300">
                                                                                <span>🕒</span>
                                                                                <span x-text="item.place.opening_hours.open + ' - ' + item.place.opening_hours.close"></span>
                                                                            </span>
                                                                        </template>
                                                                        <template x-if="item.place && item.place.opening_hours === 'chưa xác minh'">
                                                                            <span class="text-slate-400 italic">Giờ: chưa xác minh</span>
                                                                        </template>
                                                                        <span>•</span>
                                                                        <span class="font-semibold text-emerald-700 dark:text-emerald-400" x-text="item.place ? item.place.ticket_price_vnd : ''"></span>
                                                                    </div>

                                                                    <template x-if="item.note">
                                                                        <p class="text-[11px] text-slate-600 dark:text-slate-400 italic leading-snug" x-text="item.note"></p>
                                                                    </template>
                                                                </div>

                                                                <!-- Google Maps Link Button -->
                                                                <template x-if="item.place && item.place.google_maps_url">
                                                                    <a :href="item.place.google_maps_url"
                                                                       target="_blank"
                                                                       rel="noopener noreferrer"
                                                                       class="flex-shrink-0 p-1.5 bg-white dark:bg-slate-700 hover:bg-teal-50 dark:hover:bg-teal-900/40 text-teal-600 dark:text-teal-400 border border-slate-200 dark:border-slate-600 rounded-lg transition-colors shadow-2xs"
                                                                       title="Mở Google Maps chỉ đường">
                                                                        <x-icon name="map" class="w-3.5 h-3.5" />
                                                                    </a>
                                                                </template>
                                                            </div>

                                                            <!-- Travel indicator to next place -->
                                                            <template x-if="item.travel_to_next">
                                                                <div class="flex items-center gap-1 pl-4 text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                                                                    <span>↓ Di chuyển:</span>
                                                                    <span class="text-teal-600 dark:text-teal-400 font-semibold" x-text="item.travel_to_next.travel_minutes + ' phút (' + item.travel_to_next.distance_km + ' km)'"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Legal Attributions & Disclaimer -->
                                    <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800 text-[10px] text-slate-400 dark:text-slate-500 space-y-1">
                                        <p class="italic" x-text="msg.suggested_action.params.disclaimer || 'Giờ mở cửa và giá vé có thể thay đổi tùy thời điểm, vui lòng kiểm tra lại trước chuyến đi.'"></p>
                                        <p class="text-[9px] text-slate-400">
                                            Dữ liệu: © OpenStreetMap (ODbL) • Thời tiết: Open-Meteo.com • Wikivoyage (CC BY-SA 4.0)
                                        </p>
                                    </div>

                                    <!-- Create Trip CTA Button -->
                                    <button @click="createTripFromItinerary(msg.suggested_action.params)"
                                            :disabled="isCreatingTrip"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-teal-900/20 hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50 cursor-pointer">
                                        <template x-if="!isCreatingTrip">
                                            <div class="flex items-center gap-1.5">
                                                <span>🚀</span>
                                                <span>Tạo chuyến đi từ lịch trình này</span>
                                            </div>
                                        </template>
                                        <template x-if="isCreatingTrip">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                                <span>Đang tạo chuyến đi...</span>
                                            </div>
                                        </template>
                                    </button>
                                </div>
                            </template>

                            <!-- Suggested Action Card (If Present for single activity) -->
                            <template x-if="msg.suggested_action && msg.suggested_action.params && msg.suggested_action.action !== 'create_trip_itinerary' && msg.suggested_action.type !== 'create_trip_itinerary'">
                                <div class="p-3.5 bg-gradient-to-br from-teal-50 to-emerald-50 dark:from-slate-800 dark:to-teal-950/40 border border-teal-200 dark:border-teal-700/50 rounded-xl shadow-sm space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-teal-800 dark:text-teal-300">
                                            <x-icon name="sparkles" class="w-4 h-4 text-amber-500" />
                                            <span>{{ __('Action Card: Hoạt động đề xuất') }}</span>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide"
                                              :class="msg.is_applied ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'"
                                              x-text="msg.is_applied ? 'Đã thêm vào lịch' : 'Chờ duyệt'">
                                        </span>
                                    </div>

                                    <div class="bg-white/80 dark:bg-slate-900/80 p-2.5 rounded-lg border border-teal-100 dark:border-teal-900/50 text-xs space-y-1">
                                        <div class="font-bold text-slate-900 dark:text-white text-sm" x-text="msg.suggested_action.params.title"></div>
                                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                            <span x-text="'Ngày ' + msg.suggested_action.params.day + ' • ' + (msg.suggested_action.params.time_slot || 'morning')"></span>
                                            <span class="font-bold text-teal-700 dark:text-teal-400" x-text="formatCurrency(msg.suggested_action.params.cost || 0)"></span>
                                        </div>
                                        <template x-if="msg.suggested_action.params.description">
                                            <p class="text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-100 dark:border-slate-800 italic" x-text="msg.suggested_action.params.description"></p>
                                        </template>
                                    </div>

                                    <template x-if="!msg.is_applied">
                                        <button @click="applyAction(msg)"
                                                :disabled="isApplying"
                                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium text-xs rounded-lg shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-teal-500">
                                            <x-icon name="check" class="w-4 h-4" />
                                            <span>{{ __('Apply to Timeline') }}</span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Loading Typing Indicator -->
                <div x-show="isLoading" class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-700 dark:text-teal-300 flex-shrink-0">
                        <x-icon name="bot" class="w-4 h-4" />
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-800 p-3.5 rounded-2xl rounded-tl-sm text-sm text-slate-500 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-teal-500 animate-bounce"></span>
                        <span class="w-2 h-2 rounded-full bg-teal-500 animate-bounce" style="animation-delay: 0.15s"></span>
                        <span class="w-2 h-2 rounded-full bg-teal-500 animate-bounce" style="animation-delay: 0.3s"></span>
                        <span class="ml-2 text-xs font-medium text-slate-500">{{ __('AI đang suy nghĩ...') }}</span>
                    </div>
                </div>
            </div>

            <!-- Chat Input Footer -->
            <div class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
                <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                    <input type="text"
                           x-model="inputMessage"
                           :disabled="isLoading"
                           placeholder="{{ __('Hỏi AI hoặc yêu cầu gợi ý giải pháp...') }}"
                           class="flex-1 px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-900 dark:text-white placeholder-slate-400">
                    <button type="submit"
                            :disabled="isLoading || !inputMessage.trim()"
                            class="p-2.5 bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white rounded-xl shadow-md transition-all">
                        <x-icon name="send" class="w-4 h-4" />
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ingest Review Text -->
    <div x-show="isReviewModalOpen"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="isReviewModalOpen = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 text-left shadow-2xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <h4 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <x-icon name="file-text" class="w-5 h-5 text-teal-600" />
                        <span>{{ __('Trích xuất Lịch trình từ Bài Review') }}</span>
                    </h4>
                    <button @click="isReviewModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                        {{ __('Dán đoạn văn bản review chuyến đi từ Facebook, Blog hoặc ghi chú cá nhân. AI sẽ tự động nhận diện ngày, khung giờ (sáng/trưa/chiều/tối) và ước tính chi phí.') }}
                    </p>
                    <textarea x-model="reviewRawText"
                              rows="6"
                              placeholder="Ví dụ:&#10;Ngày 1:&#10;Sáng: Ăn phở gà Bát Đàn 50k rồi dạo quanh Hồ Gươm.&#10;Chiều: Thăm Văn Miếu Quốc Tử Giám 30k.&#10;Tối: Đi dạo phố Tạ Hiện uống bia 100k..."
                              class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-teal-500 focus:outline-none"></textarea>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="isReviewModalOpen = false" class="px-4 py-2 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">
                        {{ __('Hủy') }}
                    </button>
                    <button type="button"
                            @click="ingestReview()"
                            :disabled="isIngesting || !reviewRawText.trim()"
                            class="px-4 py-2 text-xs font-semibold bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 inline-flex items-center gap-1.5">
                        <x-icon name="sparkles" class="w-4 h-4" />
                        <span>{{ __('Bóc tách vào Lịch trình') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
