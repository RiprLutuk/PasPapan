<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $isAdminRoute = request()->routeIs('admin.*');
    $isUserShell = ! $isAdminRoute && auth()->check();
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $appName ?? config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">

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
                    const url = new URL(window.location.href);
                    const shouldReset = url.searchParams.get('reset-sw') === '1';

                    if (shouldReset) {
                        const registrations = await navigator.serviceWorker.getRegistrations();
                        await Promise.all(registrations.map((registration) => registration.unregister()));

                        if ('caches' in window) {
                            const cacheNames = await caches.keys();
                            await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
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

    @php
        $pasPapanBroadcast = \App\Support\BroadcastRuntime::clientConfig(request());
        $pasPapanBroadcast['enabled'] = $pasPapanBroadcast['enabled']
            && (
                \App\Support\AnnouncementRefresh::broadcastingEnabled()
                || \App\Support\CollaborationRealtime::enabled()
            );
    @endphp

    <script>
        window.PasPapanBroadcast = {{ Illuminate\Support\Js::from($pasPapanBroadcast) }};
        window.PasPapanAlertLabels = {{ Illuminate\Support\Js::from([
            'confirm' => __('Confirm'),
            'cancel' => __('Cancel'),
            'confirmTitle' => __('Are you sure?'),
            'noticeTitle' => __('Notice'),
        ]) }};
        window.PasPapanValidationLabels = {{ Illuminate\Support\Js::from([
            'required' => __('Please complete this field.'),
            'email' => __('Please enter a valid email address.'),
            'url' => __('Please enter a valid URL.'),
            'pattern' => __('Please match the requested format.'),
            'minLength' => __('Please enter at least :min characters.'),
            'maxLength' => __('Please enter no more than :max characters.'),
            'rangeUnderflow' => __('Please enter a value greater than or equal to :min.'),
            'rangeOverflow' => __('Please enter a value less than or equal to :max.'),
            'invalid' => __('Please review this field.'),
            'summary' => __('Please review the highlighted fields before continuing.'),
        ]) }};
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

    <div class="app-shell min-h-screen {{ $isAdminRoute ? 'bg-slate-50 pt-[calc(4rem+env(safe-area-inset-top))] dark:bg-slate-950' : 'bg-transparent' }} {{ $isUserShell ? 'pb-[calc(6.5rem+env(safe-area-inset-bottom))]' : 'pb-[env(safe-area-inset-bottom)]' }}">
        @if ($isAdminRoute)
            <livewire:navigation-menu />
        @endif

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

        <main id="main-content" tabindex="-1" class="{{ $isAdminRoute || $isUserShell ? 'relative isolate' : '' }}">
            @if ($isAdminRoute)
                <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[22rem] overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-full bg-gradient-to-b from-white via-slate-50 to-transparent dark:from-slate-900 dark:via-slate-950 dark:to-transparent"></div>
                    <div class="hidden"></div>
                    <div class="absolute right-0 top-0 h-56 w-56 rounded-full bg-emerald-200/25 blur-3xl dark:bg-emerald-500/10"></div>
                </div>
            @elseif ($isUserShell)
                <div class="user-app-backdrop" aria-hidden="true"></div>
            @endif
            {{ $slot }}
        </main>

        @if ($isUserShell)
            <x-user.app-bottom-navigation />
        @endif
    </div>

    @stack('modals')
    <x-shared.feature-lock-modal />
    <script>
        window.isNativeApp = function() {
            return false;
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
            const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

            if ((isPWA || isTouch) && window.PullToRefresh && !document.body.dataset.pullToRefreshReady) {
                document.body.dataset.pullToRefreshReady = '1';

                PullToRefresh.init({
                    mainElement: 'body',
                    triggerElement: 'body',
                    instructionsPullToRefresh: @js(__('Pull to sync this page')),
                    instructionsReleaseToRefresh: @js(__('Release to refresh data')),
                    instructionsRefreshing: @js(__('Syncing data')),
                    instructionsPullHint: @js(__('Start from the very top of the page.')),
                    instructionsReleaseHint: @js(__('Release now to update the content.')),
                    instructionsRefreshingHint: @js(__('Content is refreshing for a moment.')),
                    statusPull: @js(__('Ready')),
                    statusRelease: @js(__('Release')),
                    statusRefreshing: @js(__('Syncing')),
                    shouldPullToRefresh() {
                        const active = document.activeElement;
                        const activeTag = active?.tagName || '';
                        const isTyping = ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag) || active?.isContentEditable;
                        const hasVisibleDialog = Array.from(document.querySelectorAll('[role="dialog"], [aria-modal="true"]'))
                            .some((dialog) => dialog.offsetParent !== null || dialog.getClientRects().length > 0);

                        return !document.body.classList.contains('is-native-scanning') &&
                            !isTyping &&
                            !hasVisibleDialog &&
                            Math.max(window.scrollY || 0, document.documentElement.scrollTop || 0, document.body.scrollTop || 0) <= 0;
                    },
                    onRefresh() {
                        window.location.reload();
                    },
                });
            }
        });
    </script>

    <script>
        window.tomSelectInput = (options, placeholder, wireModel, disabled = false, livewireModel = null, submitOnChange = false, livewireSetLive = false, dropdownDirection = 'auto', dropdownParent = 'body') => ({
            tomSelectInstance: null,
            options: options,
            value: wireModel,
            pendingValue: wireModel,
            disabled: disabled,
            livewireModel: livewireModel,
            submitOnChange: submitOnChange,
            livewireSetLive: livewireSetLive,
            dropdownDirection: dropdownDirection,
            dropdownParent: dropdownParent,
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
                    openOnFocus: true,
                    closeAfterSelect: true,
                    preload: true,
                    maxOptions: 1000,
                    shouldLoad: () => true,
                    dropdownParent: this.dropdownParent === 'self' ? this.$root : this.dropdownParent,
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
                    onFocus: () => {
                        if (!this.tomSelectInstance || this.disabled) {
                            return;
                        }

                        this.tomSelectInstance.refreshOptions(false);
                        requestAnimationFrame(() => this.tomSelectInstance?.open());
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

                const isUserSelect = this.$root?.classList.contains('ts-wrapper-user') || this.$root?.closest('.user-ui');
                const isAdminSelect = this.$root?.classList.contains('ts-wrapper-admin') || this.$root?.closest('.admin-ui');

                if (isUserSelect) {
                    this.tomSelectInstance.dropdown?.classList.add('ts-dropdown-user');
                }

                if (isAdminSelect) {
                    this.tomSelectInstance.dropdown?.classList.add('ts-dropdown-admin');
                }

                const openOptions = () => {
                    if (this.disabled || !this.tomSelectInstance) {
                        return;
                    }

                    this.tomSelectInstance.refreshOptions(false);
                    requestAnimationFrame(() => this.tomSelectInstance?.open());
                };

                if (!isAdminSelect) {
                    this.tomSelectInstance.control.addEventListener('mousedown', openOptions);
                    this.tomSelectInstance.control.addEventListener('pointerdown', openOptions);
                }

                this.tomSelectInstance.control_input?.addEventListener('focus', openOptions);
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

    <script>
        // Provide a dummy Echo object so Livewire doesn't crash on boot before app.js finishes.
        // Once app.js executes, it will overwrite window.Echo with the real instance and dispatch 'echo:ready'.
        if (typeof window.Echo === 'undefined') {
            window.Echo = {
                isDummy: true,
                channel: function(name) {
                    return {
                        listen: function(event, callback) {
                            window.addEventListener('echo:ready', () => {
                                if (window.Echo && typeof window.Echo.channel === 'function') {
                                    window.Echo.channel(name).listen(event, callback);
                                }
                            });
                        },
                        stopListening: function(event, callback) {}
                    };
                },
                private: function(name) {
                    return {
                        listen: function(event, callback) {
                            window.addEventListener('echo:ready', () => {
                                if (window.Echo && typeof window.Echo.private === 'function') {
                                    window.Echo.private(name).listen(event, callback);
                                }
                            });
                        },
                        stopListening: function(event, callback) {}
                    };
                },
                leave: function() {},
                leaveChannel: function() {},
                socketId: function() { return null; },
                connector: { pusher: {} }
            };
        }
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
            const notify = (icon, message) => {
                window.PasPapanAlert?.toast({
                    icon,
                    title: message
                });
            };

            // Listen for Livewire Events
            if (typeof Livewire !== 'undefined') {
                Livewire.on('success', (data) => {
                    notify('success', data.message || data);
                });

                Livewire.on('error', (data) => {
                    notify('error', data.message || data);
                });

                Livewire.on('warning', (data) => {
                    notify('warning', data.message || data);
                });

                Livewire.on('info', (data) => {
                    notify('info', data.message || data);
                });
            }

            @if(session('success'))
            notify('success', @js(session('success')));
            @endif

            @if(session('error'))
            notify('error', @js(session('error')));
            @endif

            @if(session('warning'))
            notify('warning', @js(session('warning')));
            @endif

            @if(session('info'))
            notify('info', @js(session('info')));
            @endif

            @if(session('flash.banner'))
            notify(@js(session('flash.bannerStyle', 'success')), @js(session('flash.banner')));
            @endif
        });
    </script>
</body>

</html>
