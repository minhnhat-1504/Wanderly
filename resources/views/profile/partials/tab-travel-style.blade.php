@props([
    'travelStyle',
    'user',
    'isOwner' => false,
])

@php
    $hasPreferences = $travelStyle['has_preferences'] ?? false;
    $pace = $travelStyle['pace'] ?? null;
    $budget = $travelStyle['budget'] ?? 'moderate';
    $interests = $travelStyle['interests'] ?? [];
@endphp

@if(! $hasPreferences)
    <x-profile.empty-state
        :title="$isOwner ? __('Set your travel style') : __('No travel preferences shared')"
        :description="$isOwner ? __('Tell your travel partners how fast you like to explore, your comfort budget, and your favorite vibes.') : __('This explorer has not configured their public travel style preferences yet.')"
        icon="compass"
    >
        @if($isOwner)
            <x-slot:cta>
                <a href="{{ route('profile.edit', ['section' => 'preferences']) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-semibold text-white bg-primary hover:bg-teal-700 shadow-md shadow-primary/20 transition-all">
                    <x-icon name="pencil" class="w-4 h-4 text-white" />
                    <span>{{ __('Set your travel style') }}</span>
                </a>
            </x-slot:cta>
        @endif
    </x-profile.empty-state>
@else
    <div class="bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-sm space-y-8">
        {{-- Travel Pace: 3-notch track with centered thumb --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-bold text-gray-900">{{ __('Travel Pace') }}</span>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-teal-50 text-teal-800 capitalize">
                    {{ $pace ? ucfirst($pace) : __('Balanced') }}
                </span>
            </div>

            @php
                $leftPercent = match($pace) {
                    'relaxed' => '0%',
                    'packed', 'fast' => '100%',
                    default => '50%',
                };
            @endphp

            <div class="relative py-4">
                {{-- Track line --}}
                <div class="w-full bg-slate-200 h-2 rounded-full relative">
                    {{-- Active line --}}
                    <div class="absolute left-0 top-0 h-full bg-primary rounded-full transition-all duration-300" style="width: {{ $leftPercent }};"></div>
                </div>

                {{-- 3 Tick marks --}}
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border-2 border-white {{ $pace === 'relaxed' ? 'bg-primary shadow-sm' : 'bg-slate-300' }}"></div>
                <div class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border-2 border-white {{ ($pace === 'balanced' || !$pace) ? 'bg-primary shadow-sm ring-2 ring-primary/20' : 'bg-slate-300' }}"></div>
                <div class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border-2 border-white {{ in_array($pace, ['packed', 'fast']) ? 'bg-primary shadow-sm' : 'bg-slate-300' }}"></div>
            </div>

            <div class="flex justify-between text-xs text-slate-500 font-medium">
                <span class="{{ $pace === 'relaxed' ? 'font-bold text-teal-800' : 'text-slate-500' }}">
                    {{ __('Relaxed (1-2 POIs/day)') }}
                </span>
                <span class="{{ ($pace === 'balanced' || !$pace) ? 'font-bold text-teal-800' : 'text-slate-500' }}">
                    {{ __('Balanced (3-4 POIs/day)') }}
                </span>
                <span class="{{ in_array($pace, ['packed', 'fast']) ? 'font-bold text-teal-800' : 'text-slate-500' }}">
                    {{ __('Action-Packed (5+ POIs/day)') }}
                </span>
            </div>
        </div>

        {{-- Budget Comfort Segmented Display --}}
        <div>
            <span class="text-sm font-bold text-gray-900 block mb-3">{{ __('Budget Comfort Level') }}</span>
            <div class="grid grid-cols-3 gap-3 p-1.5 bg-gray-50 rounded-2xl border border-gray-100 max-w-md">
                @foreach(['budget' => ['$ ' . __('Budget'), 'wallet'], 'moderate' => ['$$ ' . __('Moderate'), 'wallet'], 'luxury' => ['$$$ ' . __('Luxury'), 'wallet']] as $tierKey => [$tierName, $tierIcon])
                    @php
                        $isSelected = ($budget === $tierKey);
                    @endphp
                    <div class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-semibold transition-all {{ $isSelected ? 'bg-white text-teal-800 shadow-sm border border-gray-100' : 'text-slate-400' }}">
                        <x-icon :name="$tierIcon" class="w-3.5 h-3.5 {{ $isSelected ? 'text-teal-600' : 'text-slate-400' }}" />
                        <span>{{ $tierName }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Vibe & Interests Tags --}}
        <div>
            <span class="text-sm font-bold text-gray-900 block mb-3">{{ __('Favorite Travel Vibes & Interests') }}</span>
            <div class="flex flex-wrap gap-2">
                @forelse($interests as $tag)
                    <x-profile.vibe-tag :tag="$tag" />
                @empty
                    <p class="text-xs text-slate-400">{{ __('No specific vibes selected yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endif
