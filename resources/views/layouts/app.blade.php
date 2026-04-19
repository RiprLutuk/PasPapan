<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $isAdminRoute = request()->routeIs('admin.*');
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $appName ?? config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon-circle.png') }}">

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>

<body class="font-sans antialiased {{ $isAdminRoute ? 'admin-ui' : 'user-ui' }}">

    <a href="#main-content" class="skip-link">{{ __('Skip to main content') }}</a>


    <x-feedback.banner />

    @unless ($isAdminRoute)
        <livewire:shared.high-priority-announcement-modal />
    @endunless

    <div class="app-shell min-h-screen {{ $isAdminRoute ? 'bg-slate-50 dark:bg-slate-950' : 'bg-gray-100 dark:bg-gray-900' }} pt-[calc(4rem+env(safe-area-inset-top))] pb-[env(safe-area-inset-bottom)]">
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

        <main id="main-content" tabindex="-1" class="{{ $isAdminRoute ? 'relative isolate' : '' }}">
            @if ($isAdminRoute)
                <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[22rem] overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-full bg-gradient-to-b from-white via-slate-50 to-transparent dark:from-slate-900 dark:via-slate-950 dark:to-transparent"></div>
                    <div class="absolute left-0 top-10 h-48 w-48 rounded-full bg-cyan-200/30 blur-3xl dark:bg-cyan-500/10"></div>
                    <div class="absolute right-0 top-0 h-56 w-56 rounded-full bg-emerald-200/25 blur-3xl dark:bg-emerald-500/10"></div>
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>

    @stack('modals')
    <x-shared.feature-lock-modal />
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

    <script>
        window.tomSelectInput = (options, placeholder, wireModel, disabled = false, livewireModel = null, submitOnChange = false, livewireSetLive = false, dropdownDirection = 'auto') => ({
            tomSelectInstance: null,
            options: options,
            value: wireModel,
            pendingValue: wireModel,
            disabled: disabled,
            livewireModel: livewireModel,
            submitOnChange: submitOnChange,
            livewireSetLive: livewireSetLive,
            dropdownDirection: dropdownDirection,
            tomSelectRetryCount: 0,
            destroyed: false,

            init() {
                if (this.destroyed) {
                    return;
                }

                if (this.tomSelectInstance) {
                    this.tomSelectInstance.sync();
                    return;
                }

                if (!window.TomSelect) {
                    if (this.tomSelectRetryCount < 20) {
                        this.tomSelectRetryCount += 1;
                        setTimeout(() => this.init(), 25);
                    }

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

                this.tomSelectInstance = new window.TomSelect(this.$refs.select, config);
                this.configureDropdownPosition();

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

            configureDropdownPosition() {
                if (this.dropdownDirection !== 'up' || !this.tomSelectInstance) return;

                const instance = this.tomSelectInstance;
                const originalPositionDropdown = instance.positionDropdown.bind(instance);

                instance.dropdown.classList.add('ts-dropdown-up');
                instance.positionDropdown = () => {
                    originalPositionDropdown();

                    const controlRect = instance.control.getBoundingClientRect();
                    const viewportGap = 12;
                    const maxHeight = Math.max(120, controlRect.top - viewportGap);

                    instance.dropdown_content.style.maxHeight = `${maxHeight}px`;
                    instance.dropdown_content.style.overflowY = 'auto';
                    const dropdownHeight = instance.dropdown.offsetHeight || 0;
                    instance.dropdown.style.top = `${controlRect.top + window.scrollY - dropdownHeight - 6}px`;
                };
            },

            syncLivewireValue(value) {
                if (!this.livewireModel || !this.$wire) return;

                this.$wire.set(this.livewireModel, value, this.livewireSetLive);
            },

            hasValue(value) {
                return value !== null && value !== undefined && value !== '';
            },

            commitPendingValue() {
                if (this.pendingValue == this.value) return;

                this.value = this.pendingValue;
                this.syncLivewireValue(this.pendingValue);
                this.submitFormIfNeeded();
            },

            submitFormIfNeeded() {
                if (!this.submitOnChange || !this.$refs.select?.form) return;

                queueMicrotask(() => {
                    if (typeof this.$refs.select.form.requestSubmit === 'function') {
                        this.$refs.select.form.requestSubmit();
                    } else {
                        this.$refs.select.form.submit();
                    }
                });
            },

            destroy() {
                this.destroyed = true;

                if (this.tomSelectInstance) {
                    this.tomSelectInstance.destroy();
                    this.tomSelectInstance = null;
                }
            }
        });
    </script>

    @livewireScripts

    {{-- Global Notification --}}
    <div x-data="{ show: false, message: '' }"
        x-on:saved.window="show = true; message = $event.detail?.message || 'Saved successfully'; setTimeout(() => show = false, 2000)"
        class="fixed bottom-6 right-6 z-[9999]"
        role="status"
        aria-live="polite"
        style="display: none;"
        x-show="show"
        x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="flex items-center gap-3 rounded-lg bg-green-700 px-4 py-3 text-white shadow-lg">
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

            Alpine.data('tomSelectInput', window.tomSelectInput);
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Toast Configuration
            // Toast Configuration
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
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
