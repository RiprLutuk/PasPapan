@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200/50 bg-white/80 p-12 text-center shadow-xl backdrop-blur-xl dark:border-gray-700/50 dark:bg-gray-800/80']) }}>
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
