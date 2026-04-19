@props([
    'title' => null,
    'description' => null,
    'gridClass' => 'grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-12',
])

<div {{ $attributes }}>
    @if ($title || $description || isset($summary))
        <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0 max-w-2xl">
                @if ($title)
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
                @endif

                @if ($description)
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $description }}</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @isset($summary)
                    {{ $summary }}
                @endisset

                @isset($actions)
                    {{ $actions }}
                @endisset
            </div>
        </div>
    @endif

    <div class="{{ $gridClass }}">
        {{ $slot }}
    </div>
</div>
