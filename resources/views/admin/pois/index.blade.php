@extends('layouts.app')

@section('title', __('POIs — :zone', ['zone' => $zone->name]))

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.destinations.zones', $zone->destination) }}" class="text-sm text-gray-500 hover:text-primary transition-colors">
            ← {{ $zone->destination->name }} / {{ __('Zones') }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">📌 {{ $zone->name }} — {{ __('Points of Interest') }}</h1>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">✅ {{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- POI List --}}
        <div class="lg:col-span-2 space-y-3">
            @forelse($pois as $poi)
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-gray-900">{{ $poi->name }}</span>
                            @if($poi->category)
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $poi->category }}</span>
                            @endif
                            @if($poi->rating)
                                <span class="text-xs text-yellow-600 font-medium">⭐ {{ $poi->rating }}</span>
                            @endif
                        </div>
                        @if($poi->address)<p class="text-xs text-gray-500 mt-1">📍 {{ $poi->address }}</p>@endif
                        @if($poi->avg_cost)<p class="text-xs text-gray-400">💰 ~{{ number_format($poi->avg_cost, 0, ',', '.') }} VND</p>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.pois.destroy', $poi) }}"
                          onsubmit="return confirm('{{ __('Delete this POI?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="text-center py-10 text-gray-400 bg-white rounded-xl border border-gray-100">
                <p>{{ __('No POIs yet. Add your first one!') }}</p>
            </div>
            @endforelse
        </div>

        {{-- Add POI Form --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-900 mb-4">{{ __('Add POI') }}</h2>
            <form method="POST" action="{{ route('admin.zones.pois.store', $zone) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="255"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Category') }}</label>
                    <input type="text" name="category" maxlength="100"
                           placeholder="{{ __('attraction, cafe, restaurant…') }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Address') }}</label>
                    <input type="text" name="address"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Rating') }}</label>
                        <input type="number" name="rating" step="0.1" min="0" max="5"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Avg Cost (VND)') }}</label>
                        <input type="number" name="avg_cost" min="0"
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition">
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary/90 transition-colors">
                    {{ __('Add POI') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
