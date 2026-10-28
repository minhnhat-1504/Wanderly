@extends('layouts.guest')

@section('title', __('Reset Password'))

@section('content')
    <div class="text-center mb-6">
        <div class="w-12 h-12 mx-auto mb-4 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shadow-inner">
            <x-icon name="shield-check" class="w-6 h-6" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            {{ __('Set new password') }}
        </h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            {{ __('Please choose a strong new password for your account.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        {{-- Password Reset Token --}}
        <input type="hidden" name="token" value="{{ $token }}">

        {{-- Email Address --}}
        <x-input
            :label="__('Email Address')"
            name="email"
            type="email"
            :placeholder="__('name@example.com')"
            autocomplete="email"
            :value="old('email', $email)"
            :required="true"
            autofocus
        />

        {{-- New Password --}}
        <x-input
            :label="__('New Password')"
            name="password"
            type="password"
            :placeholder="__('At least 8 characters')"
            autocomplete="new-password"
            :required="true"
        />

        {{-- Confirm Password --}}
        <x-input
            :label="__('Confirm Password')"
            name="password_confirmation"
            type="password"
            :placeholder="__('Repeat your new password')"
            autocomplete="new-password"
            :required="true"
        />

        {{-- Submit Button --}}
        <div class="pt-1">
            <x-button variant="primary" type="submit" class="w-full justify-center">
                <x-icon name="check" class="w-4 h-4" />
                <span>{{ __('Reset Password') }}</span>
            </x-button>
        </div>
    </form>
@endsection
