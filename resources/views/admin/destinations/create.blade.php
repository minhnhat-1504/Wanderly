@extends('layouts.app')

@section('title', __('Add Destination'))

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.destinations.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            {{ __('Back to Destinations') }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ __('Add Destination') }}</h1>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('admin.destinations.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Destination Name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" required maxlength="255" value="{{ old('name') }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition"
                       placeholder="{{ __('E.g., Da Lat') }}">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Country') }}</label>
                <input type="text" name="country" maxlength="100" value="{{ old('country') }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition"
                       placeholder="{{ __('E.g., Vietnam') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Description') }}</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition resize-none">{{ old('description') }}</textarea>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.destinations.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary/90 transition-colors shadow-lg shadow-primary/25">
                    {{ __('Create Destination') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
