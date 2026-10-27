@extends('layouts.app')

@section('title', __('Join :name', ['name' => $trip->name]))

@section('content')
<div class="max-w-xl mx-auto py-10 px-4 sm:px-6">
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden" x-data="{
        selectedPace: 'balanced',
        selectedInterests: ['sightseeing', 'chill'],
        toggleInterest(tag) {
            const idx = this.selectedInterests.indexOf(tag);
            if (idx > -1) {
                this.selectedInterests.splice(idx, 1);
            } else {
                this.selectedInterests.push(tag);
            }
        }
    }">
        {{-- Cover or Banner --}}
        <div class="h-36 bg-gradient-to-r from-teal-800 via-teal-700 to-emerald-800 p-6 flex flex-col justify-end text-white relative">
            <span class="absolute top-4 right-4 bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold">
                {{ __('Team Invitation') }}
            </span>
            <p class="text-xs font-medium text-white/80 uppercase tracking-wider">{{ $trip->destination }}</p>
            <h1 class="text-2xl font-black truncate">{{ $trip->name }}</h1>
        </div>

        {{-- Content --}}
        <div class="p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between text-xs text-gray-500 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-gray-700">{{ __('Dates:') }}</span>
                    <span>
                        {{ $trip->start_date ? $trip->start_date->format('M d, Y') : '' }}
                        @if($trip->end_date) - {{ $trip->end_date->format('M d, Y') }} @endif
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-gray-700">{{ __('Host:') }}</span>
                    <span>{{ $trip->owner->name ?? 'Leader' }}</span>
                </div>
            </div>

            {{-- Role info banner --}}
            @php
                $assignedRole = $invitation->role ?? 'viewer';
            @endphp
            <div class="p-4 rounded-2xl border text-xs sm:text-sm {{ $assignedRole === 'viewer' ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-teal-50 border-teal-200 text-teal-900' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="font-bold uppercase tracking-wider text-[11px]">{{ __('Assigned Role') }}</span>
                    <span class="font-bold px-2.5 py-0.5 rounded-md text-xs {{ $assignedRole === 'viewer' ? 'bg-amber-200 text-amber-900' : 'bg-teal-200 text-teal-900' }}">
                        {{ ucfirst($assignedRole) }}
                    </span>
                </div>
                @if($assignedRole === 'viewer')
                    <p class="text-xs text-amber-800 leading-relaxed">
                        {{ __('You have been invited as a Viewer (Read-only). You can view the itinerary, destinations, and members, but cannot add, edit, or delete activities.') }}
                    </p>
                @else
                    <p class="text-xs text-teal-800 leading-relaxed">
                        {{ __('You have been invited as an Editor. You can collaborate on the itinerary, suggest venues, and contribute to the trip plan.') }}
                    </p>
                @endif
            </div>

            @guest
                <div class="text-center py-6 space-y-4">
                    <p class="text-sm text-gray-600">
                        {{ __('You have been invited to collaborate on this trip! Please log in or create an account to accept the invitation.') }}
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-teal-700 text-white hover:bg-teal-800 transition-all shadow-md">
                            {{ __('Log In to Join') }}
                        </a>
                        <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
                            {{ __('Register New Account') }}
                        </a>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('trips.join.process', $token) }}" class="space-y-6">
                    @csrf

                    {{-- Travel Pace Preference --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            {{ __('1. Choose Your Preferred Travel Pace') }}
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([
                                'relaxed' => ['title' => 'Relaxed', 'desc' => '3 spots/day'],
                                'balanced' => ['title' => 'Balanced', 'desc' => '4 spots/day'],
                                'packed' => ['title' => 'Packed', 'desc' => '5 spots/day']
                            ] as $paceKey => $paceData)
                                <label
                                    class="p-3.5 rounded-2xl border text-center cursor-pointer transition-all flex flex-col items-center gap-1 select-none"
                                    :class="selectedPace === '{{ $paceKey }}' ? 'border-teal-700 bg-teal-50/50 text-teal-900 font-bold shadow-xs' : 'border-gray-200 hover:bg-gray-50 text-gray-600'"
                                >
                                    <span class="text-xs font-bold">{{ __($paceData['title']) }}</span>
                                    <span class="text-[10px] text-gray-500 font-normal">{{ __($paceData['desc']) }}</span>
                                    <input type="radio" name="pace" value="{{ $paceKey }}" x-model="selectedPace" class="sr-only">
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Interests Selection --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            {{ __('2. Select Your Top Interests') }}
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
                                    class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5 select-none"
                                    :class="selectedInterests.includes('{{ $tagKey }}')
                                        ? 'bg-teal-700 text-white border-teal-700 shadow-xs'
                                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                >
                                    <span>{{ __($tagLabel) }}</span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Hidden inputs for form submit --}}
                        <template x-for="tag in selectedInterests" :key="tag">
                            <input type="hidden" name="interests[]" :value="tag">
                        </template>
                    </div>

                    <div class="pt-4">
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 transition-all shadow-md cursor-pointer"
                        >
                            <span>{{ __('Accept Invitation & Join Trip') }}</span>
                        </button>
                    </div>
                </form>
            @endguest
        </div>
    </div>
</div>
@endsection
