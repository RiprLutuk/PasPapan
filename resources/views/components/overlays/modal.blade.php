@props(['id', 'maxWidth', 'onclose' => null])

@php
  $id = $id ?? md5($attributes->wire('model'));

  $maxWidth = [
      'sm' => 'sm:max-w-sm',
      'md' => 'sm:max-w-md',
      'lg' => 'sm:max-w-lg',
      'xl' => 'sm:max-w-xl',
      '2xl' => 'sm:max-w-2xl',
      '3xl' => 'sm:max-w-3xl',
      '4xl' => 'sm:max-w-4xl',
      '5xl' => 'sm:max-w-5xl',
      '6xl' => 'sm:max-w-6xl',
      '7xl' => 'sm:max-w-7xl',
      'full' => 'sm:max-w-full',
  ][$maxWidth ?? '2xl'];
@endphp

<div x-data="{ show: @entangle($attributes->wire('model')) }" x-on:keydown.escape.window="show = false; {{ $onclose }}">
  <template x-teleport="body">
    <div x-show="show" x-effect="if (show) { $nextTick(() => window.initUiPickers?.($el)) }" x-on:close.stop="show = false; {{ $onclose }}" id="{{ $id }}"
      class="jetstream-modal fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]"
      style="display: none;">
      <div x-show="show" class="fixed inset-0 transform transition-all" x-on:click="show = false; {{ $onclose }}"
        x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-gray-500 opacity-75 dark:bg-gray-900"></div>
      </div>

      <div x-show="show"
        class="{{ $maxWidth }} w-full transform overflow-y-auto rounded-lg bg-white shadow-xl transition-all dark:bg-gray-800 sm:mx-auto"
        style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));"
        role="dialog"
        aria-modal="true"
        x-trap.inert.noscroll="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        {{ $slot }}
      </div>
    </div>
  </template>
</div>
