@props([
    'posts',
    'user',
    'isOwner' => false,
])

<div class="space-y-6">
    @forelse($posts as $post)
        @include('social.post-card', ['post' => $post])
    @empty
        <x-profile.empty-state
            :title="$isOwner ? __('Share your first travel story') : __('No posts published yet')"
            :description="$isOwner ? __('Post your itineraries, vacation photos, and trip tips to inspire other explorers in the community.') : __('This explorer has not shared any stories or photos yet.')"
            icon="camera"
        >
            @if($isOwner)
                <x-slot:cta>
                    <a href="{{ route('social.feed') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-semibold text-white bg-primary hover:bg-teal-700 shadow-md shadow-primary/20 transition-all">
                        <x-icon name="pencil" class="w-4 h-4 text-white" />
                        <span>{{ __('Write your first post') }}</span>
                    </a>
                </x-slot:cta>
            @endif
        </x-profile.empty-state>
    @endforelse

    @if($posts->hasPages())
        <div class="pt-4">
            {{ $posts->links() }}
        </div>
    @endif
</div>
