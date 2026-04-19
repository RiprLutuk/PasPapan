<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f5faf4">
    <title>@yield('title') | {{ config('app.name') }}</title>

    <script>
        if (localStorage.getItem('isDark') === 'true' || (!('isDark' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[radial-gradient(circle_at_top_left,rgba(87,148,74,0.14),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(87,148,74,0.08),transparent_30%),#f5faf4] font-sans antialiased text-gray-950 selection:bg-primary-600 selection:text-white dark:bg-[radial-gradient(circle_at_top_left,rgba(132,193,120,0.08),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(132,193,120,0.06),transparent_25%),#07110c] dark:text-gray-100">
    <a href="#error-main" class="skip-link">{{ __('Skip to main content') }}</a>

    <div class="min-h-screen px-4 py-[max(1rem,env(safe-area-inset-top))] sm:px-6">
        <main id="error-main" tabindex="-1" class="mx-auto flex min-h-screen w-full max-w-5xl items-center justify-center py-8">
            <section aria-labelledby="error-page-title" class="w-full overflow-hidden rounded-[1.75rem] border border-primary-100/80 bg-white/95 shadow-[0_24px_60px_-34px_rgba(34,64,41,0.35)] backdrop-blur dark:border-primary-900/40 dark:bg-gray-900/90 dark:shadow-[0_24px_60px_-38px_rgba(0,0,0,0.8)]">
                <div class="h-1.5 bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700"></div>

                <div class="grid gap-8 px-5 py-6 sm:px-8 sm:py-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-center lg:gap-10 lg:px-10">
                    <div class="space-y-6 text-left">
                        <div class="inline-flex items-center gap-3">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-primary-700 text-white shadow-lg shadow-primary-500/30">
                                <x-branding.application-mark class="h-8 w-8 text-white" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">
                                    {{ config('app.name') }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('Accessible system status page') }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-primary-100 bg-primary-50/80 p-5 dark:border-primary-900/40 dark:bg-primary-950/20">
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-primary-800 dark:text-primary-200">
                                {{ __('Need help?') }}
                            </p>
                            <p class="mt-3 text-sm leading-7 text-gray-700 dark:text-gray-300">
                                {{ __('If this issue keeps appearing, document the page you were opening and contact the administrator so it can be checked more quickly.') }}
                            </p>
                        </div>
                    </div>

                    <div class="min-w-0">
                        @yield('content')
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
