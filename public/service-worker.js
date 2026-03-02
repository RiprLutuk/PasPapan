const CACHE_NAME = "absensi-v4"; // Bumped version to invalidate old caches
const urlsToCache = [
    "/offline",
    "/manifest.json",
    "/images/icons/icon-192x192.png",
    "/images/icons/icon-512x512.png",
];

// Install Service Worker
self.addEventListener("install", (event) => {
    console.log("[SW] Installing...");
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => {
                console.log("[SW] Caching app shell");
                return cache.addAll(urlsToCache);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Service Worker
self.addEventListener("activate", (event) => {
    console.log("[SW] Activating...");
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log("[SW] Deleting old cache:", cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch Strategy: Hybrid Cache-First & Network-First
self.addEventListener("fetch", (event) => {
    // Skip cross-origin requests unless they are from unpkg or google fonts
    if (!event.request.url.startsWith(self.location.origin) && !event.request.url.includes("unpkg.com") && !event.request.url.includes("fonts.")) {
        return;
    }

    // Don't cache POST, login, logout, livewire, or csrf requests
    if (
        event.request.method !== "GET" ||
        event.request.url.includes("/login") ||
        event.request.url.includes("/logout") ||
        event.request.url.includes("/csrf-token") ||
        event.request.url.includes("/livewire/")
    ) {
        return;
    }

    const url = new URL(event.request.url);

    // Cache-First strategy for static assets (instant load)
    if (
        url.pathname.startsWith('/build/') || 
        url.pathname.startsWith('/images/') || 
        url.pathname.startsWith('/assets/') ||
        url.pathname.startsWith('/fonts/') ||
        url.hostname === 'unpkg.com' ||
        url.hostname.includes('fonts.')
    ) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((response) => {
                    if (!response || response.status !== 200 || response.type === 'error') {
                        return response;
                    }
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                    return response;
                }).catch(() => {
                    return new Response('', { status: 404, statusText: 'Not Found' });
                });
            })
        );
        return;
    }

    // Network-First strategy for HTML and API requests
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (!response || response.status !== 200 || response.type === 'error') {
                    return response;
                }

                const responseToCache = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseToCache);
                });

                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((response) => {
                    if (response) {
                        return response;
                    }
                    
                    // Offline fallback for HTML
                    if (event.request.mode === 'navigate' ||
                        (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html'))) {
                        return caches.match("/offline");
                    }
                    
                    return new Response('', { status: 404, statusText: 'Not Found' });
                });
            })
    );
});
