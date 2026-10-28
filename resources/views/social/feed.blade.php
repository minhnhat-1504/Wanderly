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

{{-- Centered Delete Post Confirmation Modal --}}
<div id="delete-post-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center transform transition-all z-10 border border-gray-100 animate-in fade-in zoom-in-95 duration-150">
        <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100 shadow-sm">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">
            {{ __('Delete Post?') }}
        </h3>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">
            {{ __('Are you sure you want to delete this post? This action will permanently remove it from the community feed.') }}
        </p>
        <div class="flex items-center justify-center gap-3">
            <button type="button" onclick="closeDeleteModal()"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                {{ __('Cancel') }}
            </button>
            <form id="delete-post-form" method="POST" action="" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700 shadow-md shadow-red-600/20 transition-colors"
                        id="btn-confirm-delete-post">
                    {{ __('Yes, Delete') }}
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Centered Delete Comment Confirmation Modal --}}
<div id="delete-comment-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeDeleteCommentModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center transform transition-all z-10 border border-gray-100 animate-in fade-in zoom-in-95 duration-150">
        <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-3.5 border border-red-100 shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 mb-1.5">
            {{ __('Delete Comment?') }}
        </h3>
        <p class="text-xs text-gray-500 mb-5 leading-relaxed">
            {{ __('Are you sure you want to delete this comment? This action cannot be undone.') }}
        </p>
        <div class="flex items-center justify-center gap-2.5">
            <button type="button" onclick="closeDeleteCommentModal()"
                    class="flex-1 px-4 py-2 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                {{ __('Cancel') }}
            </button>
            <button type="button" onclick="executeDeleteComment()"
                    class="flex-1 px-4 py-2 rounded-xl bg-red-600 text-white text-xs font-semibold hover:bg-red-700 shadow-md shadow-red-600/20 transition-colors"
                    id="btn-confirm-delete-comment">
                {{ __('Delete') }}
            </button>
        </div>
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
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

async function apiPost(endpoint, body = null, method = 'POST') {
    const headers = {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    if (body) {
        headers['Content-Type'] = 'application/json';
    }
    const options = {
        method: method,
        headers: headers
    };
    if (body) {
        options.body = JSON.stringify(body);
    }

    let res = await fetch(`/ajax${endpoint}`, options);
    if (res.status === 404) {
        res = await fetch(endpoint, options);
    }
    return res;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openDeleteModal(actionUrl) {
    const modal = document.getElementById('delete-post-modal');
    const form = document.getElementById('delete-post-form');
    if (modal && form) {
        form.action = actionUrl;
        modal.classList.remove('hidden');
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('delete-post-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

let commentIdToDelete = null;

function confirmDeleteComment(commentId) {
    closeAllCommentMenus();
    commentIdToDelete = commentId;
    const modal = document.getElementById('delete-comment-modal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeDeleteCommentModal() {
    commentIdToDelete = null;
    const modal = document.getElementById('delete-comment-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

async function executeDeleteComment() {
    if (!commentIdToDelete) return;
    const commentId = commentIdToDelete;
    const confirmBtn = document.getElementById('btn-confirm-delete-comment');

    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.dataset.originalText = confirmBtn.textContent;
        confirmBtn.textContent = '...';
    }

    try {
        const res = await apiPost(`/social/comments/${commentId}`, null, 'DELETE');
        if (res.ok) {
            const data = await res.json();
            closeDeleteCommentModal();
            const item = document.getElementById(`comment-${commentId}`);
            if (item) {
                const postCard = item.closest('.post-card');
                item.style.transition = 'all 0.2s ease';
                item.style.opacity = '0';
                item.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    item.remove();
                }, 200);

                if (postCard && typeof data.comments_count !== 'undefined') {
                    const countEl = postCard.querySelector('.comment-count');
                    if (countEl) countEl.textContent = data.comments_count;
                }
            }
        } else {
            const errData = await res.json().catch(() => ({}));
            alert(errData.message || '{{ __("Unable to delete comment.") }}');
        }
    } catch (err) {
        console.error('Failed to delete comment:', err);
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = confirmBtn.dataset.originalText || '{{ __("Delete") }}';
        }
    }
}

function toggleCommentMenu(event, btn) {
    event.stopPropagation();
    const container = btn.closest('.comment-menu-container');
    const dropdown = container ? container.querySelector('.comment-dropdown') : null;

    document.querySelectorAll('.comment-dropdown').forEach(d => {
        if (d !== dropdown) d.classList.add('hidden');
    });

    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

function closeAllCommentMenus() {
    document.querySelectorAll('.comment-dropdown').forEach(d => d.classList.add('hidden'));
}

function startEditComment(commentId) {
    closeAllCommentMenus();
    const item = document.getElementById(`comment-${commentId}`);
    if (!item) return;

    const textEl = item.querySelector('.comment-text');
    const editBox = item.querySelector('.comment-edit-box');
    const input = item.querySelector('.comment-edit-input');

    if (textEl && editBox) {
        textEl.classList.add('hidden');
        editBox.classList.remove('hidden');
        if (input) {
            input.focus();
            input.selectionStart = input.selectionEnd = input.value.length;
        }
    }
}

function cancelEditComment(commentId) {
    const item = document.getElementById(`comment-${commentId}`);
    if (!item) return;

    const textEl = item.querySelector('.comment-text');
    const editBox = item.querySelector('.comment-edit-box');
    const input = item.querySelector('.comment-edit-input');

    if (textEl && editBox) {
        if (input) {
            input.value = textEl.textContent.trim();
        }
        editBox.classList.add('hidden');
        textEl.classList.remove('hidden');
    }
}

async function saveEditComment(commentId) {
    const item = document.getElementById(`comment-${commentId}`);
    if (!item) return;

    const textEl = item.querySelector('.comment-text');
    const editBox = item.querySelector('.comment-edit-box');
    const input = item.querySelector('.comment-edit-input');
    const saveBtn = item.querySelector('.btn-save-comment');
    const editedTag = item.querySelector('.comment-edited-tag');

    const content = input ? input.value.trim() : '';
    if (!content) return;

    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.dataset.originalText = saveBtn.textContent;
        saveBtn.textContent = '...';
    }

    try {
        const res = await apiPost(`/social/comments/${commentId}`, { content }, 'PATCH');
        if (res.ok) {
            const data = await res.json();
            if (textEl) {
                textEl.textContent = data.comment.content;
            }
            if (editedTag) {
                editedTag.classList.remove('hidden');
            }
            if (editBox) {
                editBox.classList.add('hidden');
            }
            if (textEl) {
                textEl.classList.remove('hidden');
            }
        } else {
            const errData = await res.json().catch(() => ({}));
            alert(errData.message || '{{ __("Unable to update comment.") }}');
        }
    } catch (err) {
        console.error('Failed to update comment:', err);
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = saveBtn.dataset.originalText || '{{ __("Save") }}';
        }
    }
}

function togglePostComments(btn) {
    const postCard = btn.closest('.post-card');
    if (!postCard) return;
    const commentsSection = postCard.querySelector('.comments-section');
    if (commentsSection) {
        const isHidden = commentsSection.classList.contains('hidden');
        commentsSection.classList.toggle('hidden');
        if (isHidden) {
            const input = commentsSection.querySelector('input[name="content"]');
            if (input) input.focus();
        }
    }
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.comment-menu-container')) {
        closeAllCommentMenus();
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
        closeDeleteCommentModal();
        closeAllCommentMenus();
        const createModal = document.getElementById('create-post-modal');
        if (createModal) createModal.classList.add('hidden');
    }
});

document.addEventListener('DOMContentLoaded', function () {

    // ── Like Toggle ──────────────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const likeBtn = e.target.closest('.like-btn');
        if (!likeBtn) return;

        const postId = likeBtn.dataset.postId;
        const countEl = likeBtn.querySelector('.like-count');
        const svg = likeBtn.querySelector('svg');

        likeBtn.disabled = true;
        try {
            const res = await apiPost(`/social/posts/${postId}/like`);
            if (res.ok) {
                const data = await res.json();
                likeBtn.classList.toggle('liked', data.liked);
                if (data.liked) {
                    likeBtn.classList.add('text-red-500', 'bg-red-50');
                    if (svg) { svg.setAttribute('fill', '#ef4444'); svg.setAttribute('stroke', '#ef4444'); }
                } else {
                    likeBtn.classList.remove('text-red-500', 'bg-red-50');
                    if (svg) { svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor'); }
                }
                if (countEl) countEl.textContent = data.likes_count;
            }
        } catch (err) {
            console.error('Like request failed:', err);
        } finally {
            likeBtn.disabled = false;
        }
    });

    // ── Save Toggle ───────────────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const saveBtn = e.target.closest('.save-btn');
        if (!saveBtn) return;

        const postId = saveBtn.dataset.postId;
        const svg = saveBtn.querySelector('svg');

        saveBtn.disabled = true;
        try {
            const res = await apiPost(`/social/posts/${postId}/save`);
            if (res.ok) {
                const data = await res.json();
                saveBtn.classList.toggle('saved', data.saved);
                if (data.saved) {
                    saveBtn.classList.add('text-yellow-500', 'bg-yellow-50');
                    if (svg) { svg.setAttribute('fill', '#f59e0b'); svg.setAttribute('stroke', '#f59e0b'); }
                    saveBtn.title = '{{ __("Unsave") }}';
                } else {
                    saveBtn.classList.remove('text-yellow-500', 'bg-yellow-50');
                    if (svg) { svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor'); }
                    saveBtn.title = '{{ __("Save") }}';
                }
            } else {
                console.error('Save request failed with status:', res.status);
            }
        } catch (err) {
            console.error('Save request error:', err);
        } finally {
            saveBtn.disabled = false;
        }
    });

    // ── Comment Submit ────────────────────────────────────────────────────
    document.addEventListener('submit', async function (e) {
        const form = e.target.closest('.comment-form');
        if (!form) return;
        e.preventDefault();

        const postId = form.dataset.postId;
        const input = form.querySelector('input[name="content"]');
        const submitBtn = form.querySelector('button[type="submit"]');
        const content = input ? input.value.trim() : '';
        if (!content) return;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = '...';
        }

        try {
            const res = await apiPost(`/social/posts/${postId}/comments`, { content });

            if (res.ok) {
                const data = await res.json();
                if (data.comment) {
                    const postCard = form.closest('.post-card');
                    const commentList = postCard.querySelector('.comment-list');
                    const isFirst = !commentList.children.length;
                    const html = `<div class="comment-item group flex gap-2.5 items-start py-2.5 ${isFirst ? '' : 'border-t border-gray-50'} animate-fade-in"
                         id="comment-${data.comment.id}"
                         data-comment-id="${data.comment.id}">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-emerald-500 flex items-center justify-center text-white text-xs font-semibold shrink-0 mt-0.5 shadow-sm">
                            ${escapeHtml(data.comment.avatar_initial)}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-semibold text-gray-700">${escapeHtml(data.comment.author)}</span>
                                    <span class="text-[11px] text-gray-400 comment-time">${escapeHtml(data.comment.created_at)}</span>
                                    <span class="text-[10px] text-gray-400 italic font-normal comment-edited-tag hidden">({{ __('edited') }})</span>
                                </div>
                                <div class="relative comment-menu-container">
                                    <button type="button" 
                                            class="comment-menu-btn p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors opacity-80 group-hover:opacity-100 focus:outline-none"
                                            onclick="toggleCommentMenu(event, this)"
                                            title="{{ __('Options') }}">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="5" cy="12" r="2"/>
                                            <circle cx="12" cy="12" r="2"/>
                                            <circle cx="19" cy="12" r="2"/>
                                        </svg>
                                    </button>
                                    <div class="comment-dropdown hidden absolute right-0 top-full mt-1 w-32 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-30 divide-y divide-gray-50">
                                        <button type="button" 
                                                onclick="startEditComment(${data.comment.id})"
                                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors text-left font-medium">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            <span>{{ __('Edit') }}</span>
                                        </button>
                                        <button type="button" 
                                                onclick="confirmDeleteComment(${data.comment.id})"
                                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors text-left font-medium">
                                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>{{ __('Delete') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p class="comment-text text-sm text-gray-600 mt-0.5 whitespace-pre-line break-words leading-relaxed">${escapeHtml(data.comment.content)}</p>
                            <div class="comment-edit-box hidden mt-2">
                                <textarea class="comment-edit-input w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition resize-none leading-relaxed" 
                                          rows="2" maxlength="1000">${escapeHtml(data.comment.content)}</textarea>
                                <div class="flex items-center justify-end gap-2 mt-1.5">
                                    <button type="button" 
                                            onclick="cancelEditComment(${data.comment.id})"
                                            class="px-2.5 py-1 text-xs font-medium text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                                        {{ __('Cancel') }}
                                    </button>
                                    <button type="button" 
                                            onclick="saveEditComment(${data.comment.id})"
                                            class="btn-save-comment px-3 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors shadow-sm shadow-primary/20">
                                        {{ __('Save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                    commentList.insertAdjacentHTML('beforeend', html);
                    const countEl = postCard.querySelector('.comment-count');
                    if (countEl) countEl.textContent = data.comments_count;
                    input.value = '';
                    commentList.scrollTop = commentList.scrollHeight;
                }
            } else {
                const errData = await res.json().catch(() => ({}));
                alert(errData.message || '{{ __("Unable to post comment. Please try again.") }}');
            }
        } catch (err) {
            console.error('Comment submit error:', err);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.dataset.originalText || '{{ __("Post") }}';
            }
        }
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

