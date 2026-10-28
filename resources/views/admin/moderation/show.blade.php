@extends('layouts.admin')

@section('title', __('Report #:id Details', ['id' => $report->id]))
@section('header', __('Moderation Report Dossier'))

@section('content')
<div class="space-y-6">

    {{-- Back Link & Navigation --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.moderation.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-teal-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('Back to Reports') }}
        </a>
    </div>

    {{-- Top Status Banner --}}
    <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-mono font-bold text-slate-400">#{{ $report->id }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                    @if($report->isPending()) bg-amber-100 text-amber-800
                    @elseif($report->isResolved()) bg-emerald-100 text-emerald-800
                    @else bg-slate-100 text-slate-700 @endif">
                    {{ ucfirst($report->status) }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                    {{ __('Reason:') }} {{ ucfirst($report->reason) }}
                </span>
            </div>
            <p class="text-xs text-slate-500">
                {{ __('Submitted on :date', ['date' => $report->created_at->format('M d, Y H:i:s')]) }}
            </p>
        </div>

        @if($report->isPending())
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.moderation.dismiss', $report->id) }}" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('{{ __('Dismiss this report without penalizing content?') }}')"
                            class="py-2 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                        {{ __('Dismiss Report') }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left 2 Cols: Content & Report Details --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Reported Target Content Card --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>{{ __('Reported Content') }}</span>
                        @if($report->reportable instanceof \App\Models\Post)
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-blue-100 text-blue-700 rounded">{{ __('Community Post') }}</span>
                        @else
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-purple-100 text-purple-700 rounded">{{ __('Comment') }}</span>
                        @endif
                    </h3>
                    @if($report->reportable && $report->reportable->is_hidden)
                        <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                            {{ __('Currently Hidden') }}
                        </span>
                    @endif
                </div>

                @if($report->reportable)
                    @php
                        $author = $report->reportable->author;
                    @endphp
                    {{-- Author info --}}
                    @if($author)
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" class="w-8 h-8 rounded-full object-cover border border-slate-200">
                            <div>
                                <div class="font-bold text-xs text-slate-900">
                                    <a href="{{ route('admin.users.show', $author->id) }}" class="hover:text-teal-600 transition-colors">
                                        {{ $author->name }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono">&#64;{{ $author->username }} &bull; {{ $author->email }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Target text --}}
                    <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200/80 space-y-2">
                        @if($report->reportable instanceof \App\Models\Post && $report->reportable->title)
                            <h4 class="font-bold text-sm text-slate-900">{{ $report->reportable->title }}</h4>
                        @endif
                        <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ $report->reportable->content }}</p>
                    </div>

                    @if($report->reportable instanceof \App\Models\Post && !empty($report->reportable->images))
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($report->reportable->images as $img)
                                <img src="{{ asset('storage/' . $img) }}" alt="Post image" class="h-24 w-full object-cover rounded-xl border border-slate-200">
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                        {{ __('The reported content has already been permanently deleted from the platform.') }}
                    </div>
                @endif
            </div>

            {{-- Reporter Information & Allegations --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">{{ __('Report Submission Info') }}</h3>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Submitted By') }}</dt>
                        <dd class="text-slate-900 font-medium mt-0.5">
                            @if($report->reporter)
                                <a href="{{ route('admin.users.show', $report->reporter->id) }}" class="text-teal-600 hover:text-teal-700 font-bold">
                                    {{ $report->reporter->name }} (&#64;{{ $report->reporter->username }})
                                </a>
                            @else
                                <span class="italic text-slate-400">{{ __('Unknown User') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Alleged Violation') }}</dt>
                        <dd class="text-rose-600 font-bold uppercase mt-0.5">{{ $report->reason }}</dd>
                    </div>
                </dl>

                @if($report->details)
                    <div class="mt-3">
                        <dt class="font-semibold text-xs text-slate-500 mb-1">{{ __('Additional Explanation from Reporter') }}</dt>
                        <dd class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-700 italic">
                            "{{ $report->details }}"
                        </dd>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Col: Resolution Form or History --}}
        <div class="space-y-6">

            @if($report->isPending())
                {{-- Action Form --}}
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">{{ __('Take Resolution Action') }}</h3>

                    <form method="POST" action="{{ route('admin.moderation.resolve', $report->id) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">{{ __('Action') }}</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2.5 text-xs text-slate-700 cursor-pointer">
                                    <input type="radio" name="action" value="hide" checked class="text-teal-600 focus:ring-teal-500">
                                    <span class="font-medium">{{ __('Hide Content from Public Feed') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 text-xs text-slate-700 cursor-pointer">
                                    <input type="radio" name="action" value="delete" class="text-rose-600 focus:ring-rose-500">
                                    <span class="font-medium text-rose-700">{{ __('Soft Delete Content') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 text-xs text-slate-700 cursor-pointer">
                                    <input type="radio" name="action" value="dismiss" class="text-slate-600 focus:ring-slate-500">
                                    <span class="font-medium text-slate-600">{{ __('Dismiss Report (No Violation)') }}</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Moderator Note (Optional)') }}</label>
                            <textarea name="admin_note" rows="3" maxlength="1000"
                                      placeholder="{{ __('Record internal notes for this decision...') }}"
                                      class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20"></textarea>
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                            {{ __('Apply Action & Notify Users') }}
                        </button>
                    </form>
                </div>
            @else
                {{-- Resolution History --}}
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">{{ __('Resolution Details') }}</h3>

                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="font-semibold text-slate-500">{{ __('Status') }}</dt>
                            <dd class="text-slate-900 font-bold uppercase mt-0.5">{{ $report->status }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">{{ __('Action Taken') }}</dt>
                            <dd class="text-slate-900 font-medium mt-0.5">{{ ucfirst($report->action_taken) }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">{{ __('Resolved By') }}</dt>
                            <dd class="text-slate-900 font-medium mt-0.5">{{ $report->admin->name ?? __('System') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">{{ __('Resolved At') }}</dt>
                            <dd class="text-slate-900 mt-0.5">{{ $report->resolved_at ? $report->resolved_at->format('M d, Y H:i:s') : '—' }}</dd>
                        </div>
                        @if($report->admin_note)
                            <div>
                                <dt class="font-semibold text-slate-500">{{ __('Admin Notes') }}</dt>
                                <dd class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-slate-700 mt-1 italic">
                                    "{{ $report->admin_note }}"
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection
