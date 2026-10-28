@extends('layouts.admin')

@section('title', __('Backups & Disaster Recovery'))

@section('content')
<div x-data="{
    restoreModalOpen: false,
    deleteModalOpen: false,
    selectedBackup: null,
    selectedBackupFile: '',
    selectedBackupType: '',
    openRestoreModal(filename, type) {
        this.selectedBackupFile = filename;
        this.selectedBackupType = type;
        this.restoreModalOpen = true;
    },
    openDeleteModal(id, filename) {
        this.selectedBackup = id;
        this.selectedBackupFile = filename;
        this.deleteModalOpen = true;
    },
    copiedStep: null,
    copyToClipboard(text, step) {
        navigator.clipboard.writeText(text).then(() => {
            this.copiedStep = step;
            setTimeout(() => { this.copiedStep = null; }, 2000);
        });
    }
}" class="space-y-6">

    {{-- Top Header & Actions --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                {{ __('Backups & Disaster Recovery') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ __('Point-in-time database dumps, media asset archives, and manual disaster recovery runbooks.') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Quick Trigger Database Backup --}}
            <form action="{{ route('admin.backups.store') }}" method="POST"
                  onsubmit="return confirm('{{ __('Create a database backup snapshot now?') }}')">
                @csrf
                <input type="hidden" name="type" value="database">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-teal-700 bg-teal-50 border border-teal-200 rounded-xl hover:bg-teal-100 hover:border-teal-300 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    <span>{{ __('Backup Database') }}</span>
                </button>
            </form>

            {{-- Quick Trigger Full Backup --}}
            <form action="{{ route('admin.backups.store') }}" method="POST"
                  onsubmit="return confirm('{{ __('Create a full backup including database and user uploads? This may take several moments.') }}')">
                @csrf
                <input type="hidden" name="type" value="full">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-xl hover:bg-teal-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    <span>{{ __('Create Full Backup') }}</span>
                </button>
            </form>
        </div>
    </div>

    {{-- System Status Banners --}}
    @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="font-medium">{{ session('status') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="font-medium">{{ session('error') }}</div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Archives --}}
        <div class="p-5 bg-white border border-slate-200/80 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Total Snapshots') }}</span>
                <span class="p-2 rounded-xl bg-teal-50 text-teal-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total_backups']) }}</div>
            <div class="mt-1 text-xs text-slate-500 flex items-center gap-2">
                <span>{{ $stats['db_backups_count'] }} {{ __('DB') }}</span>
                <span>&bull;</span>
                <span>{{ $stats['full_backups_count'] }} {{ __('Full') }}</span>
            </div>
        </div>

        {{-- Storage Used --}}
        <div class="p-5 bg-white border border-slate-200/80 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Storage Occupied') }}</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['formatted_total_size'] }}</div>
            <div class="mt-1 text-xs text-slate-500">
                {{ __('Private disk: storage/app/backups') }}
            </div>
        </div>

        {{-- Last Backup --}}
        <div class="p-5 bg-white border border-slate-200/80 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Latest Snapshot') }}</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-lg font-bold text-slate-900">
                @if($stats['last_backup'])
                    {{ $stats['last_backup']->created_at->diffForHumans() }}
                @else
                    {{ __('No backups yet') }}
                @endif
            </div>
            <div class="mt-1 text-xs text-slate-500 truncate">
                {{ $stats['last_backup'] ? $stats['last_backup']->filename : __('Trigger your first backup above') }}
            </div>
        </div>

        {{-- Retention Policy --}}
        <div class="p-5 bg-white border border-slate-200/80 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Retention Guard') }}</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['retention_limit'] }} {{ __('Copies') }}</div>
            <div class="mt-1 text-xs text-slate-500">
                {{ __('Auto-prunes oldest snapshots') }}
            </div>
        </div>
    </div>

    {{-- Backups Table Card --}}
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">
                {{ __('Backup Archives') }}
            </h2>
            <span class="text-xs font-medium text-slate-500">
                {{ __('Showing') }} {{ $backups->firstItem() ?? 0 }} - {{ $backups->lastItem() ?? 0 }} {{ __('of') }} {{ $backups->total() }}
            </span>
        </div>

        @if($backups->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">{{ __('No backup archives found') }}</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                    {{ __('Create a database snapshot or full archive to safeguard your platform against data loss.') }}
                </p>
                <div class="mt-4">
                    <form action="{{ route('admin.backups.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="database">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-xl hover:bg-teal-700 transition-colors shadow-sm">
                            {{ __('Create First Backup Now') }}
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">{{ __('File Name') }}</th>
                            <th class="py-3 px-4">{{ __('Type') }}</th>
                            <th class="py-3 px-4">{{ __('Size') }}</th>
                            <th class="py-3 px-4">{{ __('Created By') }}</th>
                            <th class="py-3 px-4">{{ __('Created At') }}</th>
                            <th class="py-3 px-4">{{ __('Status') }}</th>
                            <th class="py-3 px-6 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($backups as $backup)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                {{-- File Name --}}
                                <td class="py-4 px-6 font-mono text-xs font-medium text-slate-800">
                                    <div class="flex items-center gap-2.5">
                                        @if($backup->type === 'full')
                                            <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="p-1.5 rounded-lg bg-teal-50 text-teal-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                                                </svg>
                                            </span>
                                        @endif
                                        <span class="truncate max-w-xs" title="{{ $backup->filename }}">{{ $backup->filename }}</span>
                                    </div>
                                </td>

                                {{-- Type --}}
                                <td class="py-4 px-4">
                                    @if($backup->type === 'full')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ __('Full (DB + Media)') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                            {{ __('Database Only') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Size --}}
                                <td class="py-4 px-4 font-medium text-slate-700">
                                    {{ $backup->formatted_size }}
                                </td>

                                {{-- Created By --}}
                                <td class="py-4 px-4 text-slate-600">
                                    @if($backup->admin)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-teal-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($backup->admin->name, 0, 1)) }}
                                            </div>
                                            <span class="text-xs font-medium text-slate-700">{{ $backup->admin->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">{{ __('System Scheduler') }}</span>
                                    @endif
                                </td>

                                {{-- Created At --}}
                                <td class="py-4 px-4 text-xs text-slate-500 whitespace-nowrap">
                                    <div>{{ $backup->created_at->format('M d, Y') }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $backup->created_at->format('H:i:s') }}</div>
                                </td>

                                {{-- Status --}}
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if($backup->status === 'completed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            {{ __('Completed') }}
                                        </span>
                                    @elseif($backup->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('In Progress') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200"
                                              title="{{ $backup->error_message }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            {{ __('Failed') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Download --}}
                                        @if($backup->isCompleted())
                                            <a href="{{ route('admin.backups.download', $backup) }}"
                                               class="p-1.5 text-slate-500 hover:text-teal-600 hover:bg-slate-100 rounded-lg transition-colors"
                                               title="{{ __('Download Archive') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>
                                        @endif

                                        {{-- Disaster Recovery Instructions --}}
                                        <button type="button"
                                                @click="openRestoreModal('{{ $backup->filename }}', '{{ $backup->type }}')"
                                                class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors"
                                                title="{{ __('Disaster Recovery Guide') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>

                                        {{-- Delete --}}
                                        <button type="button"
                                                @click="openDeleteModal({{ $backup->id }}, '{{ $backup->filename }}')"
                                                class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition-colors"
                                                title="{{ __('Delete Archive') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($backups->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $backups->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Disaster Recovery Guide Modal --}}
    <div x-show="restoreModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
             @click="restoreModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden"
                 @click.away="restoreModalOpen = false">
                
                {{-- Header --}}
                <div class="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-xl bg-teal-500/20 text-teal-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold">{{ __('Disaster Recovery Runbook') }}</h3>
                            <p class="text-xs text-slate-300 font-mono mt-0.5" x-text="selectedBackupFile"></p>
                        </div>
                    </div>
                    <button type="button" @click="restoreModalOpen = false" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Safety Alert --}}
                <div class="p-4 mx-6 mt-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1">
                    <div class="font-bold flex items-center gap-2 text-amber-800">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        {{ __('CRITICAL OPERATIONAL SAFETY NOTICE') }}
                    </div>
                    <p class="text-amber-700">
                        {{ __('Do not perform automated in-place restorations on a live database. Follow the official CLI steps below on your server to verify data integrity and prevent data corruption.') }}
                    </p>
                </div>

                {{-- Step-by-Step Instructions --}}
                <div class="p-6 space-y-5 max-h-[60vh] overflow-y-auto">
                    {{-- Step 1 --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                            <span>{{ __('1. Put Application in Maintenance Mode') }}</span>
                            <button type="button"
                                    @click="copyToClipboard('php artisan down --secret=\'wanderly-recovery\'', 1)"
                                    class="text-teal-600 hover:text-teal-700 flex items-center gap-1 text-[11px]">
                                <span x-show="copiedStep !== 1">{{ __('Copy Command') }}</span>
                                <span x-show="copiedStep === 1" class="text-emerald-600 font-bold">{{ __('Copied!') }}</span>
                            </button>
                        </div>
                        <pre class="p-3 bg-slate-900 text-slate-200 font-mono text-xs rounded-xl overflow-x-auto">php artisan down --secret="wanderly-recovery"</pre>
                        <p class="text-[11px] text-slate-500">{{ __('Prevents any user mutations while database tables are being restored.') }}</p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                            <span>{{ __('2. Unzip Archive to Staging Directory') }}</span>
                            <button type="button"
                                    @click="copyToClipboard('unzip storage/app/backups/' + selectedBackupFile + ' -d storage/app/backups/restore_tmp', 2)"
                                    class="text-teal-600 hover:text-teal-700 flex items-center gap-1 text-[11px]">
                                <span x-show="copiedStep !== 2">{{ __('Copy Command') }}</span>
                                <span x-show="copiedStep === 2" class="text-emerald-600 font-bold">{{ __('Copied!') }}</span>
                            </button>
                        </div>
                        <pre class="p-3 bg-slate-900 text-slate-200 font-mono text-xs rounded-xl overflow-x-auto"
                             x-text="'unzip storage/app/backups/' + selectedBackupFile + ' -d storage/app/backups/restore_tmp'"></pre>
                        <p class="text-[11px] text-slate-500">{{ __('Extracts database.sql and optional uploads directory.') }}</p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                            <span>{{ __('3. Import Database Snapshot') }}</span>
                            <button type="button"
                                    @click="copyToClipboard('mysql -h 127.0.0.1 -u root -p wanderly < storage/app/backups/restore_tmp/database.sql', 3)"
                                    class="text-teal-600 hover:text-teal-700 flex items-center gap-1 text-[11px]">
                                <span x-show="copiedStep !== 3">{{ __('Copy Command') }}</span>
                                <span x-show="copiedStep === 3" class="text-emerald-600 font-bold">{{ __('Copied!') }}</span>
                            </button>
                        </div>
                        <pre class="p-3 bg-slate-900 text-slate-200 font-mono text-xs rounded-xl overflow-x-auto">mysql -h 127.0.0.1 -u root -p wanderly &lt; storage/app/backups/restore_tmp/database.sql</pre>
                        <p class="text-[11px] text-slate-500">{{ __('Recreates tables and restores all records with foreign keys checked.') }}</p>
                    </div>

                    {{-- Step 4 (If Full) --}}
                    <template x-if="selectedBackupType === 'full'">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                                <span>{{ __('4. Restore User Uploads (Avatars, Covers & POIs)') }}</span>
                                <button type="button"
                                        @click="copyToClipboard('cp -r storage/app/backups/restore_tmp/uploads/* storage/app/public/', 4)"
                                        class="text-teal-600 hover:text-teal-700 flex items-center gap-1 text-[11px]">
                                    <span x-show="copiedStep !== 4">{{ __('Copy Command') }}</span>
                                    <span x-show="copiedStep === 4" class="text-emerald-600 font-bold">{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <pre class="p-3 bg-slate-900 text-slate-200 font-mono text-xs rounded-xl overflow-x-auto">cp -r storage/app/backups/restore_tmp/uploads/* storage/app/public/</pre>
                            <p class="text-[11px] text-slate-500">{{ __('Synchronizes media files back into public storage.') }}</p>
                        </div>
                    </template>

                    {{-- Step 5 --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                            <span>{{ __('5. Clear Application Caches & Bring Online') }}</span>
                            <button type="button"
                                    @click="copyToClipboard('php artisan optimize:clear && php artisan up', 5)"
                                    class="text-teal-600 hover:text-teal-700 flex items-center gap-1 text-[11px]">
                                <span x-show="copiedStep !== 5">{{ __('Copy Command') }}</span>
                                <span x-show="copiedStep === 5" class="text-emerald-600 font-bold">{{ __('Copied!') }}</span>
                            </button>
                        </div>
                        <pre class="p-3 bg-slate-900 text-slate-200 font-mono text-xs rounded-xl overflow-x-auto">php artisan optimize:clear && php artisan up</pre>
                        <p class="text-[11px] text-slate-500">{{ __('Refreshes cached configs and resumes user access.') }}</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="button"
                            @click="restoreModalOpen = false"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                        {{ __('Close Runbook') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
             @click="deleteModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 overflow-hidden"
                 @click.away="deleteModalOpen = false">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-2xl bg-rose-50 text-rose-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ __('Delete Backup Archive') }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('This action will permanently delete the archive from disk.') }}</p>
                    </div>
                </div>

                <div class="mt-4 p-3 rounded-xl bg-slate-50 font-mono text-xs text-slate-700 truncate"
                     x-text="selectedBackupFile"></div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="deleteModalOpen = false"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                        {{ __('Cancel') }}
                    </button>

                    <form :action="'{{ url('/admin/backups') }}/' + selectedBackup" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors shadow-sm">
                            {{ __('Permanently Delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
