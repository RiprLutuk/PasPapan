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

    <div class="absolute right-4 top-4">
      <x-theme-toggle x-data />
    </div>

    {{ $slot }}
  </div>

  @livewireScripts
</body>

</html>
