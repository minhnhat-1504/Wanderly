@php
    use App\Services\NotificationService;
    $notifService = app(NotificationService::class);
    $unreadCount = auth()->check() ? $notifService->countUnread(auth()->user()) : 0;
@endphp

<div x-data="{
    open: false,
    unread: {{ $unreadCount }},
    notifications: [],
    loading: false,

    async fetchNotifications() {
        this.loading = true;
        try {
            const res = await fetch('/ajax/notifications', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            this.notifications = data.notifications;
            this.unread = data.unread_count;
        } catch(e) { console.error(e); }
        this.loading = false;
    },

    async markAllRead() {
        const csrfToken = document.querySelector('meta[name=\'csrf-token\']').getAttribute('content');
        await fetch('/ajax/notifications/mark-all-read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        this.notifications = this.notifications.map(n => ({ ...n, is_read: true }));
        this.unread = 0;
    },

    async markOneRead(id, url) {
        const csrfToken = document.querySelector('meta[name=\'csrf-token\']').getAttribute('content');
        await fetch('/ajax/notifications/' + id + '/read', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        this.notifications = this.notifications.map(n => n.id === id ? { ...n, is_read: true } : n);
        if (this.unread > 0) this.unread--;
        if (url) window.location.href = url;
    }
}"
     @click.outside="open = false"
     class="relative">

    {{-- Bell Button --}}
    <button type="button"
            @click="open = !open; if(open) fetchNotifications()"
            class="relative p-2.5 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all duration-200"
            id="btn-notification-bell"
            :aria-expanded="open"
            aria-label="{{ __('Notifications') }}">

        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>

        {{-- Unread Badge --}}
        <span x-show="unread > 0"
              x-cloak
              class="absolute top-1.5 right-1.5 min-w-[16px] h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center ring-2 ring-white"
              x-text="unread > 99 ? '99+' : unread">
        </span>
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-(-4px)"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak
         class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl ring-1 ring-black/5 overflow-hidden z-50"
         id="notification-dropdown">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50/80">
            <span class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <x-icon name="bell" class="w-4 h-4 text-primary" />
                <span>{{ __('Notifications') }}</span>
            </span>
            <button type="button"
                    @click="markAllRead()"
                    x-show="unread > 0"
                    x-cloak
                    class="text-xs text-primary hover:text-primary/80 font-medium transition-colors"
                    id="btn-mark-all-read">
                {{ __('Mark all read') }}
            </button>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="flex justify-center py-8">
            <svg class="animate-spin w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
        </div>

        {{-- Notification List --}}
        <div x-show="!loading" class="max-h-80 overflow-y-auto divide-y divide-gray-50">
            <template x-if="notifications.length === 0">
                <div class="text-center py-10 text-gray-400">
                    <div class="w-12 h-12 mx-auto mb-2 rounded-2xl bg-teal-50 flex items-center justify-center text-teal-600">
                        <x-icon name="check-circle" class="w-6 h-6" />
                    </div>
                    <p class="text-sm font-medium">{{ __("You're all caught up!") }}</p>
                </div>
            </template>

            <template x-for="notif in notifications" :key="notif.id">
                <div :class="notif.is_read ? 'bg-white' : 'bg-blue-50/40'"
                     class="flex gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                     @click="markOneRead(notif.id, notif.action_url)">

                    {{-- Type Icon --}}
                    <div class="shrink-0 mt-0.5">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm"
                             :class="{
                                'bg-red-100': notif.type === 'budget_warning',
                                'bg-blue-100': notif.type === 'trip_invite',
                                'bg-violet-100': notif.type === 'new_proposal',
                                'bg-green-100': notif.type === 'booking_update',
                                'bg-yellow-100': notif.type === 'post_like' || notif.type === 'new_comment',
                                'bg-emerald-100': notif.type === 'report_resolved',
                                'bg-teal-100': notif.type === 'broadcast',
                                'bg-gray-100': notif.type === 'general' || notif.type === 'new_message',
                             }">
                            <span x-text="{
                                budget_warning: '💰',
                                trip_invite: '🎫',
                                new_proposal: '📋',
                                booking_update: '✅',
                                new_message: '💬',
                                new_comment: '💬',
                                post_like: '❤️',
                                report_resolved: '🛡️',
                                broadcast: '📢',
                                general: '🔔'
                            }[notif.type] || '🔔'"></span>
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="notif.title"></p>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed line-clamp-2" x-text="notif.message"></p>
                        <p class="text-xs text-gray-400 mt-1" x-text="notif.created_at"></p>
                    </div>

                    {{-- Unread dot --}}
                    <div x-show="!notif.is_read" class="shrink-0 mt-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50/50">
            <a href="{{ route('social.feed') }}" class="block text-center text-xs text-gray-400 hover:text-primary transition-colors">
                {{ __('Go to Community Feed') }} →
            </a>
        </div>
    </div>
</div>
