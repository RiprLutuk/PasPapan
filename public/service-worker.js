const CACHE_NAME = "absensi-v1";
const urlsToCache = [
    "/pwa",
    "/manifest.json",
    "/images/icons/web-app-manifest-192x192.png",
    "/images/icons/web-app-manifest-512x512.png",
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

// Fetch Strategy: Network First, fallback to Cache
self.addEventListener("fetch", (event) => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Don't cache POST requests or login/logout requests
    if (
        event.request.method !== "GET" ||
        event.request.url.includes("/login") ||
        event.request.url.includes("/logout") ||
        event.request.url.includes("/csrf-token")
    ) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Clone the response
                const responseToCache = response.clone();

                // Cache successful responses
                if (response.status === 200) {
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                }

                return response;
            })
            .catch(() => {
                // If network fails, try cache
                return caches.match(event.request).then((response) => {
                    return response || caches.match("/pwa");
                });
            })
    );
});
