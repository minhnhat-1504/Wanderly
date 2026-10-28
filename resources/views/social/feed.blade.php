@extends('layouts.app')

@section('title', __('Community Feed'))
@section('description', __('Explore travel stories, tips, and itineraries shared by the Wanderly community.'))

@push('styles')
<style>
    .feed-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .feed-card:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,0,0,0.10); }
    .like-btn.liked svg { fill: #ef4444; stroke: #ef4444; }
    .save-btn.saved svg { fill: #f59e0b; stroke: #f59e0b; }
    .trip-card-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .post-avatar { background: linear-gradient(135deg, #4ade80, #06b6d4); }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2.5">
                <x-icon name="globe" class="w-8 h-8 text-primary" />
                <span>{{ __('Community Feed') }}</span>
            </h1>
            <p class="text-gray-500 mt-1">{{ __('Discover and share travel stories with fellow wanderers.') }}</p>
        </div>
        <button type="button" onclick="document.getElementById('create-post-modal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl font-medium hover:bg-primary/90 transition-colors shadow-lg shadow-primary/25"
                id="btn-create-post">
            <x-icon name="plus" class="w-5 h-5 text-white" />
            {{ __('Share a Story') }}
        </button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-6 bg-gray-100 p-1 rounded-xl w-fit">
        <a href="{{ route('social.feed', ['tab' => 'feed']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tab === 'feed' ? 'bg-white text-gray-900 shadow' : 'text-gray-500 hover:text-gray-700' }}"
           id="tab-feed">
            <x-icon name="globe" class="w-4 h-4 text-inherit" />
            <span>{{ __('All Posts') }}</span>
        </a>
        <a href="{{ route('social.feed', ['tab' => 'saved']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tab === 'saved' ? 'bg-white text-gray-900 shadow' : 'text-gray-500 hover:text-gray-700' }}"
           id="tab-saved">
            <x-icon name="bookmark" class="w-4 h-4 text-inherit" />
            <span>{{ __('Saved') }}</span>
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm font-medium flex items-center gap-2">
            <x-icon name="check-circle" class="w-4 h-4 text-green-600 shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Feed --}}
    <div class="space-y-6" id="feed-list">
        @forelse($posts as $post)
            @include('social.post-card', ['post' => $post])
        @empty
            <div class="text-center py-20 text-gray-400">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center text-teal-600">
                    <x-icon name="plane" class="w-8 h-8" />
                </div>
                <p class="text-lg font-medium text-gray-500">{{ __('No posts yet. Be the first to share!') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($posts->hasPages())
        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</div>

{{-- Create Post Modal --}}
<div id="create-post-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('create-post-modal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <x-icon name="edit-3" class="w-5 h-5 text-primary" />
                <span>{{ __('Share Your Story') }}</span>
            </h2>
            <button type="button" onclick="document.getElementById('create-post-modal').classList.add('hidden')" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                <x-icon name="x" class="w-5 h-5 text-gray-400" />
            </button>
        </div>
        <form method="POST" action="{{ route('social.posts.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="post-title" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Post Title') }}</label>
                <input type="text" name="title" id="post-title" required maxlength="255"
                       placeholder="{{ __('E.g., Our Amazing Dalat Trip!') }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition">
            </div>
            <div>
                <label for="post-content" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Your Story') }}</label>
                <textarea name="content" id="post-content" rows="5" required maxlength="5000"
                          placeholder="{{ __('Share your experience, tips, and highlights...') }}"
                          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition resize-none"></textarea>
            </div>
            {{-- Attach Trip --}}
            @if($myTrips->isNotEmpty())
            <div>
                <label for="post-trip" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                    <x-icon name="paperclip" class="w-4 h-4 text-gray-500" />
                    <span>{{ __('Attach a Trip Card (optional)') }}</span>
                </label>
                <select name="trip_id" id="post-trip"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition bg-white">
                    <option value="">— {{ __('No trip attached') }} —</option>
                    @foreach($myTrips as $trip)
                        <option value="{{ $trip->id }}">{{ $trip->name }} · {{ $trip->destination }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('create-post-modal').classList.add('hidden')"
                        class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary/90 transition-colors shadow-md shadow-primary/25"
                        id="btn-submit-post">
                    <x-icon name="send" class="w-4 h-4 text-white" />
                    <span>{{ __('Publish Post') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Report Content Modal --}}
<div id="report-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                <span>{{ __('Report Content') }}</span>
            </h2>
            <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="report-form" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <p id="report-item-title" class="text-xs text-gray-500 mb-2 truncate italic"></p>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('Select Violation Category') }}</label>
                <select name="reason" required class="w-full text-xs rounded-xl border border-gray-200 p-2.5 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                    <option value="spam">{{ __('Spam or Unsolicited Commercial Content') }}</option>
                    <option value="inappropriate">{{ __('Inappropriate or Explicit Content') }}</option>
                    <option value="harassment">{{ __('Harassment, Bullying or Hate Speech') }}</option>
                    <option value="copyright">{{ __('Intellectual Property / Copyright Violation') }}</option>
                    <option value="other">{{ __('Other Issue') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('Additional Context (Optional)') }}</label>
                <textarea name="details" rows="3" maxlength="500"
                          placeholder="{{ __('Provide any additional information that will help our moderation team...') }}"
                          class="w-full text-xs rounded-xl border border-gray-200 p-2.5 focus:border-red-500 focus:ring-1 focus:ring-red-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                    {{ __('Submit Report') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ── Like Toggle ──────────────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const likeBtn = e.target.closest('.like-btn');
        if (!likeBtn) return;

        const postId = likeBtn.dataset.postId;
        const countEl = likeBtn.querySelector('.like-count');

        likeBtn.disabled = true;
        try {
            const res = await fetch(`/social/posts/${postId}/like`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            likeBtn.classList.toggle('liked', data.liked);
            if (countEl) countEl.textContent = data.likes_count;
        } catch (err) { console.error(err); }
        likeBtn.disabled = false;
    });

    // ── Save Toggle ───────────────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const saveBtn = e.target.closest('.save-btn');
        if (!saveBtn) return;

        const postId = saveBtn.dataset.postId;
        saveBtn.disabled = true;
        try {
            const res = await fetch(`/social/posts/${postId}/save`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            saveBtn.classList.toggle('saved', data.saved);
            saveBtn.title = data.saved ? '{{ __("Unsave") }}' : '{{ __("Save") }}';
        } catch (err) { console.error(err); }
        saveBtn.disabled = false;
    });

    // ── Comment Submit ────────────────────────────────────────────────────
    document.addEventListener('submit', async function (e) {
        const form = e.target.closest('.comment-form');
        if (!form) return;
        e.preventDefault();

        const postId = form.dataset.postId;
        const input = form.querySelector('input[name="content"]');
        const content = input.value.trim();
        if (!content) return;

        try {
            const res = await fetch(`/social/posts/${postId}/comments`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ content })
            });
            const data = await res.json();
            if (data.comment) {
                const commentList = form.closest('.post-card').querySelector('.comment-list');
                const html = `<div class="flex gap-2 items-start py-2 border-t border-gray-50">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-emerald-500 flex items-center justify-center text-white text-xs font-semibold shrink-0">${data.comment.avatar_initial}</div>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-semibold text-gray-700">${data.comment.author}</span>
                        <span class="text-xs text-gray-500 ml-2">${data.comment.created_at}</span>
                        <p class="text-sm text-gray-600 mt-0.5">${data.comment.content}</p>
                    </div>
                </div>`;
                commentList.insertAdjacentHTML('beforeend', html);
                const countEl = form.closest('.post-card').querySelector('.comment-count');
                if (countEl) countEl.textContent = data.comments_count;
                input.value = '';
            }
        } catch (err) { console.error(err); }
    });

    // ── Open Report Modal Helper ──────────────────────────────────────────
    window.openReportModal = function(actionUrl, itemTitle) {
        const modal = document.getElementById('report-modal');
        const form = document.getElementById('report-form');
        const titleEl = document.getElementById('report-item-title');
        if (!modal || !form) return;
        form.action = actionUrl;
        if (titleEl) titleEl.textContent = itemTitle ? 'Regarding: ' + itemTitle : '';
        modal.classList.remove('hidden');
    };
});
</script>
@endpush
