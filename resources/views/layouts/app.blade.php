<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wanderly') }} — @yield('title', __('Dashboard'))</title>
    <meta name="description" content="@yield('description', __('Plan your trips with Wanderly — your smart travel planning companion.'))">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Styles & Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex flex-col">

        {{-- ================================================================
             NAVBAR
        ================================================================= --}}
        <nav class="bg-white/80 backdrop-blur-md border-b border-gray-200/80 sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    {{-- Left: Logo + Navigation --}}
                    <div class="flex items-center">
                        {{-- Logo --}}
                        <a href="{{ url('/') }}" class="flex items-center gap-2.5 shrink-0 group">
                            <div class="w-9 h-9 bg-primary rounded-xl flex items-center justify-center shadow-lg shadow-primary/25 group-hover:shadow-primary/40 transition-shadow duration-300">
                                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M2 12h20"></path>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-gray-900 hidden sm:block">Wanderly</span>
                        </a>

                        {{-- Desktop Navigation Links --}}
                        <div class="hidden sm:ml-8 sm:flex sm:items-center sm:space-x-1">
                            <a href="{{ url('/trips') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium {{ request()->is('trips*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                                </svg>
                                {{ __('Dashboard') }}
                            </a>

                            <a href="{{ route('social.feed') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium {{ request()->is('social*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="18" cy="5" r="3"></circle>
                                    <circle cx="6" cy="12" r="3"></circle>
                                    <circle cx="18" cy="19" r="3"></circle>
                                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                                </svg>
                                {{ __('Community Feed') }}
                            </a>

                            <a href="{{ route('discovery.index') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium {{ request()->is('discovery*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                                <span>🇻🇳</span>
                                <span>{{ __('Discover Vietnam') }}</span>
                            </a>

                            @auth
                                @if(auth()->user()->role === 'provider')
                                <a href="{{ route('provider.dashboard') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium {{ request()->is('provider*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                                    <span>🏢</span>
                                    {{ __('Partner Portal') }}
                                </a>
                                @elseif(auth()->user()->role === 'traveler')
                                <a href="{{ route('provider.register') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium {{ request()->is('provider*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                                    <span>🤝</span>
                                    {{ __('Become Partner') }}
                                </a>
                                @endif

                                @if(auth()->user()->role === 'admin')
                                <a href="{{ route('admin.destinations.index') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium {{ request()->is('admin*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                        <path d="M2 17l10 5 10-5"></path>
                                        <path d="M2 12l10 5 10-5"></path>
                                    </svg>
                                    {{ __('Admin') }}
                                </a>
                                @endif
                            @endauth
                        </div>
                    </div>

                    {{-- Right: Actions --}}
                    <div class="flex items-center gap-2">

                        {{-- Notification Bell (D4-03 M15) --}}
                        @auth
                        <x-notification-bell />
                        @endauth

                        {{-- User Dropdown --}}
                        @auth
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <button @click="open = !open"
                                    class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-gray-100 transition-all duration-200"
                                    id="btn-user-menu">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                         alt="{{ auth()->user()->name }}"
                                         class="w-8 h-8 rounded-lg object-cover ring-2 ring-gray-100">
                                @else
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-emerald-600 flex items-center justify-center text-white text-sm font-semibold shadow-sm">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="hidden md:block text-sm font-medium text-gray-700 max-w-[120px] truncate">
                                    {{ auth()->user()->name }}
                                </span>
                                <svg class="w-4 h-4 text-gray-400 hidden md:block transition-transform duration-200"
                                     :class="{ 'rotate-180': open }"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 x-cloak
                                 class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl ring-1 ring-black/5 overflow-hidden"
                                 id="user-dropdown-menu">

                                <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ auth()->user()->email }}</p>
                                </div>

                                <div class="p-1.5">
                                    <a href="#"
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                                        <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        {{ __('My Profile') }}
                                    </a>
                                </div>

                                <div class="p-1.5 border-t border-gray-100">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-600 hover:bg-red-50 transition-colors duration-150">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                                <polyline points="16 17 21 12 16 7"></polyline>
                                                <line x1="21" y1="12" x2="9" y2="12"></line>
                                            </svg>
                                            {{ __('Sign Out') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endauth

                        {{-- Mobile Menu Toggle --}}
                        <button @click="mobileMenuOpen = !mobileMenuOpen"
                                class="sm:hidden p-2.5 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all duration-200"
                                id="btn-mobile-menu">
                            <svg x-show="!mobileMenuOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                            <svg x-show="mobileMenuOpen" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile Menu Panel --}}
            <div x-show="mobileMenuOpen" x-transition x-cloak class="sm:hidden border-t border-gray-100">
                <div class="px-4 py-3 space-y-1">
                    <a href="{{ url('/trips') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->is('trips*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                        </svg>
                        {{ __('Dashboard') }}
                    </a>

                    <a href="{{ route('social.feed') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->is('social*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                        {{ __('Community Feed') }}
                    </a>

                    <a href="{{ route('discovery.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->is('discovery*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                        <span>🇻🇳</span>
                        {{ __('Discover Vietnam') }}
                    </a>

                    @auth
                        @if(auth()->user()->role === 'provider')
                        <a href="{{ route('provider.dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->is('provider*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                            <span>🏢</span>
                            {{ __('Partner Portal') }}
                        </a>
                        @elseif(auth()->user()->role === 'traveler')
                        <a href="{{ route('provider.register') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->is('provider*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                            <span>🤝</span>
                            {{ __('Become Partner') }}
                        </a>
                        @endif

                        @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.destinations.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->is('admin*') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:text-primary hover:bg-primary/5' }} transition-all duration-200">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                            {{ __('Admin') }}
                        </a>
                        @endif
                    @endauth
                </div>
            </div>
        </nav>

        {{-- ================================================================
             TOAST NOTIFICATIONS
        ================================================================= --}}
        <x-toast />

        {{-- ================================================================
             PAGE CONTENT
        ================================================================= --}}
        <main class="flex-1 py-6 lg:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>

        {{-- Global Modal Container --}}
        @stack('modals')

    </div>

    @stack('scripts')
</body>
</html>
