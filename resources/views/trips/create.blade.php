@extends('layouts.app')

@section('title', __('Create New Trip'))
@section('description', __('Plan a new adventure and invite your friends.'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumbs --}}
    <nav class="flex items-center text-sm font-medium text-gray-500 space-x-2">
        <a href="{{ route('trips.index') }}" class="hover:text-primary transition-colors duration-200">
            {{ __('Dashboard') }}
        </a>
        <svg class="w-4 h-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="text-gray-900 font-semibold">{{ __('Create New Trip') }}</span>
    </nav>

    {{-- Header --}}
    <div class="pb-4 border-b border-gray-200">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
            {{ __('Plan a New Journey') }} ✈️
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ __('Set up your trip details, define your itinerary dates, and set your budget.') }}
        </p>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 sm:p-8">
        <form method="POST" action="{{ route('trips.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Trip Name --}}
            <x-input
                name="name"
                label="{{ __('Trip Name') }}"
                placeholder="{{ __('e.g., Summer Getaway in Da Lat') }}"
                :value="old('name')"
                required
            />

            {{-- Destination --}}
            <div>
                <x-input
                    name="destination"
                    label="{{ __('Destination (City or Region)') }}"
                    placeholder="{{ __('e.g., Da Lat, Lam Dong') }}"
                    :value="old('destination')"
                    list="destination-list"
                    required
                />
                <datalist id="destination-list">
                    @if(isset($destinations) && $destinations->isNotEmpty())
                        @foreach($destinations as $dest)
                            <option value="{{ $dest->name }}">
                        @endforeach
                    @else
                        <option value="Da Lat">
                        <option value="Da Nang">
                        <option value="Phu Quoc">
                        <option value="Hoi An">
                        <option value="Nha Trang">
                        <option value="Sa Pa">
                    @endif
                </datalist>
                <p class="mt-1 text-xs text-gray-400">{{ __('Choose or type any destination city name.') }}</p>
            </div>

            {{-- Dates: Start & End --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input
                    type="date"
                    name="start_date"
                    label="{{ __('Start Date') }}"
                    :value="old('start_date')"
                    required
                />
                <x-input
                    type="date"
                    name="end_date"
                    label="{{ __('End Date') }}"
                    :value="old('end_date')"
                    required
                />
            </div>

            {{-- Budget --}}
            <div>
                <x-input
                    type="number"
                    step="0.01"
                    min="0"
                    name="budget"
                    label="{{ __('Estimated Total Budget (VND)') }}"
                    placeholder="5000000"
                    :value="old('budget', '0')"
                    required
                />
                <p class="mt-1 text-xs text-gray-400">{{ __('Allocated budget for accommodations, dining, and activities.') }}</p>
            </div>

            {{-- Privacy Selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Privacy Setting') }} <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-primary/50 transition-colors {{ old('privacy', 'private') === 'private' ? 'border-primary ring-2 ring-primary/10 bg-primary/5' : 'border-gray-200' }}">
                        <input type="radio" name="privacy" value="private" class="sr-only" {{ old('privacy', 'private') === 'private' ? 'checked' : '' }}>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-900">{{ __('Private') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">{{ __('Only you and invited members can access.') }}</span>
                    </label>

                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-primary/50 transition-colors {{ old('privacy') === 'group' ? 'border-primary ring-2 ring-primary/10 bg-primary/5' : 'border-gray-200' }}">
                        <input type="radio" name="privacy" value="group" class="sr-only" {{ old('privacy') === 'group' ? 'checked' : '' }}>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-900">{{ __('Group') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">{{ __('Members can view and collaborate.') }}</span>
                    </label>

                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-primary/50 transition-colors {{ old('privacy') === 'public' ? 'border-primary ring-2 ring-primary/10 bg-primary/5' : 'border-gray-200' }}">
                        <input type="radio" name="privacy" value="public" class="sr-only" {{ old('privacy') === 'public' ? 'checked' : '' }}>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-900">{{ __('Public') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">{{ __('Anyone with the link can view itinerary.') }}</span>
                    </label>
                </div>
                @error('privacy')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cover Photo Upload --}}
            <div>
                <label for="cover" class="block text-sm font-medium text-gray-700 mb-1.5">
                    {{ __('Cover Image (Optional)') }}
                </label>
                <input
                    type="file"
                    name="cover"
                    id="cover"
                    accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer border border-gray-300 rounded-xl"
                >
                <p class="mt-1 text-xs text-gray-400">{{ __('PNG, JPG, or WEBP up to 3MB.') }}</p>
                @error('cover')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('trips.index') }}">
                    <x-button variant="secondary" type="button">
                        {{ __('Cancel') }}
                    </x-button>
                </a>
                <x-button variant="primary" type="submit" class="shadow-md">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>{{ __('Create Trip') }}</span>
                </x-button>
            </div>
        </form>
    </div>

</div>
@endsection
