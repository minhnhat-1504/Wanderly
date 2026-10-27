@extends('layouts.app')

@section('title', __('Booking & Chat — :code', ['code' => $booking->voucher_code]))

@section('content')
<div class="space-y-6" x-data="bookingChat({{ $booking->id }})">

    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <a href="{{ route('provider.bookings.index') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                    <x-icon name="ticket" class="w-3.5 h-3.5 text-primary" />
                    <span>{{ __('All Bookings') }}</span>
                </a>
                <span>/</span>
                <span class="text-gray-900 font-bold font-mono">{{ $booking->voucher_code }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                <span>{{ $booking->item_name }}</span>
                <span class="text-xs font-mono px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ $booking->voucher_code }}</span>
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ __('Client:') }} <strong class="text-gray-800">{{ $booking->trip?->owner?->name ?? __('Traveler') }}</strong>
                ({{ $booking->trip?->owner?->email ?? '' }})
                • {{ __('Trip:') }} <strong class="text-gray-800">{{ $booking->trip?->name }}</strong>
            </p>
        </div>

        <a href="{{ route('provider.bookings.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            &larr; {{ __('Back to Bookings') }}
        </a>
    </div>

    {{-- Chat Box Container --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[560px]">

        {{-- Chat Header --}}
        <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/70 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">
                    {{ strtoupper(substr($booking->trip?->owner?->name ?? 'T', 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900 leading-tight">
                        {{ $booking->trip?->owner?->name ?? __('Traveler') }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                        {{ __('1-on-1 direct channel for booking fulfillment') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ __('Live Polling (4s)') }}
                </span>
            </div>
        </div>

        {{-- Messages Stream --}}
        <div class="flex-1 overflow-y-auto p-5 space-y-3.5 bg-gray-50/30" x-ref="messagesContainer">
            <template x-if="loading && messages.length === 0">
                <div class="text-center py-12 text-gray-400 text-xs">
                    {{ __('Loading conversation history...') }}
                </div>
            </template>

            <template x-if="!loading && messages.length === 0">
                <div class="text-center py-16 text-gray-400 text-xs space-y-2">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-teal-50 flex items-center justify-center text-teal-600 mb-2">
                        <x-icon name="message-circle" class="w-6 h-6" />
                    </div>
                    <p class="font-medium text-gray-600">{{ __('No messages yet in this conversation') }}</p>
                    <p class="text-[11px] text-gray-400">{{ __('Send a greeting, confirm arrival time, or provide meeting instructions.') }}</p>
                </div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div class="flex gap-2.5 max-w-[85%]" :class="msg.is_mine ? 'ml-auto flex-row-reverse' : 'mr-auto'">
                    {{-- Avatar --}}
                    <div class="w-7 h-7 rounded-full text-[11px] font-bold shrink-0 flex items-center justify-center shadow-sm"
                         :class="msg.is_mine ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'"
                         x-text="msg.sender.initial">
                    </div>

                    <div class="space-y-1">
                        <div class="rounded-2xl px-4 py-2.5 text-xs shadow-sm"
                             :class="msg.is_mine ? 'bg-primary text-white rounded-tr-none' : 'bg-white text-gray-900 border border-gray-100 rounded-tl-none'">
                            <p class="whitespace-pre-wrap leading-relaxed" x-text="msg.content" x-show="msg.content"></p>

                            <template x-if="msg.attachment_url">
                                <div class="mt-2 rounded-lg overflow-hidden border" :class="msg.is_mine ? 'border-teal-700' : 'border-gray-200'">
                                    <a :href="msg.attachment_url" target="_blank">
                                        <img :src="msg.attachment_url" alt="Attachment" class="max-w-xs max-h-56 object-cover hover:opacity-90 transition-opacity">
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

        {{-- Chat Input Bar --}}
        <div class="p-4 bg-white border-t border-gray-100">
            {{-- Image Preview Bar --}}
            <template x-if="imagePreview">
                <div class="mb-3 flex items-center gap-2 p-2 bg-gray-50 rounded-xl border border-gray-200 w-fit">
                    <img :src="imagePreview" class="w-12 h-12 object-cover rounded-lg border">
                    <button type="button" @click="clearAttachment()" class="text-xs text-red-500 hover:text-red-700 font-bold px-1.5">
                        ✕ {{ __('Remove') }}
                    </button>
                </div>
            </template>

            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                {{-- Attach Image Button --}}
                <label class="p-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-500 cursor-pointer transition-colors shrink-0" title="{{ __('Attach image') }}">
                    <input type="file" accept="image/*" class="hidden" @change="handleFileSelect($event)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </label>

                {{-- Text Input --}}
                <input
                    type="text"
                    x-model="content"
                    placeholder="{{ __('Type your message to traveler...') }}"
                    :disabled="sending"
                    class="flex-1 rounded-xl border-gray-200 text-xs focus:border-primary focus:ring-primary shadow-sm"
                >

                {{-- Send Button --}}
                <button
                    type="submit"
                    :disabled="sending || (!content.trim() && !file)"
                    class="px-4 py-2.5 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-teal-800 disabled:opacity-50 transition-all shrink-0 flex items-center gap-1.5 shadow-sm"
                >
                    <span x-show="!sending">{{ __('Send') }}</span>
                    <span x-show="sending">{{ __('Sending...') }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </form>
        </div>

    </div>

</div>

@push('scripts')
<script>
function bookingChat(bookingId) {
    return {
        bookingId: bookingId,
        messages: [],
        content: '',
        file: null,
        imagePreview: null,
        loading: true,
        sending: false,
        pollTimer: null,
        lastMessageId: 0,

        init() {
            this.fetchHistory();
            this.pollTimer = setInterval(() => this.pollNewMessages(), 4000);
        },

        async fetchHistory() {
            try {
                const res = await fetch(`/ajax/bookings/${this.bookingId}/messages`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.messages = data.messages || [];
                    if (this.messages.length > 0) {
                        this.lastMessageId = this.messages[this.messages.length - 1].id;
                    }
                    this.scrollToBottom();
                }
            } finally {
                this.loading = false;
            }
        },

        async pollNewMessages() {
            if (this.loading || document.hidden) return;

            try {
                const url = `/ajax/bookings/${this.bookingId}/messages?after_id=${this.lastMessageId}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    if (data.messages && data.messages.length > 0) {
                        this.messages = [...this.messages, ...data.messages];
                        this.lastMessageId = this.messages[this.messages.length - 1].id;
                        this.scrollToBottom();
                    }
                }
            } catch (err) {
                // Ignore transient polling network drops
            }
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.file = file;

            const reader = new FileReader();
            reader.onload = (evt) => { this.imagePreview = evt.target.result; };
            reader.readAsDataURL(file);
        },

        clearAttachment() {
            this.file = null;
            this.imagePreview = null;
        },

        async sendMessage() {
            if ((!this.content.trim() && !this.file) || this.sending) return;
            this.sending = true;

            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            const formData = new FormData();
            if (this.content.trim()) formData.append('content', this.content.trim());
            if (this.file) formData.append('attachment', this.file);

            try {
                const res = await fetch(`/ajax/bookings/${this.bookingId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.message) {
                        this.messages.push(data.message);
                        this.lastMessageId = data.message.id;
                        this.content = '';
                        this.clearAttachment();
                        this.scrollToBottom();
                    }
                } else {
                    const err = await res.json();
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: err.message || 'Failed to send' } }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: 'Network error' } }));
            } finally {
                this.sending = false;
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messagesContainer;
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    };
}
</script>
@endpush
@endsection
