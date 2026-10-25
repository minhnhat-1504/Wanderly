@extends('layouts.app')

@section('title', __('Compare Proposals — :trip', ['trip' => $trip->name]))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
    confirmModalOpen: false,
    selectedProposal: null,
    selectedItem: null,
    modalAction: 'bundle', // 'bundle' or 'item'
    openBundleModal(proposal) {
        this.selectedProposal = proposal;
        this.modalAction = 'bundle';
        this.confirmModalOpen = true;
    },
    openItemModal(proposal, item) {
        this.selectedProposal = proposal;
        this.selectedItem = item;
        this.modalAction = 'item';
        this.confirmModalOpen = true;
    }
}">
    {{-- Breadcrumb & Navigation --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('trips.show', $trip) }}" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ $trip->name }}
            </a>
            <span>/</span>
            <a href="{{ route('trips.marketplace.index', $trip) }}" class="hover:text-primary transition-colors font-medium">
                {{ __('Marketplace') }}
            </a>
            <span>/</span>
            <span class="text-gray-900 font-semibold">{{ __('Proposal Comparison') }}</span>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('trips.marketplace.bookings', $trip) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('Confirmed Bookings & Vouchers') }}
            </a>

            @can('manageMarketplace', $trip)
                <a href="{{ route('trips.marketplace.requests.create', $trip) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-primary text-white hover:bg-primary/95 transition-all shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('New Request') }}
                </a>
            @endcan
        </div>
    </div>

    {{-- Request Overview Banner --}}
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 shadow-sm mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                        {{ $travelRequest->status === 'fulfilled' ? 'bg-emerald-100 text-emerald-800' : ($travelRequest->status === 'bidding' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ __(ucfirst($travelRequest->status)) }}
                    </span>
                    <span class="text-xs text-gray-500 font-medium">
                        {{ __('Request ID: #') }}{{ $travelRequest->id }} • {{ $travelRequest->created_at->diffForHumans() }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                    {{ __('Service Quotes for :dest', ['dest' => $trip->destination]) }}
                </h1>
                <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-gray-600 mt-2">
                    <span class="flex items-center gap-1">
                        <strong>{{ __('Pace:') }}</strong> {{ __(ucfirst($travelRequest->pace ?? 'Balanced')) }}
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <strong>{{ __('Target Budget:') }}</strong> {{ number_format((float) $travelRequest->max_budget, 0, ',', '.') }} VND
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <strong>{{ __('Services:') }}</strong>
                        @foreach ((array) ($travelRequest->needs ?? []) as $need)
                            <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">{{ __(ucfirst($need)) }}</span>
                        @endforeach
                    </span>
                </div>
            </div>

            {{-- Summary Stats Pills --}}
            <div class="grid grid-cols-3 gap-3 border-t lg:border-t-0 lg:border-l border-gray-100 pt-4 lg:pt-0 lg:pl-6 shrink-0">
                <div class="text-center p-3 bg-gray-50 rounded-xl">
                    <div class="text-xs text-gray-500 font-medium">{{ __('Proposals') }}</div>
                    <div class="text-xl font-bold text-gray-900 mt-0.5">{{ $matrix['stats']['count'] }}</div>
                </div>
                <div class="text-center p-3 bg-emerald-50 rounded-xl">
                    <div class="text-xs text-emerald-700 font-medium">{{ __('Lowest Offer') }}</div>
                    <div class="text-sm sm:text-base font-bold text-emerald-800 mt-0.5">
                        {{ $matrix['stats']['min_price'] > 0 ? number_format($matrix['stats']['min_price'], 0, ',', '.') : '—' }}
                    </div>
                </div>
                <div class="text-center p-3 bg-blue-50 rounded-xl">
                    <div class="text-xs text-blue-700 font-medium">{{ __('Average') }}</div>
                    <div class="text-sm sm:text-base font-bold text-blue-800 mt-0.5">
                        {{ $matrix['stats']['avg_price'] > 0 ? number_format($matrix['stats']['avg_price'], 0, ',', '.') : '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Comparison Cards Section --}}
    @if (empty($matrix['proposals']))
        {{-- Empty State --}}
        <div class="text-center py-16 px-4 bg-white rounded-2xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4 text-primary">
                <svg class="w-8 h-8 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">{{ __('Awaiting Proposals from Local Providers') }}</h2>
            <p class="text-sm text-gray-500 max-w-md mx-auto mt-1.5">
                {{ __('Your request has been broadcast to qualified partners in :destination. You will receive competitive packages shortly.', ['destination' => $trip->destination]) }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($matrix['proposals'] as $proposal)
                <div class="bg-white rounded-2xl border transition-all duration-200 flex flex-col justify-between overflow-hidden
                    {{ $proposal['status'] === 'accepted' ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md' : ($proposal['is_lowest_price'] ? 'border-primary shadow-sm hover:shadow-md' : 'border-gray-200 hover:border-gray-300 shadow-sm') }}">

                    <div>
                        {{-- Card Header: Provider Info --}}
                        <div class="p-5 border-b border-gray-100 flex items-start justify-between bg-gradient-to-b from-gray-50/50 to-white">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary font-bold flex items-center justify-center text-base">
                                    {{ substr($proposal['provider']['business_name'], 0, 2) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <h2 class="text-base font-bold text-gray-900 leading-tight">
                                            {{ $proposal['provider']['business_name'] }}
                                        </h2>
                                        @if ($proposal['provider']['is_verified'])
                                            <span title="{{ __('Verified Provider') }}" class="text-blue-500">
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center text-xs font-semibold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">
                                            ★ {{ number_format($proposal['provider']['rating'], 1) }}
                                        </span>
                                        <span class="text-xs text-gray-500 capitalize">
                                            {{ __($proposal['provider']['service_type'] ?? 'Partner') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @if ($proposal['is_lowest_price'])
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                    {{ __('Best Price') }}
                                </span>
                            @endif
                        </div>

                        {{-- Pricing Banner --}}
                        <div class="px-5 py-4 bg-gray-50/50 border-b border-gray-100">
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs font-medium text-gray-500">{{ __('Package Total') }}</span>
                                <div class="text-right">
                                    <span class="text-xl font-bold text-gray-900">
                                        {{ number_format($proposal['total_price'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500">VND</span>
                                </div>
                            </div>

                            @if ($proposal['savings'] > 0)
                                <div class="mt-1 text-right text-xs font-medium text-emerald-600">
                                    {{ __('Saves :amount VND vs Target', ['amount' => number_format($proposal['savings'], 0, ',', '.')]) }}
                                </div>
                            @endif
                        </div>

                        {{-- Provider Message --}}
                        <div class="p-5 text-sm text-gray-600 border-b border-gray-100">
                            <p class="italic text-xs text-gray-500 mb-1 font-semibold uppercase">{{ __('Provider Note:') }}</p>
                            <p class="line-clamp-3 text-xs sm:text-sm leading-relaxed">"{{ $proposal['message'] }}"</p>
                            <div class="mt-2 text-[11px] text-gray-400">
                                {{ __('Valid until:') }} {{ \Carbon\Carbon::parse($proposal['valid_until'])->format('d/m/Y H:i') }}
                            </div>
                        </div>

                        {{-- Included Services / Items --}}
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-3">
                                {{ __('Included Services (:count)', ['count' => count($proposal['items'])]) }}
                            </h3>
                            <div class="space-y-2.5">
                                @forelse ($proposal['items'] as $item)
                                    <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-gray-50 hover:bg-gray-100/70 transition-colors">
                                        <div class="flex-1 pr-2">
                                            <div class="font-semibold text-gray-800">{{ $item['name'] }}</div>
                                            @if (!empty($item['specs']))
                                                <div class="text-[10px] text-gray-500 truncate">
                                                    {{ json_encode($item['specs']) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-right shrink-0 flex items-center gap-2">
                                            <span class="font-bold text-gray-900">{{ number_format($item['price'], 0, ',', '.') }} đ</span>

                                            @if ($item['is_accepted'])
                                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded">
                                                    {{ __('Booked') }}
                                                </span>
                                            @elseif ($proposal['status'] === 'pending')
                                                @can('manageMarketplace', $trip)
                                                    <button type="button"
                                                            @click="openItemModal({{ json_encode($proposal) }}, {{ json_encode($item) }})"
                                                            class="text-[10px] font-semibold text-primary hover:text-primary/80 underline">
                                                        {{ __('Pick') }}
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 italic">{{ __('All-inclusive bundled service') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Actions Footer --}}
                    <div class="p-5 bg-gray-50/50 border-t border-gray-100">
                        @if ($proposal['status'] === 'accepted')
                            <div class="w-full py-2.5 px-4 rounded-xl bg-emerald-100 text-emerald-800 text-xs font-bold text-center flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Proposal Accepted & Booked') }}
                            </div>
                        @elseif ($proposal['status'] === 'rejected')
                            <div class="w-full py-2.5 px-4 rounded-xl bg-gray-100 text-gray-500 text-xs font-semibold text-center">
                                {{ __('Proposal Declined') }}
                            </div>
                        @elseif ($proposal['status'] === 'expired')
                            <div class="w-full py-2.5 px-4 rounded-xl bg-gray-100 text-gray-400 text-xs font-semibold text-center">
                                {{ __('Offer Expired') }}
                            </div>
                        @else
                            @can('manageMarketplace', $trip)
                                <button type="button"
                                        @click="openBundleModal({{ json_encode($proposal) }})"
                                        class="w-full py-3 px-4 rounded-xl bg-primary text-white text-xs font-bold shadow-md shadow-primary/20 hover:shadow-primary/30 hover:bg-primary/95 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('Accept Full Bundle') }}
                                </button>
                            @else
                                <div class="text-center text-xs text-gray-400 py-2">
                                    {{ __('Only Leader/Editor can accept proposals') }}
                                </div>
                            @endcan
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Confirmation Modal (Alpine.js) --}}
    <div x-show="confirmModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div x-show="confirmModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="confirmModalOpen = false"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Content --}}
            <div x-show="confirmModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 sm:p-8">

                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h3 class="text-lg font-bold text-gray-900" id="modal-title">
                    <span x-show="modalAction === 'bundle'">{{ __('Confirm Full Bundle Booking') }}</span>
                    <span x-show="modalAction === 'item'">{{ __('Confirm Single Item Booking') }}</span>
                </h3>

                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Accepting will create official booking vouchers, automatically add items into your itinerary timeline, and update your trip budget in real-time.') }}
                </p>

                {{-- Price & Provider Preview --}}
                <div class="mt-4 p-4 rounded-xl bg-gray-50 border border-gray-100 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('Provider:') }}</span>
                        <span class="font-bold text-gray-900" x-text="selectedProposal?.provider?.business_name"></span>
                    </div>
                    <div class="flex justify-between" x-show="modalAction === 'item'">
                        <span class="text-gray-500">{{ __('Selected Item:') }}</span>
                        <span class="font-bold text-gray-900" x-text="selectedItem?.name"></span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 text-sm">
                        <span class="font-semibold text-gray-700">{{ __('Total Amount to Deduct:') }}</span>
                        <span class="font-bold text-primary">
                            <span x-text="modalAction === 'bundle' ? new Intl.NumberFormat().format(selectedProposal?.total_price || 0) : new Intl.NumberFormat().format(selectedItem?.price || 0)"></span> VND
                        </span>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="confirmModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </button>

                    {{-- Form for Full Bundle --}}
                    <form x-show="modalAction === 'bundle'"
                          :action="'/marketplace/proposals/' + selectedProposal?.id + '/accept-bundle'"
                          method="POST">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-sm">
                            {{ __('Yes, Accept & Deduct Budget') }}
                        </button>
                    </form>

                    {{-- Form for Single Item --}}
                    <form x-show="modalAction === 'item'"
                          :action="'/marketplace/items/' + selectedItem?.id + '/accept-item'"
                          method="POST">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-sm">
                            {{ __('Yes, Book Item Only') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
