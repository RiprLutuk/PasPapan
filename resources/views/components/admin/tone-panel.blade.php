@props([
    'tone' => 'neutral',
])

@php
    $toneClasses = match ($tone) {
        'primary' => 'border-primary-200/70 bg-primary-50/70 dark:border-primary-900/40 dark:bg-primary-900/10',
        'amber' => 'border-amber-200/70 bg-amber-50/80 dark:border-amber-900/40 dark:bg-amber-900/10',
        'sky' => 'border-sky-200/70 bg-sky-50/80 dark:border-sky-900/40 dark:bg-sky-900/10',
        'rose' => 'border-rose-200/70 bg-rose-50/80 dark:border-rose-900/40 dark:bg-rose-900/10',
        'emerald' => 'border-emerald-200/70 bg-emerald-50/80 dark:border-emerald-900/40 dark:bg-emerald-900/10',
        default => 'border-slate-200/70 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-800/70',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border ' . $toneClasses]) }}>
    {{ $slot }}
</div>
