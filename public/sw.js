const CACHE_NAME = "absensi-v1";
const urlsToCache = [
    "/",
    "/css/app.css",
    "/js/app.js",
    "/images/icons/icon-192x192.png",
    "/offline.html",
];

// Install Service Worker
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log("Cache dibuka");
            return cache.addAll(urlsToCache);
        })
    );
});

// Fetch - Cache First Strategy
self.addEventListener("fetch", (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            // Cache hit - return response
            if (response) {
                return response;
            }

            return fetch(event.request)
                .then((response) => {
                    // Check jika response valid
                    if (
                        !response ||
                        response.status !== 200 ||
                        response.type !== "basic"
                    ) {
                        return response;
                    }

                    // Clone response
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });

                    return response;
                })
                .catch(() => {
                    // Jika offline, tampilkan halaman offline
                    return caches.match("/offline.html");
                });
        })
    );
});

// Activate - Clean old caches
self.addEventListener("activate", (event) => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
