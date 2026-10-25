import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFile, access } from 'node:fs/promises';

const source = await readFile(new URL('../static/sw.js', import.meta.url), 'utf8');
function worker(online = true) {
    const handlers = {}, entries = new Map(), removed = [];
    const cache = {
        addAll: async paths => { for (const path of paths) entries.set(path, new Response(path)); },
        match: async request => entries.get(typeof request === 'string' ? request : request.url)?.clone(),
        put: async (request, response) => entries.set(request.url, response)
    };
    vm.runInNewContext(source, {
        URL, Response,
        self: { location: { origin: 'https://paspapan.test' }, addEventListener: (name, fn) => handlers[name] = fn,
            skipWaiting: async () => {}, clients: { claim: async () => {} } },
        caches: { open: async () => cache, keys: async () => ['paspapan-rewrite-public-v1', 'other-app'], delete: async key => removed.push(key) },
        fetch: async request => {
            if (!online) throw new Error('offline');
            const response = new Response('network');
            Object.defineProperty(response, 'type', { value: 'basic' });
            return response;
        }
    });
    async function lifecycle(name) { let task; handlers[name]({ waitUntil: promise => task = promise }); await task; }
    function fetch(path, mode = 'cors', method = 'GET') {
        let response;
        handlers.fetch({ request: { url: `https://paspapan.test${path}`, mode, method }, respondWith: promise => response = promise });
        return response;
    }
    return { entries, removed, lifecycle, fetch };
}

test('offline install caches local fonts and only deletes its own obsolete cache', async () => {
    const sw = worker();
    await sw.lifecycle('install');
    assert.ok(sw.entries.has('/fonts/plus-jakarta-sans-latin.woff2'));
    for (const path of sw.entries.keys()) await access(new URL(`../static${path}`, import.meta.url));
    await sw.lifecycle('activate');
    assert.deepEqual(sw.removed, ['paspapan-rewrite-public-v1']);
});

test('API, sessions and mutations bypass the cache, including direct navigations', () => {
    const sw = worker(false);
    for (const path of ['/v1', '/v1/auth/session', '/api', '/api/user']) {
        assert.equal(sw.fetch(path), undefined);
        assert.equal(sw.fetch(path, 'navigate'), undefined);
    }
    assert.equal(sw.fetch('/app/today', 'cors', 'POST'), undefined);
});

test('offline navigation shows a public fallback; online HTML is never cached', async () => {
    const offline = worker(false);
    await offline.lifecycle('install');
    assert.equal(await (await offline.fetch('/app/today', 'navigate')).text(), '/offline.html');
    const online = worker();
    assert.equal(await (await online.fetch('/app/today', 'navigate')).text(), 'network');
    assert.equal(online.entries.size, 0);
});

test('only allowlisted public assets and immutable bundles can be cached', async () => {
    const sw = worker();
    assert.equal(sw.fetch('/private/photo.jpg'), undefined);
    await sw.fetch('/_app/immutable/example.js');
    assert.equal(sw.entries.size, 1);
});
