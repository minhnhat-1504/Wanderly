@extends('layouts.admin')

@section('title', __('Community Moderation'))
@section('header', __('Content Moderation'))

@section('content')
<div class="space-y-6" x-data="{
    resolveModalOpen: false,
    selectedReportId: null,
    selectedReportType: '',
    selectedReportTargetText: '',
    selectedReportReason: '',
    actionSelected: 'hide',
    adminNote: '',

    openResolveModal(report) {
        this.selectedReportId = report.id;
        this.selectedReportType = report.type;
        this.selectedReportTargetText = report.target_text;
        this.selectedReportReason = report.reason;
        this.actionSelected = 'hide';
        this.adminNote = '';
        this.resolveModalOpen = true;
    }
}">

    {{-- Header & Subtext --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ __('Community Moderation & Reports') }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ __('Review user-submitted reports, moderate objectionable content, and enforce platform safety standards.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.moderation.content') }}"
               class="inline-flex items-center gap-1.5 py-2.5 px-4 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold shadow-sm transition-colors">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                {{ __('Manage All Posts & Comments') }}
            </a>
        </div>
    </div>

    {{-- KPI Pills --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="{{ route('admin.moderation.index', ['status' => 'pending']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['status'] ?? 'pending') === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Pending') }}</div>
            <div class="text-xl font-bold text-amber-600 mt-0.5">{{ number_format($stats['pending']) }}</div>
        </a>
        <a href="{{ route('admin.moderation.index', ['status' => 'resolved']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['status'] ?? '') === 'resolved' ? 'border-emerald-500 ring-2 ring-emerald-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Resolved') }}</div>
            <div class="text-xl font-bold text-emerald-600 mt-0.5">{{ number_format($stats['resolved']) }}</div>
        </a>
        <a href="{{ route('admin.moderation.index', ['status' => 'dismissed']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['status'] ?? '') === 'dismissed' ? 'border-slate-500 ring-2 ring-slate-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Dismissed') }}</div>
            <div class="text-xl font-bold text-slate-600 mt-0.5">{{ number_format($stats['dismissed']) }}</div>
        </a>
        <a href="{{ route('admin.moderation.index', ['status' => 'all']) }}"
           class="p-3.5 bg-white rounded-2xl border transition-all {{ ($filters['status'] ?? '') === 'all' ? 'border-teal-500 ring-2 ring-teal-500/10 shadow-sm' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Total Reports') }}</div>
            <div class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['total']) }}</div>
        </a>
        <a href="{{ route('admin.moderation.content', ['type' => 'posts', 'status' => 'hidden']) }}"
           class="p-3.5 bg-white rounded-2xl border border-slate-200/80 hover:border-rose-300 transition-all">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Hidden Posts') }}</div>
            <div class="text-xl font-bold text-rose-600 mt-0.5">{{ number_format($stats['hidden_posts']) }}</div>
        </a>
        <a href="{{ route('admin.moderation.content', ['type' => 'comments', 'status' => 'hidden']) }}"
           class="p-3.5 bg-white rounded-2xl border border-slate-200/80 hover:border-indigo-300 transition-all">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Hidden Comments') }}</div>
            <div class="text-xl font-bold text-indigo-600 mt-0.5">{{ number_format($stats['hidden_comments']) }}</div>
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.moderation.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Status') }}</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="pending" {{ ($filters['status'] ?? 'pending') === 'pending' ? 'selected' : '' }}>{{ __('Pending Review') }}</option>
                    <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                    <option value="dismissed" {{ ($filters['status'] ?? '') === 'dismissed' ? 'selected' : '' }}>{{ __('Dismissed') }}</option>
                    <option value="all" {{ ($filters['status'] ?? '') === 'all' ? 'selected' : '' }}>{{ __('All Reports') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Violation Reason') }}</label>
                <select name="reason" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="">{{ __('All Reasons') }}</option>
                    <option value="spam" {{ ($filters['reason'] ?? '') === 'spam' ? 'selected' : '' }}>{{ __('Spam / Unsolicited Ads') }}</option>
                    <option value="inappropriate" {{ ($filters['reason'] ?? '') === 'inappropriate' ? 'selected' : '' }}>{{ __('Inappropriate Content') }}</option>
                    <option value="harassment" {{ ($filters['reason'] ?? '') === 'harassment' ? 'selected' : '' }}>{{ __('Harassment / Hate') }}</option>
                    <option value="copyright" {{ ($filters['reason'] ?? '') === 'copyright' ? 'selected' : '' }}>{{ __('Copyright Infringement') }}</option>
                    <option value="other" {{ ($filters['reason'] ?? '') === 'other' ? 'selected' : '' }}>{{ __('Other Concern') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Target Type') }}</label>
                <select name="target_type" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="">{{ __('All Content (Posts & Comments)') }}</option>
                    <option value="post" {{ ($filters['target_type'] ?? '') === 'post' ? 'selected' : '' }}>{{ __('Posts Only') }}</option>
                    <option value="comment" {{ ($filters['target_type'] ?? '') === 'comment' ? 'selected' : '' }}>{{ __('Comments Only') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Reporter or details...') }}"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm">
                    {{ __('Apply Filters') }}
                </button>
                <a href="{{ route('admin.moderation.index') }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Reports Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">{{ __('Report #') }}</th>
                        <th class="py-3.5 px-4">{{ __('Reporter') }}</th>
                        <th class="py-3.5 px-4">{{ __('Reported Target') }}</th>
                        <th class="py-3.5 px-4">{{ __('Reason & Details') }}</th>
                        <th class="py-3.5 px-4">{{ __('Status') }}</th>
                        <th class="py-3.5 px-4">{{ __('Submitted') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($reports as $report)
                        @php
                            $targetIsPost = $report->reportable instanceof \App\Models\Post;
                            $targetTitle = $targetIsPost ? ($report->reportable->title ?? __('Deleted Post')) : __('Comment on post');
                            $targetSnippet = $report->reportable ? Str::limit($report->reportable->content ?? '', 90) : __('Content has been deleted.');
                            $targetIsHidden = $report->reportable && (bool) $report->reportable->is_hidden;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            {{-- ID --}}
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                #{{ $report->id }}
                            </td>

                            {{-- Reporter --}}
                            <td class="py-3.5 px-4">
                                @if($report->reporter)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $report->reporter->avatar_url }}" alt="{{ $report->reporter->name }}" class="w-7 h-7 rounded-full object-cover border border-slate-200">
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $report->reporter->name }}</div>
                                            <div class="text-[11px] text-slate-500 font-mono">&#64;{{ $report->reporter->username }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">{{ __('Unknown user') }}</span>
                                @endif
                            </td>

                            {{-- Target --}}
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    @if($targetIsPost)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">
                                            {{ __('Post') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">
                                            {{ __('Comment') }}
                                        </span>
                                    @endif

                                    @if($targetIsHidden)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">
                                            {{ __('Hidden') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="font-semibold text-slate-900 truncate" title="{{ $targetTitle }}">{{ $targetTitle }}</div>
                                <div class="text-[11px] text-slate-500 line-clamp-2" title="{{ $targetSnippet }}">{{ $targetSnippet }}</div>
                            </td>

                            {{-- Reason & Details --}}
                            <td class="py-3.5 px-4 max-w-xs">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                    @if($report->reason === 'spam') bg-amber-100 text-amber-700
                                    @elseif($report->reason === 'harassment') bg-rose-100 text-rose-700
                                    @elseif($report->reason === 'inappropriate') bg-red-100 text-red-700
                                    @elseif($report->reason === 'copyright') bg-blue-100 text-blue-700
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ ucfirst($report->reason) }}
                                </span>
                                @if($report->details)
                                    <div class="text-[11px] text-slate-600 mt-1 italic line-clamp-2" title="{{ $report->details }}">
                                        "{{ $report->details }}"
                                    </div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="py-3.5 px-4">
                                @if($report->isPending())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        {{ __('Pending Review') }}
                                    </span>
                                @elseif($report->isResolved())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        {{ __('Resolved') }} ({{ $report->action_taken }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ __('Dismissed') }}
                                    </span>
                                @endif

                                @if($report->admin)
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ __('by :name', ['name' => $report->admin->name]) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Submitted --}}
                            <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                {{ $report->created_at->format('M d, Y H:i') }}
                            </td>

                            {{-- Actions --}}
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.moderation.show', $report->id) }}"
                                       class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition-colors">
                                        {{ __('Detail') }}
                                    </a>

                                    @if($report->isPending())
                                        <button type="button"
                                                @click="openResolveModal({
                                                    id: {{ $report->id }},
                                                    type: '{{ $targetIsPost ? __('Post') : __('Comment') }}',
                                                    target_text: '{{ addslashes(Str::limit($targetSnippet, 80)) }}',
                                                    reason: '{{ $report->reason }}'
                                                })"
                                                class="px-2.5 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-[11px] font-semibold transition-colors shadow-sm">
                                            {{ __('Take Action') }}
                                        </button>

                                        <form method="POST" action="{{ route('admin.moderation.dismiss', $report->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('{{ __('Dismiss this report without penalizing content?') }}')"
                                                    class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg text-[11px] font-semibold transition-colors">
                                                {{ __('Dismiss') }}
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
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="font-semibold text-slate-700 text-sm">{{ __('No moderation reports found') }}</div>
                                <p class="text-xs text-slate-400 mt-1">{{ __('All submitted community reports have been processed or none match your criteria.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($reports->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL: Resolve Report Action --}}
    <div x-show="resolveModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="resolveModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form :action="'{{ url('/admin/moderation/reports') }}/' + selectedReportId + '/resolve'" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">{{ __('Resolve Moderation Report') }}</h3>
                            <button type="button" @click="resolveModalOpen = false" class="text-slate-400 hover:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Target Summary Box --}}
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <div class="flex items-center justify-between text-slate-500 mb-1">
                                <span class="font-bold text-slate-700" x-text="selectedReportType"></span>
                                <span class="font-mono text-rose-600 uppercase" x-text="'Reason: ' + selectedReportReason"></span>
                            </div>
                            <p class="text-slate-700 italic" x-text="'&ldquo;' + selectedReportTargetText + '&rdquo;'"></p>
                        </div>

                        {{-- Action Selection --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">{{ __('Select Resolution Action') }}</label>
                            <div class="space-y-2">
                                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                                       :class="actionSelected === 'hide' ? 'border-teal-500 bg-teal-50/30' : 'border-slate-200 hover:bg-slate-50'">
                                    <input type="radio" name="action" value="hide" x-model="actionSelected" class="mt-0.5 text-teal-600 focus:ring-teal-500">
                                    <div>
                                        <div class="font-bold text-xs text-slate-900">{{ __('Hide Content (Recommended)') }}</div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ __('Removes content from the public community feed. The author is notified.') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                                       :class="actionSelected === 'delete' ? 'border-rose-500 bg-rose-50/30' : 'border-slate-200 hover:bg-slate-50'">
                                    <input type="radio" name="action" value="delete" x-model="actionSelected" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                                    <div>
                                        <div class="font-bold text-xs text-slate-900">{{ __('Delete Content (Soft Delete)') }}</div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ __('Soft deletes the item. Comment counters are adjusted and author is notified.') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                                       :class="actionSelected === 'dismiss' ? 'border-slate-400 bg-slate-50' : 'border-slate-200 hover:bg-slate-50'">
                                    <input type="radio" name="action" value="dismiss" x-model="actionSelected" class="mt-0.5 text-slate-600 focus:ring-slate-500">
                                    <div>
                                        <div class="font-bold text-xs text-slate-900">{{ __('Dismiss Report (No Violation)') }}</div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ __('Marks the report dismissed without altering content visibility.') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Admin Note --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Internal Moderator Note (Optional)') }}</label>
                            <textarea name="admin_note" x-model="adminNote" rows="2" maxlength="1000"
                                      placeholder="{{ __('Add notes for other admins or audit trail record...') }}"
                                      class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex justify-end gap-2 border-t border-slate-200/80">
                        <button type="button" @click="resolveModalOpen = false" class="py-2 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-sm">
                            {{ __('Apply Resolution') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
