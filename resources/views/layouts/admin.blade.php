<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wanderly') }} {{ __('Admin') }} — @yield('title', __('Dashboard'))</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Styles & Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    <div class="h-screen flex overflow-hidden">

        {{-- Mobile Overlay Backdrop --}}
        <div x-show="sidebarOpen"
             x-cloak
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden transition-opacity"></div>

        {{-- ================================================================
             SIDEBAR
        ================================================================= --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed lg:static inset-y-0 left-0 z-40 w-64 h-full bg-slate-900 text-slate-200 flex flex-col shrink-0 transition-transform duration-200 ease-in-out border-r border-slate-800 shadow-xl lg:shadow-none">

            {{-- Sidebar Brand --}}
            <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800 bg-slate-950/40">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 bg-teal-600 rounded-xl flex items-center justify-center text-white font-bold shadow-md shadow-teal-900/40 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M2 12h20"></path>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-base font-bold text-white tracking-tight flex items-center gap-1.5">
                            Wanderly
                            <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-teal-500/20 text-teal-400 border border-teal-500/30">
                                Admin
                            </span>
                        </span>
                    </div>
                </a>

                {{-- Close button for mobile --}}
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Sidebar Navigation --}}
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <div class="px-3 pb-2 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                    {{ __('Core Management') }}
                </div>

                {{-- Dashboard --}}
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-teal-600 text-white shadow-md shadow-teal-900/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                    </svg>
                    <span>{{ __('Dashboard') }}</span>
                </a>

                {{-- Users --}}
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-900/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>{{ __('User Management') }}</span>
                </a>

                {{-- Moderation --}}
                <a href="{{ route('admin.moderation.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.moderation.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-900/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>{{ __('Moderation') }}</span>
                </a>

                {{-- Notifications / Broadcasts --}}
                <a href="{{ route('admin.broadcasts.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.broadcasts.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-900/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                    <span>{{ __('Broadcasts') }}</span>
                </a>

                {{-- Backups --}}
                <a href="{{ route('admin.backups.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.backups.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-900/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    <span>{{ __('Backups') }}</span>
                </a>

                {{-- Audit Log --}}
                <a href="{{ route('admin.audit-logs.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.audit-logs.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-900/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>{{ __('Audit Logs') }}</span>
                </a>
            </div>

            {{-- Sidebar Footer --}}
            <div class="p-3 border-t border-slate-800 bg-slate-950/40 space-y-2.5">
                <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-slate-800/60 border border-slate-700/60">
                    <div class="w-8 h-8 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-sm">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="truncate flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-100 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-teal-400 font-mono">{{ __('Administrator') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit"
                            id="btn-admin-logout-sidebar"
                            class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-slate-800 hover:bg-rose-600/20 text-slate-300 hover:text-rose-400 border border-slate-700/60 hover:border-rose-500/30 text-xs font-semibold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>{{ __('Log Out') }}</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- ================================================================
             MAIN CONTENT WRAPPER
        ================================================================= --}}
        <div class="flex-1 flex flex-col h-full min-w-0 overflow-hidden">

            {{-- Topbar --}}
            <header class="h-16 shrink-0 bg-white border-b border-slate-200 px-4 sm:px-6 lg:px-8 flex items-center justify-between z-30 shadow-sm">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden text-slate-500 hover:text-slate-900 p-2 rounded-xl border border-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">
                        @yield('header', __('Administration Console'))
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <x-notification-bell />

                    <div class="hidden sm:flex items-center gap-2 pl-3 border-l border-slate-200 text-xs text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ __('System Healthy') }}</span>
                    </div>

                    {{-- Quick Logout in Header --}}
                    <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                        @csrf
                        <button type="submit"
                                id="btn-admin-logout-topbar"
                                class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-700 border border-slate-200 hover:border-rose-200 text-xs font-semibold transition-all"
                                title="{{ __('Log out of administrator session') }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>{{ __('Log Out') }}</span>
                        </button>
                    </form>
                </div>
            </header>

            {{-- Main Body --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
                <div class="max-w-7xl w-full mx-auto space-y-6">
                    {{-- Flash Messages --}}
                    @if(session('success'))
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-sm font-medium flex items-center gap-2.5 shadow-sm">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-sm font-medium flex items-center gap-2.5 shadow-sm">
                            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
