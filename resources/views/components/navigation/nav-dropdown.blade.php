@props([
    'active' => false,
    'align' => 'left',
    'contentClasses' => 'py-1 bg-white dark:bg-gray-800',
    'dropdownClasses' => 'w-48',
    'id' => null,
    'triggerClasses' => '',
])

@php
  $dropdownId = $id ?: 'nav-dropdown-' . uniqid();

  switch ($align) {
      case 'left':
          $alignmentClasses = 'ltr:origin-top-left rtl:origin-top-right start-0';
          break;
      case 'top':
          $alignmentClasses = 'origin-top';
          break;
      case 'none':
      case 'false':
          $alignmentClasses = '';
          break;
      case 'right':
      default:
          $alignmentClasses = 'ltr:origin-top-right rtl:origin-top-left end-0';
          break;
  }
  $classes = $active
      ? 'wcag-touch-target inline-flex items-center border-b-2 border-primary-700 px-2 py-1 text-sm font-semibold leading-5 text-gray-950 transition duration-150 ease-in-out dark:border-primary-400 dark:text-white'
      : 'wcag-touch-target inline-flex items-center border-b-2 border-transparent px-2 py-1 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out hover:border-gray-400 hover:text-gray-950 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white';
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-flex h-full items-center']) }} x-data="{ open: false }" @click.away="open = false"
  @keydown.escape.window="open = false" @close.stop="open = false">
  <button
    type="button"
    @click="open = ! open"
    class="{{ $classes }} {{ $triggerClasses }}"
    :aria-expanded="open.toString()"
    aria-controls="{{ $dropdownId }}"
    aria-haspopup="true"
    @if ($active) aria-current="page" @endif>
    {{ $trigger }}
  </button>
  <div>
    <div id="{{ $dropdownId }}" x-show="open" x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
      x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
      x-transition:leave-end="transform opacity-0 scale-95"
      class="{{ $alignmentClasses }} {{ $dropdownClasses }} absolute z-50 mt-2 rounded-md shadow-lg"
      style="display: none;" @click="open = false">
      <div class="{{ $contentClasses }} rounded-md ring-1 ring-black/10 dark:ring-white/10">
        {{ $content }}
      </div>
    </div>
  </div>
</div>
