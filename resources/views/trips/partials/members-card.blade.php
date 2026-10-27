@props([
    'trip',
])

@php
    $memberCount = $trip->members->count();
    $displayedMembers = $trip->members->take(3);
@endphp

<div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm p-5 space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div>
            <h3 class="text-sm font-bold text-gray-900">{{ __('Trip Members') }}</h3>
            <span class="text-xs text-gray-500 font-medium">
                {{ $memberCount }} {{ $memberCount === 1 ? __('member') : __('members') }}
            </span>
        </div>

        <button
            type="button"
            @click="window.dispatchEvent(new CustomEvent('open-member-modal'))"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition"
        >
            + {{ __('Invite / Manage') }}
        </button>
    </div>

    {{-- Members List --}}
    <div class="space-y-3">
        @foreach($displayedMembers as $member)
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/70 border border-gray-100">
                <div class="flex items-center gap-2.5 min-w-0">
                    @if($member->user && $member->user->username)
                        <a href="{{ route('profile.show', $member->user->username) }}" class="shrink-0">
                            <x-profile.avatar :user="$member->user" size="sm" />
                        </a>
                        <div class="min-w-0">
                            <a href="{{ route('profile.show', $member->user->username) }}" class="text-xs font-semibold text-gray-900 hover:text-primary transition-colors truncate block">
                                {{ $member->user->name }}
                                @if($member->user_id === auth()->id())
                                    <span class="text-teal-700 font-normal">({{ __('You') }})</span>
                                @endif
                            </a>
                            <p class="text-[11px] text-gray-500 truncate">@<span>{{ $member->user->username }}</span></p>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-teal-700 text-white font-bold text-xs flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-900 truncate">
                                {{ $member->user->name ?? __('Member') }}
                                @if($member->user_id === auth()->id())
                                    <span class="text-teal-700 font-normal">({{ __('You') }})</span>
                                @endif
                            </p>
                            <p class="text-[11px] text-gray-500 truncate">{{ $member->user->email ?? '' }}</p>
                        </div>
                    @endif
                </div>

                <span class="shrink-0 text-[11px] font-semibold px-2 py-0.5 rounded-md
                    @if($member->role === 'leader') bg-amber-50 text-amber-800 border border-amber-200
                    @elseif($member->role === 'editor') bg-blue-50 text-blue-800 border border-blue-200
                    @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                    {{ ucfirst(__($member->role)) }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- View All Link if more than 3 members --}}
    @if($memberCount > 3)
        <div class="pt-1 text-center">
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('open-member-modal'))"
                class="text-xs font-semibold text-teal-700 hover:text-teal-800 transition"
            >
                {{ __('View all :count members', ['count' => $memberCount]) }} &rarr;
            </button>
        </div>
    @endif
</div>
