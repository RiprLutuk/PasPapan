@props([
    'title',
    'description' => null,
    'containerClass' => 'w-full px-4 sm:px-6 lg:px-8 2xl:px-10',
    'showDescription' => false,
])

@php
    $titleId = \Illuminate\Support\Str::slug($title).'-title';
    $descriptionId = $description ? \Illuminate\Support\Str::slug($title).'-description' : null;
@endphp

<section class="relative" aria-labelledby="{{ $titleId }}" @if($descriptionId) aria-describedby="{{ $descriptionId }}" @endif>
    <div {{ $attributes->merge(['class' => $containerClass . ' py-4 sm:py-5 lg:py-6']) }}>
        <div class="mb-4 flex flex-col gap-3 border-b border-slate-200/70 pb-4 dark:border-slate-800 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 id="{{ $titleId }}" class="truncate text-xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                    {{ $title }}
                </h1>

                @if ($description)
                    <p id="{{ $descriptionId }}" class="{{ $showDescription ? 'mt-1 max-w-3xl text-sm leading-5 text-slate-600 dark:text-slate-300' : 'sr-only' }}">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                    {{ $actions }}
                </div>
            @endisset
        </div>

        @isset($toolbar)
            <div class="mb-4 rounded-xl border border-slate-200/70 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                {{ $toolbar }}
            </div>
        @endisset

        <div class="space-y-4">
            {{ $slot }}
        </div>
    </div>
</section>
