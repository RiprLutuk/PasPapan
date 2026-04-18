@props(['active'])

@php
$classes = ($active ?? false)
            ? 'wcag-touch-target block w-full border-l-4 border-primary-700 bg-primary-50 py-2.5 pe-4 ps-3 text-start text-base font-semibold text-primary-800 transition duration-150 ease-in-out dark:border-primary-400 dark:bg-primary-950/60 dark:text-primary-100'
            : 'wcag-touch-target block w-full border-l-4 border-transparent py-2.5 pe-4 ps-3 text-start text-base font-medium text-gray-700 transition duration-150 ease-in-out hover:border-gray-400 hover:bg-gray-50 hover:text-gray-950 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:bg-gray-700 dark:hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
