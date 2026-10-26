@extends('layouts.app')

@section('title', __('Admin — Destinations'))

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">🗺️ {{ __('Destination Management') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('Manage destinations, zones and POIs for the Match Engine.') }}</p>
        </div>
        <a href="{{ route('admin.destinations.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary/90 transition-colors shadow-lg shadow-primary/25"
           id="btn-add-destination">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            {{ __('Add Destination') }}
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-medium">✅ {{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">{{ __('Destination') }}</th>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">{{ __('Country') }}</th>
                    <th class="px-5 py-3 text-center font-semibold text-gray-600">{{ __('Zones') }}</th>
                    <th class="px-5 py-3 text-center font-semibold text-gray-600">{{ __('POIs') }}</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($destinations as $destination)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3 font-semibold text-gray-900">{{ $destination->name }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $destination->country ?? '—' }}</td>
                    <td class="px-5 py-3 text-center">
                        <a href="{{ route('admin.destinations.zones', $destination) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium hover:bg-blue-100 transition-colors">
                            {{ $destination->zones_count }} {{ __('zones') }}
                        </a>
                    </td>
                    <td class="px-5 py-3 text-center text-gray-500">{{ $destination->pois_count }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.destinations.edit', $destination) }}"
                               class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.destinations.destroy', $destination) }}"
                                  onsubmit="return confirm('{{ __('Delete this destination and all its zones/POIs?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                    {{ __('Delete') }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">{{ __('No destinations yet. Add your first one!') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
