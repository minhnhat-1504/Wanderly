<div x-data="aiCopilotDrawer({ tripId: {{ $trip->id }} })"
     x-init="init()"
     class="relative z-50">

    <!-- Floating Trigger Button (Bottom Right) -->
    <button @click="openDrawer()"
            type="button"
            class="fixed bottom-6 right-6 z-40 inline-flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-medium text-sm rounded-full shadow-xl shadow-teal-900/20 hover:shadow-teal-900/40 transition-all duration-300 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-300 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
        </span>
        <x-icon name="sparkles" class="w-5 h-5 text-amber-300" />
        <span>{{ __('AI Co-pilot') }}</span>
        <template x-if="weatherAlertsCount > 0">
            <span class="px-1.5 py-0.5 text-xs font-bold bg-amber-400 text-amber-950 rounded-full" x-text="weatherAlertsCount"></span>
        </template>
    </button>

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"
         style="display: none;"></div>

    <!-- Sliding Drawer Panel -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 max-w-full flex pl-10"
         style="display: none;">
        
        <div class="w-screen max-w-md bg-white dark:bg-slate-900 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
            
            <!-- Header -->
            <div class="p-4 bg-gradient-to-r from-teal-700 via-teal-800 to-slate-900 text-white flex items-center justify-between shadow-md">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-white/10 rounded-xl backdrop-blur-md">
                        <x-icon name="bot" class="w-6 h-6 text-teal-300" />
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h3 class="font-bold text-base tracking-tight text-white">{{ __('Wanderly AI Co-pilot') }}</h3>
                            <span class="px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-teal-500/30 text-teal-200 border border-teal-400/30 rounded">Smart</span>
                        </div>
                        <p class="text-xs text-teal-100/80">{{ __('Group mediator & adaptive action assistant') }}</p>
                    </div>
                </div>
                <button @click="isOpen = false" class="p-1.5 text-white/70 hover:text-white rounded-lg hover:bg-white/10 transition-colors">
                    <x-icon name="x" class="w-5 h-5" />
                </button>
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
            </div>

            <!-- Messages Stream Area -->
            <div x-ref="messagesContainer" class="flex-1 p-4 overflow-y-auto space-y-4">
                
                <!-- Initial Welcome Message -->
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-700 dark:text-teal-300 flex-shrink-0">
                        <x-icon name="bot" class="w-4 h-4" />
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-800 p-3.5 rounded-2xl rounded-tl-sm text-sm text-slate-800 dark:text-slate-200 max-w-[85%] space-y-2">
                        <p>{{ __('Xin chào! Mình là Wanderly AI Co-pilot. Mình nắm rõ thông tin lịch trình, ngân sách và sở thích từng thành viên. Hãy hỏi mình bất kỳ điều gì hoặc nhờ mình hòa giải ý kiến nhóm!') }}</p>
                    </div>
                </div>

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

                            <!-- Suggested Action Card (If Present) -->
                            <template x-if="msg.suggested_action && msg.suggested_action.params">
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
