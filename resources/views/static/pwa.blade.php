<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>PasPapan</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/web-app-manifest-192x192.png">
    <meta name="theme-color" content="#4CAF50" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background:
                radial-gradient(circle at top, rgba(134, 239, 172, 0.32), transparent 42%),
                linear-gradient(160deg, #0f172a 0%, #14532d 52%, #166534 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }

        .splash-container {
            text-align: center;
            animation: fadeIn 0.5s ease-in;
        }

        .logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.94);
            border-radius: 24px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.24);
        }

        h1 {
            font-size: 28px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .loading {
            margin-top: 32px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        .status-text {
            margin-top: 16px;
            font-size: 14px;
            opacity: 0.9;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Hide pada saat sudah loaded */
        body.loaded {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
    </style>
</head>

<body>
    <div class="splash-container">
        <div class="logo">📱</div>
        <h1>PasPapan</h1>
        <p style="opacity: 0.8; font-size: 14px;">{{ __('Employee Attendance App') }}</p>

        <div class="loading">
            <div class="spinner"></div>
            <p class="status-text" id="status">{{ __('Loading application...') }}</p>
        </div>
    </div>

    <script>
        const statusEl = document.getElementById('status');
        let redirectTimer;

        // Fungsi untuk redirect dengan smooth transition
        function redirectToLogin() {
            statusEl.textContent = @js(__('Ready! Opening application...'));
            document.body.classList.add('loaded');

            setTimeout(() => {
                window.location.href = "/login";
            }, 300);
        }

        // Register Service Worker
        if ("serviceWorker" in navigator) {
            statusEl.textContent = @js(__('Registering service worker...'));

            const url = new URL(window.location.href);
            const isNativeApp = !!(window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor
                .isNativePlatform());

            const resetPromise = isNativeApp || url.searchParams.get("reset-sw") === "1"
                ? navigator.serviceWorker.getRegistrations()
                    .then((registrations) => Promise.all(registrations.map((registration) => registration.unregister())))
                    .then(() => "caches" in window ? caches.keys().then((cacheNames) => Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)))) : null)
                    .then(() => {
                        if (isNativeApp) {
                            statusEl.textContent = @js(__('Native mode active.'));
                            return Promise.reject(new Error("SW disabled on native runtime"));
                        }
                        url.searchParams.delete("reset-sw");
                        window.location.replace(url.toString());
                        return Promise.reject(new Error("SW reset in progress"));
                    })
                : Promise.resolve();

            resetPromise.then(() => navigator.serviceWorker.register("/sw.js", {
                    updateViaCache: "none"
                }))
                .then((registration) => registration.update().then(() => registration))
                .then((registration) => {
                    if (registration.waiting) {
                        registration.waiting.postMessage({
                            type: "SKIP_WAITING"
                        });
                    }
                    statusEl.textContent = @js(__('Service worker active!'));

                    // Tunggu 1 detik sebelum redirect (lebih smooth)
                    redirectTimer = setTimeout(redirectToLogin, 1000);
                })
                .catch((err) => {
                    if (err?.message === "SW reset in progress") {
                        return;
                    }
                    console.error("SW registration failed:", err);
                    statusEl.textContent = @js(__('Failed to register service worker'));

                    // Tetap redirect meskipun SW gagal
                    redirectTimer = setTimeout(redirectToLogin, 1500);
                });
        } else {
            statusEl.textContent = @js(__('Browser does not support PWA'));
            redirectTimer = setTimeout(redirectToLogin, 1000);
        }

        // Prevent multiple redirects
        window.addEventListener('beforeunload', () => {
            if (redirectTimer) clearTimeout(redirectTimer);
        });
    </script>
</body>

</html>
