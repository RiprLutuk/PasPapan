// Cache only public assets. Sessions, API responses and authenticated HTML stay online-only.
const CACHE_NAME = 'paspapan-rewrite-public-v1';
const PUBLIC_ASSETS = ['/offline.html', '/brand/icon-192x192.png', '/brand/icon-512x512.png', '/brand/apple-touch-icon.png', '/brand/icon-maskable-512.png', '/brand/favicon.svg'];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(PUBLIC_ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const names = await caches.keys();
        await Promise.all(names.filter(name => name !== CACHE_NAME && (
            name.startsWith('paspapan-rewrite-public-') || name.startsWith('paspapan-v')
        )).map(name => caches.delete(name)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    if (event.request.method !== 'GET' || url.origin !== self.location.origin) return;
    // Never substitute offline HTML for an API response, even for direct navigation.
    if (url.pathname === '/v1' || url.pathname.startsWith('/v1/') || url.pathname === '/api' || url.pathname.startsWith('/api/')) return;

    if (event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request).catch(async () => {
            const cache = await caches.open(CACHE_NAME);
            return (await cache.match('/offline.html')) ?? new Response('Offline', { status: 503 });
        }));
        return;
    }

    if (!PUBLIC_ASSETS.includes(url.pathname) && !url.pathname.startsWith('/_app/immutable/')) return;
    event.respondWith((async () => {
        const cache = await caches.open(CACHE_NAME);
        const cached = await cache.match(event.request);
        if (cached) return cached;
        const response = await fetch(event.request);
        if (response.ok && response.type === 'basic' && !response.redirected) {
            await cache.put(event.request, response.clone());
        }
        return response;
    })());
});
