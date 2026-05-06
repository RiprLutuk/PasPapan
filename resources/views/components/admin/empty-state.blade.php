@props([
    'title',
    'description' => null,
    'framed' => false,
])

@php
    $baseClass = $framed
        ? 'mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5'
        : 'mx-auto max-w-2xl px-4 py-6 text-center sm:py-8';
@endphp

<div {{ $attributes->merge(['class' => $baseClass]) }}>
    @isset($icon)
        <div class="mx-auto mb-3 flex justify-center">
            {{ $icon }}
        </div>
    @endisset

    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @isset($actions)
        <div class="mt-3">
            {{ $actions }}
        </div>
    @endisset
</div>
