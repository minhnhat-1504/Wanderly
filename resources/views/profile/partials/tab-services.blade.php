@props([
    'providerProfile',
    'user',
    'isOwner' => false,
])

@if($providerProfile)
    <div class="space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span>{{ $providerProfile['provider']->business_name }}</span>
                    @if($providerProfile['is_verified'])
                        <x-icon name="badge-check" class="w-5 h-5 text-teal-600" title="{{ __('Verified Partner') }}" />
                    @endif
                </h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $providerProfile['provider']->bio }}</p>
            </div>
            <div class="flex items-center gap-4 bg-teal-50/50 p-3 rounded-2xl shrink-0 border border-teal-100/50">
                <div class="text-center px-2">
                    <span class="block text-base font-bold text-teal-900">{{ $providerProfile['completed_bookings_count'] }}</span>
                    <span class="text-xs text-slate-500 font-medium">{{ __('Completed Orders') }}</span>
                </div>
                @if($providerProfile['rating'])
                    <div class="border-l border-teal-200/60 pl-4 text-center">
                        <span class="block text-base font-bold text-amber-600 flex items-center justify-center gap-1">
                            <span>★</span> {{ $providerProfile['rating'] }}
                        </span>
                        <span class="text-xs text-slate-500 font-medium">{{ __('Rating') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($providerProfile['services'] as $service)
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 uppercase tracking-wide">
                                {{ $service->category ?? __('Service') }}
                            </span>
                            <span class="text-base font-extrabold text-teal-800">
                                {{ number_format($service->price) }} {{ $service->currency ?? 'VND' }}
                            </span>
                        </div>
                        <h4 class="font-bold text-gray-900 text-base mb-1">{{ $service->title }}</h4>
                        <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed">{{ $service->description }}</p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end">
                        <a href="{{ route('trips.index') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-teal-800 bg-teal-50 hover:bg-teal-100 transition-colors">
                            <span>{{ __('Inquire via Trip Request') }}</span>
                            <x-icon name="share-2" class="w-3 h-3 text-teal-700" />
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-profile.empty-state
                        :title="__('No services listed yet')"
                        :description="__('This partner has not published any catalog services yet.')"
                        icon="briefcase"
                    />
                </div>
            @endforelse
        </div>
    </div>
@endif
