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
        @include('layouts.navigation')


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

        {{-- Global AI Copilot / Travel Assistant (Trip-scoped on trip view, Global elsewhere) --}}
        @auth
            <x-ai-copilot-drawer :trip="$trip ?? null" />
        @endauth

    </div>

    @stack('scripts')
</body>
</html>
