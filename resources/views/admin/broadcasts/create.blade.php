@extends('layouts.admin')

@section('title', __('Compose Broadcast'))
@section('header', __('New System Announcement'))

@section('content')
<div class="space-y-6" x-data="{
    title: '',
    message: '',
    audience: 'all',
    actionUrl: '',
    audienceCounts: {
        all: {{ $stats['all_users_count'] }},
        travelers: {{ $stats['travelers_count'] }},
        providers: {{ $stats['providers_count'] }}
    }
}">

    {{-- Back Link --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.broadcasts.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-teal-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('Back to Broadcasts') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Left Form (7 cols) --}}
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-bold text-slate-900">{{ __('Compose Announcement') }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ __('Dispatched notifications will be delivered in 500-recipient queued chunks into users\' inboxes.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('admin.broadcasts.store') }}" class="space-y-5">
                @csrf

                @if ($errors->any())
                    <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs space-y-1">
                        <div class="font-bold">{{ __('Please correct the following errors:') }}</div>
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Target Audience Cards --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">{{ __('Target Audience') }}</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                               :class="audience === 'all' ? 'border-teal-500 bg-teal-50/20 ring-2 ring-teal-500/10' : 'border-slate-200 hover:bg-slate-50'">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="target_audience" value="all" x-model="audience" class="text-teal-600 focus:ring-teal-500">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-700">
                                    <span x-text="audienceCounts.all"></span>
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="font-bold text-xs text-slate-900">{{ __('All Users') }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ __('Every active account') }}</div>
                            </div>
                        </label>

                        <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                               :class="audience === 'travelers' ? 'border-teal-500 bg-teal-50/20 ring-2 ring-teal-500/10' : 'border-slate-200 hover:bg-slate-50'">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="target_audience" value="travelers" x-model="audience" class="text-teal-600 focus:ring-teal-500">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-teal-100 text-teal-700">
                                    <span x-text="audienceCounts.travelers"></span>
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="font-bold text-xs text-slate-900">{{ __('Travelers') }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ __('Trip planners only') }}</div>
                            </div>
                        </label>

                        <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                               :class="audience === 'providers' ? 'border-teal-500 bg-teal-50/20 ring-2 ring-teal-500/10' : 'border-slate-200 hover:bg-slate-50'">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="target_audience" value="providers" x-model="audience" class="text-teal-600 focus:ring-teal-500">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">
                                    <span x-text="audienceCounts.providers"></span>
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="font-bold text-xs text-slate-900">{{ __('Providers') }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ __('Verified vendors only') }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Title --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700">{{ __('Announcement Title') }}</label>
                        <span class="text-[10px] text-slate-400" x-text="title.length + ' / 255'"></span>
                    </div>
                    <input type="text" name="title" x-model="title" required minlength="3" maxlength="255"
                           placeholder="{{ __('e.g. System Maintenance Notice / Lunar New Year Deals!') }}"
                           class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                </div>

                {{-- Message --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700">{{ __('Message Content') }}</label>
                        <span class="text-[10px] text-slate-400" x-text="message.length + ' / 2000 (min 10)'"></span>
                    </div>
                    <textarea name="message" x-model="message" required minlength="10" maxlength="2000" rows="5"
                              placeholder="{{ __('Write the full text of your announcement here...') }}"
                              class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20"></textarea>
                </div>

                {{-- Action URL --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        {{ __('Action URL / Deep Link (Optional)') }}
                    </label>
                    <input type="text" name="action_url" x-model="actionUrl" maxlength="500"
                           placeholder="{{ __('https://wanderly.test/social or /trips') }}"
                           class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <p class="text-[10px] text-slate-400 mt-1">
                        {{ __('Recipients clicking on this notification in their dropdown panel will be redirected to this link.') }}
                    </p>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3 border-t border-slate-100">
                    <a href="{{ route('admin.broadcasts.index') }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit"
                            id="btn-dispatch-broadcast"
                            class="py-2.5 px-5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>{{ __('Dispatch Announcement') }}</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right Live Preview (5 cols) --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">{{ __('Live User Notification Preview') }}</h3>
                    </div>
                    <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-mono">{{ __('Bell Dropdown') }}</span>
                </div>

                {{-- Mock Notification Item --}}
                <div class="p-4 bg-slate-50/75 rounded-2xl border border-slate-200/80 space-y-2">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-teal-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-900 truncate" x-text="title || '{{ __('Announcement Title') }}'"></h4>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap ml-2">{{ __('Just now') }}</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed whitespace-pre-line" x-text="message || '{{ __('Your announcement text will appear here exactly as recipients will read it in their navigation bell...') }}'"></p>

                            <template x-if="actionUrl">
                                <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center gap-1 text-[11px] text-teal-600 font-semibold">
                                    <span>{{ __('Open Link') }}</span>
                                    <span>&rarr;</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Delivery Estimate Note --}}
                <div class="p-3 bg-teal-50/60 rounded-xl border border-teal-100 text-[11px] text-teal-800 mt-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>
                        {{ __('Estimated delivery to') }} <strong x-text="audienceCounts[audience]"></strong> {{ __('users in batches of 500.') }}
                    </span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
