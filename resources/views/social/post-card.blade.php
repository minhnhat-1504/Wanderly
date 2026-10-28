{{--
    Post Card Component
    Variables: $post (Post model with author, trip, likedByUsers, savedByUsers eager-loaded)
--}}
@php
    $isLiked = auth()->check() && $post->isLikedBy(auth()->user());
    $isSaved = auth()->check() && $post->isSavedBy(auth()->user());
    $isAuthor = auth()->check() && auth()->id() === $post->user_id;
    $avatarInitial = strtoupper(substr($post->author->name ?? 'U', 0, 1));
@endphp

<div class="feed-card post-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" id="post-{{ $post->id }}">

    {{-- Post Header --}}
    <div class="flex items-start justify-between p-5 pb-3">
        <div class="flex items-center gap-3">
            @if($post->author && $post->author->username)
                <a href="{{ route('profile.show', $post->author->username) }}" class="shrink-0 group">
                    <x-profile.avatar :user="$post->author" size="md" class="group-hover:ring-primary/40 transition-all" />
                </a>
            @else
                <div class="w-10 h-10 rounded-xl post-avatar flex items-center justify-center text-white font-bold text-base shrink-0">
                    {{ $avatarInitial }}
                </div>
            @endif
            <div>
                @if($post->author && $post->author->username)
                    <a href="{{ route('profile.show', $post->author->username) }}" class="font-semibold text-gray-900 text-sm hover:text-primary transition-colors">
                        {{ $post->author->name }}
                    </a>
                @else
                    <p class="font-semibold text-gray-900 text-sm">{{ $post->author->name ?? __('Unknown') }}</p>
                @endif
                <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
            </div>
        </div>

        {{-- Author Actions --}}
        @if($isAuthor)
        <button type="button"
                onclick="openDeleteModal('{{ route('social.posts.destroy', $post) }}')"
                class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                title="{{ __('Delete Post') }}"
                id="btn-delete-post-{{ $post->id }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
        @endif
    </div>

    {{-- Post Content --}}
    <div class="px-5 pb-3">
        <h3 class="font-bold text-gray-900 text-base mb-1.5">{{ $post->title }}</h3>
        <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">{{ Str::limit($post->content, 300) }}</p>
    </div>

    {{-- Attached Trip Card --}}
    @if($post->trip)
    <div class="mx-5 mb-4 rounded-xl overflow-hidden border border-violet-100 bg-gradient-to-br from-violet-50 to-indigo-50">
        <div class="trip-card-badge px-4 py-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-white opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <span class="text-white text-xs font-semibold uppercase tracking-wide">{{ __('Trip Card') }}</span>
        </div>
        <div class="px-4 py-3 flex items-center justify-between gap-4">
            <div>
                <p class="font-semibold text-gray-900 text-sm">{{ $post->trip->name }}</p>
                <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                    <x-icon name="map-pin" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                    <span>{{ $post->trip->destination }} · {{ $post->trip->start_date->format('d M') }} — {{ $post->trip->end_date->format('d M Y') }}</span>
                </p>
                @if($post->trip->budget > 0)
                <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                    <x-icon name="wallet" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                    <span>{{ __('Budget') }}: {{ number_format($post->trip->budget, 0, ',', '.') }} VND</span>
                </p>
                @endif
            </div>
            {{-- Clone Trip Button --}}
            @auth
            <form method="POST" action="{{ route('social.posts.clone-trip', $post) }}">
                @csrf
                <button type="submit"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-white text-violet-700 rounded-lg border border-violet-200 hover:bg-violet-50 transition-colors"
                        title="{{ __('Clone this trip to your account') }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    {{ __('Clone Trip') }}
                </button>
            </form>
            @endauth
        </div>
    </div>
    @endif

    {{-- Action Bar --}}
    <div class="flex items-center gap-1 px-5 py-3 border-t border-gray-50">
        {{-- Like --}}
        <button class="like-btn flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm text-gray-500 hover:bg-red-50 hover:text-red-500 transition-colors {{ $isLiked ? 'liked text-red-500 bg-red-50' : '' }}"
                data-post-id="{{ $post->id }}"
                title="{{ __('Like') }}">
            <svg class="w-4 h-4" fill="{{ $isLiked ? '#ef4444' : 'none' }}" stroke="{{ $isLiked ? '#ef4444' : 'currentColor' }}" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span class="like-count font-medium">{{ $post->likes_count }}</span>
        </button>

        {{-- Comment toggle --}}
        <button type="button"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm text-gray-500 hover:bg-blue-50 hover:text-blue-500 transition-colors"
                onclick="togglePostComments(this)"
                title="{{ __('Comment') }}"
                id="btn-comment-toggle-{{ $post->id }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span class="comment-count font-medium">{{ $post->comments_count }}</span>
        </button>

        {{-- Save --}}
        <button class="save-btn ml-auto flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm text-gray-500 hover:bg-yellow-50 hover:text-yellow-500 transition-colors {{ $isSaved ? 'saved text-yellow-500 bg-yellow-50' : '' }}"
                data-post-id="{{ $post->id }}"
                title="{{ $isSaved ? __('Unsave') : __('Save') }}">
            <svg class="w-4 h-4" fill="{{ $isSaved ? '#f59e0b' : 'none' }}" stroke="{{ $isSaved ? '#f59e0b' : 'currentColor' }}" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        </button>

        {{-- Report Post --}}
        @auth
        @if(!$isAuthor)
        <button type="button"
                onclick="window.openReportModal && window.openReportModal('{{ route('social.posts.report', $post->id) }}', '{{ addslashes($post->title) }}')"
                class="flex items-center gap-1 px-2.5 py-2 rounded-xl text-xs text-gray-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                title="{{ __('Report violation') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
            <span class="hidden sm:inline text-[11px] font-medium">{{ __('Report') }}</span>
        </button>
        @endif
        @endauth
    </div>

    {{-- Comments Section --}}
    <div class="comments-section hidden border-t border-gray-50 px-5 py-4">
        {{-- Existing comments --}}
        <div class="comment-list space-y-0 mb-3 max-h-48 overflow-y-auto">
            @foreach($post->comments->take(5) as $comment)
                @include('social.comment-item', ['comment' => $comment, 'post' => $post])
            @endforeach
        </div>

        {{-- New Comment Input --}}
        @auth
        <form class="comment-form flex gap-2" data-post-id="{{ $post->id }}">
            @csrf
            <input type="text" name="content" maxlength="1000" required
                   placeholder="{{ __('Add a comment...') }}"
                   class="flex-1 px-4 py-2 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition">
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary/90 transition-colors">
                {{ __('Post') }}
            </button>
        </form>
        @endauth
    </div>
</div>
