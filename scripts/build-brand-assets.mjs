// Deterministic SVG exports. Local font outlines; no browser or system fonts.
import { Resvg } from '@resvg/resvg-js';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const mark = await readFile(resolve(root, 'assets/brand/mark.svg'), 'utf8');
const shapes = mark.replace(/<svg[^>]*>|<\/svg>|<title>.*?<\/title>/gs, '').trim();
const svg = (width, height, content) => `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">${content}</svg>\n`;
const appIcon = svg(512, 512, `<rect width="512" height="512" rx="32" fill="#f6f8f6"/><g transform="translate(56 56) scale(3.125)">${shapes}</g>`);
// Solid full-bleed tile; artwork fits inside the maskable icon's central safe zone.
const maskable = svg(512, 512, `<rect width="512" height="512" fill="#f6f8f6"/><g transform="translate(102.4 102.4) scale(2.4)">${shapes}</g>`);
const mono = mark.replace(/fill="#[0-9a-f]+"/g, 'fill="currentColor"');
const wordmark = svg(480, 128, `${shapes}<text x="151" y="85" fill="#1e331a" font-family="Plus Jakarta Sans" font-size="64" font-weight="700" letter-spacing="-3">PasPapan</text>`);
const social = svg(1200, 630, `<rect width="1200" height="630" fill="#f6f8f6"/><g transform="translate(82 74) scale(.62)">${shapes}</g><text x="182" y="132" fill="#1e331a" font-family="Plus Jakarta Sans" font-weight="700" font-size="46" letter-spacing="-2">PasPapan</text><text x="82" y="303" fill="#1e331a" font-family="Plus Jakarta Sans" font-size="65" font-weight="700" letter-spacing="-2">Hadir. Terhubung.</text><text x="82" y="382" fill="#1e331a" font-family="Plus Jakarta Sans" font-size="65" font-weight="700" letter-spacing="-2">Tertata.</text><text x="86" y="477" fill="#617067" font-family="Plus Jakarta Sans" font-size="24">Kehadiran, jadwal, dan kebutuhan kerja tim.</text><g transform="translate(810 210) scale(2.35)">${shapes}</g><path d="M84 548h1032" stroke="#dde4df"/><text x="86" y="585" fill="#44733a" font-family="Plus Jakarta Sans" font-size="18">WORKSPACE TIM</text>`);
const font = { fontFiles: [resolve(root, 'assets/fonts/PlusJakartaSans.ttf')], loadSystemFonts: false, defaultFontFamily: 'Plus Jakarta Sans' };
function outline(source) { return new Resvg(source, { font }).toString(); }
function render(source, width) {
	return new Resvg(source, { font, fitTo: { mode: 'width', value: width } }).render().asPng();
}
const outputs = new Map([
	['mark.svg', mark], ['mark-mono.svg', mono], ['mark-inverse.svg', mark.replace(/fill="#[0-9a-f]+"/g, 'fill="#ffffff"')], ['wordmark.svg', outline(wordmark)], ['app-icon.svg', appIcon],
	['favicon.svg', appIcon], ['social-card.svg', outline(social)],
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
await mkdir(resolve(root, 'apps/web/static/brand'), { recursive: true });
for (const [name, data] of outputs) {
	await writeFile(resolve(root, 'apps/web/static/brand', name), data);
}
console.log(`Exported ${outputs.size} brand assets to apps/web/static/brand.`);
