// Deterministic exports from the hand-authored SVG, using the existing browser tooling.
// No generative raster source, network fonts, or image-processing dependency is needed.
import { chromium } from '@playwright/test';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const mark = await readFile(resolve(root, 'assets/brand/mark.svg'), 'utf8');
const shapes = mark.replace(/<svg[^>]*>|<\/svg>|<title>.*?<\/title>/gs, '').trim();
const svg = (width, height, content) => `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">${content}</svg>\n`;
const appIcon = svg(512, 512, `<rect width="512" height="512" rx="112" fill="#f6f8f6"/><g transform="translate(56 56) scale(3.125)">${shapes}</g>`);
// Solid full-bleed tile; artwork fits inside the maskable icon's central safe zone.
const maskable = svg(512, 512, `<rect width="512" height="512" fill="#f6f8f6"/><g transform="translate(102.4 102.4) scale(2.4)">${shapes}</g>`);
const mono = mark.replace(/fill="#[0-9a-f]+"/g, 'fill="currentColor"');
const wordmark = svg(480, 128, `${shapes}<text x="151" y="85" fill="#1e331a" font-family="Arial, sans-serif" font-size="64" font-weight="700" letter-spacing="-3">PasPapan</text>`);
const social = svg(1200, 630, `<rect width="1200" height="630" fill="#f6f8f6"/><g transform="translate(82 74) scale(.62)">${shapes}</g><text x="182" y="132" fill="#1e331a" font-family="Arial, sans-serif" font-weight="700" font-size="46" letter-spacing="-2">PasPapan</text><text x="82" y="303" fill="#1e331a" font-family="Arial, sans-serif" font-size="65" font-weight="700" letter-spacing="-2">Hadir. Terhubung.</text><text x="82" y="382" fill="#1e331a" font-family="Arial, sans-serif" font-size="65" font-weight="700" letter-spacing="-2">Tertata.</text><text x="86" y="477" fill="#617067" font-family="Arial, sans-serif" font-size="24">Kehadiran, jadwal, dan kebutuhan kerja tim.</text><g transform="translate(810 210) scale(2.35)">${shapes}</g><path d="M84 548h1032" stroke="#dde4df"/><text x="86" y="585" fill="#44733a" font-family="Arial, sans-serif" font-size="18">WORKSPACE TIM</text>`);
const browser = await chromium.launch({ headless: true });
try {
    const page = await browser.newPage({ deviceScaleFactor: 1 });
    async function render(source, width, height, type = 'png') {
        await page.setViewportSize({ width, height });
        await page.setContent(`<html><body style="margin:0;background:transparent"><img width="${width}" height="${height}" src="data:image/svg+xml;base64,${Buffer.from(source).toString('base64')}" /></body></html>`);
        await page.locator('img').evaluate(img => img.decode());
        return page.screenshot({ type, omitBackground: type === 'png' });
    }
    const outputs = new Map([
        ['mark.svg', mark], ['mark-mono.svg', mono], ['mark-inverse.svg', mark.replace(/fill="#[0-9a-f]+"/g, 'fill="#ffffff"')], ['wordmark.svg', wordmark], ['app-icon.svg', appIcon],
        ['favicon.svg', appIcon], ['social-card.svg', social],
        ['favicon-16.png', await render(appIcon, 16, 16)], ['favicon-32.png', await render(appIcon, 32, 32)],
        ['icon-192x192.png', await render(appIcon, 192, 192)], ['icon-512x512.png', await render(appIcon, 512, 512)],
        ['icon-maskable-512.png', await render(maskable, 512, 512)], ['apple-touch-icon.png', await render(maskable, 180, 180)],
        ['social-card.png', await render(social, 1200, 630)], ['mark-512.png', await render(mark, 512, 512)],
    ]);
    const faviconPng = outputs.get('favicon-32.png');
    const ico = Buffer.alloc(22);
    ico.writeUInt16LE(1, 2); ico.writeUInt16LE(1, 4);
    ico[6] = 32; ico[7] = 32; ico.writeUInt16LE(1, 10); ico.writeUInt16LE(32, 12);
    ico.writeUInt32LE(faviconPng.length, 14); ico.writeUInt32LE(22, 18);
    outputs.set('favicon.ico', Buffer.concat([ico, faviconPng]));
    for (const directory of ['public/brand', 'apps/web/static/brand']) {
        await mkdir(resolve(root, directory), { recursive: true });
        for (const [name, data] of outputs) await writeFile(resolve(root, directory, name), data);
    }
    // Compatibility filenames still used by Laravel emails, PDFs and installed PWAs.
    for (const name of ['icon-192x192.png', 'icon-512x512.png', 'apple-touch-icon.png', 'favicon.ico']) {
        await writeFile(resolve(root, 'public/images/icons', name), outputs.get(name));
    }
    await writeFile(resolve(root, 'public/images/icons/favicon-circle.png'), outputs.get('favicon-32.png'));
    await writeFile(resolve(root, 'public/images/icons/logo.png'), outputs.get('mark-512.png'));
    await writeFile(resolve(root, 'public/images/icons/logo.jpeg'), await render(appIcon, 512, 512, 'jpeg'));
    await writeFile(resolve(root, 'public/hero-banner.png'), outputs.get('social-card.png'));
    console.log(`Exported ${outputs.size} brand assets to public/brand and apps/web/static/brand; updated legacy aliases.`);
} finally { await browser.close(); }
