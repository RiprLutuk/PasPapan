@props([
    'title',
    'description' => null,
    'containerClass' => 'w-full px-4 sm:px-6 lg:px-8 2xl:px-10',
])

<section class="relative">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-56 overflow-hidden">
        <div class="absolute inset-x-0 top-0 h-full bg-gradient-to-b from-slate-200/70 via-white/30 to-transparent dark:from-slate-800/70 dark:via-slate-900/10"></div>
        <div class="absolute -left-24 top-6 h-40 w-40 rounded-full bg-cyan-200/40 blur-3xl dark:bg-cyan-500/10"></div>
        <div class="absolute right-0 top-0 h-48 w-48 rounded-full bg-emerald-200/30 blur-3xl dark:bg-emerald-500/10"></div>
    </div>

    <div {{ $attributes->merge(['class' => $containerClass . ' py-6 sm:py-8 lg:py-10']) }}>
        <div class="mb-6 flex flex-col gap-4 lg:mb-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    {{ $title }}
                </h1>

                @if ($description)
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                    {{ $actions }}
                </div>
            @endisset
        </div>

        @isset($toolbar)
            <div class="mb-6 rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm shadow-slate-200/60 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-none">
                {{ $toolbar }}
            </div>
        @endisset

        <div class="space-y-6">
            {{ $slot }}
        </div>
    </div>
</section>
