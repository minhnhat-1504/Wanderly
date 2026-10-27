@extends('layouts.guest')

@section('title', __('Create Account'))

@section('content')
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            {{ __('Create your account') }}
        </h1>
        <p class="text-sm text-gray-500 mt-2">
            {{ __('Start planning your next adventure with Wanderly') }}
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        {{-- Full Name --}}
        <x-input
            :label="__('Full Name')"
            name="name"
            type="text"
            :placeholder="__('John Doe')"
            autocomplete="name"
            :required="true"
            autofocus
        />

        {{-- Email Address --}}
        <x-input
            :label="__('Email Address')"
            name="email"
            type="email"
            :placeholder="__('name@example.com')"
            autocomplete="email"
            :required="true"
        />

        {{-- Password --}}
        <div>
            <x-input
                :label="__('Password')"
                name="password"
                type="password"
                :placeholder="__('Min 8 chars, Aa1@...')"
                autocomplete="new-password"
                :required="true"
            />
            <p class="text-[11px] text-gray-500 mt-1">
                {{ __('Must be at least 8 characters and include uppercase, lowercase, numbers, and symbols.') }}
            </p>
        </div>

        {{-- Confirm Password --}}
        <x-input
            :label="__('Confirm Password')"
            name="password_confirmation"
            type="password"
            :placeholder="__('Repeat your password')"
            autocomplete="new-password"
            :required="true"
        />

        {{-- Submit Button --}}
        <div class="pt-2">
            <x-button variant="primary" type="submit" class="w-full justify-center">
                {{ __('Create Account') }}
            </x-button>
        </div>
    </form>

    {{-- Link to Login --}}
    <div class="mt-6 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-teal-700 transition-colors duration-150 ml-1">
            {{ __('Sign in') }}
        </a>
    </div>
@endsection
