@props([
    'title' => __('No items found'),
    'description' => __('There are currently no items to display here.'),
    'icon' => 'folder',
    'cta' => null,
])

<div class="py-12 px-6 text-center bg-white rounded-3xl border border-gray-100 shadow-sm flex flex-col items-center justify-center">
    <div class="w-16 h-16 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 mb-4 shadow-sm ring-1 ring-teal-100">
        <x-icon :name="$icon" class="w-8 h-8 text-teal-600" />
    </div>
    <h3 class="text-base font-bold text-gray-900 mb-1.5">{{ $title }}</h3>
    <p class="text-sm text-slate-500 max-w-sm leading-relaxed">{{ $description }}</p>
    
    @if(isset($cta) && $cta)
        <div class="mt-5">
            {{ $cta }}
        </div>
    @elseif($slot->isNotEmpty())
        <div class="mt-5">
            {{ $slot }}
        </div>
    @endif
</div>
