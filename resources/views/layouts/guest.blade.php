<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wanderly') }} — @yield('title', __('Welcome'))</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Styles & Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-900 relative overflow-hidden py-8 px-4">

        {{-- Decorative Background Shapes --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] bg-emerald-500/10 rounded-full blur-3xl"></div>
            <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-teal-400/5 rounded-full blur-2xl"></div>
            <div class="absolute bottom-1/4 left-1/3 w-48 h-48 bg-white/[0.03] rounded-full blur-2xl"></div>
        </div>

        <div class="relative z-10 w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/20 shadow-lg group-hover:bg-white/15 transition-colors duration-300">
                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M2 12h20"></path>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">Wanderly</span>
                </a>
                <p class="mt-3 text-teal-200/70 text-sm">{{ __('Your journey begins here') }}</p>
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-2xl shadow-black/20 p-8">
                @yield('content')
            </div>

            {{-- Footer --}}
            <p class="text-center mt-6 text-sm text-teal-300/50">
                &copy; {{ date('Y') }} Wanderly. {{ __('All rights reserved.') }}
            </p>
        </div>
    </div>
</body>
</html>
