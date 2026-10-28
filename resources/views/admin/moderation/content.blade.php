@extends('layouts.admin')

@section('title', __('Content Moderation'))
@section('header', __('Post & Comment Moderation'))

@section('content')
<div class="space-y-6" x-data="{
    hideModalOpen: false,
    hideActionUrl: '',
    hideTargetTitle: '',
    hideReason: ''
}">

    {{-- Breadcrumb & Navigation --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.moderation.index') }}" class="text-xs text-slate-500 hover:text-teal-600 font-semibold">
                    &larr; {{ __('Back to Reports') }}
                </a>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ __('Content Moderation Manager') }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ __('Inspect community posts and comments directly, control visibility, and moderate policy breaches.') }}
            </p>
        </div>
    </div>

    {{-- Tabs (Posts vs Comments) --}}
    <div class="flex border-b border-slate-200">
        <a href="{{ route('admin.moderation.content', ['type' => 'posts']) }}"
           class="py-3 px-6 text-xs font-bold border-b-2 transition-colors flex items-center gap-2 {{ $type === 'posts' ? 'border-teal-600 text-teal-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            {{ __('Community Posts') }}
            @if($stats['hidden_posts'] > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-100 text-rose-700 font-bold">{{ $stats['hidden_posts'] }}</span>
            @endif
        </a>
        <a href="{{ route('admin.moderation.content', ['type' => 'comments']) }}"
           class="py-3 px-6 text-xs font-bold border-b-2 transition-colors flex items-center gap-2 {{ $type === 'comments' ? 'border-teal-600 text-teal-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            {{ __('Comments') }}
            @if($stats['hidden_comments'] > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-100 text-indigo-700 font-bold">{{ $stats['hidden_comments'] }}</span>
            @endif
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.moderation.content') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 items-end">
            <input type="hidden" name="type" value="{{ $type }}">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Visibility Status') }}</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
                    <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All (Active & Hidden)') }}</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                    <option value="hidden" {{ ($filters['status'] ?? '') === 'hidden' ? 'selected' : '' }}>{{ __('Hidden Only') }}</option>
                    <option value="trashed" {{ ($filters['status'] ?? '') === 'trashed' ? 'selected' : '' }}>{{ __('Soft Deleted') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('Search Keywords') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search content or author...') }}"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-teal-500 focus:ring-teal-500/20">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm">
                    {{ __('Filter') }}
                </button>
                <a href="{{ route('admin.moderation.content', ['type' => $type]) }}" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Content Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">{{ __('ID') }}</th>
                        <th class="py-3.5 px-4">{{ __('Author') }}</th>
                        <th class="py-3.5 px-4">{{ $type === 'posts' ? __('Title & Content Preview') : __('Comment Content') }}</th>
                        <th class="py-3.5 px-4 text-center">{{ __('Reports') }}</th>
                        <th class="py-3.5 px-4">{{ __('Visibility') }}</th>
                        <th class="py-3.5 px-4">{{ __('Created At') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $item->is_hidden ? 'bg-rose-50/30' : '' }}">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                #{{ $item->id }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($item->author)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $item->author->avatar_url }}" alt="{{ $item->author->name }}" class="w-7 h-7 rounded-full object-cover border border-slate-200">
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $item->author->name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono">&#64;{{ $item->author->username }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">{{ __('Unknown') }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 max-w-md">
                                @if($type === 'posts' && $item->title)
                                    <div class="font-bold text-slate-900 truncate mb-0.5">{{ $item->title }}</div>
                                @endif
                                <div class="text-[11px] text-slate-600 line-clamp-2">{{ $item->content }}</div>
                                @if($item->is_hidden && $item->hidden_reason)
                                    <div class="text-[10px] text-rose-600 mt-1">
                                        {{ __('Reason:') }} {{ $item->hidden_reason }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($item->reports_count > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        {{ $item->reports_count }} {{ __('reports') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($item->trashed())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">
                                        {{ __('Deleted') }}
                                    </span>
                                @elseif($item->is_hidden)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                        {{ __('Hidden') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                        {{ __('Visible') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                {{ $item->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if(!$item->trashed())
                                    <form method="POST"
                                          action="{{ $type === 'posts' ? route('admin.moderation.posts.toggle-hide', $item->id) : route('admin.moderation.comments.toggle-hide', $item->id) }}"
                                          class="inline">
                                        @csrf
                                        <button type="submit"
                                                onclick="return confirm('{{ $item->is_hidden ? __('Restore visibility for this item?') : __('Hide this item from the public feed?') }}')"
                                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-colors {{ $item->is_hidden ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' : 'bg-rose-50 hover:bg-rose-100 text-rose-700' }}">
                                            {{ $item->is_hidden ? __('Unhide') : __('Hide') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="font-semibold text-slate-700 text-sm">{{ __('No items found') }}</div>
                                <p class="text-xs text-slate-400 mt-1">{{ __('Try adjusting your search criteria.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
