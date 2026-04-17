@php
    $class = 'wcag-touch-target inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-800 focus:bg-primary-800 active:bg-primary-900 disabled:cursor-not-allowed disabled:opacity-60';
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
