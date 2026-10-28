@extends('layouts.guest')

@section('title', __('Sign In'))

@section('content')
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            {{ __('Sign in to your account') }}
        </h1>
        <p class="text-sm text-gray-500 mt-2">
            {{ __('Welcome back! Please enter your details.') }}
        </p>
    </div>

    @if (session('status'))
        <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <x-icon name="check-circle" class="w-4 h-4 text-emerald-600 shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email Address --}}
        <x-input
            :label="__('Email Address')"
            name="email"
            type="email"
            :placeholder="__('name@example.com')"
            autocomplete="email"
            :required="true"
            autofocus
        />

        {{-- Password --}}
        <x-input
            :label="__('Password')"
            name="password"
            type="password"
            :placeholder="__('Enter your password')"
            autocomplete="current-password"
            :required="true"
        />

        {{-- Remember Me & Forgot Password --}}
        <div class="flex items-center justify-between text-sm">
            <label for="remember" class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input
                    type="checkbox"
                    name="remember"
                    id="remember"
                    class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20 transition duration-150 cursor-pointer"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <span class="text-gray-600 text-sm">{{ __('Remember me') }}</span>
            </label>

            <a href="{{ route('password.request') }}" class="font-medium text-sm text-primary hover:text-teal-700 transition-colors">
                {{ __('Forgot password?') }}
            </a>
        </div>

        {{-- Submit Button --}}
        <div class="pt-1">
            <x-button variant="primary" type="submit" class="w-full justify-center">
                {{ __('Sign In') }}
            </x-button>
        </div>
    </form>

    {{-- Link to Register --}}
    <div class="mt-6 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="font-semibold text-primary hover:text-teal-700 transition-colors duration-150 ml-1">
            {{ __('Sign up') }}
        </a>
    </div>
@endsection
