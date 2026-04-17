<div {{ $attributes->merge(['class' => 'flex items-center justify-center']) }}>
  <input id="{{ $id ?? 'theme-switcher' }}" type="checkbox" name="switch" class="hidden" :checked="$store.darkMode.on">

  <button type="button" @click="$store.darkMode.toggle()"
    class="topbar-tool topbar-tool--icon"
    :aria-pressed="$store.darkMode.on.toString()"
    aria-haspopup="false"
    aria-label="{{ __('Toggle color theme') }}">
    <span class="sr-only">{{ __('Toggle color theme') }}</span>
    <x-heroicon-o-moon class="h-5 w-5" x-show="$store.darkMode.on" />
    <x-heroicon-o-sun class="h-5 w-5" x-show="!$store.darkMode.on" />
  </button>
</div>
