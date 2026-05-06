@props([
    'tone' => 'info',
])

@php
    $toneClasses = match ($tone) {
        'success' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300',
        'danger' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300',
        'primary' => 'border-primary-200 bg-primary-50 text-primary-800 dark:border-primary-900/50 dark:bg-primary-900/20 dark:text-primary-300',
        default => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/50 dark:bg-sky-900/20 dark:text-sky-300',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 ' . $toneClasses]) }}>
    {{ $slot }}
</div>
