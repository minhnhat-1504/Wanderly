@props([
    'savedPosts',
    'user',
])

<div class="space-y-6">
    @forelse($savedPosts as $post)
        @include('social.post-card', ['post' => $post])
    @empty
        <x-profile.empty-state
            :title="__('No saved posts yet')"
            :description="__('When you find inspiring itineraries or useful travel stories on the Community Feed, click the bookmark icon to save them here for quick access.')"
            icon="bookmark"
        >
            <x-slot:cta>
                <a href="{{ route('social.feed') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-semibold text-white bg-primary hover:bg-teal-700 shadow-md shadow-primary/20 transition-all">
                    <x-icon name="compass" class="w-4 h-4 text-white" />
                    <span>{{ __('Explore Community Feed') }}</span>
                </a>
            </x-slot:cta>
        </x-profile.empty-state>
    @endforelse

    @if($savedPosts && $savedPosts->hasPages())
        <div class="pt-4">
            {{ $savedPosts->links() }}
        </div>
    @endif
</div>
