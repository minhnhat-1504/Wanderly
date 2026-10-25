@extends('layouts.app')

@section('title', __('Submit Proposal for Request #:id', ['id' => $travelRequest->id]))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
    items: [
        { name: '', price: 0 }
    ],
    addItem() {
        this.items.push({ name: '', price: 0 });
    },
    removeItem(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        }
    },
    get totalPrice() {
        return this.items.reduce((sum, item) => sum + (parseFloat(item.price) || 0), 0);
    }
}">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            {{ __('Submit Proposal — Request #:id', ['id' => $travelRequest->id]) }}
        </h1>
        <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-primary/10 text-primary">
            {{ $provider->business_name }}
        </span>
    </div>

    {{-- Request Overview --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm mb-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">{{ __('Traveler Requirements') }}</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-gray-500">{{ __('Destination:') }}</span>
                <div class="font-bold text-gray-900 mt-0.5">{{ $travelRequest->trip?->destination }}</div>
            </div>
            <div>
                <span class="text-gray-500">{{ __('Target Budget:') }}</span>
                <div class="font-bold text-gray-900 mt-0.5">{{ number_format((float) $travelRequest->max_budget, 0, ',', '.') }} VND</div>
            </div>
            <div>
                <span class="text-gray-500">{{ __('Pace:') }}</span>
                <div class="font-bold text-gray-900 mt-0.5">{{ __(ucfirst($travelRequest->pace ?? 'Balanced')) }}</div>
            </div>
            <div>
                <span class="text-gray-500">{{ __('Needs:') }}</span>
                <div class="font-bold text-gray-900 mt-0.5">
                    @foreach ((array) ($travelRequest->needs ?? []) as $need)
                        <span class="inline-block px-1.5 py-0.5 bg-gray-100 rounded text-[11px]">{{ __(ucfirst($need)) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @if ($travelRequest->notes)
            <div class="mt-3 text-xs text-gray-600 bg-gray-50 p-3 rounded-xl italic">
                "{{ $travelRequest->notes }}"
            </div>
        @endif
    </div>

    {{-- Proposal Form --}}
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 shadow-sm">
        <form action="{{ route('marketplace.proposals.store', $travelRequest) }}" method="POST" class="space-y-6">
            @csrf

            {{-- Message to Traveler --}}
            <div>
                <label for="message" class="block text-sm font-semibold text-gray-900 mb-1">
                    {{ __('Proposal Pitch & Message') }} <span class="text-red-500">*</span>
                </label>
                <textarea name="message" id="message" rows="3" required
                          placeholder="{{ __('Describe what makes your service package special (e.g., brand-new car, English-speaking guide, free bottled water)...') }}"
                          class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary shadow-sm">{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Valid Until Date --}}
            <div>
                <label for="valid_until" class="block text-sm font-semibold text-gray-900 mb-1">
                    {{ __('Offer Valid Until') }} <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="valid_until" id="valid_until" required
                       value="{{ old('valid_until', now()->addDays(7)->format('Y-m-d\TH:i')) }}"
                       class="rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary shadow-sm">
                @error('valid_until')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Proposal Items --}}
            <div class="pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('Package Service Items') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Break down your proposal into specific service items and rates.') }}</p>
                    </div>
                    <button type="button" @click="addItem()" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                        + {{ __('Add Item') }}
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="flex-1">
                                <input type="text" :name="'items[' + index + '][name]'" x-model="item.name" required
                                       placeholder="{{ __('Service name (e.g. 7-Seater Car 2 Days)') }}"
                                       class="w-full rounded-lg border-gray-300 text-xs focus:border-primary focus:ring-primary shadow-sm">
                            </div>
                            <div class="w-36">
                                <div class="relative">
                                    <input type="number" :name="'items[' + index + '][price]'" x-model="item.price" min="0" step="1000" required
                                           placeholder="0"
                                           class="w-full rounded-lg border-gray-300 text-xs pr-10 focus:border-primary focus:ring-primary shadow-sm">
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-2 text-[10px] text-gray-400 font-semibold">VND</span>
                                </div>
                            </div>
                            <button type="button" @click="removeItem(index)" class="text-gray-400 hover:text-red-500 p-1" title="{{ __('Remove') }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Total Calculation Bar --}}
                <div class="mt-4 p-4 rounded-xl bg-gray-100 flex items-center justify-between text-sm">
                    <span class="font-semibold text-gray-700">{{ __('Total Calculated Price:') }}</span>
                    <span class="text-lg font-bold text-primary">
                        <span x-text="new Intl.NumberFormat().format(totalPrice)"></span> VND
                    </span>
                </div>
            </div>

            {{-- Submit --}}
            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-primary text-white font-semibold text-xs shadow-md shadow-primary/25 hover:bg-primary/95 transition-all">
                    {{ __('Submit Official Proposal') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
