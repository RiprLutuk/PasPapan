const CACHE_NAME = 'paspapan-v2.5';
const OFFLINE_URL = '/offline';

// Assets to cache on install
const PRECACHE_ASSETS = [
    '/offline',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
];

// Install event - precache assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch Strategy: Hybrid Cache-First & Network-First
self.addEventListener('fetch', (event) => {
    if (!event.request.url.startsWith(self.location.origin) && !event.request.url.includes("unpkg.com") && !event.request.url.includes("fonts.")) {
        return;
    }

    if (
        event.request.method !== 'GET' ||
        event.request.url.includes('/login') ||
        event.request.url.includes('/logout') ||
        event.request.url.includes('/csrf-token') ||
        event.request.url.includes('/livewire/') ||
        event.request.url.includes('/api/')
    ) {
        return;
    }

    const url = new URL(event.request.url);

    // Cache-First configuration for static assets
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
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                }).catch(() => {
                    return new Response('', { status: 404, statusText: 'Not Found' });
                });
            })
        );
        return;
    }

    // Network-First strategy for HTML and User APIs
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (!response || response.status !== 200 || response.type === 'error') {
                    return response;
                }
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseClone);
                });
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (event.request.mode === 'navigate' ||
                        (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html'))) {
                        return caches.match(OFFLINE_URL);
                    }
                    return new Response('Offline', { status: 503, statusText: 'Offline' });
                });
            })
    );
});
