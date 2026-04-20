@php
    $displayName = $companyName ?? \App\Models\Setting::getValue('app.company_name', config('app.name'));
@endphp

<x-app-layout>
    <section data-dynamic-kiosk aria-label="{{ __('Dynamic barcode display') }}">
        <div data-dynamic-kiosk-card>
            <button type="button" id="dynamic-barcode-fullscreen-btn" data-dynamic-fullscreen-button>
                {{ __('Fullscreen') }}
            </button>

            <div data-dynamic-qr-stack>
                <div data-dynamic-brand>{{ $displayName }}</div>

                <div data-dynamic-qr-frame>
                    <div id="dynamic-barcode-qrcode" data-dynamic-qrcode></div>
                </div>

                <span id="dynamic-barcode-screen-state" class="sr-only">{{ __('Normal mode') }}</span>
                <span id="dynamic-barcode-wake-lock-state" class="sr-only">{{ __('Checking browser support...') }}</span>
                <span id="dynamic-barcode-countdown" class="sr-only">--</span>
                <span id="dynamic-barcode-last-refresh" class="sr-only">--</span>
            </div>
        </div>
    </section>

    @pushOnce('styles')
        <style>
            [data-dynamic-kiosk] {
                min-height: calc(100svh - 4rem - env(safe-area-inset-top));
                display: grid;
                place-items: center;
                padding: clamp(1rem, 3vmin, 2.75rem);
            }

            [data-dynamic-kiosk-card] {
                position: relative;
                width: min(94vw, 54rem);
                display: grid;
                place-items: center;
                border: 1px solid rgb(226 232 240 / 0.8);
                border-radius: clamp(1.25rem, 3vmin, 2rem);
                background: rgb(255 255 255 / 0.86);
                box-shadow: 0 24px 70px rgb(15 23 42 / 0.10);
                backdrop-filter: blur(18px);
                padding: clamp(1.25rem, 4vmin, 3rem);
            }

            .dark [data-dynamic-kiosk-card] {
                border-color: rgb(51 65 85 / 0.82);
                background: rgb(15 23 42 / 0.78);
                box-shadow: 0 24px 80px rgb(0 0 0 / 0.28);
            }

            [data-dynamic-fullscreen-button] {
                position: absolute;
                top: clamp(0.75rem, 2vmin, 1.25rem);
                right: clamp(0.75rem, 2vmin, 1.25rem);
                border: 1px solid rgb(100 116 139 / 0.28);
                border-radius: 999px;
                background: rgb(255 255 255 / 0.72);
                color: rgb(51 65 85);
                font-size: 0.75rem;
                font-weight: 700;
                line-height: 1;
                padding: 0.75rem 1rem;
                transition: background-color 150ms ease, border-color 150ms ease, transform 150ms ease;
            }

            [data-dynamic-fullscreen-button]:hover {
                border-color: rgb(22 101 52 / 0.45);
                background: rgb(240 253 244 / 0.9);
                transform: translateY(-1px);
            }

            .dark [data-dynamic-fullscreen-button] {
                background: rgb(15 23 42 / 0.72);
                color: rgb(226 232 240);
            }

            [data-dynamic-qr-stack] {
                width: min(100%, calc(var(--dynamic-qr-size, 320px) + clamp(1.5rem, 7vmin, 7rem)));
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: clamp(1rem, 2.6vmin, 1.75rem);
            }

            [data-dynamic-brand] {
                max-width: min(90vw, 42rem);
                overflow: hidden;
                color: rgb(15 23 42);
                font-size: clamp(1.15rem, 3vmin, 2rem);
                font-weight: 800;
                letter-spacing: -0.04em;
                line-height: 1.05;
                text-align: center;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .dark [data-dynamic-brand] {
                color: rgb(248 250 252);
            }

            [data-dynamic-qr-frame] {
                border: 1px solid rgb(226 232 240 / 0.9);
                border-radius: clamp(1.25rem, 3vmin, 2rem);
                background: #ffffff;
                padding: clamp(0.7rem, 2vmin, 1.25rem);
                box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.9), 0 16px 40px rgb(15 23 42 / 0.08);
            }

            [data-dynamic-qrcode],
            [data-dynamic-qrcode] canvas,
            [data-dynamic-qrcode] img {
                width: var(--dynamic-qr-size, 320px) !important;
                height: var(--dynamic-qr-size, 320px) !important;
            }

            body.dynamic-barcode-fullscreen-active nav {
                display: none !important;
            }

            body.dynamic-barcode-fullscreen-active [data-dynamic-fullscreen-button] {
                display: none !important;
            }

            body.dynamic-barcode-fullscreen-active > .min-h-screen {
                padding-top: 0 !important;
            }

            body.dynamic-barcode-fullscreen-active [data-dynamic-kiosk] {
                min-height: 100svh;
            }

            body.dynamic-barcode-fullscreen-active [data-dynamic-kiosk-card] {
                width: min(96vw, 64rem);
                min-height: min(92svh, 64rem);
            }

            body.dynamic-barcode-fullscreen-active [data-dynamic-kiosk-card] > [data-dynamic-qr-stack] {
                width: min(100%, calc(var(--dynamic-qr-size, 320px) + clamp(2rem, 8vmin, 10rem)));
            }
        </style>
    @endPushOnce

    @pushOnce('scripts')
        <script src="{{ url('/assets/js/qrcode.min.js') }}"></script>
        <script>
            window.addEventListener('load', () => {
                const qrContainer = document.getElementById('dynamic-barcode-qrcode');
                const screenStateEl = document.getElementById('dynamic-barcode-screen-state');
                const wakeLockStateEl = document.getElementById('dynamic-barcode-wake-lock-state');
                const fullscreenBtn = document.getElementById('dynamic-barcode-fullscreen-btn');
                const countdownEl = document.getElementById('dynamic-barcode-countdown');
                const lastRefreshEl = document.getElementById('dynamic-barcode-last-refresh');
                const tokenEndpoint = @json(route('admin.barcodes.dynamic-token', $barcode));
                let expiresAt = new Date(@json($tokenPayload['expires_at'])).getTime();
                let refreshTimer = null;
                let countdownTimer = null;
                let wakeLock = null;
                let currentToken = @json($tokenPayload['token']);
                let resizeTimer = null;

                function calculateQrSize() {
                    const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 1024;
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 768;
                    const isFullscreen = Boolean(document.fullscreenElement);
                    const reservedHeight = isFullscreen ? 96 : 136;
                    const maxByHeight = Math.max(220, viewportHeight - reservedHeight);
                    const maxByWidth = Math.max(220, viewportWidth - 80);
                    const viewportScale = Math.min(viewportWidth, viewportHeight) * (isFullscreen ? 0.82 : 0.74);

                    return Math.round(Math.min(620, maxByHeight, maxByWidth, Math.max(240, viewportScale)));
                }

                function applyQrSize() {
                    document.documentElement.style.setProperty('--dynamic-qr-size', calculateQrSize() + 'px');
                }

                function renderQr(value) {
                    currentToken = value;
                    applyQrSize();
                    qrContainer.innerHTML = '';

                    if (typeof QRCode !== 'undefined') {
                        const size = calculateQrSize();

                        new QRCode(qrContainer, {
                            text: value,
                            width: size,
                            height: size,
                            colorDark: '#0f172a',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.L
                        });
                        qrContainer.removeAttribute('title');
                    }
                }

                function updateLastRefresh() {
                    if (!lastRefreshEl) return;

                    lastRefreshEl.textContent = new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }

                function updateScreenState() {
                    document.body.classList.toggle('dynamic-barcode-fullscreen-active', Boolean(document.fullscreenElement));
                    screenStateEl.textContent = document.fullscreenElement
                        ? @json(__('Fullscreen active'))
                        : @json(__('Normal mode'));
                    window.setTimeout(() => renderQr(currentToken), 120);
                }

                function updateCountdown() {
                    const remainingSeconds = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));

                    if (countdownEl) {
                        countdownEl.textContent = remainingSeconds + 's';
                    }

                    return remainingSeconds;
                }

                async function requestWakeLock() {
                    if (!('wakeLock' in navigator) || typeof navigator.wakeLock?.request !== 'function') {
                        wakeLockStateEl.textContent = @json(__('Not supported in this browser'));
                        return;
                    }

                    try {
                        wakeLock = await navigator.wakeLock.request('screen');
                        wakeLockStateEl.textContent = @json(__('Active'));
                        wakeLock.addEventListener('release', () => {
                            wakeLockStateEl.textContent = @json(__('Released'));
                        });
                    } catch (error) {
                        wakeLockStateEl.textContent = @json(__('Unable to activate automatically'));
                    }
                }

                async function refreshToken() {
                    try {
                        const response = await fetch(tokenEndpoint, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                        });

                        const payload = await response.json();

                        if (!response.ok || !payload.success) {
                            throw new Error(payload.message || 'Unable to refresh dynamic barcode.');
                        }

                        renderQr(payload.data.token);
                        expiresAt = new Date(payload.data.expires_at).getTime();
                        updateCountdown();
                        updateLastRefresh();
                    } catch (error) {
                        console.warn('Dynamic barcode refresh failed', error);
                    }
                }

                async function toggleFullscreen() {
                    try {
                        if (!document.fullscreenElement) {
                            await document.documentElement.requestFullscreen();
                        } else {
                            await document.exitFullscreen();
                        }
                    } catch (error) {
                        screenStateEl.textContent = @json(__('Fullscreen request was blocked'));
                    } finally {
                        updateScreenState();
                        requestWakeLock();
                    }
                }

                renderQr(currentToken);
                updateLastRefresh();
                updateCountdown();
                updateScreenState();
                requestWakeLock();

                countdownTimer = setInterval(updateCountdown, 1000);
                refreshTimer = setInterval(refreshToken, 5000);
                fullscreenBtn?.addEventListener('click', toggleFullscreen);

                document.addEventListener('fullscreenchange', updateScreenState);
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => renderQr(currentToken), 150);
                });
                window.addEventListener('orientationchange', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => renderQr(currentToken), 250);
                });
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        refreshToken();
                        requestWakeLock();
                    }
                });

                window.addEventListener('beforeunload', () => {
                    document.body.classList.remove('dynamic-barcode-fullscreen-active');
                    clearInterval(countdownTimer);
                    clearInterval(refreshTimer);
                    if (wakeLock && typeof wakeLock.release === 'function') {
                        wakeLock.release().catch(() => {});
                    }
                });
            });
        </script>
    @endPushOnce
</x-app-layout>
