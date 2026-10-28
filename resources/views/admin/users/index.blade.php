@extends('layouts.admin')

@section('title', __('User Management'))
@section('header', __('Platform Users'))

@section('content')
<div class="space-y-6" x-data="{
    roleModalOpen: false,
    roleTargetUser: null,
    roleTargetName: '',
    roleSelected: '',

    suspendModalOpen: false,
    suspendTargetUser: null,
    suspendTargetName: '',
    suspendReason: '',

    unsuspendModalOpen: false,
    unsuspendTargetUser: null,
    unsuspendTargetName: '',

    openRoleModal(user) {
        this.roleTargetUser = user.id;
        this.roleTargetName = user.name;
        this.roleSelected = user.role;
        this.roleModalOpen = true;
    },

    openSuspendModal(user) {
        this.suspendTargetUser = user.id;
        this.suspendTargetName = user.name;
        this.suspendReason = '';
        this.suspendModalOpen = true;
    },

    openUnsuspendModal(user) {
        this.unsuspendTargetUser = user.id;
        this.unsuspendTargetName = user.name;
        this.unsuspendModalOpen = true;
    }
}">

    {{-- Breadcrumb & Stats Overview --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ __('User Accounts & Access') }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ __('Manage platform travelers, service providers, and administrators with role assignment and security controls.') }}
            </p>
        </div>
    </div>

    {{-- KPI Pills --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="{{ route('admin.users.index', ['status' => 'all']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['status'] ?? 'all') === 'all' && empty($filters['role']) ? 'border-teal-500 ring-2 ring-teal-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Total') }}</div>
            <div class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['total']) }}</div>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'traveler']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['role'] ?? '') === 'traveler' ? 'border-teal-500 ring-2 ring-teal-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Travelers') }}</div>
            <div class="text-xl font-bold text-teal-600 mt-0.5">{{ number_format($stats['travelers']) }}</div>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'provider']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['role'] ?? '') === 'provider' ? 'border-teal-500 ring-2 ring-teal-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Providers') }}</div>
            <div class="text-xl font-bold text-indigo-600 mt-0.5">{{ number_format($stats['providers']) }}</div>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'admin']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['role'] ?? '') === 'admin' ? 'border-teal-500 ring-2 ring-teal-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Admins') }}</div>
            <div class="text-xl font-bold text-purple-600 mt-0.5">{{ number_format($stats['admins']) }}</div>
        </a>
        <a href="{{ route('admin.users.index', ['status' => 'suspended']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['status'] ?? '') === 'suspended' ? 'border-rose-500 ring-2 ring-rose-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Suspended') }}</div>
            <div class="text-xl font-bold text-rose-600 mt-0.5">{{ number_format($stats['suspended']) }}</div>
        </a>
        <a href="{{ route('admin.users.index', ['status' => 'trashed']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['status'] ?? '') === 'trashed' ? 'border-amber-500 ring-2 ring-amber-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Deleted') }}</div>
            <div class="text-xl font-bold text-amber-600 mt-0.5">{{ number_format($stats['trashed']) }}</div>
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Search User') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Name, email, or @username') }}"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Role') }}</label>
                <select name="role" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="">{{ __('All Roles') }}</option>
                    <option value="traveler" {{ request('role') === 'traveler' ? 'selected' : '' }}>{{ __('Traveler') }}</option>
                    <option value="provider" {{ request('role') === 'provider' ? 'selected' : '' }}>{{ __('Provider') }}</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Account Status') }}</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>{{ __('All Active / Suspended') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>{{ __('Suspended Only') }}</option>
                    <option value="trashed" {{ request('status') === 'trashed' ? 'selected' : '' }}>{{ __('Deleted (Soft Trashed)') }}</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm">
                    {{ __('Apply Filters') }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">{{ __('User') }}</th>
                        <th class="py-3.5 px-4">{{ __('Role') }}</th>
                        <th class="py-3.5 px-4">{{ __('Status') }}</th>
                        <th class="py-3.5 px-4 text-center">{{ __('Trips') }}</th>
                        <th class="py-3.5 px-4 text-center">{{ __('Posts') }}</th>
                        <th class="py-3.5 px-4">{{ __('Joined') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $user->trashed() ? 'opacity-60 bg-slate-50/30' : '' }}">
                            {{-- User Info --}}
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover border border-slate-200">
                                    <div>
                                        <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                            <a href="{{ route('admin.users.show', $user->id) }}" class="hover:text-teal-600 transition-colors">
                                                {{ $user->name }}
                                            </a>
                                            @if($user->id === auth()->id())
                                                <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-medium">{{ __('You') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500">{{ $user->email }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">&#64;{{ $user->username }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Role --}}
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    @if($user->role === 'admin')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700">
                                            {{ __('Admin') }}
                                        </span>
                                    @elseif($user->role === 'provider')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-700">
                                            {{ __('Provider') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                                            {{ __('Traveler') }}
                                        </span>
                                    @endif

                                    @if(!$user->trashed() && $user->id !== auth()->id())
                                        <button type="button"
                                                @click="openRoleModal({{ json_encode(['id' => $user->id, 'name' => $user->name, 'role' => $user->role]) }})"
                                                class="text-slate-400 hover:text-teal-600 transition-colors p-1"
                                                title="{{ __('Change role') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="py-3.5 px-4">
                                @if($user->trashed())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">
                                        {{ __('Deleted') }}
                                    </span>
                                @elseif($user->isSuspended())
                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                            {{ __('Suspended') }}
                                        </span>
                                        @if($user->suspended_reason)
                                            <div class="text-[10px] text-rose-600 mt-0.5 max-w-xs truncate" title="{{ $user->suspended_reason }}">
                                                {{ $user->suspended_reason }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                        {{ __('Active') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Trips --}}
                            <td class="py-3.5 px-4 text-center font-semibold text-slate-800">
                                {{ $user->trips_count }}
                            </td>

                            {{-- Posts --}}
                            <td class="py-3.5 px-4 text-center font-semibold text-slate-800">
                                {{ $user->posts_count }}
                            </td>

                            {{-- Joined --}}
                            <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.users.show', $user->id) }}"
                                       class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition-colors">
                                        {{ __('View') }}
                                    </a>

                                    @if(!$user->trashed())
                                        @if($user->isSuspended())
                                            <button type="button"
                                                    @click="openUnsuspendModal({{ json_encode(['id' => $user->id, 'name' => $user->name]) }})"
                                                    class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-[11px] font-semibold transition-colors">
                                                {{ __('Unsuspend') }}
                                            </button>
                                        @elseif($user->id !== auth()->id())
                                            <button type="button"
                                                    @click="openSuspendModal({{ json_encode(['id' => $user->id, 'name' => $user->name]) }})"
                                                    class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-semibold transition-colors">
                                                {{ __('Suspend') }}
                                            </button>
                                        @endif
                                    @else
                                        {{-- Trashed user actions --}}
                                        <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-[11px] font-semibold transition-colors">
                                                {{ __('Restore') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div class="font-semibold text-slate-700 text-sm">{{ __('No users found') }}</div>
                                <p class="text-xs text-slate-400 mt-1">{{ __('Try adjusting your search terms or filters.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL: Change Role --}}
    <div x-show="roleModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="roleModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form :action="'{{ url('/admin/users') }}/' + roleTargetUser + '/role'" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">{{ __('Modify User Role') }}</h3>
                            <button type="button" @click="roleModalOpen = false" class="text-slate-400 hover:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ __('Change permission role for') }} <span class="font-bold text-slate-900" x-text="roleTargetName"></span>.
                            {{ __('This action is logged in administrative audit trails.') }}
                        </p>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ __('Select Role') }}</label>
                            <select name="role" x-model="roleSelected" required class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                                <option value="traveler">{{ __('Traveler (Standard Community Member)') }}</option>
                                <option value="provider">{{ __('Provider (Tourism Service Vendor)') }}</option>
                                <option value="admin">{{ __('Admin (Full Platform Authority)') }}</option>
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
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="suspendModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form :action="'{{ url('/admin/users') }}/' + suspendTargetUser + '/suspend'" method="POST">
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

                        <div class="p-3 bg-rose-50 rounded-xl border border-rose-100 text-xs text-rose-800">
                            {{ __('Suspending') }} <span class="font-bold" x-text="suspendTargetName"></span> {{ __('will instantly kick the user out of all devices and prevent further access until lifted.') }}
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

    {{-- MODAL: Unsuspend User --}}
    <div x-show="unsuspendModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="unsuspendModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form :action="'{{ url('/admin/users') }}/' + unsuspendTargetUser + '/unsuspend'" method="POST">
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
                            {{ __('Are you sure you want to lift suspension for') }} <span class="font-bold text-slate-900" x-text="unsuspendTargetName"></span>?
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
