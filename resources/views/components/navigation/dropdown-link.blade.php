@props(['active' => false])
<a
  {{ $attributes->merge(['class' => 'wcag-touch-target block w-full rounded-md px-4 py-2.5 text-start text-sm leading-5 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-800 ' . ($active ? 'bg-gray-100 font-semibold text-gray-950 dark:bg-gray-800 dark:text-white' : 'text-gray-800 dark:text-gray-100')]) }}
  @if ($active) aria-current="page" @endif>
  {{ $slot }}
</a>
