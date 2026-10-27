/**
 * Wanderly Chat Module (Sprint 3 - D3-05)
 * Handles dual-channel chat (Trip Group Chat & Provider 1-1 Booking Chat)
 * with AJAX polling every 4 seconds, image upload, audio chime, and auto-scroll.
 */

// Gentle Web Audio API chime for incoming messages (zero external audio dependencies)
function playMessageChime() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12); // A5

        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start();
        osc.stop(ctx.currentTime + 0.35);
    } catch (e) {
        // Silently skip if user hasn't interacted or browser restricts audio
    }
}

export function initChatDrawer(config = {}) {
    return {
        open: false,
        tripId: config.tripId || null,
        activeTab: 'group', // 'group' or 'direct'
        selectedBookingId: null,
        directChannels: [],

        messages: [],
        lastMessageId: 0,
        content: '',
        file: null,
        imagePreview: null,
        lightboxImage: null,

        loading: false,
        sending: false,
        pollTimer: null,
        unreadCount: 0,

        init() {
            window.addEventListener('open-chat-drawer', (e) => {
                // Mutual exclusion: Close other modals/drawers
                window.dispatchEvent(new CustomEvent('close-activity-modal'));
                window.dispatchEvent(new CustomEvent('close-room-drawer'));
                window.dispatchEvent(new CustomEvent('drawer-toggled', { detail: { open: true } }));

                this.open = true;

                if (e.detail && e.detail.channel === 'direct' && e.detail.bookingId) {
                    this.activeTab = 'direct';
                    this.selectedBookingId = e.detail.bookingId;
                }

                this.loadActiveChannel();
                this.startPolling();
                this.fetchChannels();
            });

            window.addEventListener('close-chat-drawer', () => {
                this.closeDrawer();
            });

            // If tripId is provided, prefetch channel list
            if (this.tripId) {
                this.fetchChannels();
                this.fetchUnreadCount();
            }
        },

        closeDrawer() {
            this.open = false;
            this.stopPolling();
            window.dispatchEvent(new CustomEvent('drawer-toggled', { detail: { open: false } }));
        },

        switchTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.messages = [];
            this.lastMessageId = 0;
            this.loadActiveChannel();
        },

        selectBooking(bookingId) {
            this.selectedBookingId = bookingId;
            this.messages = [];
            this.lastMessageId = 0;
            this.loadActiveChannel();
        },

        async fetchChannels() {
            if (!this.tripId) return;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/channels`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.directChannels = data.channels || [];
                    if (!this.selectedBookingId && this.directChannels.length > 0) {
                        this.selectedBookingId = this.directChannels[0].booking_id;
                    }
                }
            } catch (e) {
                // Ignore transient network errors
            }
        },

        async fetchUnreadCount() {
            try {
                const res = await fetch('/ajax/messages/unread-count', {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.unreadCount = data.unread || 0;
                }
            } catch (e) {}
        },

        getActiveEndpoint() {
            if (this.activeTab === 'group') {
                return `/ajax/trips/${this.tripId}/messages`;
            }
            if (this.activeTab === 'direct' && this.selectedBookingId) {
                return `/ajax/bookings/${this.selectedBookingId}/messages`;
            }
            return null;
        },

        async loadActiveChannel() {
            const endpoint = this.getActiveEndpoint();
            if (!endpoint) return;

            this.loading = true;
            try {
                const res = await fetch(endpoint, {
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

        startPolling() {
            this.stopPolling();
            this.pollTimer = setInterval(() => this.pollMessages(), 4000);
        },

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        async pollMessages() {
            if (!this.open || document.hidden || this.loading) return;

            const endpoint = this.getActiveEndpoint();
            if (!endpoint) return;

            try {
                const url = `${endpoint}?after_id=${this.lastMessageId}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    if (data.messages && data.messages.length > 0) {
                        const hasIncoming = data.messages.some(m => !m.is_mine);
                        if (hasIncoming) {
                            playMessageChime();
                        }
                        this.messages = [...this.messages, ...data.messages];
                        this.lastMessageId = this.messages[this.messages.length - 1].id;
                        this.scrollToBottom();
                    }
                    if (typeof data.unread !== 'undefined') {
                        this.unreadCount = data.unread;
                    }
                }
            } catch (e) {}
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
            const endpoint = this.getActiveEndpoint();
            if (!endpoint || this.sending) return;
            if (!this.content.trim() && !this.file) return;

            this.sending = true;
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            const formData = new FormData();
            if (this.content.trim()) formData.append('content', this.content.trim());
            if (this.file) formData.append('attachment', this.file);

            try {
                const res = await fetch(endpoint, {
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
                const el = this.$refs.chatMessagesContainer;
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    };
}
