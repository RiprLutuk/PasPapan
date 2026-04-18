@props(['active'])

@php
$classes = ($active ?? false)
            ? 'wcag-touch-target inline-flex items-center border-b-2 border-primary-700 px-2 py-1 text-sm font-semibold leading-5 text-gray-950 transition duration-150 ease-in-out dark:border-primary-400 dark:text-white'
            : 'wcag-touch-target inline-flex items-center border-b-2 border-transparent px-2 py-1 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out hover:border-gray-400 hover:text-gray-950 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
