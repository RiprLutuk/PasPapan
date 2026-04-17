@php
    $primaryAction = $primaryAction ?? null;
    $secondaryAction = $secondaryAction ?? null;
    $details = array_values(array_filter($details ?? []));

    $toneClasses = match ($tone ?? 'primary') {
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200',
        'red' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-200',
        'blue' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-200',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-800 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-200',
        default => 'border-primary-200 bg-primary-50 text-primary-800 dark:border-primary-900/40 dark:bg-primary-950/20 dark:text-primary-200',
    };
@endphp

<div class="space-y-6 text-left">
    <div class="inline-flex min-h-[2.75rem] items-center gap-3 rounded-full border px-4 py-2 {{ $toneClasses }}">
        <span class="text-xs font-black uppercase tracking-[0.24em]">{{ __('HTTP Status') }}</span>
        <span class="text-base font-black">{{ $status }}</span>
    </div>

    <div class="space-y-3">
        @if (!empty($eyebrow))
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-700 dark:text-primary-300">{{ $eyebrow }}</p>
        @endif

        <h1 id="error-page-title" class="text-3xl font-black tracking-tight text-gray-950 dark:text-white sm:text-4xl">
            {{ $titleText }}
        </h1>

        <p class="max-w-2xl text-base leading-8 text-gray-700 dark:text-gray-300">
            {{ $summary }}
        </p>
    </div>

    @if ($details !== [])
        <section aria-labelledby="error-details-title" class="rounded-3xl border border-gray-200 bg-white/80 p-5 dark:border-gray-800 dark:bg-gray-950/30">
            <h2 id="error-details-title" class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-900 dark:text-gray-100">
                {{ __('What this usually means') }}
            </h2>

            <ul class="mt-4 space-y-3 text-sm leading-7 text-gray-700 dark:text-gray-300">
                @foreach ($details as $detail)
                    <li class="flex gap-3">
                        <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-primary-600 dark:bg-primary-400" aria-hidden="true"></span>
                        <span>{{ $detail }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-3 sm:grid-cols-2" aria-label="{{ __('Available actions') }}">
        @if ($primaryAction)
            <a href="{{ $primaryAction['href'] }}"
               class="wcag-touch-target inline-flex items-center justify-center rounded-2xl bg-primary-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition duration-150 ease-in-out hover:bg-primary-800">
                {{ $primaryAction['label'] }}
            </a>
        @endif

        @if ($secondaryAction)
            <a href="{{ $secondaryAction['href'] }}"
               class="wcag-touch-target inline-flex items-center justify-center rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-800 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800">
                {{ $secondaryAction['label'] }}
            </a>
        @endif
    </div>
</div>
