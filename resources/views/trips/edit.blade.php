@extends('layouts.app')

@section('title', __('Edit Trip: :name', ['name' => $trip->name]))
@section('description', __('Update trip details, status, and privacy.'))

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
        <a href="{{ route('trips.show', $trip) }}" class="hover:text-primary transition-colors duration-200 truncate max-w-xs">
            {{ $trip->name }}
        </a>
        <svg class="w-4 h-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="text-gray-900 font-semibold">{{ __('Edit') }}</span>
    </nav>

    {{-- Header --}}
    <div class="pb-4 border-b border-gray-200">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
            {{ __('Edit Trip Details') }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ __('Modify schedule, allocated budget, status, and permissions for :name.', ['name' => $trip->name]) }}
        </p>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 sm:p-8">
        <form method="POST" action="{{ route('trips.update', $trip) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Trip Name --}}
            <x-input
                name="name"
                label="{{ __('Trip Name') }}"
                placeholder="{{ __('e.g., Summer Getaway in Da Lat') }}"
                :value="old('name', $trip->name)"
                required
            />

            {{-- Destination --}}
            <div>
                <label for="destination" class="block text-sm font-medium text-gray-700 mb-1.5">
                    {{ __('Destination (34 Provinces / Cities)') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <select
                        name="destination"
                        id="destination"
                        required
                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm py-2.5 px-3 bg-white"
                    >
                        <option value="" disabled>
                            -- {{ __('Select a Province or City') }} --
                        </option>
                        @foreach($provinces as $region => $items)
                            <optgroup label="📍 {{ $region }}">
                                @foreach($items as $prov)
                                    <option value="{{ $prov['name'] }}" {{ old('destination', $trip->destination) === $prov['name'] || (str_contains(strtolower($trip->destination ?? ''), strtolower($prov['name']))) ? 'selected' : '' }}>
                                        {{ $prov['name'] }} @if(!empty($prov['note'])) ({{ $prov['note'] }}) @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                @error('destination')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Dates: Start & End --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input
                    type="date"
                    name="start_date"
                    label="{{ __('Start Date') }}"
                    :value="old('start_date', $trip->start_date ? $trip->start_date->format('Y-m-d') : '')"
                    required
                />
                <x-input
                    type="date"
                    name="end_date"
                    label="{{ __('End Date') }}"
                    :value="old('end_date', $trip->end_date ? $trip->end_date->format('Y-m-d') : '')"
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
                    :value="old('budget', $trip->budget)"
                    required
                />
            </div>

            {{-- Trip Status Selector --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">
                    {{ __('Trip Status') }}
                </label>
                <select
                    name="status"
                    id="status"
                    class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-900 shadow-sm transition duration-200 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm"
                >
                    <option value="planning" {{ old('status', $trip->status) === 'planning' ? 'selected' : '' }}>
                        {{ __('Planning (Preparation phase)') }}
                    </option>
                    <option value="ongoing" {{ old('status', $trip->status) === 'ongoing' ? 'selected' : '' }}>
                        {{ __('Ongoing (Trip is active)') }}
                    </option>
                    <option value="completed" {{ old('status', $trip->status) === 'completed' ? 'selected' : '' }}>
                        {{ __('Completed (Trip has ended)') }}
                    </option>
                </select>
                @error('status')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Privacy Selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Privacy Setting') }} <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-primary/50 transition-colors {{ old('privacy', $trip->privacy) === 'private' ? 'border-primary ring-2 ring-primary/10 bg-primary/5' : 'border-gray-200' }}">
                        <input type="radio" name="privacy" value="private" class="sr-only" {{ old('privacy', $trip->privacy) === 'private' ? 'checked' : '' }}>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-900">{{ __('Private') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">{{ __('Only you and invited members.') }}</span>
                    </label>

                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-primary/50 transition-colors {{ old('privacy', $trip->privacy) === 'group' ? 'border-primary ring-2 ring-primary/10 bg-primary/5' : 'border-gray-200' }}">
                        <input type="radio" name="privacy" value="group" class="sr-only" {{ old('privacy', $trip->privacy) === 'group' ? 'checked' : '' }}>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-900">{{ __('Group') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">{{ __('Members can collaborate.') }}</span>
                    </label>

                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-primary/50 transition-colors {{ old('privacy', $trip->privacy) === 'public' ? 'border-primary ring-2 ring-primary/10 bg-primary/5' : 'border-gray-200' }}">
                        <input type="radio" name="privacy" value="public" class="sr-only" {{ old('privacy', $trip->privacy) === 'public' ? 'checked' : '' }}>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-900">{{ __('Public') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 mt-1">{{ __('Anyone with link can view.') }}</span>
                    </label>
                </div>
                @error('privacy')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cover Photo Upload --}}
            <div>
                <label for="cover" class="block text-sm font-medium text-gray-700 mb-1.5">
                    {{ __('Cover Image') }}
                </label>
                @if($trip->cover)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ asset('storage/' . $trip->cover) }}" alt="{{ $trip->name }}" class="w-16 h-16 rounded-xl object-cover border border-gray-200">
                        <span class="text-xs text-gray-500">{{ __('Current cover image') }}</span>
                    </div>
                @endif
                <input
                    type="file"
                    name="cover"
                    id="cover"
                    accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer border border-gray-300 rounded-xl"
                >
                <p class="mt-1 text-xs text-gray-400">{{ __('Leave empty to keep existing cover, or upload a new PNG/JPG/WEBP (max 3MB).') }}</p>
                @error('cover')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('trips.show', $trip) }}">
                    <x-button variant="secondary" type="button">
                        {{ __('Cancel') }}
                    </x-button>
                </a>
                <x-button variant="primary" type="submit" class="shadow-md">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>{{ __('Save Changes') }}</span>
                </x-button>
            </div>
        </form>
    </div>

</div>
@endsection
