<div
    x-data="{
        open: false,
        tripId: {{ $trip->id }},
        activeTab: 'members', // 'members', 'invite', 'preferences'
        inviteRole: 'editor',
        inviteUrl: '',
        copied: false,
        generating: false,
        savingPreferences: false,
        selectedInterests: {{ json_encode(auth()->user()->memberPreferences->where('trip_id', $trip->id)->first()?->interests ?? ['sightseeing', 'chill']) }},
        selectedPace: '{{ auth()->user()->memberPreferences->where('trip_id', $trip->id)->first()?->pace ?? 'balanced' }}',
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
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to remove member');
                }
            } catch (err) {
                console.error('Failed to remove member', err);
            }
        },

        async savePreferences() {
            this.savingPreferences = true;
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
                    this.mood = data.group_mood;
                    this.activeTab = 'members';
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
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
        @click="open = false"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-gray-100"
            @click.stop
        >
            {{-- Modal Header --}}
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                        👥
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" id="member-modal-title">
                            {{ __('Trip Members & Group Mood') }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ __('Collaborate, invite team members, and balance collective preferences.') }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Tab Switcher --}}
            <div class="flex border-b border-gray-100 px-6 bg-gray-50/50">
                <button
                    type="button"
                    @click="activeTab = 'members'"
                    :class="activeTab === 'members' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-4 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2"
                >
                    <span>{{ __('Members & Mood') }}</span>
                    <span class="bg-gray-200/80 text-gray-700 text-[10px] px-1.5 py-0.5 rounded-full">
                        {{ $trip->members->count() }}
                    </span>
                </button>

                @can('update', $trip)
                    <button
                        type="button"
                        @click="activeTab = 'invite'"
                        :class="activeTab === 'invite' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-3 px-4 text-xs font-semibold border-b-2 transition-colors flex items-center gap-1.5"
                    >
                        <span>🔗</span>
                        <span>{{ __('Invite Friends') }}</span>
                    </button>
                @endcan

                <button
                    type="button"
                    @click="activeTab = 'preferences'"
                    :class="activeTab === 'preferences' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-4 text-xs font-semibold border-b-2 transition-colors flex items-center gap-1.5"
                >
                    <span>🎯</span>
                    <span>{{ __('My Preferences') }}</span>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">

                {{-- 1. TAB: MEMBERS & MOOD --}}
                <div x-show="activeTab === 'members'" class="space-y-6">

                    {{-- Group Mood Indicator Widget --}}
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-50/80 via-purple-50/40 to-pink-50/40 border border-indigo-100/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">✨</span>
                                <h4 class="text-sm font-bold text-gray-900">{{ __('Group Harmony & Mood') }}</h4>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold text-gray-500">{{ __('Common Pace:') }}</span>
                                <span
                                    class="px-2 py-0.5 text-xs font-bold rounded-lg uppercase tracking-wider bg-white text-indigo-600 shadow-xs border border-indigo-100"
                                    x-text="mood.common_pace"
                                ></span>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600">{{ __('Compatibility Index') }}</span>
                                <span class="font-bold text-indigo-700" x-text="mood.harmony_score + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200/80 h-2.5 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="mood.harmony_score >= 80 ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : (mood.harmony_score >= 60 ? 'bg-gradient-to-r from-amber-400 to-orange-400' : 'bg-rose-500')"
                                    :style="'width: ' + mood.harmony_score + '%'"
                                ></div>
                            </div>
                        </div>

                        {{-- Top Group Interests --}}
                        <div class="pt-2 flex flex-wrap items-center gap-1.5" x-show="mood.top_interests && mood.top_interests.length > 0">
                            <span class="text-xs font-medium text-gray-500">{{ __('Shared Interests:') }}</span>
                            <template x-for="item in mood.top_interests" :key="item.tag">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-semibold bg-white text-gray-700 shadow-xs border border-gray-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                    <span class="capitalize" x-text="item.tag"></span>
                                    <span class="text-[10px] text-gray-400" x-text="item.percentage + '%'"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Members List --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                            {{ __('Current Collaborators (:count)', ['count' => $trip->members->count()]) }}
                        </h4>

                        <div class="divide-y divide-gray-100 border border-gray-100 rounded-2xl overflow-hidden bg-white shadow-xs">
                            @foreach($trip->members as $member)
                                <div class="p-3.5 flex items-center justify-between hover:bg-gray-50/60 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary/20 to-primary/5 text-primary font-bold flex items-center justify-center shrink-0 shadow-xs">
                                            {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate flex items-center gap-1.5">
                                                <span>{{ $member->user->name ?? __('Member') }}</span>
                                                @if($member->user_id === auth()->id())
                                                    <span class="text-[10px] font-medium bg-primary/10 text-primary px-1.5 py-0.5 rounded-md">
                                                        {{ __('You') }}
                                                    </span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-400 truncate">{{ $member->user->email ?? '' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold
                                            @if($member->role === 'leader') bg-amber-50 text-amber-700 border border-amber-200/80
                                            @elseif($member->role === 'editor') bg-blue-50 text-blue-700 border border-blue-200/80
                                            @else bg-gray-100 text-gray-600 border border-gray-200/80 @endif">
                                            {{ ucfirst(__($member->role)) }}
                                        </span>

                                        @can('delete', $trip)
                                            @if($member->user_id !== auth()->id())
                                                <button
                                                    type="button"
                                                    @click="removeMember({{ $member->user_id }})"
                                                    class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors"
                                                    title="{{ __('Remove Member') }}"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 2. TAB: INVITE FRIENDS --}}
                <div x-show="activeTab === 'invite'" class="space-y-5">
                    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 text-blue-800 text-xs leading-relaxed space-y-1">
                        <p class="font-bold">✨ {{ __('Invite Friends with a Secure Link') }}</p>
                        <p>{{ __('Invited members can join directly using the generated token link. Links are valid for 7 days.') }}</p>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-gray-700">
                            {{ __('Assign Member Permission Role') }}
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                class="p-3.5 rounded-2xl border cursor-pointer transition-all flex flex-col gap-1"
                                :class="inviteRole === 'editor' ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 hover:bg-gray-50 text-gray-700'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm">{{ __('Editor') }}</span>
                                    <input type="radio" value="editor" x-model="inviteRole" class="text-primary focus:ring-primary">
                                </div>
                                <span class="text-[11px] text-gray-500 font-normal">
                                    {{ __('Can add, edit activities, room stays and vote.') }}
                                </span>
                            </label>

                            <label
                                class="p-3.5 rounded-2xl border cursor-pointer transition-all flex flex-col gap-1"
                                :class="inviteRole === 'viewer' ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 hover:bg-gray-50 text-gray-700'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm">{{ __('Viewer') }}</span>
                                    <input type="radio" value="viewer" x-model="inviteRole" class="text-primary focus:ring-primary">
                                </div>
                                <span class="text-[11px] text-gray-500 font-normal">
                                    {{ __('Read-only access and candidate voting.') }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            @click="generateInvite()"
                            :disabled="generating"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-sm font-bold text-white bg-primary hover:bg-primary/90 transition-all shadow-md cursor-pointer disabled:opacity-50"
                        >
                            <span x-show="!generating">🔗 {{ __('Generate 7-Day Invite Link') }}</span>
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
                                class="px-4 py-2.5 rounded-xl text-xs font-bold shrink-0 transition-colors shadow-xs"
                                :class="copied ? 'bg-emerald-600 text-white' : 'bg-gray-900 text-white hover:bg-black'"
                            >
                                <span x-show="!copied">{{ __('Copy') }}</span>
                                <span x-show="copied">✓ {{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 3. TAB: PREFERENCES --}}
                <div x-show="activeTab === 'preferences'" class="space-y-5">
                    <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-100 text-purple-800 text-xs leading-relaxed">
                        {{ __('Your preferences feed directly into the Match Engine algorithm to tailor the itinerary to the entire group.') }}
                    </div>

                    {{-- Travel Pace --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            {{ __('Your Preferred Travel Pace') }}
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([
                                'relaxed' => ['title' => 'Relaxed', 'desc' => '3 spots/day', 'icon' => '☕'],
                                'balanced' => ['title' => 'Balanced', 'desc' => '4 spots/day', 'icon' => '⚖️'],
                                'packed' => ['title' => 'Packed', 'desc' => '5 spots/day', 'icon' => '⚡']
                            ] as $paceKey => $paceData)
                                <label
                                    class="p-3 rounded-2xl border text-center cursor-pointer transition-all flex flex-col items-center gap-1"
                                    :class="selectedPace === '{{ $paceKey }}' ? 'border-primary bg-primary/5 text-primary font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600'"
                                >
                                    <span class="text-xl">{{ $paceData['icon'] }}</span>
                                    <span class="text-xs font-bold">{{ __($paceData['title']) }}</span>
                                    <span class="text-[10px] text-gray-400 font-normal">{{ __($paceData['desc']) }}</span>
                                    <input type="radio" value="{{ $paceKey }}" x-model="selectedPace" class="sr-only">
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Interest Tags --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            {{ __('Your Favorite Categories & Interests') }}
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach([
                                'sightseeing' => '📸 Sightseeing',
                                'foodie' => '🍜 Foodie & Dining',
                                'nature' => '🌲 Nature & Outdoors',
                                'chill' => '☕ Chill & Cafes',
                                'culture' => '🏛️ Culture & Heritage',
                                'nightlife' => '🍸 Nightlife & Bars',
                                'adventure' => '🧗 Adventure & Trekking',
                                'shopping' => '🛍️ Shopping & Markets'
                            ] as $tagKey => $tagLabel)
                                <button
                                    type="button"
                                    @click="toggleInterest('{{ $tagKey }}')"
                                    class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5"
                                    :class="selectedInterests.includes('{{ $tagKey }}')
                                        ? 'bg-primary text-white border-primary shadow-xs'
                                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                >
                                    <span>{{ $tagLabel }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-3">
                        <button
                            type="button"
                            @click="savePreferences()"
                            :disabled="savingPreferences"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-sm font-bold text-white bg-primary hover:bg-primary/90 transition-all shadow-md cursor-pointer disabled:opacity-50"
                        >
                            <span x-show="!savingPreferences">{{ __('Save My Preferences') }}</span>
                            <span x-show="savingPreferences">{{ __('Saving...') }}</span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                <button
                    type="button"
                    @click="open = false"
                    class="px-4 py-2 text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl hover:bg-gray-200/60 transition-colors"
                >
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
