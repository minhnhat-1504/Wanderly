@props([
    'trip',
])

@php
    $canManageMembers = auth()->check() && (
        auth()->id() === $trip->owner_id ||
        $trip->members->where('user_id', auth()->id())->whereIn('role', ['leader'])->isNotEmpty()
    );
@endphp

<div
    x-data="{
        open: false,
        tripId: {{ $trip->id }},
        activeTab: 'members', // 'members', 'invite', 'preferences'
        inviteRole: 'viewer', // default to viewer for safe joining
        inviteUrl: '',
        copied: false,
        generating: false,
        savingPreferences: false,
        prefMessage: '',
        selectedInterests: {{ json_encode(auth()->user()?->memberPreferences->where('trip_id', $trip->id)->first()?->interests ?? ['sightseeing', 'chill']) }},
        selectedPace: '{{ auth()->user()?->memberPreferences->where('trip_id', $trip->id)->first()?->pace ?? 'balanced' }}',
        mood: {
            harmony_score: 100,
            common_pace: 'balanced',
            top_interests: [],
            breakdown: {}
        },

        init() {
            window.addEventListener('open-member-modal', () => {
                this.open = true;
                this.fetchMood();
            });
            this.fetchMood();
        },

        async fetchMood() {
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/mood`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.group_mood) {
                    this.mood = data.group_mood;
                }
            } catch (err) {
                console.error('Failed to fetch mood', err);
            }
        },

        async generateInvite() {
            this.generating = true;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/members/invite`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    },
                    body: JSON.stringify({ role: this.inviteRole })
                });

                if (res.status === 403) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { type: 'error', message: '{{ __('Permission denied: Only Leaders and Editors can generate invite links.') }}' }
                    }));
                    return;
                }

                const data = await res.json();
                if (data.success && data.invite_url) {
                    this.inviteUrl = data.invite_url;
                }
            } catch (err) {
                console.error('Failed to generate invite', err);
            } finally {
                this.generating = false;
            }
        },

        copyInviteLink() {
            if (!this.inviteUrl) return;
            navigator.clipboard.writeText(this.inviteUrl);
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        },

        async removeMember(userId) {
            if (!confirm('{{ __('Are you sure you want to remove this member?') }}')) return;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/members/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    }
                });

                if (res.status === 403) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { type: 'error', message: '{{ __('Permission denied: You do not have permission to remove this member.') }}' }
                    }));
                    return;
                }

                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { type: 'error', message: data.message || 'Failed to remove member' }
                    }));
                }
            } catch (err) {
                console.error('Failed to remove member', err);
            }
        },

        async savePreferences() {
            this.savingPreferences = true;
            this.prefMessage = '';
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/preferences`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        interests: this.selectedInterests,
                        pace: this.selectedPace
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.prefMessage = '{{ __('Preferences saved successfully!') }}';
                    if (data.group_mood) {
                        this.mood = data.group_mood;
                    }
                    setTimeout(() => this.prefMessage = '', 3000);
                }
            } catch (err) {
                console.error('Failed to save preferences', err);
            } finally {
                this.savingPreferences = false;
            }
        },

        toggleInterest(tag) {
            const idx = this.selectedInterests.indexOf(tag);
            if (idx > -1) {
                this.selectedInterests.splice(idx, 1);
            } else {
                this.selectedInterests.push(tag);
            }
        }
    }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="member-modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
        @click="open = false"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-gray-100"
            @click.stop
        >
            {{-- Modal Header (Zero icons/emojis) --}}
            <div class="px-6 pt-5 pb-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900" id="member-modal-title">
                        {{ __('Trip Members & Group Mood') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('Collaborate, manage permissions, and align preferences.') }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="h-8 w-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center text-lg font-bold select-none"
                    aria-label="{{ __('Close') }}"
                >
                    &times;
                </button>
            </div>

            {{-- Tab Switcher --}}
            <div class="flex border-b border-gray-100 px-6 bg-gray-50/50 text-xs sm:text-sm font-semibold">
                <button
                    type="button"
                    @click="activeTab = 'members'"
                    :class="activeTab === 'members' ? 'border-teal-700 text-teal-700 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-4 border-b-2 transition-colors flex items-center gap-1.5"
                >
                    <span>{{ __('Members & Mood') }}</span>
                    <span class="bg-gray-200/80 text-gray-700 text-[10px] px-1.5 py-0.5 rounded-full font-mono">
                        {{ $trip->members->count() }}
                    </span>
                </button>

                @can('update', $trip)
                    <button
                        type="button"
                        @click="activeTab = 'invite'"
                        :class="activeTab === 'invite' ? 'border-teal-700 text-teal-700 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-3 px-4 border-b-2 transition-colors"
                    >
                        <span>{{ __('Invite Friends') }}</span>
                    </button>
                @endcan

                <button
                    type="button"
                    @click="activeTab = 'preferences'"
                    :class="activeTab === 'preferences' ? 'border-teal-700 text-teal-700 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-4 border-b-2 transition-colors"
                >
                    <span>{{ __('My Preferences') }}</span>
                </button>
            </div>

            {{-- Modal Content --}}
            <div class="p-6 overflow-y-auto max-h-[70vh] space-y-6">

                {{-- 1. TAB: MEMBERS & GROUP MOOD --}}
                <div x-show="activeTab === 'members'" class="space-y-6">

                    {{-- Group Mood KPI Banner --}}
                    <div class="p-4 rounded-xl bg-teal-50/70 border border-teal-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-[11px] font-bold text-teal-800 uppercase tracking-wider block">
                                {{ __('Group Harmony Score') }}
                            </span>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <span class="text-2xl font-black text-teal-900" x-text="mood.harmony_score + '%'"></span>
                                <span class="text-xs text-teal-700 font-medium">
                                    {{ __('Alignment across members') }}
                                </span>
                            </div>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-[11px] font-bold text-teal-800 uppercase tracking-wider block">
                                {{ __('Consensus Pace') }}
                            </span>
                            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-xs font-bold uppercase bg-white text-teal-800 border border-teal-200" x-text="mood.common_pace"></span>
                        </div>
                    </div>

                    {{-- Members List --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            {{ __('Trip Collaborators') }}
                        </h4>

                        <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden bg-white">
                            @foreach($trip->members as $member)
                                <div class="p-3.5 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full bg-teal-700 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $member->user->name ?? __('Member') }}
                                                @if($member->user_id === auth()->id())
                                                    <span class="text-teal-700 font-normal text-xs">({{ __('You') }})</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500 truncate">{{ $member->user->email ?? '' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-md
                                            @if($member->role === 'leader') bg-amber-50 text-amber-800 border border-amber-200
                                            @elseif($member->role === 'editor') bg-blue-50 text-blue-800 border border-blue-200
                                            @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                                            {{ ucfirst(__($member->role)) }}
                                        </span>

                                        @if(($member->user_id !== auth()->id() && $canManageMembers) || ($member->user_id === auth()->id() && $member->role !== 'leader'))
                                            <button
                                                type="button"
                                                @click="removeMember({{ $member->user_id }})"
                                                class="text-xs text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition font-medium"
                                                title="{{ $member->user_id === auth()->id() ? __('Leave Trip') : __('Remove Member') }}"
                                            >
                                                {{ $member->user_id === auth()->id() ? __('Leave') : __('Remove') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 2. TAB: INVITE FRIENDS --}}
                @can('update', $trip)
                    <div x-show="activeTab === 'invite'" class="space-y-5">
                        <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-600 leading-relaxed">
                            <p class="font-bold text-gray-800">{{ __('Invite Friends with a Secure Link') }}</p>
                            <p class="mt-0.5">{{ __('Select a role for new invitees. Viewers cannot add, modify, or delete activities.') }}</p>
                        </div>

                        {{-- Role Selector --}}
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                {{ __('Assign Role to Invitee') }}
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col gap-1"
                                    :class="inviteRole === 'viewer' ? 'border-teal-700 bg-teal-50/50 text-teal-900' : 'border-gray-200 hover:bg-gray-50 text-gray-700'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs sm:text-sm">{{ __('Viewer (Read-Only)') }}</span>
                                        <input type="radio" value="viewer" x-model="inviteRole" class="text-teal-700 focus:ring-teal-500">
                                    </div>
                                    <span class="text-[11px] text-gray-500 font-normal">
                                        {{ __('Read-only access: locked timeline, no editing allowed.') }}
                                    </span>
                                </label>

                                <label
                                    class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col gap-1"
                                    :class="inviteRole === 'editor' ? 'border-teal-700 bg-teal-50/50 text-teal-900' : 'border-gray-200 hover:bg-gray-50 text-gray-700'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs sm:text-sm">{{ __('Editor') }}</span>
                                        <input type="radio" value="editor" x-model="inviteRole" class="text-teal-700 focus:ring-teal-500">
                                    </div>
                                    <span class="text-[11px] text-gray-500 font-normal">
                                        {{ __('Can add, edit activities, room stays, and vote.') }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-1">
                            <button
                                type="button"
                                @click="generateInvite()"
                                :disabled="generating"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 transition shadow-sm disabled:opacity-50"
                            >
                                <span x-show="!generating">{{ __('Generate 7-Day Invite Link') }}</span>
                                <span x-show="generating">{{ __('Generating...') }}</span>
                            </button>
                        </div>

                        {{-- Generated Link Box --}}
                        <div x-show="inviteUrl" class="space-y-2 pt-2">
                            <label class="block text-xs font-bold text-gray-700">
                                {{ __('Share this link with your friends:') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    readonly
                                    :value="inviteUrl"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-xs text-gray-700 font-mono focus:outline-none"
                                >
                                <button
                                    type="button"
                                    @click="copyInviteLink()"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold shrink-0 transition shadow-xs"
                                    :class="copied ? 'bg-emerald-600 text-white' : 'bg-gray-900 text-white hover:bg-black'"
                                >
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied">{{ __('Copied!') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- 3. TAB: PREFERENCES (Zero icons/emojis) --}}
                <div x-show="activeTab === 'preferences'" class="space-y-5">
                    <div class="p-4 rounded-xl bg-teal-50/50 border border-teal-100 text-teal-800 text-xs leading-relaxed">
                        {{ __('Your preferences feed directly into the Match Engine algorithm to tailor the itinerary to the entire group.') }}
                    </div>

                    <template x-if="prefMessage">
                        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
                            <span x-text="prefMessage"></span>
                        </div>
                    </template>

                    {{-- Travel Pace --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            {{ __('Your Preferred Travel Pace') }}
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([
                                'relaxed' => ['title' => 'Relaxed', 'desc' => '3 spots / day'],
                                'balanced' => ['title' => 'Balanced', 'desc' => '4 spots / day'],
                                'packed' => ['title' => 'Packed', 'desc' => '5 spots / day']
                            ] as $paceKey => $paceData)
                                <label
                                    class="p-3 rounded-xl border text-center cursor-pointer transition flex flex-col items-center gap-1"
                                    :class="selectedPace === '{{ $paceKey }}' ? 'border-teal-700 bg-teal-50/50 text-teal-900 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600'"
                                >
                                    <span class="text-xs font-bold">{{ __($paceData['title']) }}</span>
                                    <span class="text-[10px] text-gray-400 font-normal">{{ __($paceData['desc']) }}</span>
                                    <input type="radio" value="{{ $paceKey }}" x-model="selectedPace" class="sr-only">
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Interest Tags (Zero emojis, clean tags) --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            {{ __('Your Favorite Categories & Interests') }}
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach([
                                'sightseeing' => 'Sightseeing',
                                'foodie' => 'Foodie & Dining',
                                'nature' => 'Nature & Outdoors',
                                'chill' => 'Chill & Cafes',
                                'culture' => 'Culture & Heritage',
                                'nightlife' => 'Nightlife & Bars',
                                'adventure' => 'Adventure & Trekking',
                                'shopping' => 'Shopping & Markets'
                            ] as $tagKey => $tagLabel)
                                <button
                                    type="button"
                                    @click="toggleInterest('{{ $tagKey }}')"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition cursor-pointer"
                                    :class="selectedInterests.includes('{{ $tagKey }}')
                                        ? 'bg-teal-700 text-white border-teal-700 shadow-xs'
                                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                >
                                    <span>{{ $tagLabel }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            @click="savePreferences()"
                            :disabled="savingPreferences"
                            class="w-full inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 transition shadow-sm disabled:opacity-50"
                        >
                            <span x-show="!savingPreferences">{{ __('Save My Preferences') }}</span>
                            <span x-show="savingPreferences">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                <button
                    type="button"
                    @click="open = false"
                    class="px-4 py-2 text-xs font-bold text-gray-700 hover:text-gray-900 rounded-lg hover:bg-gray-200/60 transition"
                >
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
