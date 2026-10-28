@extends('layouts.guest')

@section('title', __('Forgot Password'))

@section('content')
    <div class="text-center mb-6">
        <div class="w-12 h-12 mx-auto mb-4 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shadow-inner">
            <x-icon name="key-round" class="w-6 h-6" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            {{ __('Forgot your password?') }}
        </h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            {{ __('No worries! Enter your account email and we will send you a password reset link.') }}
        </p>
    </div>

    @if (session('status'))
        <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <x-icon name="check-circle" class="w-4 h-4 text-emerald-600 shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        {{-- Email Address --}}
        <x-input
            :label="__('Email Address')"
            name="email"
            type="email"
            :placeholder="__('name@example.com')"
            autocomplete="email"
            :value="old('email')"
            :required="true"
            autofocus
        />

        {{-- Submit Button --}}
        <div class="pt-1">
            <x-button variant="primary" type="submit" class="w-full justify-center">
                <x-icon name="send" class="w-4 h-4" />
                <span>{{ __('Send Password Reset Link') }}</span>
            </x-button>
        </div>
    </form>

    {{-- Link to Login --}}
    <div class="mt-6 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
        {{ __('Remember your password?') }}
        <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-teal-700 transition-colors duration-150 ml-1">
            {{ __('Sign in') }}
        </a>
    </div>
@endsection
