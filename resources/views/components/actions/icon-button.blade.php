@props([
    'label',
    'variant' => 'neutral',
])

@php
    $variantClasses = [
        'neutral' => 'border-transparent bg-transparent text-gray-700 shadow-none hover:bg-gray-100 focus:ring-primary-600 dark:text-gray-200 dark:hover:bg-gray-800',
        'primary' => 'border-transparent bg-primary-50 text-primary-700 shadow-none hover:bg-primary-100 focus:ring-primary-600 dark:bg-primary-950/30 dark:text-primary-200 dark:hover:bg-primary-900/40',
        'success' => 'border-transparent bg-emerald-50 text-emerald-700 shadow-none hover:bg-emerald-100 focus:ring-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-200 dark:hover:bg-emerald-900/40',
        'warning' => 'border-transparent bg-amber-50 text-amber-700 shadow-none hover:bg-amber-100 focus:ring-amber-600 dark:bg-amber-950/30 dark:text-amber-200 dark:hover:bg-amber-900/40',
        'danger' => 'border-transparent bg-red-50 text-red-700 shadow-none hover:bg-red-100 focus:ring-red-600 dark:bg-red-950/30 dark:text-red-200 dark:hover:bg-red-900/40',
    ][$variant] ?? 'border-transparent bg-transparent text-gray-700 shadow-none hover:bg-gray-100 focus:ring-primary-600 dark:text-gray-200 dark:hover:bg-gray-800';

    $classes = 'wcag-touch-target inline-flex h-10 w-10 items-center justify-center gap-2 rounded-xl border p-0 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-gray-900 ' . $variantClasses;
@endphp

@if (!isset($attributes['href']))
    <button
        type="button"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@else
    <a
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@endif
