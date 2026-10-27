@extends('layouts.app')

@section('title', $service->exists ? __('Edit Service — :name', ['name' => $service->name]) : __('Add New Service'))

@section('content')
@php
    $initialSpecs = [];
    if (old('specs')) {
        $initialSpecs = old('specs');
    } elseif (!empty($service->specs)) {
        foreach ($service->specs as $k => $v) {
            $initialSpecs[] = ['key' => $k, 'value' => is_bool($v) ? ($v ? 'true' : 'false') : (string)$v];
        }
    }
    if (empty($initialSpecs)) {
        $initialSpecs = [
            ['key' => 'seats', 'value' => '7'],
            ['key' => 'air_conditioned', 'value' => 'true'],
        ];
    }
@endphp

<div class="max-w-3xl mx-auto py-6" x-data="serviceForm({{ json_encode($initialSpecs) }})">

    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('provider.services.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-primary transition-colors mb-3">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('Back to Service Catalog') }}
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
            {{ $service->exists ? __('Edit Catalog Service') : __('Add New Service Offering') }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ __('Define pricing, tags, and flexible technical specifications for this offering.') }}
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form
            method="POST"
            action="{{ $service->exists ? route('provider.services.update', $service) : route('provider.services.store') }}"
            enctype="multipart/form-data"
            class="space-y-6"
        >
            @csrf
            @if($service->exists)
                @method('PUT')
            @endif

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-900 mb-1">
                    {{ __('Service Name') }} <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $service->name) }}"
                    required
                    placeholder="{{ __('e.g., Private 7-Seater Airport & City Shuttle (2 Days)') }}"
                    class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary shadow-sm"
                >
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Price & Tags --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="price" class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('Base Price (VND)') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="price"
                        id="price"
                        step="1000"
                        min="0"
                        value="{{ old('price', $service->price) }}"
                        required
                        placeholder="{{ __('1800000') }}"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary shadow-sm"
                    >
                    @error('price')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tags" class="block text-sm font-semibold text-gray-900 mb-1">
                        {{ __('Search Tags (Comma-separated)') }}
                    </label>
                    <input
                        type="text"
                        name="tags"
                        id="tags"
                        value="{{ old('tags', is_array($service->tags) ? implode(', ', $service->tags) : $service->tags) }}"
                        placeholder="{{ __('transport, car, private, airport') }}"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary shadow-sm"
                    >
                    @error('tags')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Specifications JSON Builder --}}
            <div class="border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900">
                            {{ __('Custom Specifications (Key - Value Pairs)') }}
                        </label>
                        <p class="text-xs text-gray-500">
                            {{ __('Vehicle seats, duration, meals included, language spoken, etc.') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="addSpec()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-semibold text-gray-700 transition-colors"
                    >
                        <span>+</span>
                        <span>{{ __('Add Field') }}</span>
                    </button>
                </div>

                <div class="space-y-2.5 mt-3">
                    <template x-for="(spec, index) in specs" :key="index">
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                :name="'specs[' + index + '][key]'"
                                x-model="spec.key"
                                placeholder="{{ __('Field name (e.g., seats, ac, duration)') }}"
                                class="w-1/2 rounded-xl border-gray-300 text-xs focus:border-primary focus:ring-primary shadow-sm"
                            >
                            <input
                                type="text"
                                :name="'specs[' + index + '][value]'"
                                x-model="spec.value"
                                placeholder="{{ __('Value (e.g., 7, true, Full-day)') }}"
                                class="w-1/2 rounded-xl border-gray-300 text-xs focus:border-primary focus:ring-primary shadow-sm"
                            >
                            <button
                                type="button"
                                @click="removeSpec(index)"
                                class="p-2 text-gray-400 hover:text-red-500 rounded-lg transition-colors"
                                title="{{ __('Remove') }}"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Image Upload --}}
            <div class="border-t border-gray-100 pt-5">
                <label class="block text-sm font-semibold text-gray-900 mb-2">
                    {{ __('Service Photo') }}
                </label>

                @if($service->image)
                    <div class="mb-3 flex items-center gap-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <img src="{{ asset('storage/' . $service->image) }}" alt="Preview" class="w-16 h-16 object-cover rounded-lg border">
                        <div class="text-xs space-y-1">
                            <p class="font-semibold text-gray-700">{{ __('Current photo uploaded') }}</p>
                            <label class="inline-flex items-center gap-1.5 text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_image" value="1" class="rounded text-red-600 focus:ring-red-500">
                                <span>{{ __('Delete existing photo') }}</span>
                            </label>
                        </div>
                    </div>
                @endif

                <input
                    type="file"
                    name="image"
                    id="image"
                    accept="image/*"
                    class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
                >
                @error('image')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Actions --}}
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('provider.services.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    {{ __('Cancel') }}
                </a>
                <button
                    type="submit"
                    class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold shadow-md shadow-primary/20 hover:bg-teal-800 transition-all"
                >
                    {{ $service->exists ? __('Save Changes') : __('Create Service') }}
                </button>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
function serviceForm(initialSpecs) {
    return {
        specs: initialSpecs && initialSpecs.length > 0 ? initialSpecs : [{ key: '', value: '' }],
        addSpec() {
            if (this.specs.length < 20) {
                this.specs.push({ key: '', value: '' });
            }
        },
        removeSpec(index) {
            this.specs.splice(index, 1);
        }
    };
}
</script>
@endpush
@endsection
