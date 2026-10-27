@props([
    'badge',
])

@php
    $isEarned = $badge['earned'] ?? true;
@endphp

<div class="flex items-center justify-between p-3 rounded-2xl border transition-all duration-200 {{ $isEarned ? 'bg-white border-gray-100 shadow-sm hover:border-teal-200 hover:shadow-md' : 'bg-gray-50/60 border-dashed border-gray-200 opacity-60' }}">
    <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $isEarned ? 'bg-teal-50 text-teal-700 ring-1 ring-teal-100' : 'bg-gray-100 text-gray-400' }}">
            @if($isEarned)
                <x-icon :name="$badge['icon_name'] ?? 'compass'" class="w-5 h-5 text-teal-600" />
            @else
                <x-icon name="lock" class="w-4 h-4 text-gray-400" />
            @endif
        </div>
        <div class="min-w-0">
            <h4 class="text-xs sm:text-sm font-bold text-gray-900 truncate">{{ $badge['name'] }}</h4>
            <p class="text-[11px] text-slate-500 line-clamp-1">{{ $badge['description'] }}</p>
        </div>
    </div>

    @if(isset($badge['progress']))
        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0 {{ $isEarned ? 'bg-teal-50 text-teal-700' : 'bg-gray-100 text-gray-500' }}">
            {{ $badge['progress'] }}
        </span>
    @endif
</div>
