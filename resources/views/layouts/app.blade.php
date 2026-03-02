<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $appName ?? config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon-circle.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#ffffff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PasPapan">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">


    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>

<body class="font-sans antialiased">


    <x-banner />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 pt-[calc(4rem+env(safe-area-inset-top))] pb-[env(safe-area-inset-bottom)]">
        @livewire('navigation-menu')

        <!-- @if (isset($header))
            <header class="bg-white shadow dark:bg-gray-800">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif -->

        <!-- Mosallas Refresh Container -->
        <div class="refresh-container">
            <div class="spinner">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12 18V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M4.92993 4.92999L7.75993 7.75999" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M16.24 16.24L19.07 19.07" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M2 12H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M18 12H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M4.92993 19.07L7.75993 16.24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M16.24 7.75999L19.07 4.92999" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <main id="main-content">
            {{ $slot }}
        </main>
    </div>

    @stack('modals')
    <x-feature-lock-modal />
    <script>
        window.isNativeApp = function() {
            return !!window.Capacitor && window.Capacitor.isNativePlatform();
        };

        document.addEventListener('DOMContentLoaded', () => {
            @if(session('show-feature-lock'))
            window.dispatchEvent(new CustomEvent('feature-lock', {
                detail: @json(session('show-feature-lock'))
            }));
            @endif
        });
    </script>

    <script src="{{ asset('js/pulltorefresh.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone || document.referrer.includes('android-app://');
            const isNative = window.isNativeApp();
            const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

            /* PullToRefresh disabled globally per user request
            if (isPWA || isNative || isTouch) {
                if (!document.body.classList.contains('is-native-scanning')) {
                     PullToRefresh.init({...}); 
                }
            }
            */
        });
    </script>

    @livewireScripts

    {{-- Global Notification --}}
    <div x-data="{ show: false, message: '' }"
        x-on:saved.window="show = true; message = $event.detail?.message || 'Saved successfully'; setTimeout(() => show = false, 2000)"
        class="fixed bottom-6 right-6 z-[9999]"
        style="display: none;"
        x-show="show"
        x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="flex items-center gap-3 rounded-lg bg-green-500 px-4 py-3 text-white shadow-lg">
            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="font-medium" x-text="message"></span>
        </div>
    </div>

    @stack('scripts')


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
                }
            });

            Alpine.data('tomSelectInput', (options, placeholder, wireModel, disabled = false) => ({
                tomSelectInstance: null,
                options: options,
                value: wireModel,
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
                            this.value = value;
                        },
                        onDropdownOpen: () => {
                            if (this.tomSelectInstance) this.tomSelectInstance.positionDropdown();
                        }
                    };

                    if (this.options && this.options.length > 0) {
                        config.options = this.options;
                    }

                    this.tomSelectInstance = new TomSelect(this.$refs.select, config);

                    this.$watch('value', (newValue) => {
                        if (!this.tomSelectInstance) return;
                        const currentValue = this.tomSelectInstance.getValue();
                        if (newValue != currentValue) {
                            this.tomSelectInstance.setValue(newValue, true);
                        }
                    });

                    if (this.value) {
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
                }
            }));
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Toast Configuration
            // Toast Configuration
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: 'transparent',
                customClass: {
                    popup: '!bg-white dark:!bg-gray-800 !text-gray-900 dark:!text-white !rounded-3xl !shadow-xl !border !border-gray-100 dark:!border-gray-700/50 !px-4 !py-3 !w-auto !max-w-[90vw] !mx-auto !mt-4',
                    title: '!text-sm !font-bold',
                    timerProgressBar: '!bg-primary-500 !h-1'
                },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            window.Toast = Toast;

            // Listen for Livewire Events
            if (typeof Livewire !== 'undefined') {
                Livewire.on('success', (data) => {
                    Toast.fire({
                        icon: 'success',
                        title: data.message || data
                    });
                });

                Livewire.on('error', (data) => {
                    Toast.fire({
                        icon: 'error',
                        title: data.message || data
                    });
                });

                Livewire.on('warning', (data) => {
                    Toast.fire({
                        icon: 'warning',
                        title: data.message || data
                    });
                });

                Livewire.on('info', (data) => {
                    Toast.fire({
                        icon: 'info',
                        title: data.message || data
                    });
                });
            }

            @if(session('success'))
            Toast.fire({
                icon: 'success',
                title: "{{ session('success') }}"
            });
            @endif

            @if(session('error'))
            Toast.fire({
                icon: 'error',
                title: "{{ session('error') }}"
            });
            @endif

            @if(session('warning'))
            Toast.fire({
                icon: 'warning',
                title: "{{ session('warning') }}"
            });
            @endif

            @if(session('info'))
            Toast.fire({
                icon: 'info',
                title: "{{ session('info') }}"
            });
            @endif

            @if(session('flash.banner'))
            Toast.fire({
                icon: 'success',
                title: "{{ session('flash.banner') }}"
            });
            @endif
        });
    </script>
</body>

</html>