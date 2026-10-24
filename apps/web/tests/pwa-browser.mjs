// Run against a production build: bun run build && bun run preview --host 127.0.0.1 --port 4274
import { chromium, expect } from '@playwright/test';
import assert from 'node:assert/strict';

const base = process.env.TEST_WEB_URL ?? 'http://127.0.0.1:4274';
const browser = await chromium.launch({ headless: true });
try {
    const context = await browser.newContext();
    const page = await context.newPage();
    page.setDefaultTimeout(15_000);
    await page.goto(base + '/login');
    await expect(page.getByRole('heading', { name: 'Selamat datang kembali' })).toBeVisible();
    await expect(page.locator('link[rel=manifest]')).toHaveAttribute('href', '/manifest.webmanifest');
    await expect(page.locator('link[rel=icon][type="image/svg+xml"]')).toHaveAttribute('href', '/brand/favicon.svg');
    await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content', base + '/brand/social-card.png');
    assert.equal(await page.locator('img[src="/brand/mark.svg"]').evaluate(img => img.complete && img.naturalWidth > 0), true);
    const manifest = await (await context.request.get(base + '/manifest.webmanifest')).json();
    assert.equal(manifest.display, 'standalone');
    assert.equal(manifest.start_url, '/app/today');
    for (const icon of manifest.icons) assert.equal((await context.request.get(base + icon.src)).ok(), true);
    await page.waitForFunction(() => navigator.serviceWorker.controller !== null);
    // A response may be an API error or preview fallback; neither may be cached.
    await page.evaluate(() => fetch('/v1/auth/session').catch(() => null));
    const keys = await page.evaluate(async () => (await (await caches.open('paspapan-rewrite-public-v1')).keys()).map(request => new URL(request.url).pathname));
    assert.ok(keys.includes('/offline.html'));
    assert.ok(keys.every(path => path === '/offline.html' || path.startsWith('/brand/') || path.startsWith('/_app/immutable/')));
    assert.ok(!keys.some(path => path.startsWith('/v1') || path === '/login' || path.startsWith('/app/')));
    await context.setOffline(true);
    await page.goto(base + '/app/today');
    await expect(page.getByRole('heading', { name: 'Anda sedang offline' })).toBeVisible();
    await expect(page.getByText('Tidak ada transaksi yang disimpan atau dikirim saat offline.', { exact: false })).toBeVisible();
    assert.equal(await page.evaluate(() => fetch('/v1/auth/session').then(() => false).catch(() => true)), true, 'Offline API calls must fail, not receive cached credentials or offline HTML');
    await context.setOffline(false);
    await page.goto(base + '/login');
    await expect(page.getByRole('heading', { name: 'Selamat datang kembali' })).toBeVisible();
    console.log('PASS: PWA manifest/icons, worker activation, offline fallback, no API/session/HTML caching, online recovery');
} finally { await browser.close(); }
