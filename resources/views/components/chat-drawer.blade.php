@props([
    'trip',
])

<div
    x-data="initChatDrawer({ tripId: {{ $trip->id }} })"
    @keydown.escape.window="if (open) closeDrawer()"
    @open-chat-drawer.window="open = true; loadActiveChannel(); startPolling(); fetchChannels();"
    @close-chat-drawer.window="closeDrawer()"
    class="relative z-[65]"
    id="chat-drawer-root"
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
        x-cloak
    ></div>

    {{-- Sliding Drawer Panel (Right Side) --}}
    <div
        x-show="open"
        x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-200 sm:duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 w-full sm:w-[440px] md:w-[480px] bg-white shadow-2xl flex flex-col z-[70] overflow-hidden"
        x-cloak
    >
        {{-- Drawer Header --}}
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-base">💬</span>
                    <h2 class="text-base font-bold text-gray-900 leading-tight">
                        {{ __('Trip & Provider Messages') }}
                    </h2>
                </div>
                <p class="text-[11px] text-gray-500 mt-0.5 truncate max-w-xs">
                    {{ $trip->name }}
                </p>
            </div>

            <button
                type="button"
                @click="closeDrawer()"
                class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                title="{{ __('Close chat') }}"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Channel Tabs --}}
        <div class="px-4 pt-3 pb-2 bg-white border-b border-gray-100 flex items-center gap-2 shrink-0">
            <button
                type="button"
                @click="switchTab('group')"
                :class="activeTab === 'group' ? 'bg-primary text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="flex-1 py-1.5 px-3 rounded-xl text-xs font-semibold transition-all flex items-center justify-center gap-1.5"
            >
                <span>👥</span>
                <span>{{ __('Trip Group Chat') }}</span>
            </button>

            <button
                type="button"
                @click="switchTab('direct')"
                :class="activeTab === 'direct' ? 'bg-primary text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="flex-1 py-1.5 px-3 rounded-xl text-xs font-semibold transition-all flex items-center justify-center gap-1.5 relative"
            >
                <span>🏢</span>
                <span>{{ __('Provider 1-1 Chat') }}</span>
                <template x-if="directChannels.some(c => c.unread > 0)">
                    <span class="w-2 h-2 rounded-full bg-red-500 absolute top-1 right-2"></span>
                </template>
            </button>
        </div>

        {{-- Direct Booking Selector (When on 'direct' tab) --}}
        <div x-show="activeTab === 'direct'" class="px-4 py-2.5 bg-gray-50/60 border-b border-gray-100 shrink-0">
            <template x-if="directChannels.length === 0">
                <p class="text-xs text-gray-500 py-1 text-center">
                    {{ __('No confirmed provider bookings found for this trip.') }}
                </p>
            </template>

            <template x-if="directChannels.length > 0">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">
                        {{ __('Select Service Partner:') }}
                    </label>
                    <div class="flex gap-1.5 overflow-x-auto pb-1">
                        <template x-for="ch in directChannels" :key="ch.booking_id">
                            <button
                                type="button"
                                @click="selectBooking(ch.booking_id)"
                                :class="selectedBookingId === ch.booking_id ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300'"
                                class="px-2.5 py-1 rounded-lg text-xs font-medium whitespace-nowrap shrink-0 transition-colors flex items-center gap-1.5"
                            >
                                <span x-text="ch.counterpart"></span>
                                <template x-if="ch.unread > 0">
                                    <span class="bg-red-500 text-white text-[9px] font-bold px-1 rounded-full" x-text="ch.unread"></span>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Messages Scroll Area --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50/30" x-ref="chatMessagesContainer">
            <template x-if="loading && messages.length === 0">
                <div class="text-center py-12 text-gray-400 text-xs">
                    {{ __('Loading messages...') }}
                </div>
            </template>

            <template x-if="!loading && messages.length === 0">
                <div class="text-center py-16 text-gray-400 text-xs space-y-2">
                    <span class="text-3xl">💬</span>
                    <p class="font-medium text-gray-600">
                        <span x-show="activeTab === 'group'">{{ __('No group messages yet') }}</span>
                        <span x-show="activeTab === 'direct'">{{ __('No messages with this provider yet') }}</span>
                    </p>
                    <p class="text-[11px] text-gray-400">
                        {{ __('Start the conversation to discuss schedule, luggage, or logistics!') }}
                    </p>
                </div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div class="flex gap-2.5 max-w-[85%]" :class="msg.is_mine ? 'ml-auto flex-row-reverse' : 'mr-auto'">
                    <div
                        class="w-7 h-7 rounded-full text-[11px] font-bold shrink-0 flex items-center justify-center shadow-sm"
                        :class="msg.is_mine ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'"
                        x-text="msg.sender.initial"
                    ></div>

                    <div class="space-y-1">
                        <div
                            class="rounded-2xl px-3.5 py-2.5 text-xs shadow-sm"
                            :class="msg.is_mine ? 'bg-primary text-white rounded-tr-none' : 'bg-white text-gray-900 border border-gray-100 rounded-tl-none'"
                        >
                            <p class="whitespace-pre-wrap leading-relaxed" x-text="msg.content" x-show="msg.content"></p>

                            <template x-if="msg.attachment_url">
                                <div class="mt-2 rounded-lg overflow-hidden border" :class="msg.is_mine ? 'border-teal-700' : 'border-gray-200'">
                                    <a :href="msg.attachment_url" target="_blank">
                                        <img :src="msg.attachment_url" alt="Attachment" class="max-w-xs max-h-48 object-cover hover:opacity-90 transition-opacity">
                                    </a>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center gap-1.5 text-[10px] text-gray-400" :class="msg.is_mine ? 'justify-end' : 'justify-start'">
                            <span x-text="msg.sender.name" x-show="!msg.is_mine" class="font-semibold text-gray-600"></span>
                            <span x-show="!msg.is_mine">•</span>
                            <span x-text="msg.time_label"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Input Footer --}}
        <div class="p-3 bg-white border-t border-gray-100 shrink-0">
            <template x-if="imagePreview">
                <div class="mb-2 flex items-center gap-2 p-1.5 bg-gray-50 rounded-xl border border-gray-200 w-fit">
                    <img :src="imagePreview" class="w-10 h-10 object-cover rounded-lg border">
                    <button type="button" @click="clearAttachment()" class="text-[11px] text-red-500 hover:text-red-700 font-bold px-1">
                        ✕ {{ __('Remove') }}
                    </button>
                </div>
            </template>

            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                <label class="p-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-500 cursor-pointer transition-colors shrink-0" title="{{ __('Attach image') }}">
                    <input type="file" accept="image/*" class="hidden" @change="handleFileSelect($event)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </label>

                <input
                    type="text"
                    x-model="content"
                    placeholder="{{ __('Type your message...') }}"
                    :disabled="sending"
                    @keydown.enter.prevent="if (!$event.isComposing) sendMessage()"
                    class="flex-1 rounded-xl border border-gray-200 text-xs px-3 py-2 outline-none focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-500/20 shadow-sm transition"
                    style="outline: none !important;"
                >

                <button
                    type="submit"
                    :disabled="sending || (!content.trim() && !file)"
                    class="px-3.5 py-2 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-teal-800 disabled:opacity-50 transition-all shrink-0 flex items-center gap-1 shadow-sm"
                >
                    <span x-show="!sending">{{ __('Send') }}</span>
                    <span x-show="sending">{{ __('...') }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </form>
        </div>
    </div>
</div>
