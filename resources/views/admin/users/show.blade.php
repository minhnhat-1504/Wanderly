@extends('layouts.admin')

@section('title', __('User Details — :name', ['name' => $user->name]))
@section('header', __('User Profile & Activity'))

@section('content')
<div class="space-y-6" x-data="{
    roleModalOpen: false,
    suspendModalOpen: false,
    unsuspendModalOpen: false,
    deleteModalOpen: false,
    suspendReason: '',
    logDiffModalOpen: false,
    diffTitle: '',
    diffOld: null,
    diffNew: null,

    viewDiff(title, oldVals, newVals) {
        this.diffTitle = title;
        this.diffOld = oldVals;
        this.diffNew = newVals;
        this.logDiffModalOpen = true;
    }
}">

    {{-- Back Link & Navigation --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-teal-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('Back to Users') }}
        </a>

        {{-- Action Bar --}}
        <div class="flex items-center gap-2">
            @if(!$user->trashed())
                @if($user->id !== auth()->id())
                    <button type="button" @click="roleModalOpen = true"
                            class="py-2 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold shadow-sm transition-colors">
                        {{ __('Change Role') }}
                    </button>
                @endif

                @if($user->isSuspended())
                    <button type="button" @click="unsuspendModalOpen = true"
                            class="py-2 px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                        {{ __('Unsuspend Account') }}
                    </button>
                @elseif($user->id !== auth()->id())
                    <button type="button" @click="suspendModalOpen = true"
                            class="py-2 px-3.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 rounded-xl text-xs font-semibold transition-colors">
                        {{ __('Suspend Account') }}
                    </button>
                @endif

                @if($user->id !== auth()->id())
                    <button type="button" @click="deleteModalOpen = true"
                            class="py-2 px-3.5 bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-700 rounded-xl text-xs font-semibold transition-colors">
                        {{ __('Delete User') }}
                    </button>
                @endif
            @else
                <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="py-2 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                        {{ __('Restore Account') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Suspension Alert Banner --}}
    @if($user->isSuspended())
        <div class="p-4 bg-rose-50 rounded-2xl border border-rose-200 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-xs font-bold text-rose-900 uppercase tracking-wider">{{ __('Account Currently Suspended') }}</h3>
                <p class="text-xs text-rose-700 mt-0.5">
                    <span class="font-semibold">{{ __('Reason:') }}</span> {{ $user->suspended_reason }}
                </p>
                @if($user->suspended_at)
                    <p class="text-[10px] text-rose-500 mt-1">
                        {{ __('Suspended on:') }} {{ $user->suspended_at->format('M d, Y H:i:s') }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- Trashed Banner --}}
    @if($user->trashed())
        <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">{{ __('Account is Soft Deleted') }}</h3>
                <p class="text-xs text-amber-700 mt-0.5">
                    {{ __('Deleted at:') }} {{ $user->deleted_at->format('M d, Y H:i:s') }}. {{ __('User cannot log in or participate until restored.') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Profile Overview Card --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover border-2 border-slate-200 shadow-sm shrink-0">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                    @if($user->role === 'admin')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
                            {{ __('Administrator') }}
                        </span>
                    @elseif($user->role === 'provider')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">
                            {{ __('Service Provider') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                            {{ __('Traveler') }}
                        </span>
                    @endif

                    @if($user->email_verified_at)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                            {{ __('Verified Email') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            {{ __('Unverified') }}
                        </span>
                    @endif
                </div>

                <div class="text-xs text-slate-500 flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span>{{ $user->email }}</span>
                    <span>&bull;</span>
                    <span class="font-mono">&#64;{{ $user->username }}</span>
                    @if($user->phone)
                        <span>&bull;</span>
                        <span>{{ $user->phone }}</span>
                    @endif
                    @if($user->location)
                        <span>&bull;</span>
                        <span>{{ $user->location }}</span>
                    @endif
                </div>

                @if($user->bio)
                    <p class="text-xs text-slate-600 mt-3 p-3 bg-slate-50 rounded-xl border border-slate-100 italic">
                        "{{ $user->bio }}"
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Activity Statistics --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Trips Created') }}</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($user->trips_count) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Community Posts') }}</div>
            <div class="text-2xl font-bold text-teal-600 mt-1">{{ number_format($user->posts_count) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Comments') }}</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($user->comments_count) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Bookings') }}</div>
            <div class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($bookingsCount) }}</div>
        </div>
    </div>

    {{-- Profile Details Grid --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">{{ __('Account Specifications') }}</h3>

        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
            <div>
                <dt class="font-semibold text-slate-500">{{ __('User ID') }}</dt>
                <dd class="text-slate-900 font-mono mt-0.5">#{{ $user->id }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">{{ __('Registration Date') }}</dt>
                <dd class="text-slate-900 mt-0.5">{{ $user->created_at ? $user->created_at->format('M d, Y H:i:s') : '—' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">{{ __('Last Account Update') }}</dt>
                <dd class="text-slate-900 mt-0.5">{{ $user->updated_at ? $user->updated_at->format('M d, Y H:i:s') : '—' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">{{ __('Preferred Locale') }}</dt>
                <dd class="text-slate-900 mt-0.5 uppercase">{{ $user->locale ?? 'en' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">{{ __('Preferred Currency') }}</dt>
                <dd class="text-slate-900 mt-0.5 uppercase">{{ $user->currency ?? 'VND' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">{{ __('Public Profile Visibility') }}</dt>
                <dd class="text-slate-900 mt-0.5">{{ $user->is_profile_public ? __('Public') : __('Private') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Related Audit Logs History --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">{{ __('Related Administrative Audit Trail') }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('Log events where this user was either modified or acted as administrator.') }}</p>
            </div>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700">
                {{ __('View Full Trail') }} &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">{{ __('Timestamp') }}</th>
                        <th class="py-3 px-4">{{ __('Administrator') }}</th>
                        <th class="py-3 px-4">{{ __('Action') }}</th>
                        <th class="py-3 px-4">{{ __('IP Address') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Data Diff') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->format('M d, Y H:i:s') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($log->admin)
                                    <span class="font-semibold text-slate-900">{{ $log->admin->name }}</span>
                                @else
                                    <span class="text-slate-400 italic">{{ __('System / Unknown') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 text-slate-700">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if($log->old_values || $log->new_values)
                                    <button type="button"
                                            @click="viewDiff('{{ $log->action }}', {{ json_encode($log->old_values) }}, {{ json_encode($log->new_values) }})"
                                            class="text-teal-600 hover:text-teal-700 font-semibold text-[11px]">
                                        {{ __('View Diff') }}
                                    </button>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                {{ __('No audit logs recorded for this user yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL: Change Role --}}
    <div x-show="roleModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="roleModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form action="{{ route('admin.users.role', $user->id) }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">{{ __('Modify User Role') }}</h3>
                            <button type="button" @click="roleModalOpen = false" class="text-slate-400 hover:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ __('Select Role') }}</label>
                            <select name="role" required class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                                <option value="traveler" {{ $user->role === 'traveler' ? 'selected' : '' }}>{{ __('Traveler (Standard Community Member)') }}</option>
                                <option value="provider" {{ $user->role === 'provider' ? 'selected' : '' }}>{{ __('Provider (Tourism Service Vendor)') }}</option>
                                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>{{ __('Admin (Full Platform Authority)') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex justify-end gap-2 border-t border-slate-200/80">
                        <button type="button" @click="roleModalOpen = false" class="py-2 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-sm">
                            {{ __('Update Role') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Suspend User --}}
    <div x-show="suspendModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="suspendModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form action="{{ route('admin.users.suspend', $user->id) }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">{{ __('Suspend User Account') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Terminate user sessions and block login access.') }}</p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700">{{ __('Suspension Reason (Required)') }}</label>
                                <span class="text-[10px] text-slate-400" x-text="suspendReason.length + ' / 500 (min 10)'"></span>
                            </div>
                            <textarea name="reason" x-model="suspendReason" required minlength="10" maxlength="500" rows="3"
                                      placeholder="{{ __('Describe why this account is being suspended (at least 10 characters)...') }}"
                                      class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500/20"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex justify-end gap-2 border-t border-slate-200/80">
                        <button type="button" @click="suspendModalOpen = false" class="py-2 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                                :disabled="suspendReason.trim().length < 10"
                                class="py-2 px-4 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-semibold shadow-sm">
                            {{ __('Confirm Suspension') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Delete User --}}
    <div x-show="deleteModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="deleteModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">{{ __('Soft Delete Account') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Deactivate account with restoration capability.') }}</p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ __('Are you sure you want to delete this account? The user will lose access immediately and their active sessions will be terminated.') }}
                        </p>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex justify-end gap-2 border-t border-slate-200/80">
                        <button type="button" @click="deleteModalOpen = false" class="py-2 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="py-2 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-sm">
                            {{ __('Delete Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Diff Inspector --}}
    <div x-show="logDiffModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="logDiffModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('Audit Diff Payload') }}</h3>
                            <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="diffTitle"></p>
                        </div>
                        <button type="button" @click="logDiffModalOpen = false" class="text-slate-400 hover:text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider mb-1">{{ __('Old Values (Previous)') }}</div>
                            <pre class="bg-rose-50/50 border border-rose-200/80 rounded-xl p-3 text-[11px] font-mono text-rose-900 overflow-x-auto max-h-60"
                                 x-text="diffOld ? JSON.stringify(diffOld, null, 2) : '{{ __('No previous payload') }}'"></pre>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider mb-1">{{ __('New Values (Updated)') }}</div>
                            <pre class="bg-emerald-50/50 border border-emerald-200/80 rounded-xl p-3 text-[11px] font-mono text-emerald-900 overflow-x-auto max-h-60"
                                 x-text="diffNew ? JSON.stringify(diffNew, null, 2) : '{{ __('No new payload') }}'"></pre>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-3 flex justify-end border-t border-slate-200/80">
                    <button type="button" @click="logDiffModalOpen = false" class="py-2 px-4 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-semibold">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Unsuspend User --}}
    <div x-show="unsuspendModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="unsuspendModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form action="{{ route('admin.users.unsuspend', $user->id) }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">{{ __('Lift Account Suspension') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Restore login access for this account.') }}</p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ __('Are you sure you want to lift suspension for') }} <span class="font-bold text-slate-900">{{ $user->name }}</span>?
                            {{ __('The user will be immediately allowed to log back in and use all platform features.') }}
                        </p>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex justify-end gap-2 border-t border-slate-200/80">
                        <button type="button" @click="unsuspendModalOpen = false" class="py-2 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="py-2 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm">
                            {{ __('Confirm Unsuspend') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
