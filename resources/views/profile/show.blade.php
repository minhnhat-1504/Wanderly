@extends('layouts.app')

@section('title', $user->name . ' (@' . $user->username . ')')
@section('description', $user->bio ?: __('Explore travel itineraries and adventures by :name on Wanderly.', ['name' => $user->name]))

@push('styles')
    <meta property="og:title" content="{{ $user->name }} — Wanderly Profile">
    <meta property="og:description" content="{{ $user->bio ?: __('Check out :name on Wanderly.', ['name' => $user->name]) }}">
    <meta property="og:image" content="{{ $user->cover_url ?: $user->avatar_url }}">
    <meta property="og:type" content="profile">
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- =====================================================================
         HEADER: COVER & AVATAR & IDENTITY
    ====================================================================== --}}
    <div class="relative bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8"
         x-data="{
             copied: false,
             copyProfileUrl() {
                 navigator.clipboard.writeText('{{ url('/u/' . $user->username) }}');
                 this.copied = true;
                 setTimeout(() => this.copied = false, 2500);
             }
         }">

        {{-- Cover Image with sunset glow and subtle pattern --}}
        <div class="relative h-40 md:h-52 w-full bg-gradient-to-r from-teal-700 via-teal-600 to-teal-500 overflow-hidden">
            {{-- Radial sunset glow at top right --}}
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_15%,rgba(251,191,36,.45),transparent_50%)] pointer-events-none"></div>

            {{-- Subtle SVG contour pattern --}}
            <svg class="absolute inset-0 w-full h-full opacity-10 mix-blend-overlay pointer-events-none" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <defs>
                    <pattern id="contour-pattern" width="60" height="60" patternUnits="userSpaceOnUse">
                        <circle cx="30" cy="30" r="28" fill="none" stroke="#ffffff" stroke-width="1.5" />
                        <circle cx="30" cy="30" r="16" fill="none" stroke="#ffffff" stroke-width="1" />
                        <circle cx="30" cy="30" r="4" fill="#ffffff" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#contour-pattern)" />
            </svg>

            @if($user->cover_url)
                <img src="{{ $user->cover_url }}"
                     alt="{{ $user->name }}"
                     loading="lazy"
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/20 backdrop-blur-[0.5px]"></div>
            @endif

            {{-- Quick Cover Edit Button (Owner only) --}}
            @if($is_owner)
                <a href="{{ route('profile.edit', ['section' => 'profile']) }}"
                   class="absolute top-4 right-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-black/40 hover:bg-black/60 text-white backdrop-blur-md transition-all shadow-sm"
                   title="{{ __('Change Cover Photo') }}">
                    <x-icon name="camera" class="w-3.5 h-3.5 text-white" />
                    <span>{{ __('Edit Cover') }}</span>
                </a>
            @endif
        </div>

        {{-- Profile Main Bar --}}
        <div class="px-6 sm:px-8 pb-6">
            <div class="flex flex-col sm:flex-row items-center sm:items-end justify-between -mt-12 sm:-mt-16 gap-4">
                {{-- Avatar + Identity --}}
                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4 text-center sm:text-left">
                    <div class="relative group">
                        <x-profile.avatar :user="$user" size="xl" class="shadow-xl" />
                        @if($is_owner)
                            <a href="{{ route('profile.edit', ['section' => 'profile']) }}"
                               class="absolute bottom-1 right-1 p-2 rounded-xl bg-teal-800 text-white shadow-md hover:bg-teal-900 transition-transform hover:scale-105"
                               title="{{ __('Update Avatar') }}">
                                <x-icon name="camera" class="w-3.5 h-3.5 text-white" />
                            </a>
                        @endif
                    </div>

                    <div class="mt-2 sm:mt-0">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">{{ $user->name }}</h1>
                            @if($user->role === 'provider')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                    <x-icon name="store" class="w-3.5 h-3.5 text-teal-600" />
                                    <span>{{ __('Provider') }}</span>
                                    @if($user->provider && $user->provider->is_verified)
                                        <x-icon name="badge-check" class="w-3.5 h-3.5 text-teal-600 ml-0.5" title="{{ __('Verified Partner') }}" />
                                    @endif
                                </span>
                            @elseif($user->role === 'admin')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-800 border border-purple-200">
                                    <x-icon name="shield" class="w-3.5 h-3.5 text-purple-600" />
                                    <span>{{ __('Admin') }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-100">
                                    <x-icon name="compass" class="w-3.5 h-3.5 text-teal-600" />
                                    <span>{{ __('Traveler') }}</span>
                                </span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-slate-500 mt-0.5">@<span>{{ $user->username }}</span></p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2.5 shrink-0">
                    {{-- Share profile button --}}
                    <button type="button"
                            @click="copyProfileUrl()"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl text-xs font-semibold text-slate-700 bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300 shadow-sm transition-all duration-200"
                            title="{{ __('Copy profile link') }}">
                        <template x-if="!copied">
                            <span class="inline-flex items-center gap-1.5">
                                <x-icon name="share-2" class="w-4 h-4 text-slate-500" />
                                <span>{{ __('Share') }}</span>
                            </span>
                        </template>
                        <template x-if="copied">
                            <span class="inline-flex items-center gap-1.5 text-teal-700 font-bold">
                                <x-icon name="check" class="w-4 h-4 text-teal-600" />
                                <span>{{ __('Copied!') }}</span>
                            </span>
                        </template>
                    </button>

                    @if($is_owner)
                        <a href="{{ route('profile.edit') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-semibold text-teal-700 bg-white border border-teal-300 hover:bg-teal-50 shadow-sm transition-all duration-200">
                            <x-icon name="pencil" class="w-4 h-4 text-teal-600" />
                            <span>{{ __('Edit Profile') }}</span>
                        </a>
                    @else
                        @auth
                            @if($user->role === 'provider')
                                <a href="{{ route('trips.index') }}"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-semibold text-white bg-primary hover:bg-teal-700 shadow-md shadow-primary/25 transition-all">
                                    <x-icon name="handshake" class="w-4 h-4 text-white" />
                                    <span>{{ __('Request a Quote') }}</span>
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>

            {{-- Bio & Meta Chips --}}
            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 text-sm">
                <div class="max-w-2xl text-slate-600">
                    @if($user->bio)
                        <p class="leading-relaxed">{{ $user->bio }}</p>
                    @elseif($is_owner)
                        <a href="{{ route('profile.edit', ['section' => 'profile']) }}" class="inline-flex items-center gap-1.5 text-xs text-primary font-semibold hover:underline">
                            <x-icon name="pencil" class="w-3.5 h-3.5 text-primary" />
                            <span>{{ __('Add a short bio to introduce yourself') }}</span>
                        </a>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600 shrink-0">
                    @if($user->location)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-50 border border-gray-100 font-medium">
                            <x-icon name="map-pin" class="w-3.5 h-3.5 text-teal-600" />
                            <span>{{ $user->location }}</span>
                        </span>
                    @endif

                    @if($user->website)
                        <a href="{{ $user->website }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-primary font-medium hover:bg-gray-100 transition-colors">
                            <x-icon name="link" class="w-3.5 h-3.5 text-primary" />
                            <span>{{ parse_url($user->website, PHP_URL_HOST) ?: $user->website }}</span>
                        </a>
                    @endif

                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-50 border border-gray-100 font-medium">
                        <x-icon name="calendar" class="w-3.5 h-3.5 text-slate-400" />
                        <span>{{ __('Joined') }} {{ $user->created_at->format('M Y') }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         STATS ROW (CONSOLIDATED 5-COLUMN CARD)
    ====================================================================== --}}
    <x-profile.stats-row :stats="$stats" />

    {{-- =====================================================================
         TWO-COLUMN MAIN BODY (SIDEBAR 1/3 + CONTENT 2/3)
    ====================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8"
         x-data="{
             tab: window.location.hash.replace('#', '') || 'posts',
             setTab(newTab) {
                 this.tab = newTab;
                 window.location.hash = newTab;
             }
         }">

        {{-- LEFT SIDEBAR (1/3) --}}
        <div class="space-y-6">
            {{-- About Card --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <x-icon name="compass" class="w-4 h-4 text-teal-600" />
                    <span>{{ __('About') }}</span>
                </h3>

                <div class="space-y-2.5 text-xs text-slate-600">
                    @if($user->location)
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-50">
                            <span class="text-slate-400 font-medium">{{ __('Location') }}</span>
                            <span class="font-semibold text-gray-900">{{ $user->location }}</span>
                        </div>
                    @endif

                    @if($user->website)
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-50">
                            <span class="text-slate-400 font-medium">{{ __('Website') }}</span>
                            <a href="{{ $user->website }}" target="_blank" class="font-semibold text-primary hover:underline">
                                {{ parse_url($user->website, PHP_URL_HOST) ?: $user->website }}
                            </a>
                        </div>
                    @endif

                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50">
                        <span class="text-slate-400 font-medium">{{ __('Joined') }}</span>
                        <span class="font-semibold text-gray-900">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-slate-400 font-medium">{{ __('Language') }}</span>
                        <span class="font-semibold text-gray-900 uppercase">{{ $user->locale ?: 'en' }}</span>
                    </div>
                </div>

                @if(!empty($travel_style['interests']))
                    <div class="pt-3 border-t border-gray-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">{{ __('Favorite Vibes') }}</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(array_slice($travel_style['interests'], 0, 5) as $vibe)
                                <x-profile.vibe-tag :tag="$vibe" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Travel Badges --}}
            @if($is_owner || !empty($badges))
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <x-icon name="badge-check" class="w-4 h-4 text-teal-600" />
                            <span>{{ __('Travel Badges') }}</span>
                        </h3>
                        <span class="text-xs font-semibold text-slate-500">
                            {{ count(array_filter($badges, fn($b) => $b['earned'] ?? true)) }}/{{ count($badges) }}
                        </span>
                    </div>
                    <div class="space-y-2.5">
                        @foreach($badges as $badge)
                            <x-profile.badge :badge="$badge" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Visited Destinations --}}
            @if(!empty($visited_destinations) || $is_owner)
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                        <x-icon name="map-pin" class="w-4 h-4 text-teal-600" />
                        <span>{{ __('Destinations Visited') }}</span>
                    </h3>
                    @if(!empty($visited_destinations))
                        <div class="flex flex-wrap gap-2">
                            @foreach($visited_destinations as $dest)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-50 border border-gray-100 text-slate-700">
                                    <x-icon name="map-pin" class="w-3.5 h-3.5 text-teal-600" />
                                    <span>{{ $dest }}</span>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 bg-gray-50/50 rounded-2xl p-4 border border-dashed border-gray-200">
                            <p class="text-xs text-slate-500 mb-3">{{ __('You haven\'t pinned any visited destinations yet.') }}</p>
                            <a href="{{ route('trips.create') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                <span>{{ __('Plan your first trip') }}</span> →
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- RIGHT MAIN CONTENT TABS (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Tabs Navigation --}}
            <div class="bg-white rounded-2xl p-1.5 border border-gray-100 shadow-sm flex items-center gap-1 overflow-x-auto scrollbar-none" role="tablist">
                <button type="button"
                        role="tab"
                        id="tab-btn-posts"
                        :aria-selected="tab === 'posts'"
                        @click="setTab('posts')"
                        :class="tab === 'posts' ? 'bg-primary text-white shadow-sm font-bold' : 'text-slate-600 hover:text-gray-900 hover:bg-gray-50 font-medium'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <x-icon name="layout-grid" class="w-4 h-4" />
                    <span>{{ __('Posts') }}</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full" :class="tab === 'posts' ? 'bg-white/20 text-white' : 'bg-gray-100 text-slate-600'">
                        {{ $stats['posts_count'] ?? 0 }}
                    </span>
                </button>

                <button type="button"
                        role="tab"
                        id="tab-btn-trips"
                        :aria-selected="tab === 'trips'"
                        @click="setTab('trips')"
                        :class="tab === 'trips' ? 'bg-primary text-white shadow-sm font-bold' : 'text-slate-600 hover:text-gray-900 hover:bg-gray-50 font-medium'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <x-icon name="map" class="w-4 h-4" />
                    <span>{{ __('Trips') }}</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full" :class="tab === 'trips' ? 'bg-white/20 text-white' : 'bg-gray-100 text-slate-600'">
                        {{ $is_owner ? ($stats['total_trips_count'] ?? 0) : ($stats['public_trips_count'] ?? 0) }}
                    </span>
                </button>

                <button type="button"
                        role="tab"
                        id="tab-btn-travel-style"
                        :aria-selected="tab === 'travel-style'"
                        @click="setTab('travel-style')"
                        :class="tab === 'travel-style' ? 'bg-primary text-white shadow-sm font-bold' : 'text-slate-600 hover:text-gray-900 hover:bg-gray-50 font-medium'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <x-icon name="compass" class="w-4 h-4" />
                    <span>{{ __('Travel Style') }}</span>
                </button>

                @if($is_owner)
                    <button type="button"
                            role="tab"
                            id="tab-btn-saved"
                            :aria-selected="tab === 'saved'"
                            title="{{ __('Only you can see this') }}"
                            @click="setTab('saved')"
                            :class="tab === 'saved' ? 'bg-primary text-white shadow-sm font-bold' : 'text-slate-600 hover:text-gray-900 hover:bg-gray-50 font-medium'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <x-icon name="bookmark" class="w-4 h-4" />
                        <span>{{ __('Saved') }}</span>
                        <span :class="tab === 'saved' ? 'text-white/80' : 'text-slate-400'">
                            <x-icon name="lock" class="w-3 h-3 text-inherit" />
                        </span>
                    </button>
                @endif

                @if($user->role === 'provider' || $user->provider)
                    <button type="button"
                            role="tab"
                            id="tab-btn-services"
                            :aria-selected="tab === 'services'"
                            @click="setTab('services')"
                            :class="tab === 'services' ? 'bg-primary text-white shadow-sm font-bold' : 'text-slate-600 hover:text-gray-900 hover:bg-gray-50 font-medium'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <x-icon name="briefcase" class="w-4 h-4" />
                        <span>{{ __('Services') }}</span>
                    </button>
                @endif
            </div>

            {{-- Tabs Content Panels --}}
            <div>
                {{-- Tab: Posts --}}
                <div x-show="tab === 'posts'" x-cloak role="tabpanel" aria-labelledby="tab-btn-posts">
                    @include('profile.partials.tab-posts', [
                        'posts' => app(\App\Services\ProfileService::class)->getPosts($user),
                        'user' => $user,
                        'isOwner' => $is_owner,
                    ])
                </div>

                {{-- Tab: Trips --}}
                <div x-show="tab === 'trips'" x-cloak role="tabpanel" aria-labelledby="tab-btn-trips">
                    @include('profile.partials.tab-trips', [
                        'trips' => app(\App\Services\ProfileService::class)->getTrips($user, auth()->user()),
                        'user' => $user,
                        'isOwner' => $is_owner,
                    ])
                </div>

                {{-- Tab: Travel Style --}}
                <div x-show="tab === 'travel-style'" x-cloak role="tabpanel" aria-labelledby="tab-btn-travel-style">
                    @include('profile.partials.tab-travel-style', [
                        'travelStyle' => $travel_style,
                        'user' => $user,
                        'isOwner' => $is_owner,
                    ])
                </div>

                {{-- Tab: Saved (Owner Only) --}}
                @if($is_owner)
                    <div x-show="tab === 'saved'" x-cloak role="tabpanel" aria-labelledby="tab-btn-saved">
                        @include('profile.partials.tab-saved', [
                            'savedPosts' => app(\App\Services\ProfileService::class)->getSavedPosts($user, auth()->user()),
                            'user' => $user,
                        ])
                    </div>
                @endif

                {{-- Tab: Services (Provider Only) --}}
                @if($user->role === 'provider' || $user->provider)
                    <div x-show="tab === 'services'" x-cloak role="tabpanel" aria-labelledby="tab-btn-services">
                        @include('profile.partials.tab-services', [
                            'providerProfile' => $provider_profile,
                            'user' => $user,
                            'isOwner' => $is_owner,
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
