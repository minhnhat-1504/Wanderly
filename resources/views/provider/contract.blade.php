@extends('layouts.app')

@section('title', __('Service Partner Agreement') . ' — Wanderly')

@section('content')
<div class="max-w-3xl mx-auto py-8 space-y-6">

    {{-- Breadcrumb & Back --}}
    <div class="flex items-center justify-between">
        <div class="inline-flex items-center gap-2 text-xs font-semibold text-gray-500">
            <span class="text-teal-700 font-bold uppercase tracking-wider text-[11px] bg-teal-50 border border-teal-200 px-2.5 py-1 rounded-md">
                {{ __('Step 2 of 2: Partnership Contract') }}
            </span>
            <span>•</span>
            <span class="font-mono text-gray-700">WND-AGR-{{ str_pad($provider->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <span class="text-xs text-gray-400 font-medium">{{ now()->format('M d, Y') }}</span>
    </div>

    {{-- Contract Document Card --}}
    <div class="bg-white rounded-3xl shadow-xl border border-gray-200/90 overflow-hidden">
        {{-- Document Header Banner --}}
        <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-900 text-white p-6 sm:p-8 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-xl shadow-inner border border-white/20">
                        📜
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight leading-tight">
                            {{ __('Wanderly Partner Cooperation Agreement') }}
                        </h1>
                        <p class="text-xs text-emerald-300 font-medium">
                            {{ __('Hợp đồng Hợp tác Đối tác Cung cấp Dịch vụ Du lịch') }}
                        </p>
                    </div>
                </div>
                <div class="hidden sm:block text-right">
                    <span class="text-[10px] uppercase font-bold tracking-widest text-teal-200 block">{{ __('Agreement Status') }}</span>
                    <span class="text-xs font-extrabold text-amber-300 bg-amber-500/20 px-2.5 py-0.5 rounded-full border border-amber-400/30">
                        {{ __('Pending Signature') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Parties Overview Grid --}}
        <div class="p-6 sm:p-8 bg-gray-50/70 border-b border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
            {{-- Party A --}}
            <div class="space-y-2 p-4 rounded-2xl bg-white border border-gray-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-800 bg-teal-50 px-2 py-0.5 rounded-md">
                    {{ __('Party A • Platform Operator') }}
                </span>
                <h3 class="font-extrabold text-gray-900 text-sm">Wanderly Tourism Technologies Co., Ltd.</h3>
                <p class="text-gray-600 leading-relaxed">
                    <strong>Address:</strong> 123 Nguyen Hue Boulevard, District 1, Ho Chi Minh City<br>
                    <strong>Hotline:</strong> 1900 6868 • <strong>Email:</strong> partners@wanderly.vn
                </p>
            </div>

            {{-- Party B --}}
            <div class="space-y-2 p-4 rounded-2xl bg-white border border-gray-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-800 bg-teal-50 px-2 py-0.5 rounded-md">
                    {{ __('Party B • Service Provider') }}
                </span>
                <h3 class="font-extrabold text-gray-900 text-sm">{{ $provider->business_name }}</h3>
                <p class="text-gray-600 leading-relaxed">
                    <strong>Representative:</strong> {{ auth()->user()->name }} ({{ auth()->user()->email }})<br>
                    <strong>Service Type:</strong> {{ ucfirst($provider->service_type) }} • <strong>Destination:</strong> {{ $provider->destination?->name ?? 'Vietnam' }}<br>
                    <strong>Hotline:</strong> {{ $provider->phone ?: __('Not provided') }}<br>
                    <strong>Address:</strong> {{ $provider->address ?: __('Not provided') }}
                </p>
            </div>
        </div>

        {{-- Contract Clauses & Terms --}}
        <div class="p-6 sm:p-8 space-y-6 text-xs sm:text-sm text-gray-700 leading-relaxed">
            <div class="space-y-2">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                    {{ __('Article 1: Scope of Service Cooperation') }}
                </h3>
                <p class="text-gray-600">
                    Party B agrees to list, offer, and fulfill travel services (including guided tours, transportation, accommodations, and culinary experiences) to travelers using the Wanderly platform. Party B commits to providing truthful pricing, accurate specifications, and high-quality experiences.
                </p>
            </div>

            <div class="space-y-2">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                    {{ __('Article 2: Platform Fee & Transparent Settlement') }}
                </h3>
                <p class="text-gray-600">
                    Wanderly operates with an automated escrow and VietQR payout system. Upon successful service completion confirmed by the traveler or within 24 hours of scheduled itinerary conclusion, Wanderly releases 90% of booking revenue directly to Party B's linked bank account, retaining a standard 10% platform facilitation fee.
                </p>
            </div>

            <div class="space-y-2">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                    {{ __('Article 3: Service Level Agreement (SLA) & Reliability') }}
                </h3>
                <p class="text-gray-600">
                    Party B undertakes to respond promptly to traveler proposals and booking chats within 2 hours during working hours (08:00 - 20:00). In case of unforeseen schedule cancellations, Party B must notify both traveler and Wanderly support at least 48 hours prior to service commencement.
                </p>
            </div>

            <div class="space-y-2">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                    {{ __('Article 4: Customer Protection & Data Confidentiality') }}
                </h3>
                <p class="text-gray-600">
                    All traveler personal information, itinerary schedules, and booking vouchers exchanged via direct booking channels are confidential. Neither party shall disclose or utilize customer contact information outside Wanderly's fulfillment workflow.
                </p>
            </div>
        </div>

        {{-- Form: Digital Signature & Acceptance --}}
        <form method="POST" action="{{ route('provider.contract.sign') }}" class="p-6 sm:p-8 bg-gray-50 border-t border-gray-200/90 space-y-5">
            @csrf

            <div class="p-4 rounded-2xl bg-teal-50/70 border border-teal-200 text-teal-950 text-xs sm:text-sm space-y-3">
                <div class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        name="agree_terms"
                        id="agree_terms"
                        value="1"
                        required
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-teal-700 focus:ring-teal-500 cursor-pointer"
                    >
                    <label for="agree_terms" class="cursor-pointer font-medium leading-relaxed">
                        {{ __('I, :name, legal representative of :business, certify that all submitted business credentials are authentic and valid. I agree to abide by the Wanderly Partner Agreement, Service Standards, and Escrow Settlement Policy.', [
                            'name' => auth()->user()->name,
                            'business' => $provider->business_name
                        ]) }}
                    </label>
                </div>
                @error('agree_terms')
                    <p class="text-xs text-red-600 font-bold ml-7">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="signature_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        {{ __('Digital Signature (Full Name)') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="signature_name"
                        id="signature_name"
                        value="{{ old('signature_name', auth()->user()->name) }}"
                        required
                        placeholder="{{ __('Your Full Legal Name') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-700 font-semibold text-gray-900 bg-white shadow-xs"
                    >
                    @error('signature_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        {{ __('Signing Date & Time') }}
                    </label>
                    <input
                        type="text"
                        disabled
                        value="{{ now()->format('Y-m-d H:i:s') }} (UTC)"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-gray-100 text-gray-500 font-mono select-none"
                    >
                </div>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-gray-200">
                <a
                    href="{{ route('trips.index') }}"
                    class="text-xs font-bold text-gray-500 hover:text-gray-800 transition"
                >
                    &larr; {{ __('Return to Dashboard (Sign later)') }}
                </a>

                <button
                    type="submit"
                    class="w-full sm:w-auto px-8 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold text-sm shadow-lg shadow-teal-900/20 transition-all flex items-center justify-center gap-2"
                >
                    <span>✍️</span>
                    <span>{{ __('Sign & Activate Provider Account') }}</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
