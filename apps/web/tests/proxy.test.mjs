import test from 'node:test';
import assert from 'node:assert/strict';
import { handle } from '../src/hooks.server.ts';

function event(origin, suppliedOrigin = origin) {
    return {
        url: new URL('/v1/auth/login', origin),
        request: new Request(new URL('/v1/auth/login', origin), {
            method: 'POST', headers: { origin: suppliedOrigin, 'content-type': 'application/json' },
            body: JSON.stringify({ email: 'proxy@example.invalid', password: 'test-only' })
        }),
        platform: { env: {
            API_PROXY_TARGET: 'https://api.example.invalid', API_INTERNAL_KEY: 'fixture-key',
            ORIGIN: 'https://paspapan.pages.dev'
        } },
        getClientAddress: () => '192.0.2.1'
    };
}

for (const origin of ['https://paspapan.pages.dev', 'https://paspapan.demo.pandanteknik.com']) {
    test(`Cloudflare bindings proxy login and its cookie for ${origin}`, async (t) => {
        t.mock.method(globalThis, 'fetch', async (url, options) => {
            assert.equal(String(url), 'https://api.example.invalid/v1/auth/login');
            assert.equal(options.method, 'POST');
            assert.equal(options.headers.get('origin'), origin);
            assert.equal(options.headers.get('x-paspapan-service-key'), 'fixture-key');
            assert.equal(options.headers.get('x-forwarded-for'), '192.0.2.1');
            return new Response('{}', { headers: {
                'content-type': 'application/json',
                'set-cookie': 'paspapan_session=fixture; HttpOnly; Secure; SameSite=Lax; Path=/',
                'cache-control': 'no-store'
            } });
        });
        const response = await handle({ event: event(origin), resolve: () => { throw Error('unexpected page resolution'); } });
        assert.equal(response.status, 200);
        assert.match(response.headers.get('set-cookie'), /HttpOnly; Secure; SameSite=Lax; Path=\//);
        assert.equal(response.headers.get('cache-control'), 'no-store');
    });
}

test('proxy does not replace an untrusted incoming origin with the configured trusted origin', async (t) => {
    t.mock.method(globalThis, 'fetch', async (_url, options) => {
        assert.equal(options.headers.get('origin'), 'https://untrusted.example');
        return new Response('{"error":"untrusted request origin"}', { status: 403 });
    });
    const response = await handle({ event: event('https://paspapan.pages.dev', 'https://untrusted.example'), resolve: () => { throw Error('unexpected'); } });
    assert.equal(response.status, 403);
});

test('production proxy has no hardcoded service-key fallback', async () => {
    const { readFile } = await import('node:fs/promises');
    const hooks = await readFile('apps/web/src/hooks.server.ts', 'utf8');
    const container = await readFile('apps/web/Containerfile', 'utf8');
    const wrangler = await readFile('apps/web/wrangler.jsonc', 'utf8');
    const retiredKeyPrefix = ['paspapan', 'production', 'service', 'key'].join('-');
    assert.equal(hooks.includes(retiredKeyPrefix), false);
    assert.doesNotMatch(container, /ENV API_INTERNAL_KEY=/);
    assert.doesNotMatch(wrangler, /API_INTERNAL_KEY/);
});
