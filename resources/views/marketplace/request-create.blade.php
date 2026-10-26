@extends('layouts.app')

@section('title', __('Post Travel Request — :trip', ['trip' => $trip->name]))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
    needs: ['transport', 'tour'],
    pace: 'balanced',
    toggleNeed(need) {
        if (this.needs.includes(need)) {
            this.needs = this.needs.filter(n => n !== need);
        } else {
            this.needs.push(need);
        }
    }
}">
    {{-- Header Breadcrumb --}}
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('trips.show', $trip) }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('Back to :trip', ['trip' => $trip->name]) }}
        </a>

        <a href="{{ route('trips.marketplace.index', $trip) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            {{ __('View Existing Requests') }}
        </a>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Card Header Banner --}}
        <div class="bg-gradient-to-r from-primary/10 via-primary/5 to-transparent p-6 sm:p-8 border-b border-gray-100">
            <div class="flex items-center gap-3 mb-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-primary text-white">
                    {{ __('Reverse Marketplace') }}
                </span>
                <span class="text-xs text-gray-500 font-medium">
                    {{ $trip->destination }} • {{ $trip->start_date?->format('d/m/Y') }} – {{ $trip->end_date?->format('d/m/Y') }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                {{ __('Post a Travel Request') }}
            </h1>
            <p class="text-sm text-gray-600 mt-1.5 max-w-2xl">
                {{ __('Publish your travel requirements to verified local providers in :destination. Receive customized proposals with transparent pricing, then compare and book with a single click.', ['destination' => $trip->destination]) }}
            </p>
        </div>

        {{-- Form Body --}}
        <form action="{{ route('trips.marketplace.requests.store', $trip) }}" method="POST" class="p-6 sm:p-8 space-y-8">
            @csrf

            {{-- 1. Services Needed --}}
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-1">
                    {{ __('1. What services do you need?') }} <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-4">
                    {{ __('Select all services you would like local partners to quote for this trip.') }}
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @php
                        $serviceOptions = [
                            'transport' => [
                                'label' => __('Transport & Car Rental'),
                                'desc' => __('Private car, motorbike, shuttle'),
                                'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                            ],
                            'tour' => [
                                'label' => __('Tours & Sightseeing'),
                                'desc' => __('Guided day tours, trekking'),
                                'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
                            ],
                            'accommodation' => [
                                'label' => __('Accommodation'),
                                'desc' => __('Hotels, homestays, resorts'),
                                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                            ],
                            'restaurant' => [
                                'label' => __('Dining & Food Tour'),
                                'desc' => __('Local cuisine, reserved meals'),
                                'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                            ],
                            'photo' => [
                                'label' => __('Photo & Media'),
                                'desc' => __('Photographer, video recording'),
                                'icon' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',
                            ],
                            'guide' => [
                                'label' => __('Private Local Guide'),
                                'desc' => __('Dedicated local expert'),
                                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                            ],
                        ];
                    @endphp

                    @foreach ($serviceOptions as $key => $svc)
                        <div @click="toggleNeed('{{ $key }}')"
                             :class="needs.includes('{{ $key }}') ? 'border-primary bg-primary/5 text-primary ring-2 ring-primary/20' : 'border-gray-200 hover:border-gray-300 bg-white text-gray-700'"
                             class="cursor-pointer border rounded-xl p-4 transition-all duration-150 flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="needs.includes('{{ $key }}') ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $svc['icon'] }}" />
                                    </svg>
                                </div>
                                <span x-show="needs.includes('{{ $key }}')" class="text-primary font-bold text-xs">✓</span>
                            </div>
                            <div class="mt-3">
                                <h3 class="text-sm font-semibold">{{ $svc['label'] }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5 leading-snug">{{ $svc['desc'] }}</p>
                            </div>
                            <input type="checkbox" name="needs[]" value="{{ $key }}" class="hidden" :checked="needs.includes('{{ $key }}')">
                        </div>
                    @endforeach
                </div>
                @error('needs')
                    <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- 2. Travel Pace & Budget Cap --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                {{-- Pace Selection --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('2. Preferred Travel Pace') }}
                    </label>
                    <p class="text-xs text-gray-500 mb-3">
                        {{ __('Helps providers calibrate the schedule density of their proposals.') }}
                    </p>

                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['relaxed' => __('Relaxed'), 'balanced' => __('Balanced'), 'packed' => __('Packed')] as $paceVal => $paceLabel)
                            <label class="cursor-pointer">
                                <input type="radio" name="pace" value="{{ $paceVal }}" class="sr-only" x-model="pace">
                                <div :class="pace === '{{ $paceVal }}' ? 'border-primary bg-primary/5 text-primary font-semibold ring-1 ring-primary' : 'border-gray-200 text-gray-600'"
                                     class="text-center py-2.5 px-3 border rounded-xl text-xs transition-all">
                                    {{ $paceLabel }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Max Budget Target --}}
                <div>
                    <label for="max_budget" class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('3. Target Budget (VND)') }}
                    </label>
                    <p class="text-xs text-gray-500 mb-3">
                        {{ __('Maximum expected expenditure for requested services.') }}
                    </p>

                    <div class="relative rounded-xl shadow-sm">
                        <input type="number" name="max_budget" id="max_budget" min="0" step="10000"
                               value="{{ old('max_budget', (float) $trip->budget > 0 ? (float) $trip->budget : 5000000) }}"
                               class="block w-full rounded-xl border-gray-300 pl-4 pr-16 py-2.5 text-sm focus:border-primary focus:ring-primary">
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                            <span class="text-xs font-semibold text-gray-500">VND</span>
                        </div>
                    </div>
                    @error('max_budget')
                        <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 3. Detailed Requirements / Notes --}}
            <div class="pt-4 border-t border-gray-100">
                <label for="notes" class="block text-sm font-semibold text-gray-900 mb-1">
                    {{ __('4. Additional Requirements & Notes') }}
                </label>
                <p class="text-xs text-gray-500 mb-2">
                    {{ __('Specify details like passenger count, pick-up location, dietary preferences, or specific places to visit.') }}
                </p>
                <textarea name="notes" id="notes" rows="4"
                          placeholder="{{ __('e.g., We are a family of 4 looking for a 7-seater car with a child seat and a morning tour to Tuyen Lam lake...') }}"
                          class="block w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary shadow-sm">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Action Bar --}}
            <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                <a href="{{ route('trips.show', $trip) }}" class="text-sm font-medium text-gray-500 hover:text-gray-800">
                    {{ __('Cancel') }}
                </a>

                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-semibold text-sm shadow-lg shadow-primary/25 hover:shadow-primary/40 hover:bg-primary/95 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    {{ __('Broadcast Request to Providers') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
