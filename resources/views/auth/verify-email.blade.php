@extends('layouts.guest')

@section('title', __('Verify Your Email'))

@section('content')
<div class="space-y-6">
    <div class="text-center">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shadow-inner">
            <x-icon name="mail-check" class="w-7 h-7" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            {{ __('Verify your email address') }}
        </h1>
        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
            {{ __('Thanks for signing up for Wanderly! Before getting started, could you verify your email address by clicking on the link we just emailed to you?') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <x-icon name="check-circle" class="w-4 h-4 text-emerald-600 shrink-0" />
            <span>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</span>
        </div>
    @endif

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button type="submit" variant="primary" class="w-full justify-center">
                <x-icon name="send" class="w-4 h-4" />
                <span>{{ __('Resend Verification Email') }}</span>
            </x-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-center text-xs font-semibold text-gray-500 hover:text-gray-800 py-2 transition-colors">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</div>
@endsection
