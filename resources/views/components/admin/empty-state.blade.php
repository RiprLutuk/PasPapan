@props([
    'title',
    'description' => null,
    'framed' => false,
])

@php
    $baseClass = $framed
        ? 'mx-auto max-w-3xl rounded-xl border border-gray-200/60 bg-white/90 p-12 text-center shadow-sm dark:border-gray-700/60 dark:bg-gray-800/90'
        : 'mx-auto max-w-3xl px-6 py-12 text-center';
@endphp

<div {{ $attributes->merge(['class' => $baseClass]) }}>
    @isset($icon)
        <div class="mx-auto mb-4 flex justify-center">
            {{ $icon }}
        </div>
    @endisset

    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $title }}</h3>

    @if ($description)
        <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @isset($actions)
        <div class="mt-4">
            {{ $actions }}
        </div>
    @endisset
</div>
