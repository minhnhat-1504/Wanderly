@extends('layouts.admin')

@section('title', __('Audit Logs'))
@section('header', __('System Audit Trail'))

@section('content')
<div class="space-y-6" x-data="{ detailsModalOpen: false, modalTitle: '', modalOld: null, modalNew: null }">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ __('Administrative Audit Logs') }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ __('Immutable audit trail of all sensitive administrative modifications and system actions.') }}
            </p>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Administrator') }}</label>
                <select name="admin_id" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="">{{ __('All Admins') }}</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                            {{ $admin->name }} ({{ $admin->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Action') }}</label>
                <input type="text" name="action" value="{{ request('action') }}" placeholder="{{ __('e.g. suspend_user, role_change') }}"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('From Date') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('To Date') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm">
                    {{ __('Filter') }}
                </button>
                <a href="{{ route('admin.audit-logs.index') }}" class="py-2 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Audit Logs Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        @if($logs->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-sm font-medium">{{ __('No audit log records match your filter criteria.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-6">{{ __('Timestamp') }}</th>
                            <th class="py-3.5 px-6">{{ __('Administrator') }}</th>
                            <th class="py-3.5 px-6">{{ __('Action') }}</th>
                            <th class="py-3.5 px-6">{{ __('Target Subject') }}</th>
                            <th class="py-3.5 px-6">{{ __('IP Address') }}</th>
                            <th class="py-3.5 px-6 text-right">{{ __('Data Diff') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($logs as $log)
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
                                    <div>
                                        <p class="font-semibold text-slate-900 text-xs">{{ $log->admin->name ?? __('Unknown') }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $log->admin->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 font-mono">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-600 whitespace-nowrap">
                                @if($log->subject_type)
                                    <span class="font-mono text-slate-500 font-semibold">{{ class_basename($log->subject_type) }}</span>
                                    <span class="text-slate-400">#{{ $log->subject_id }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-500 font-mono whitespace-nowrap">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                @if(!empty($log->old_values) || !empty($log->new_values))
                                    <button @click="modalTitle = '{{ $log->action }} #{{ $log->id }}'; modalOld = {{ json_encode($log->old_values) }}; modalNew = {{ json_encode($log->new_values) }}; detailsModalOpen = true;"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-teal-700 bg-teal-50 hover:bg-teal-100 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>{{ __('View Payload') }}</span>
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400 italic">{{ __('None') }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Payload Diff Modal --}}
    <div x-show="detailsModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="detailsModalOpen"
                 @click="detailsModalOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="detailsModalOpen"
                 class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100 p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900" x-text="modalTitle"></h3>
                        <p class="text-xs text-slate-400">{{ __('Inspect old values vs new values payload recorded for this action') }}</p>
                    </div>
                    <button @click="detailsModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                    <div>
                        <h4 class="text-xs font-bold text-rose-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            {{ __('Previous State (Old)') }}
                        </h4>
                        <pre class="bg-rose-50/50 text-rose-950 p-3.5 rounded-2xl text-[11px] font-mono overflow-x-auto border border-rose-100 h-64"
                             x-text="modalOld ? JSON.stringify(modalOld, null, 2) : '{{ __('(empty)') }}'"></pre>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            {{ __('Updated State (New)') }}
                        </h4>
                        <pre class="bg-emerald-50/50 text-emerald-950 p-3.5 rounded-2xl text-[11px] font-mono overflow-x-auto border border-emerald-100 h-64"
                             x-text="modalNew ? JSON.stringify(modalNew, null, 2) : '{{ __('(empty)') }}'"></pre>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="detailsModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
