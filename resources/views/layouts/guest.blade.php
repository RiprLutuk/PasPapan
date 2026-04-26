<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $appName ?? config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon-circle.png') }}">

    <!-- PWA iOS & Splash -->
    <meta name="theme-color" content="#ffffff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PasPapan">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">


    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const isNativeApp = !!(window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor
                        .isNativePlatform());
                    const url = new URL(window.location.href);
                    const shouldReset = isNativeApp || url.searchParams.get('reset-sw') === '1';

                    if (shouldReset) {
                        const registrations = await navigator.serviceWorker.getRegistrations();
                        await Promise.all(registrations.map((registration) => registration.unregister()));

                        if ('caches' in window) {
                            const cacheNames = await caches.keys();
                            await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
                        }

                        if (isNativeApp) {
                            return;
                        }

                        url.searchParams.delete('reset-sw');
                        window.location.replace(url.toString());
                        return;
                    }

                    const registration = await navigator.serviceWorker.register('/sw.js', {
                        updateViaCache: 'none',
                    });

                    await registration.update();

                    if (registration.waiting) {
                        registration.waiting.postMessage({
                            type: 'SKIP_WAITING'
                        });
                    }
                } catch (error) {
                    console.warn('Service worker registration failed', error);
                }
            });
        }
    </script>

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

@php
    $isAuthScreen = request()->routeIs('login', 'register', 'password.*', 'verification.*', 'two-factor.login');
@endphp

<body @class([
    'guest-ui font-sans antialiased',
    'overflow-hidden' => $isAuthScreen,
])>

    <a href="#guest-main-content" class="skip-link">{{ __('Skip to main content') }}</a>


    <div class="min-h-screen font-sans text-gray-900 antialiased dark:text-gray-100">

        <div class="guest-topbar">
            <a href="{{ url('/') }}" class="guest-brand" aria-label="{{ config('app.name') }}">
                <img src="{{ asset('images/icons/logo.jpeg') }}" class="guest-brand__logo" alt="{{ config('app.name') }}">
                <span class="guest-brand__name">{{ config('app.name') }}</span>
            </a>

            <div class="flex gap-2">
                <div class="flex items-center">
                    <form method="POST" action="{{ route('user.language.update') }}">
                        @csrf
                        <input type="hidden" name="language" value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
                        <button type="submit"
                            class="language-toggle"
                            aria-label="{{ __('Switch language to :language', ['language' => app()->getLocale() == 'id' ? 'English' : 'Bahasa Indonesia']) }}">
                            <span class="sr-only">{{ __('Switch Language') }}</span>
                            <span class="language-toggle__labels" aria-hidden="true">
                                <span class="language-toggle__label">ID</span>
                                <span class="language-toggle__label">EN</span>
                            </span>
                            <span
                                class="language-toggle__thumb {{ app()->getLocale() == 'en' ? 'language-toggle__thumb--end' : 'translate-x-0' }}">
                                <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity opacity-100">
                                    <span class="leading-none">
                                        {{ app()->getLocale() == 'id' ? '🇮🇩' : '🇺🇸' }}
                                    </span>
                                </span>
                            </span>
                        </button>
                    </form>
                </div>

                <x-navigation.theme-toggle x-data />
            </div>
        </div>

        <main id="guest-main-content" tabindex="-1">
            {{ $slot }}
        </main>

    </div>

    @livewireScripts

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store("darkMode", {
                on: false,
                init() {
                    if (localStorage.getItem("isDark")) {
                        this.on = localStorage.getItem("isDark") === "true";
                    } else {
                        this.on = window.matchMedia("(prefers-color-scheme: dark)").matches;
                    }

                    if (this.on) {
                        document.documentElement.classList.add("dark");
                    } else {
                        document.documentElement.classList.remove("dark");
                    }
                },
                toggle() {
                    this.on = !this.on;
                    localStorage.setItem("isDark", this.on);
                    if (this.on) {
                        document.documentElement.classList.add("dark");
                    } else {
                        document.documentElement.classList.remove("dark");
                    }
                },
            });

            Alpine.data('tomSelectInput', (options, placeholder, wireModel, disabled = false) => ({
                tomSelectInstance: null,
                options: options,
                value: wireModel,
                pendingValue: wireModel,
                disabled: disabled,

                init() {
                    if (this.tomSelectInstance) {
                        this.tomSelectInstance.sync();
                        return;
                    }

                    const config = {
                        create: false,
                        dropdownParent: 'body',
                        sortField: {
                            field: '$order'
                        },
                        valueField: 'id',
                        labelField: 'name',
                        searchField: 'name',
                        placeholder: placeholder,
                        onChange: (value) => {
                            this.pendingValue = value;
                            queueMicrotask(() => {
                                if (this.tomSelectInstance && !this.tomSelectInstance.isOpen) {
                                    this.commitPendingValue();
                                }
                            });
                        },
                        onDropdownOpen: () => {
                            if (this.tomSelectInstance) this.tomSelectInstance.positionDropdown();
                        },
                        onDropdownClose: () => {
                            this.commitPendingValue();
                        },
                        onBlur: () => {
                            this.commitPendingValue();
                        }
                    };

                    if (this.options && this.options.length > 0) {
                        config.options = this.options;
                    }

                    this.tomSelectInstance = new TomSelect(this.$refs.select, config);

                    this.$watch('value', (newValue) => {
                        if (!this.tomSelectInstance) return;
                        this.pendingValue = newValue;
                        const currentValue = this.tomSelectInstance.getValue();
                        if (newValue != currentValue) {
                            this.tomSelectInstance.setValue(newValue, true);
                        }
                    });

                    if (this.hasValue(this.value)) {
                        this.tomSelectInstance.setValue(this.value, true);
                    }

                    if (this.disabled) {
                        this.tomSelectInstance.lock();
                    }

                    this.$watch('disabled', (isDisabled) => {
                        if (!this.tomSelectInstance) return;
                        if (isDisabled) {
                            this.tomSelectInstance.lock();
                        } else {
                            this.tomSelectInstance.unlock();
                        }
                    });
                },

                destroy() {
                    if (this.tomSelectInstance) {
                        this.tomSelectInstance.destroy();
                        this.tomSelectInstance = null;
                    }
                },

                hasValue(value) {
                    return value !== null && value !== undefined && value !== '';
                },

                commitPendingValue() {
                    if (this.pendingValue == this.value) return;

                    this.value = this.pendingValue;
                }
            }));
        });
    </script>
</body>

</html>
