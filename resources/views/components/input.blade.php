@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => null,
    'placeholder' => '',
    'required' => false,
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-0.5">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        @if($type !== 'password')
            value="{{ old($name, $value) }}"
        @endif
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' => 'block w-full px-4 py-2.5 rounded-xl border bg-white text-gray-900 placeholder-gray-400 shadow-sm transition duration-200 focus:outline-none focus:ring-2 sm:text-sm '
                . ($errors->has($name)
                    ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-gray-300 focus:border-primary focus:ring-primary/20 hover:border-gray-400')
        ]) }}
    >

    @error($name)
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>
