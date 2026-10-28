@extends('layouts.admin')

@section('title', __('Dashboard'))
@section('header', __('System Overview'))

@section('content')
<div class="space-y-8">

    {{-- Welcome Banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-slate-800">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-500/20 text-teal-300 border border-teal-500/30 mb-3">
                <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                {{ __('Wanderly Platform Operations') }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                {{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}
            </h1>
            <p class="text-slate-300 text-sm mt-2 leading-relaxed">
                {{ __('Monitor traveler security, community moderation, system backups, and full audit logs from this central dashboard.') }}
            </p>
        </div>
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-80 h-80 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- ================================================================
         4 KPI CARDS
    ================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- Card 1: Total Users --}}
        <a href="{{ route('admin.users.index') }}"
           class="group bg-white rounded-2xl p-5 border border-slate-200/80 hover:border-teal-500/60 shadow-sm hover:shadow-lg transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Total Users') }}</span>
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($totalUsers) }}</p>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-teal-600 font-semibold">{{ __('All registered accounts') }}</span>
                    <span>&rarr;</span>
                </p>
            </div>
        </a>

        {{-- Card 2: New Users (Last 7 Days) --}}
        <a href="{{ route('admin.users.index') }}"
           class="group bg-white rounded-2xl p-5 border border-slate-200/80 hover:border-emerald-500/60 shadow-sm hover:shadow-lg transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('New Users (7 Days)') }}</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($newUsers7d) }}</p>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-emerald-600 font-semibold">{{ __('Recent registrations') }}</span>
                    <span>&rarr;</span>
                </p>
            </div>
        </a>

        {{-- Card 3: Pending Reports --}}
        <a href="{{ route('admin.moderation.index') }}"
           class="group bg-white rounded-2xl p-5 border border-slate-200/80 hover:border-amber-500/60 shadow-sm hover:shadow-lg transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Pending Reports') }}</span>
                <div class="w-10 h-10 rounded-xl {{ $pendingReports > 0 ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-3xl font-extrabold {{ $pendingReports > 0 ? 'text-amber-700' : 'text-slate-900' }} tracking-tight">
                    {{ number_format($pendingReports) }}
                </p>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="{{ $pendingReports > 0 ? 'text-amber-700 font-semibold' : 'text-slate-500' }}">
                        {{ $pendingReports > 0 ? __('Requires moderation') : __('Queue is clear') }}
                    </span>
                    <span>&rarr;</span>
                </p>
            </div>
        </a>

        {{-- Card 4: Latest Backup --}}
        <a href="{{ route('admin.backups.index') }}"
           class="group bg-white rounded-2xl p-5 border border-slate-200/80 hover:border-indigo-500/60 shadow-sm hover:shadow-lg transition-all duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Latest Backup') }}</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                @if($latestBackup)
                    <p class="text-xl font-extrabold text-slate-900 truncate">
                        {{ \Carbon\Carbon::parse($latestBackup->completed_at ?? $latestBackup->created_at)->diffForHumans() }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1 truncate">
                        {{ strtoupper($latestBackup->type) }} • {{ round(($latestBackup->size_bytes ?? 0) / (1024 * 1024), 2) }} MB
                    </p>
                @else
                    <p class="text-xl font-bold text-slate-400 italic">{{ __('None yet') }}</p>
                    <p class="text-xs text-indigo-600 font-semibold mt-1">{{ __('Schedule or trigger now &rarr;') }}</p>
                @endif
            </div>
        </a>
    </div>

    {{-- ================================================================
         RECENT AUDIT TRAIL STREAM
    ================================================================= --}}
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Recent Administrative Activity') }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('Real-time log of administrative actions across all system modules.') }}</p>
            </div>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700 hover:underline">
                {{ __('View Full Audit Trail &rarr;') }}
            </a>
        </div>

        @if($recentActivities->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-sm font-medium">{{ __('No audit log records recorded yet.') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ __('Any administrative action will be immutably recorded here.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-6">{{ __('Timestamp') }}</th>
                            <th class="py-3.5 px-6">{{ __('Administrator') }}</th>
                            <th class="py-3.5 px-6">{{ __('Action') }}</th>
                            <th class="py-3.5 px-6">{{ __('Target') }}</th>
                            <th class="py-3.5 px-6">{{ __('IP Address') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentActivities as $log)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-6 text-xs text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                <span class="text-[10px] text-slate-400 block">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="py-3.5 px-6 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-teal-100 text-teal-800 font-bold flex items-center justify-center text-[10px]">
                                        {{ strtoupper(substr($log->admin->name ?? 'A', 0, 1)) }}
                                    </div>
                                    <span class="font-semibold text-slate-900 text-xs">{{ $log->admin->name ?? __('Unknown') }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 font-mono">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-600 whitespace-nowrap">
                                @if($log->subject_type)
                                    <span class="font-mono text-slate-500">{{ class_basename($log->subject_type) }}</span> #{{ $log->subject_id }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-500 font-mono whitespace-nowrap">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
