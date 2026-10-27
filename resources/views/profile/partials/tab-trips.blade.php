@props([
    'trips',
    'user',
    'isOwner' => false,
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($trips as $trip)
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 flex flex-col">
            {{-- Cover Image --}}
            <div class="relative h-44 bg-gradient-to-r from-teal-700 via-teal-600 to-teal-500 overflow-hidden">
                @if($trip->cover)
                    <img src="{{ asset('storage/' . $trip->cover) }}"
                         alt="{{ $trip->name }}"
                         loading="lazy"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-white/50">
                        <x-icon name="map" class="w-12 h-12 text-white/40" />
                    </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/15 to-transparent"></div>

                {{-- Status / Privacy badge --}}
                <div class="absolute top-3 left-3 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $trip->is_shared ? 'bg-emerald-500/90 text-white' : 'bg-gray-900/70 text-gray-200' }} backdrop-blur-sm">
                        <x-icon :name="$trip->is_shared ? 'globe' : 'lock'" class="w-3 h-3" />
                        <span>{{ $trip->is_shared ? __('Public') : __('Private') }}</span>
                    </span>
                </div>

                {{-- Destination --}}
                <div class="absolute bottom-3 left-3 right-3 text-white">
                    <h3 class="font-bold text-lg leading-snug drop-shadow-sm truncate">{{ $trip->name }}</h3>
                    <p class="text-xs text-white/80 flex items-center gap-1.5 mt-0.5">
                        <x-icon name="map-pin" class="w-3.5 h-3.5 shrink-0 text-white/90" />
                        <span>{{ $trip->destination ?: __('Multiple Destinations') }}</span>
                    </p>
                </div>
            </div>

            {{-- Details & Actions --}}
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                        <span class="flex items-center gap-1.5">
                            <x-icon name="calendar" class="w-3.5 h-3.5 text-slate-400" />
                            <span>
                                {{ $trip->start_date ? $trip->start_date->format('M d, Y') : __('TBD') }}
                                @if($trip->end_date)
                                    - {{ $trip->end_date->format('M d, Y') }}
                                @endif
                            </span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <x-icon name="users" class="w-3.5 h-3.5 text-slate-400" />
                            <span>{{ $trip->members_count ?? $trip->members->count() }} {{ __('members') }}</span>
                        </span>
                    </div>

                    {{-- Budget (Shown only if allowed or if owner) --}}
                    @if($isOwner || ($user->show_budget_publicly && $trip->is_share_budget_visible))
                        <div class="flex items-center justify-between text-xs bg-teal-50/60 border border-teal-100/60 px-3.5 py-2 rounded-xl">
                            <span class="text-slate-600 flex items-center gap-1.5 font-medium">
                                <x-icon name="wallet" class="w-3.5 h-3.5 text-teal-600" />
                                <span>{{ __('Budget') }}:</span>
                            </span>
                            <span class="font-bold text-teal-800">{{ number_format($trip->budget) }} {{ $user->currency ?: 'VND' }}</span>
                        </div>
                    @endif
                </div>

                <div class="pt-4 mt-4 border-t border-gray-100 flex items-center justify-between gap-3">
                    @if($isOwner)
                        <a href="{{ route('trips.show', $trip) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-teal-800 bg-teal-50 hover:bg-teal-100 transition-colors">
                            <span>{{ __('Open Trip') }}</span>
                            <x-icon name="share-2" class="w-3 h-3 text-teal-700" />
                        </a>
                    @elseif($trip->share_slug)
                        <a href="{{ route('trips.share.show', $trip->share_slug) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors">
                            <x-icon name="map" class="w-3.5 h-3.5 text-gray-500" />
                            <span>{{ __('View Itinerary') }}</span>
                        </a>
                    @endif

                    @auth
                        @if(! $isOwner)
                            <form action="{{ route('trips.duplicate', $trip) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-teal-700 bg-teal-50 hover:bg-teal-100 transition-colors">
                                    <x-icon name="copy" class="w-3.5 h-3.5 text-teal-600" />
                                    <span>{{ __('Clone Trip') }}</span>
                                </button>
                            </form>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            <x-profile.empty-state
                :title="$isOwner ? __('You haven\'t created any trips yet') : __('No public trips available')"
                :description="$isOwner ? __('Build your first collaborative itinerary, drag & drop activities, and invite friends.') : __('No public travel plans have been shared by this explorer yet.')"
                icon="map"
            >
                @if($isOwner)
                    <x-slot:cta>
                        <a href="{{ route('trips.create') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-semibold text-white bg-primary hover:bg-teal-700 shadow-md shadow-primary/20 transition-all">
                            <x-icon name="compass" class="w-4 h-4 text-white" />
                            <span>{{ __('Plan a trip') }}</span>
                        </a>
                    </x-slot:cta>
                @endif
            </x-profile.empty-state>
        </div>
    @endforelse

    @if($trips->hasPages())
        <div class="col-span-full pt-4">
            {{ $trips->links() }}
        </div>
    @endif
</div>
