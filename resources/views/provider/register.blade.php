@extends('layouts.app')

@section('title', __('Become a Service Provider'))

@section('content')
<div class="max-w-2xl mx-auto py-6">
    <div class="mb-8">
        <a href="{{ route('trips.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-primary transition-colors mb-3">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('Back to Dashboard') }}
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
            {{ __('Become a Verified Service Provider') }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ __('Connect directly with travelers, submit customized proposals to travel requests, and grow your local tourism business on Wanderly.') }}
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form method="POST" action="{{ route('provider.register.store') }}" class="space-y-6">
            @csrf

            {{-- Business Name --}}
            <div>
                <label for="business_name" class="block text-sm font-semibold text-gray-900 mb-1">
                    {{ __('Business / Agency Name') }} <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="business_name"
                    id="business_name"
                    value="{{ old('business_name') }}"
                    required
                    placeholder="{{ __('e.g., Dalat Pine Explorer Travel') }}"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-primary shadow-xs"
                >
                @error('business_name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Service Type & Destination --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="service_type" class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('Primary Service Type') }} <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="service_type"
                        id="service_type"
                        required
                        class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-primary shadow-xs cursor-pointer"
                    >
                        <option value="">{{ __('Select category...') }}</option>
                        <option value="transport" @selected(old('service_type') === 'transport')>{{ __('Transport & Car Rental') }}</option>
                        <option value="tour" @selected(old('service_type') === 'tour')>{{ __('Guided Tours & Experiences') }}</option>
                        <option value="accommodation" @selected(old('service_type') === 'accommodation')>{{ __('Accommodation & Homestay') }}</option>
                        <option value="restaurant" @selected(old('service_type') === 'restaurant')>{{ __('Restaurant & Culinary') }}</option>
                        <option value="photo" @selected(old('service_type') === 'photo')>{{ __('Photography & Media') }}</option>
                        <option value="guide" @selected(old('service_type') === 'guide')>{{ __('Private Tour Guide') }}</option>
                    </select>
                    @error('service_type')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="destination_id" class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('Operating Destination') }} <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="destination_id"
                        id="destination_id"
                        required
                        class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-primary shadow-xs cursor-pointer"
                    >
                        <option value="">{{ __('Select destination...') }}</option>
                        @foreach($destinations as $dest)
                            <option value="{{ $dest->id }}" @selected(old('destination_id') == $dest->id)>
                                {{ $dest->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('destination_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Phone & Address --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('Phone / Hotline') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="phone"
                        id="phone"
                        value="{{ old('phone') }}"
                        required
                        placeholder="{{ __('+84 909 888 777') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-primary shadow-xs"
                    >
                    @error('phone')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="address" class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('Operating Address') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="address"
                        id="address"
                        value="{{ old('address') }}"
                        required
                        placeholder="{{ __('12 Phan Dinh Phung, Da Lat') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-primary shadow-xs"
                    >
                    @error('address')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Bio / Introduction --}}
            <div>
                <label for="bio" class="block text-sm font-semibold text-gray-900 mb-1">
                    {{ __('Business Bio & Introduction') }}
                </label>
                <textarea
                    name="bio"
                    id="bio"
                    rows="3"
                    placeholder="{{ __('Describe your fleet, experience, certifications, and what makes your service special...') }}"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-primary shadow-xs"
                >{{ old('bio') }}</textarea>
                @error('bio')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2 flex items-center justify-end gap-3">
                <a href="{{ route('trips.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    {{ __('Cancel') }}
                </a>
                <button
                    type="submit"
                    class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold shadow-md shadow-primary/20 hover:bg-teal-800 transition-all"
                >
                    {{ __('Complete Registration') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
