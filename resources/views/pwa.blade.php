<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Absensi Test</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: white;
            border-radius: 24px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
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
        <h1>Absensi Test</h1>
        <p style="opacity: 0.8; font-size: 14px;">Aplikasi Presensi Karyawan</p>

        <div class="loading">
            <div class="spinner"></div>
            <p class="status-text" id="status">Memuat aplikasi...</p>
        </div>
    </div>

    <script>
        const statusEl = document.getElementById('status');
        let redirectTimer;

        // Fungsi untuk redirect dengan smooth transition
        function redirectToLogin() {
            statusEl.textContent = "Siap! Membuka aplikasi...";
            document.body.classList.add('loaded');

            setTimeout(() => {
                window.location.href = "/login";
            }, 300);
        }

        // Register Service Worker
        if ("serviceWorker" in navigator) {
            statusEl.textContent = "Mendaftarkan service worker...";

            navigator.serviceWorker.register("/service-worker.js")
                .then((registration) => {
                    console.log("SW registered:", registration);
                    statusEl.textContent = "Service worker aktif!";

                    // Tunggu 1 detik sebelum redirect (lebih smooth)
                    redirectTimer = setTimeout(redirectToLogin, 1000);
                })
                .catch((err) => {
                    console.error("SW registration failed:", err);
                    statusEl.textContent = "Gagal mendaftar service worker";

                    // Tetap redirect meskipun SW gagal
                    redirectTimer = setTimeout(redirectToLogin, 1500);
                });
        } else {
            statusEl.textContent = "Browser tidak mendukung PWA";
            redirectTimer = setTimeout(redirectToLogin, 1000);
        }

        // Prevent multiple redirects
        window.addEventListener('beforeunload', () => {
            if (redirectTimer) clearTimeout(redirectTimer);
        });
    </script>
</body>

</html>
