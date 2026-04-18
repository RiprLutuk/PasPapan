@props([
    'variant' => 'primary',
    'size' => 'md',
    'label' => null,
])

@php
    $baseClass = 'wcag-touch-target inline-flex items-center justify-center gap-2 rounded-xl border text-sm font-semibold shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-gray-900';

    $variantClass = [
        'primary' => 'border-transparent bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-600 active:bg-primary-900',
        'secondary' => 'border-gray-300 bg-white text-gray-800 hover:bg-gray-50 focus:ring-primary-600 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700',
        'success' => 'border-transparent bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-600 active:bg-emerald-800',
        'warning' => 'border-transparent bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-500 active:bg-amber-700',
        'danger' => 'border-transparent bg-red-600 text-white hover:bg-red-700 focus:ring-red-600 active:bg-red-800',
        'ghost' => 'border-transparent bg-transparent text-gray-700 shadow-none hover:bg-gray-100 focus:ring-primary-600 dark:text-gray-200 dark:hover:bg-gray-800',
        'soft-primary' => 'border-transparent bg-primary-50 text-primary-700 shadow-none hover:bg-primary-100 focus:ring-primary-600 dark:bg-primary-950/30 dark:text-primary-200 dark:hover:bg-primary-900/40',
        'soft-success' => 'border-transparent bg-emerald-50 text-emerald-700 shadow-none hover:bg-emerald-100 focus:ring-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-200 dark:hover:bg-emerald-900/40',
        'soft-warning' => 'border-transparent bg-amber-50 text-amber-700 shadow-none hover:bg-amber-100 focus:ring-amber-600 dark:bg-amber-950/30 dark:text-amber-200 dark:hover:bg-amber-900/40',
        'soft-danger' => 'border-transparent bg-red-50 text-red-700 shadow-none hover:bg-red-100 focus:ring-red-600 dark:bg-red-950/30 dark:text-red-200 dark:hover:bg-red-900/40',
    ][$variant] ?? 'border-transparent bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-600 active:bg-primary-900';

    $sizeClass = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2.5',
        'lg' => 'px-5 py-3 text-base',
        'icon' => 'h-10 w-10 p-0',
    ][$size] ?? 'px-4 py-2.5';

    $class = trim($baseClass . ' ' . $variantClass . ' ' . $sizeClass);

    $accessibilityAttributes = [];
    if ($label) {
        $accessibilityAttributes['aria-label'] = $label;
        $accessibilityAttributes['title'] = $label;
    }
@endphp

@if (!isset($attributes['href']))
  <button {{ $attributes->merge(array_merge(['type' => 'submit', 'class' => $class], $accessibilityAttributes)) }}>
    {{ $slot }}
  </button>
@else
  <a {{ $attributes->merge(array_merge(['class' => $class], $accessibilityAttributes)) }}>
    {{ $slot }}
  </a>
@endif
