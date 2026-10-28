@extends('layouts.admin')

@section('title', __('System Broadcasts'))
@section('header', __('System Announcements'))

@section('content')
<div class="space-y-6">

    {{-- Header & Subtext --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ __('Broadcast Announcements') }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ __('Transmit platform-wide or role-targeted announcements and track user read engagement.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.broadcasts.create') }}"
               class="inline-flex items-center gap-2 py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                {{ __('Compose New Broadcast') }}
            </a>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>{{ __('Total Broadcasts') }}</span>
                <span class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($stats['total_broadcasts']) }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ __('All-time announcement campaigns') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>{{ __('Delivered Notices') }}</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-blue-600 mt-2">{{ number_format($stats['total_delivered']) }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ __('Dispatched via chunked queues') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>{{ __('Read Notices') }}</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-emerald-600 mt-2">{{ number_format($stats['total_read']) }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ __('Confirmed user reads') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <span>{{ __('Read Engagement') }}</span>
                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-purple-600 mt-2">{{ $stats['read_rate'] }}%</div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ min(100, $stats['read_rate']) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.broadcasts.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Target Audience') }}</label>
                <select name="target_audience" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="">{{ __('All Audiences') }}</option>
                    <option value="all" {{ ($filters['target_audience'] ?? '') === 'all' ? 'selected' : '' }}>{{ __('All Community Users') }}</option>
                    <option value="travelers" {{ ($filters['target_audience'] ?? '') === 'travelers' ? 'selected' : '' }}>{{ __('Travelers Only') }}</option>
                    <option value="providers" {{ ($filters['target_audience'] ?? '') === 'providers' ? 'selected' : '' }}>{{ __('Service Providers Only') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Search Announcement') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search title or message...') }}"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm">
                    {{ __('Apply Filters') }}
                </button>
                <a href="{{ route('admin.broadcasts.index') }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Broadcasts Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">{{ __('Broadcast #') }}</th>
                        <th class="py-3.5 px-4">{{ __('Title & Message') }}</th>
                        <th class="py-3.5 px-4">{{ __('Target Audience') }}</th>
                        <th class="py-3.5 px-4 text-center">{{ __('Recipients') }}</th>
                        <th class="py-3.5 px-4">{{ __('Read Engagement') }}</th>
                        <th class="py-3.5 px-4">{{ __('Sent Date') }}</th>
                        <th class="py-3.5 px-4">{{ __('Dispatched By') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($broadcasts as $broadcast)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                #{{ $broadcast->id }}
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-slate-900 truncate mb-0.5">
                                    <a href="{{ route('admin.broadcasts.show', $broadcast->id) }}" class="hover:text-teal-600 transition-colors">
                                        {{ $broadcast->title }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-500 line-clamp-1">{{ $broadcast->message }}</div>
                                @if($broadcast->action_url)
                                    <div class="text-[10px] text-teal-600 font-mono mt-0.5 truncate" title="{{ $broadcast->action_url }}">
                                        &rarr; {{ $broadcast->action_url }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($broadcast->target_audience === 'travelers')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">
                                        {{ __('Travelers') }}
                                    </span>
                                @elseif($broadcast->target_audience === 'providers')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                        {{ __('Providers') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                        {{ __('All Users') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-800">
                                {{ number_format($broadcast->recipients_count) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="w-32">
                                    <div class="flex items-center justify-between text-[10px] mb-1">
                                        <span class="font-semibold text-slate-700">{{ number_format($broadcast->read_count) }} / {{ number_format($broadcast->recipients_count) }}</span>
                                        <span class="text-slate-500">{{ $broadcast->read_rate }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-teal-600 h-1.5 rounded-full" style="width: {{ min(100, $broadcast->read_rate) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                {{ $broadcast->sent_at ? $broadcast->sent_at->format('M d, Y H:i') : '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-800">
                                {{ $broadcast->admin->name ?? __('System') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.broadcasts.show', $broadcast->id) }}"
                                   class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition-colors">
                                    {{ __('Details') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                </div>
                                <div class="font-semibold text-slate-700 text-sm">{{ __('No broadcast announcements sent yet') }}</div>
                                <p class="text-xs text-slate-400 mt-1">{{ __('Transmit your first announcement to keep travelers and providers informed.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($broadcasts->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $broadcasts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
