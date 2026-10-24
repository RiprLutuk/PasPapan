import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';

const root = fileURLToPath(new URL('../../../', import.meta.url));
const bytes = path => readFile(resolve(root, path));
const pngSize = data => [data.readUInt32BE(16), data.readUInt32BE(20)];

test('exported marks match the editable SVG master across both apps', async () => {
    const master = await bytes('assets/brand/mark.svg');
    assert.deepEqual(await bytes('public/brand/mark.svg'), master);
    assert.deepEqual(await bytes('apps/web/static/brand/mark.svg'), master);
    assert.ok(master.toString().includes('#2183b0'));
    assert.ok(master.toString().includes('#57944a'));
});

test('favicon and PWA raster dimensions match their declarations', async () => {
    for (const [name, size] of [['favicon-16.png', 16], ['favicon-32.png', 32], ['apple-touch-icon.png', 180], ['icon-192x192.png', 192], ['icon-512x512.png', 512], ['icon-maskable-512.png', 512]]) {
        const image = await bytes('apps/web/static/brand/' + name);
        assert.deepEqual(pngSize(image), [size, size], name);
        assert.deepEqual(await bytes('public/brand/' + name), image);
    }
    assert.deepEqual(pngSize(await bytes('apps/web/static/brand/social-card.png')), [1200, 630]);
    const ico = await bytes('apps/web/static/brand/favicon.ico');
    assert.equal(ico.readUInt16LE(2), 1);
    assert.equal(ico.readUInt32LE(18), 22);
    assert.deepEqual(pngSize(ico.subarray(22)), [32, 32]);
});

test('manifest uses genuine sizes and a dedicated maskable export', async () => {
    const manifest = JSON.parse(await bytes('apps/web/static/manifest.webmanifest'));
    assert.ok(manifest.icons.some(icon => icon.purpose === 'maskable'));
    for (const icon of manifest.icons) {
        const dimensions = pngSize(await bytes('apps/web/static' + icon.src));
        assert.equal(dimensions.join('x'), icon.sizes);
    }
});

test('legacy PDF/email/icon aliases use the new identity', async () => {
    assert.deepEqual(await bytes('public/images/icons/logo.png'), await bytes('public/brand/mark-512.png'));
    assert.deepEqual(await bytes('public/images/icons/icon-512x512.png'), await bytes('public/brand/icon-512x512.png'));
});
