@props([
    'tone' => 'neutral',
    'pill' => false,
])

@php
    $baseClass = $pill
        ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset'
        : 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset';

    $toneClass = [
        'neutral' => 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-700/30 dark:text-gray-400 dark:ring-gray-400/20',
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-900/20 dark:text-primary-300 dark:ring-primary-400/20',
        'info' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-300 dark:ring-blue-400/20',
        'success' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/20 dark:text-green-300 dark:ring-green-400/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-400/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/20 dark:text-red-300 dark:ring-red-400/20',
        'accent' => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/20 dark:text-purple-300 dark:ring-purple-400/20',
    ][$tone] ?? 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-700/30 dark:text-gray-400 dark:ring-gray-400/20';
@endphp

<span {{ $attributes->merge(['class' => $baseClass . ' ' . $toneClass]) }}>
    {{ $slot }}
</span>
