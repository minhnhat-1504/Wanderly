@php
    $isCommentAuthor = auth()->check() && auth()->id() === $comment->user_id;
    $canDeleteComment = auth()->check() && ($isCommentAuthor || auth()->id() === $post->user_id);
    $authorName = $comment->author->name ?? __('User');
    $authorUsername = $comment->author->username ?? null;
    $avatarInitial = strtoupper(substr($authorName, 0, 1));
    $isEdited = $comment->updated_at && $comment->updated_at->gt($comment->created_at->copy()->addSeconds(5));
@endphp

<div class="comment-item group flex gap-2.5 items-start py-2.5 {{ !$loop->first ? 'border-t border-gray-50' : '' }}"
     id="comment-{{ $comment->id }}"
     data-comment-id="{{ $comment->id }}">
    
    {{-- Avatar --}}
    @if($comment->author && $authorUsername)
        <a href="{{ route('profile.show', $authorUsername) }}" class="shrink-0 mt-0.5">
            <x-profile.avatar :user="$comment->author" size="sm" />
        </a>
    @else
        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-emerald-500 flex items-center justify-center text-white text-xs font-semibold shrink-0 mt-0.5 shadow-sm">
            {{ $avatarInitial }}
        </div>
    @endif

    {{-- Content Area --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 flex-wrap">
                @if($authorUsername)
                    <a href="{{ route('profile.show', $authorUsername) }}" class="text-xs font-semibold text-gray-700 hover:text-primary transition-colors">
                        {{ $authorName }}
                    </a>
                @else
                    <span class="text-xs font-semibold text-gray-700">{{ $authorName }}</span>
                @endif
                <span class="text-[11px] text-gray-400 comment-time">{{ $comment->created_at->diffForHumans() }}</span>
                <span class="text-[10px] text-gray-400 italic font-normal comment-edited-tag {{ $isEdited ? '' : 'hidden' }}">({{ __('edited') }})</span>
            </div>

            {{-- ... Options Dropdown Menu --}}
            @if($canDeleteComment)
            <div class="relative comment-menu-container">
                <button type="button" 
                        class="comment-menu-btn p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors opacity-80 group-hover:opacity-100 focus:outline-none"
                        onclick="toggleCommentMenu(event, this)"
                        title="{{ __('Options') }}"
                        aria-label="{{ __('Comment options') }}">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="5" cy="12" r="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <circle cx="19" cy="12" r="2"/>
                    </svg>
                </button>

                <div class="comment-dropdown hidden absolute right-0 top-full mt-1 w-32 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-30 divide-y divide-gray-50">
                    @if($isCommentAuthor)
                    <button type="button" 
                            onclick="startEditComment({{ $comment->id }})"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors text-left font-medium">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                        <span>{{ __('Edit') }}</span>
                    </button>
                    @endif
                    <button type="button" 
                            onclick="confirmDeleteComment({{ $comment->id }})"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors text-left font-medium">
                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>{{ __('Delete') }}</span>
                    </button>
                </div>
            </div>
            @endif
        </div>

        {{-- Normal Text View --}}
        <p class="comment-text text-sm text-gray-600 mt-0.5 whitespace-pre-line break-words leading-relaxed">{{ $comment->content }}</p>

        {{-- Inline Edit Box --}}
        @if($isCommentAuthor)
        <div class="comment-edit-box hidden mt-2">
            <textarea class="comment-edit-input w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition resize-none leading-relaxed" 
                      rows="2" maxlength="1000">{{ $comment->content }}</textarea>
            <div class="flex items-center justify-end gap-2 mt-1.5">
                <button type="button" 
                        onclick="cancelEditComment({{ $comment->id }})"
                        class="px-2.5 py-1 text-xs font-medium text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button type="button" 
                        onclick="saveEditComment({{ $comment->id }})"
                        class="btn-save-comment px-3 py-1 text-xs font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors shadow-sm shadow-primary/20">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
        @endif
    </div>
</div>
