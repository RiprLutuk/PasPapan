// Cache only public assets. Sessions, API responses and authenticated HTML stay online-only.
const CACHE_NAME = 'paspapan-rewrite-public-v2';
const PUBLIC_ASSETS = ['/offline.html', '/fonts/plus-jakarta-sans-latin.woff2', '/fonts/plus-jakarta-sans-latin-italic.woff2', '/brand/icon-192x192.png', '/brand/icon-512x512.png', '/brand/apple-touch-icon.png', '/brand/icon-maskable-512.png', '/brand/favicon.svg'];

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

// Real-time PWA & Web Notification handlers
self.addEventListener('message', event => {
    const data = event && event.data;
    if (!data || data.type !== 'SHOW_NOTIFICATION' || !self.registration || typeof self.registration.showNotification !== 'function') return;
    const title = data.title || 'PasPapan';
    const options = {
        body: data.body || 'Ada pembaruan baru di workspace Anda.',
        icon: '/brand/icon-192x192.png',
        badge: '/brand/favicon-32.png',
        tag: data.tag || 'paspapan-notification',
        data: { url: data.url || '/app/notifications' }
    };
    if (typeof event.waitUntil === 'function') {
        event.waitUntil(self.registration.showNotification(title, options));
    } else {
        self.registration.showNotification(title, options);
    }
});

self.addEventListener('push', event => {
    if (!self.registration || typeof self.registration.showNotification !== 'function') return;
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch {
        payload = { title: 'PasPapan', body: event.data ? event.data.text() : '' };
    }
    const title = payload.title || 'PasPapan';
    const options = {
        body: payload.body || 'Pembaruan baru di PasPapan.',
        icon: '/brand/icon-192x192.png',
        badge: '/brand/favicon-32.png',
        tag: payload.tag || 'paspapan-push',
        data: { url: payload.url || '/app/notifications' }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    if (event.notification && typeof event.notification.close === 'function') {
        event.notification.close();
    }
    const targetUrl = (event.notification && event.notification.data && event.notification.data.url) || '/app/notifications';
    if (!self.clients || typeof self.clients.matchAll !== 'function') return;
    event.waitUntil((async () => {
        const windowClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windowClients) {
            if ('focus' in client) {
                await client.focus();
                if ('navigate' in client) await client.navigate(targetUrl);
                return;
            }
        }
        if (typeof self.clients.openWindow === 'function') {
            await self.clients.openWindow(targetUrl);
        }
    })());
});
