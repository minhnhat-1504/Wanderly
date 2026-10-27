@extends('layouts.app')

@section('title', __('Service Catalog — :biz', ['biz' => $provider->business_name]))

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <a href="{{ route('provider.dashboard') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                    <x-icon name="store" class="w-3.5 h-3.5 text-primary" />
                    <span>{{ $provider->business_name }}</span>
                </a>
                <span>/</span>
                <span class="text-gray-900 font-bold">{{ __('Service Catalog') }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                {{ __('My Service Offerings') }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ __('Manage the services, pricing, vehicle specifications or tour inclusions available for traveler proposals.') }}
            </p>
        </div>

        <a href="{{ route('provider.services.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-xs sm:text-sm font-semibold hover:bg-teal-800 transition-colors shadow-md shadow-primary/20">
            <x-icon name="plus" class="w-4 h-4 text-white" />
            <span>{{ __('Add New Service') }}</span>
        </a>
    </div>

    {{-- Services List --}}
    @if($services->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-gray-200">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-teal-50 flex items-center justify-center text-teal-600">
                <x-icon name="package" class="w-8 h-8" />
            </div>
            <h3 class="text-base font-bold text-gray-900 mt-3">{{ __('Your service catalog is empty') }}</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                {{ __('Add your vehicles, tour packages, or accommodation options so you can quickly bundle them into proposals.') }}
            </p>
            <div class="mt-6">
                <a href="{{ route('provider.services.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold shadow-md shadow-primary/20 hover:bg-teal-800 transition-all">
                    <x-icon name="plus" class="w-4 h-4 text-white" />
                    <span>{{ __('Add Your First Service') }}</span>
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($services as $service)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col justify-between hover:border-gray-200 transition-all">
                    {{-- Service Header / Image --}}
                    <div>
                        @if($service->image)
                            <div class="h-44 w-full bg-gray-100 overflow-hidden relative">
                                <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" class="w-full h-full object-cover">
                                <span class="absolute bottom-2 right-2 bg-black/70 backdrop-blur-sm text-white text-xs font-bold px-2.5 py-1 rounded-lg">
                                    {{ number_format($service->price, 0, ',', '.') }} VND
                                </span>
                            </div>
                        @else
                            <div class="h-32 w-full bg-gradient-to-br from-teal-50 to-emerald-50 flex items-center justify-between p-4 border-b border-gray-100">
                                <span class="w-10 h-10 rounded-xl bg-white/80 flex items-center justify-center text-primary shadow-sm">
                                    <x-icon name="briefcase" class="w-5 h-5" />
                                </span>
                                <span class="bg-white text-primary text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm border border-primary/20">
                                    {{ number_format($service->price, 0, ',', '.') }} VND
                                </span>
                            </div>
                        @endif

                        <div class="p-5 space-y-3">
                            <h3 class="text-base font-bold text-gray-900 leading-snug">
                                {{ $service->name }}
                            </h3>

                            {{-- Tags --}}
                            @if(!empty($service->tags))
                                <div class="flex flex-wrap gap-1">
                                    @foreach((array)$service->tags as $tag)
                                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary">
                                            #{{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Specs JSON Breakdown --}}
                            @if(!empty($service->specs))
                                <div class="bg-gray-50 rounded-xl p-3 text-xs space-y-1 border border-gray-100">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">
                                        {{ __('Specifications:') }}
                                    </span>
                                    @foreach((array)$service->specs as $k => $v)
                                        <div class="flex items-center justify-between text-gray-600">
                                            <span class="capitalize text-gray-500">{{ str_replace('_', ' ', $k) }}:</span>
                                            <span class="font-semibold text-gray-900">
                                                @if(is_bool($v))
                                                    {{ $v ? __('Yes') : __('No') }}
                                                @else
                                                    {{ $v }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="p-4 bg-gray-50/70 border-t border-gray-100 flex items-center justify-end gap-2">
                        <a href="{{ route('provider.services.edit', $service) }}" class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-white transition-colors">
                            {{ __('Edit') }}
                        </a>

                        <form method="POST" action="{{ route('provider.services.destroy', $service) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this service?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
