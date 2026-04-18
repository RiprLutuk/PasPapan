@props([
    'checked' => false,
    'label' => null,
    'size' => 'md',
    'checkedClass' => 'bg-primary-600',
    'uncheckedClass' => 'bg-gray-200 dark:bg-gray-700',
    'focusClass' => 'focus:ring-primary-600',
])

@php
    $sizeClasses = match ($size) {
        'sm' => [
            'track' => 'h-6 w-11',
            'thumb' => 'h-5 w-5',
            'translate' => 'translate-x-5',
        ],
        'lg' => [
            'track' => 'h-7 w-14',
            'thumb' => 'h-6 w-6',
            'translate' => 'translate-x-7',
        ],
        default => [
            'track' => 'h-7 w-12',
            'thumb' => 'h-5 w-5',
            'translate' => 'translate-x-5',
        ],
    };
@endphp

<button
    type="button"
    role="switch"
    aria-checked="{{ $checked ? 'true' : 'false' }}"
    @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->merge([
        'class' => 'relative inline-flex flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 ' . $focusClass . ' ' . $sizeClasses['track'] . ' ' . ($checked ? $checkedClass : $uncheckedClass),
    ]) }}
>
    <span class="pointer-events-none inline-block transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $sizeClasses['thumb'] }} {{ $checked ? $sizeClasses['translate'] : 'translate-x-0' }}"></span>
</button>
