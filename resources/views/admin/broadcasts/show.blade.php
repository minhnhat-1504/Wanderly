@extends('layouts.admin')

@section('title', __('Broadcast #:id Details', ['id' => $broadcast->id]))
@section('header', __('Broadcast Campaign Performance'))

@section('content')
<div class="space-y-6">

    {{-- Back Link & Navigation --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.broadcasts.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-teal-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('Back to Broadcasts') }}
        </a>
    </div>

    {{-- Header Banner --}}
    <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-xs font-mono font-bold text-slate-400">#{{ $broadcast->id }}</span>
                @if($broadcast->target_audience === 'travelers')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800">
                        {{ __('Travelers Audience') }}
                    </span>
                @elseif($broadcast->target_audience === 'providers')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                        {{ __('Service Providers Audience') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                        {{ __('All Community Users') }}
                    </span>
                @endif
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="text-xs text-slate-500">
                    {{ __('Dispatched :date', ['date' => $broadcast->sent_at ? $broadcast->sent_at->format('M d, Y H:i:s') : '—']) }}
                </span>
            </div>
            <h1 class="text-xl font-bold text-slate-900">{{ $broadcast->title }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ __('Dispatched by Administrator') }} <span class="font-bold text-slate-700">{{ $broadcast->admin->name ?? __('System') }}</span>
            </p>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Total Target Users') }}</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($broadcast->recipients_count) }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ __('Recipients queued') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Delivered Notices') }}</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($broadcast->notifications_count) }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ __('Inserted in notifications table') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Confirmed Reads') }}</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($readCount) }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ __('Read by recipients') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Read Rate') }}</div>
            <div class="text-2xl font-bold text-purple-600 mt-1">{{ $broadcast->read_rate }}%</div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ min(100, $broadcast->read_rate) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Message Body Card --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">{{ __('Delivered Message Content') }}</h3>
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-700 leading-relaxed whitespace-pre-line">
            {{ $broadcast->message }}
        </div>

        @if($broadcast->action_url)
            <div class="flex items-center gap-2 text-xs">
                <span class="font-semibold text-slate-500">{{ __('Attached Action URL:') }}</span>
                <a href="{{ $broadcast->action_url }}" target="_blank" rel="noopener noreferrer" class="text-teal-600 hover:text-teal-700 font-mono underline break-all">
                    {{ $broadcast->action_url }}
                </a>
            </div>
        @endif
    </div>

    {{-- Sample Delivered Recipients --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">{{ __('Sample Delivery Status') }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('Recent recipient deliveries and individual read timestamps.') }}</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">{{ __('Displaying up to 25 records') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">{{ __('Recipient') }}</th>
                        <th class="py-3 px-4">{{ __('Role') }}</th>
                        <th class="py-3 px-4">{{ __('Delivery Status') }}</th>
                        <th class="py-3 px-4">{{ __('Read Timestamp') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($sampleRecipients as $notif)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4">
                                @if($notif->user)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $notif->user->avatar_url }}" alt="{{ $notif->user->name }}" class="w-6 h-6 rounded-full object-cover border border-slate-200">
                                        <div>
                                            <a href="{{ route('admin.users.show', $notif->user->id) }}" class="font-bold text-slate-900 hover:text-teal-600">
                                                {{ $notif->user->name }}
                                            </a>
                                            <div class="text-[10px] text-slate-400 font-mono">&#64;{{ $notif->user->username }} &bull; {{ $notif->user->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">{{ __('Unknown user') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="capitalize text-slate-600">{{ $notif->user->role ?? '—' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($notif->isRead())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        {{ __('Read') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        {{ __('Unread') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                {{ $notif->read_at ? $notif->read_at->format('M d, Y H:i:s') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                {{ __('No recipients recorded yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
