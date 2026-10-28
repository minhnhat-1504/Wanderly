import { marked } from 'marked';
import DOMPurify from 'dompurify';

marked.setOptions({
    breaks: true,
    gfm: true
});

document.addEventListener('alpine:init', () => {
    Alpine.data('aiCopilotDrawer', (config) => ({
        tripId: config?.tripId || null,
        isOpen: false,
        isLoading: false,
        isApplying: false,
        isReviewModalOpen: false,
        isIngesting: false,
        isCreatingTrip: false,
        isClearing: false,
        showClearConfirmModal: false,
        inputMessage: '',
        reviewRawText: '',
        messages: [],
        weatherForecast: null,
        weatherAlertsCount: 0,

        init() {
            if (this.tripId) {
                this.fetchWeather();
            }
        },

        renderMarkdown(content) {
            if (!content) return '';
            try {
                const rawHtml = marked.parse(content);
                return DOMPurify.sanitize(rawHtml, {
                    ALLOWED_TAGS: [
                        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
                        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                        'ul', 'ol', 'li', 'blockquote',
                        'code', 'pre', 'hr', 'a', 'span'
                    ],
                    ALLOWED_ATTR: ['href', 'target', 'rel', 'class']
                });
            } catch (err) {
                console.error('Markdown rendering error:', err);
                return content;
            }
        },

        openDrawer() {
            this.isOpen = true;
            if (this.messages.length === 0) {
                this.loadMessages();
            }
        },

        async loadMessages() {
            try {
                const endpoint = this.tripId 
                    ? `/ajax/trips/${this.tripId}/ai-copilot/messages`
                    : `/ajax/ai-copilot/messages`;

                const res = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.messages = data.messages || [];
                    this.scrollToBottom(true);
                }
            } catch (err) {
                console.error('Failed to load AI messages:', err);
            }
        },

        confirmClearHistory() {
            if (this.isClearing || this.messages.length === 0) return;
            this.showClearConfirmModal = true;
        },

        cancelClearHistory() {
            this.showClearConfirmModal = false;
        },

        async clearHistory() {
            if (this.isClearing) return;
            this.isClearing = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const endpoint = this.tripId 
                    ? `/ajax/trips/${this.tripId}/ai-copilot/messages`
                    : `/ajax/ai-copilot/messages`;

                const res = await fetch(endpoint, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.messages = [];
                    this.showClearConfirmModal = false;
                } else {
                    alert(data.message || 'Không thể xóa lịch sử trò chuyện.');
                }
            } catch (err) {
                console.error('Failed to clear chat history:', err);
                alert('Có lỗi xảy ra khi xóa lịch sử trò chuyện.');
            } finally {
                this.isClearing = false;
            }
        },

        async fetchWeather() {
            if (!this.tripId) return;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/ai-copilot/weather-alerts`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.weatherForecast = data.forecast;
                    this.weatherAlertsCount = (data.conflicts || []).length;
                }
            } catch (err) {
                console.error('Failed to fetch weather alerts:', err);
            }
        },

        handleKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        },

        quickPrompt(promptText) {
            this.inputMessage = promptText;
            this.sendMessage();
        },

        async sendMessage() {
            const text = this.inputMessage.trim();
            if (!text || this.isLoading) return;

            this.inputMessage = '';
            this.isLoading = true;

            // Optimistic message append
            const tempUserMsg = {
                id: 'temp-' + Date.now(),
                role: 'user',
                content: text,
                suggested_action: null,
                is_applied: false
            };
            this.messages.push(tempUserMsg);
            this.scrollToBottom(true);

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const endpoint = this.tripId 
                    ? `/ajax/trips/${this.tripId}/ai-copilot/chat`
                    : `/ajax/ai-copilot/chat`;

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ message: text })
                });

                const data = await res.json();
                if (data.success) {
                    // Replace temp message with persisted message
                    const idx = this.messages.findIndex(m => m.id === tempUserMsg.id);
                    if (idx !== -1) {
                        this.messages[idx] = data.user_message;
                    }
                    this.messages.push(data.assistant_message);
                    this.scrollToBottom();
                } else {
                    alert(data.message || 'Lỗi xử lý phản hồi từ AI.');
                }
            } catch (err) {
                console.error('Chat error:', err);
                alert('Không thể kết nối với AI Co-pilot lúc này.');
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
            }
        },

        async applyAction(msg) {
            if (this.isApplying || msg.is_applied) return;
            this.isApplying = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/ajax/trips/${this.tripId}/ai-copilot/apply-action/${msg.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });

                const data = await res.json();
                if (data.success) {
                    msg.is_applied = true;
                    // Trigger custom event so Timeline component can refresh or append
                    window.dispatchEvent(new CustomEvent('activity-added', { detail: data.activity }));
                    alert('Đã thêm hoạt động vào Lịch trình thành công!');
                } else {
                    alert(data.message || 'Không thể thêm hoạt động vào lịch trình.');
                }
            } catch (err) {
                console.error('Apply action error:', err);
                alert('Có lỗi xảy ra khi cập nhật lịch trình.');
            } finally {
                this.isApplying = false;
            }
        },

        async ingestReview() {
            const raw = this.reviewRawText.trim();
            if (!raw || this.isIngesting) return;
            this.isIngesting = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/ajax/trips/${this.tripId}/ai-copilot/ingest-review`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ review_text: raw })
                });

                const data = await res.json();
                if (data.success && data.activities?.length) {
                    this.isReviewModalOpen = false;
                    this.reviewRawText = '';
                    
                    // Summarize into chat
                    const summary = `AI đã bóc tách thành công ${data.activities.length} hoạt động từ bài review:\n` +
                        data.activities.map(a => `• Ngày ${a.day} (${a.time_slot}): ${a.title} - ${this.formatCurrency(a.cost)}`).join('\n');
                    
                    this.messages.push({
                        id: 'ingest-' + Date.now(),
                        role: 'assistant',
                        content: summary,
                        suggested_action: {
                            type: 'create_activity',
                            params: data.activities[0]
                        },
                        is_applied: false
                    });
                    this.openDrawer();
                    this.scrollToBottom();
                } else {
                    alert('Không tìm thấy hoạt động phù hợp trong đoạn văn bản.');
                }
            } catch (err) {
                console.error('Review ingestion error:', err);
                alert('Lỗi phân tích bài review.');
            } finally {
                this.isIngesting = false;
            }
        },

        async createTripFromItinerary(plan) {
            if (this.isCreatingTrip || !plan) return;
            this.isCreatingTrip = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/ajax/ai-copilot/create-trip', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        province: plan.province,
                        days: plan.days,
                        start_date: plan.start_date
                    })
                });

                const data = await res.json();
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert(data.message || 'Không thể tạo chuyến đi lúc này.');
                    this.isCreatingTrip = false;
                }
            } catch (err) {
                console.error('Create trip error:', err);
                alert('Có lỗi xảy ra khi tạo chuyến đi từ lịch trình.');
                this.isCreatingTrip = false;
            }
        },

        scrollToBottom(force = false) {
            this.$nextTick(() => {
                const el = this.$refs.messagesContainer;
                if (el) {
                    const isNearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 120;
                    if (force || isNearBottom) {
                        el.scrollTo({
                            top: el.scrollHeight,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        },

        formatCurrency(num) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(num);
        }
    }));
});
