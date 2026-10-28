@extends('layouts.app')

@section('title', __('Report Status & Review Details'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Back navigation --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('social.feed') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-teal-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('Back to Community Feed') }}
        </a>

        <span class="text-xs font-mono text-gray-400">#REP-{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</span>
    </div>

    {{-- Header Banner --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl {{ $report->isResolved() ? 'bg-emerald-100 text-emerald-700' : ($report->isDismissed() ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700') }} flex items-center justify-center shrink-0">
                    @if($report->isResolved())
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif($report->isDismissed())
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ __('Report Review Details') }}</h1>
                        @if($report->isResolved())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                {{ __('Resolved') }}
                            </span>
                        @elseif($report->isDismissed())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">
                                {{ __('Reviewed & Dismissed') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                {{ __('Pending Review') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('Submitted on') }} {{ $report->created_at->format('M d, Y · H:i') }}
                        @if($report->resolved_at)
                            &bull; {{ __('Reviewed on') }} {{ $report->resolved_at->format('M d, Y · H:i') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('social.feed') }}" class="py-2 px-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition-colors">
                    {{ __('Return to Feed') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Resolution Outcome Section --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider text-gray-500">{{ __('Moderator Decision & Outcome') }}</h2>

        <div class="p-4 rounded-xl border {{ $report->action_taken === 'delete' ? 'bg-rose-50 border-rose-200 text-rose-900' : ($report->action_taken === 'hide' ? 'bg-amber-50 border-amber-200 text-amber-900' : ($report->action_taken === 'suspend_user' ? 'bg-purple-50 border-purple-200 text-purple-900' : 'bg-slate-50 border-slate-200 text-slate-900')) }}">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 font-bold">
                    @if($report->action_taken === 'delete')
                        <span class="text-rose-600 text-base">🗑️</span>
                    @elseif($report->action_taken === 'hide')
                        <span class="text-amber-600 text-base">👁️</span>
                    @elseif($report->action_taken === 'suspend_user')
                        <span class="text-purple-600 text-base">🚫</span>
                    @else
                        <span class="text-slate-600 text-base">🛡️</span>
                    @endif
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold">
                        @if($report->action_taken === 'delete')
                            {{ __('Action Taken: Content Removed Permanently') }}
                        @elseif($report->action_taken === 'hide')
                            {{ __('Action Taken: Content Hidden from Community') }}
                        @elseif($report->action_taken === 'suspend_user')
                            {{ __('Action Taken: Offending Account Penalized') }}
                        @elseif($report->action_taken === 'dismiss')
                            {{ __('No Community Guidelines Violation Found') }}
                        @else
                            {{ __('Status: Report is being reviewed by safety moderators') }}
                        @endif
                    </h3>
                    <p class="text-xs mt-1 leading-relaxed opacity-90">
                        @if($report->action_taken === 'delete')
                            {{ __('Our moderation team verified this report and permanently removed the violating post/comment from Wanderly.') }}
                        @elseif($report->action_taken === 'hide')
                            {{ __('The reported content has been restricted and hidden from public feeds and searches.') }}
                        @elseif($report->action_taken === 'suspend_user')
                            {{ __('Due to severe or repeated violations, appropriate disciplinary sanctions were applied to the author.') }}
                        @elseif($report->action_taken === 'dismiss')
                            {{ __('After thorough review against Wanderly Community Standards, our team concluded this content does not violate platform policies.') }}
                        @else
                            {{ __('Your report has been queued for our trust and safety team. You will receive another notification once resolved.') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Moderator Note (if any) --}}
        @if($report->admin_note)
            <div class="mt-4 p-4 bg-teal-50/60 rounded-xl border border-teal-200/80">
                <div class="flex items-center gap-2 mb-1.5 text-teal-900 font-bold text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <span>{{ __('Note from Wanderly Safety Team') }}</span>
                </div>
                <p class="text-xs text-teal-950 font-medium leading-relaxed whitespace-pre-wrap">{{ $report->admin_note }}</p>
            </div>
        @endif
    </div>

    {{-- Report Submission Details --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider text-gray-500">{{ __('Your Report Details') }}</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-gray-500 block mb-0.5">{{ __('Reported Target') }}</span>
                <span class="font-bold text-gray-900 capitalize">{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }}</span>
            </div>

            <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-gray-500 block mb-0.5">{{ __('Violation Category') }}</span>
                <span class="font-bold text-teal-700 capitalize">{{ $report->reason }}</span>
            </div>
        </div>

        @if($report->details)
            <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-xs font-semibold text-gray-500 block mb-1">{{ __('Additional Explanation Provided') }}</span>
                <p class="text-xs text-gray-800 leading-relaxed italic">"{{ $report->details }}"</p>
            </div>
        @endif

        {{-- Content preview snippet if still present --}}
        @if($report->reportable)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">{{ __('Reported Content Preview') }}</h3>
                <div class="p-4 bg-gray-50/80 rounded-xl border border-gray-200 text-xs text-gray-800 space-y-2">
                    @if($report->reportable_type === 'App\Models\Post')
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-gray-900">{{ $report->reportable->author?->name ?? __('Unknown Author') }}</span>
                            <span class="text-gray-400">&bull;</span>
                            <span class="text-gray-400 font-mono text-[11px]">{{ $report->reportable->created_at?->format('M d, Y') }}</span>
                        </div>
                        <p class="line-clamp-3 text-gray-700">{{ $report->reportable->content }}</p>
                    @elseif($report->reportable_type === 'App\Models\Comment')
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-gray-900">{{ $report->reportable->author?->name ?? __('Unknown Author') }}</span>
                        </div>
                        <p class="line-clamp-3 text-gray-700">{{ $report->reportable->content }}</p>
                    @endif
                </div>
            </div>
        @else
            <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500 italic">
                {{ __('The reported content is no longer publicly accessible or has been removed from the platform.') }}
            </div>
        @endif
    </div>

    {{-- Safe Community Commitment Footer --}}
    <div class="p-4 bg-teal-50/40 rounded-2xl border border-teal-100 flex items-center gap-3 text-xs text-teal-800">
        <svg class="w-5 h-5 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <p>
            {{ __('Thank you for keeping Wanderly safe and welcoming for everyone. Community reports help us uphold quality and trust across all travel discussions.') }}
        </p>
    </div>

</div>
@endsection
