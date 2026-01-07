<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ $appName ?? config('app.name', 'Laravel') }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon-circle.png') }}">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <script>
      if (localStorage.getItem('isDark') === 'true' || (!('isDark' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
          document.documentElement.classList.add('dark');
      } else {
          document.documentElement.classList.remove('dark');
      }
  </script>

  <!-- Scripts -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Styles -->
  @livewireStyles
</head>

<body class="font-sans antialiased">
  <div class="font-sans text-gray-900 antialiased dark:text-gray-100 pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)]">

    <div class="absolute right-4 top-4 flex gap-2">
      <!-- Language Switcher -->
      <div class="flex items-center">
        <form method="POST" action="{{ route('user.language.update') }}">
            @csrf
            <input type="hidden" name="language" value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
            <button type="submit"
                class="relative inline-flex h-6 w-12 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600">
                <span class="sr-only">Switch Language</span>
                <!-- Labels -->
                <span class="absolute inset-0 flex h-full w-full items-center justify-between px-1.5 text-[8px] font-bold text-gray-500 select-none">
                    <span>ID</span>
                    <span>EN</span>
                </span>
                <!-- Knob -->
                <span
                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ app()->getLocale() == 'en' ? 'translate-x-[24px]' : 'translate-x-0' }}">
                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100">
                        <span class="text-[10px] leading-none pt-0.5">
                            {{ app()->getLocale() == 'id' ? '🇮🇩' : '🇺🇸' }}
                        </span>
                    </span>
                </span>
            </button>
        </form>
      </div>

      <x-theme-toggle x-data />
    </div>

    {{ $slot }}

  </div>

  @livewireScripts

  <script src="{{ asset('js/pulltorefresh.js') }}"></script>
  <script>
      document.addEventListener('DOMContentLoaded', () => {
          const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone || document.referrer.includes('android-app://');
          // Safer check for Capacitor native platform
          const isNative = window.Capacitor && (
              (typeof Capacitor.isNativePlatform === 'function' && Capacitor.isNativePlatform()) ||
              Capacitor.isNativePlatform === true ||
              Capacitor.getPlatform() !== 'web'
          );

          const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

          if (isPWA || isNative || isTouch) {
              PullToRefresh.init({
                  mainElement: 'body',
                  onRefresh() {
                      window.location.reload();
                  },
                  iconArrow: '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>',
                  iconRefreshing: '<svg class="w-6 h-6 animate-spin" fill="currentColor" viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>',
                  instructionsPullToRefresh: ' ',
                  instructionsReleaseToRefresh: ' ',
                  instructionsRefreshing: ' ',
              });
          }
      });
  </script>
</body>

</html>
