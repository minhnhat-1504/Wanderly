@extends('layouts.app')

@section('title', __('Zones — :dest', ['dest' => $destination->name]))

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.destinations.index') }}" class="text-sm text-gray-500 hover:text-primary transition-colors">← {{ __('Destinations') }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">📍 {{ $destination->name }} — {{ __('Zones') }}</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">✅ {{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Zone List --}}
        <div class="space-y-3">
            @forelse($zones as $zone)
            <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-gray-900">{{ $zone->name }}</p>
                    <p class="text-xs text-gray-400">{{ $zone->pois_count }} POIs</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.zones.pois', $zone) }}"
                       class="px-3 py-1.5 text-xs font-medium bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                        {{ __('Manage POIs') }}
                    </a>
                    <form method="POST" action="{{ route('admin.zones.destroy', $zone) }}"
                          onsubmit="return confirm('{{ __('Delete this zone?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                            {{ __('Delete') }}
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-400 bg-white rounded-xl border border-gray-100">
                <p>{{ __('No zones yet.') }}</p>
            </div>
            @endforelse
        </div>

        {{-- Add Zone Form --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-900 mb-4">{{ __('Add New Zone') }}</h2>
            <form method="POST" action="{{ route('admin.destinations.zones.store', $destination) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Zone Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="255" value="{{ old('name') }}"
                           placeholder="{{ __('E.g., Lam Vien Area') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Description') }}</label>
                    <textarea name="description" rows="2"
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition resize-none">{{ old('description') }}</textarea>
                </div>
                <button type="submit"
                        class="w-full py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary/90 transition-colors">
                    {{ __('Create Zone') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
