@php
    $class = 'wcag-touch-target inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 disabled:opacity-25 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700';
@endphp

@if (!isset($attributes['href']))
  <button {{ $attributes->merge(['type' => 'submit', 'class' => $class]) }}>
    {{ $slot }}
  </button>
@else
  <a {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
  </a>
@endif
