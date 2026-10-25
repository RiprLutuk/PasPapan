import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile, access, readdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';

const root = fileURLToPath(new URL('../../../', import.meta.url));
const read = path => readFile(resolve(root, path), 'utf8');
async function sources(dir) {
    return (await Promise.all((await readdir(resolve(root, dir), { withFileTypes: true })).map(entry =>
        entry.isDirectory() ? sources(`${dir}/${entry.name}`) : `${dir}/${entry.name}`))).flat();
}

test('Jakarta Sans is local, variable, and preloaded', async () => {
    for (const name of ['plus-jakarta-sans-latin.woff2', 'plus-jakarta-sans-latin-italic.woff2']) {
        const font = await readFile(resolve(root, 'apps/web/static/fonts', name));
        assert.equal(font.subarray(0, 4).toString(), 'wOF2');
    }
    const css = await read('apps/web/src/lib/workspace.css');
    assert.match(css, /font-weight: 200 800/);
    assert.match(css, /font-family: 'Plus Jakarta Sans'/);
    const layout = await read('apps/web/src/routes/+layout.svelte');
    assert.match(layout, /rel="preload" href="\/fonts\/plus-jakarta-sans-latin.woff2"/);
    assert.doesNotMatch(layout, /system-ui|ui-sans-serif|Inter,/);
    assert.match(await read('assets/fonts/OFL.txt'), /SIL OPEN FONT LICENSE/);
});

test('UI uses shared rounded surface tokens and library icons, not hand-drawn paths', async () => {
    const workspace = await read('apps/web/src/lib/workspace.css');
    assert.match(workspace, /--radius: 12px/);
    assert.match(workspace, /--radius-lg: 16px/);
    assert.match(workspace, /--radius-md: var\(--radius\)/);
    assert.match(workspace, /--radius-sm: 8px/);
    for (const file of (await sources('apps/web/src')).filter(file => /\.(css|svelte)$/.test(file))) {
        const code = await read(file);
        assert.doesNotMatch(code, /border-radius:\s*(?:[5-9]|\d{2,})px\s*[;}]/, file);
        assert.doesNotMatch(code, /fonts\.googleapis|fonts\.gstatic/, file);
    }
    const icon = await read('apps/web/src/lib/Icon.svelte');
    assert.match(icon, /from '@lucide\/svelte'/);
    assert.match(icon, /aria-hidden="true"/);
    assert.doesNotMatch(icon, /<path|const paths/);
    const shell = await read('apps/web/src/lib/AppShell.svelte');
    assert.match(shell, /aria-label=\{item.label\} title=\{item.label\}/);
});

test('mobile dock uses rewrite-native destinations and keeps secondary links in profile', async () => {
    const shell = await read('apps/web/src/lib/AppShell.svelte');
    assert.match(shell, /href: '\/app\/today', label: 'Beranda'/);
    assert.match(shell, /href: '\/app\/schedule', label: 'Jadwal'/);
    assert.match(shell, /href: '\/app\/scan', label: 'Scan'/);
    assert.match(shell, /href: '\/app\/requests', label: 'Pengajuan'/);
    assert.match(shell, /\{#each mobileLinks as item\}/);
    assert.match(shell, /class="nav-label-text">Profil<\/span>/);
    assert.match(shell, /href="\/user\/profile"/);
    assert.match(shell, /grid-template-columns: repeat\(5, minmax\(0, 1fr\)\)/);
    assert.match(shell, /class="mobile-profile-menu"/);
    assert.match(shell, /class="profile-dropdown"/);
    assert.match(shell, /href="\/app\/history"/);
    assert.doesNotMatch(shell, /class="notification-link"/);
    assert.match(shell, /\/v1\/notifications\/unread-count/);
    assert.match(shell, /paspapan:notifications-updated/);
    assert.doesNotMatch(shell, /href: '\/hr-tasks', label: 'Tugas'/);
    const activity = await read('apps/web/src/lib/Activity.svelte');
    assert.match(activity, /window\.dispatchEvent\(new Event\('paspapan:notifications-updated'\)\)/);
});

test('missing HR task API is not presented as a dedicated rewrite page', async () => {
    const route = await read('apps/web/src/routes/[...path]/+page.svelte');
    assert.doesNotMatch(route, /HrTasksPreview|pathname === '\/hr-tasks'/);
    assert.doesNotMatch(await read('apps/web/src/lib/AppShell.svelte'), /href: '\/hr-tasks'/);
});

test('employee directory provides searchable desktop and mobile views', async () => {
    const page = await read('apps/web/src/routes/admin/employees/+page.svelte');
    assert.match(page, /bind:value=\{search\}/);
    assert.match(page, /bind:value=\{roleFilter\}/);
    assert.match(page, /people-table-wrap/);
    assert.match(page, /people-cards/);
    assert.match(page, /@media\(max-width:760px\)/);
    assert.doesNotMatch(page, /Unit belum tersedia/);
});

test('shift CMS gates mutations and adapts shift directory to mobile', async () => {
    const page = await read('apps/web/src/routes/admin/attendance/+page.svelte');
    assert.match(page, /capabilities\.includes\('admin\.shifts\.manage'\)/);
    assert.match(page, /canManageShifts && capabilities\.includes\('admin\.schedules\.manage'\) && capabilities\.includes\('admin\.employees\.view'\)/);
    assert.match(page, /if \(busy \|\| !canManageShifts\) return/);
    assert.match(page, /bind:value=\{typeFilter\}/);
    assert.match(page, /shift-table-wrap/);
    assert.match(page, /shift-cards/);
    assert.match(page, /@media\(max-width:760px\)/);
});

test('admin schedule calendar integrates with real shift assignment APIs', async () => {
    const page = await read('apps/web/src/routes/admin/schedules/+page.svelte');
    assert.match(page, /type="month"/);
    assert.match(page, /aria-label="Navigasi bulan"/);
    assert.match(page, /dialogElement\.showModal\(\)/);
    assert.match(page, /<dialog bind:this=\{dialogElement\}/);
    assert.match(page, /shiftAssignments\(/);
    assert.match(page, /assignShift\(/);
    assert.match(page, /deleteShiftAssignment\(/);
    assert.doesNotMatch(page, /PreviewEmployee|Simpan pratinjau/);
});

test('profile panels use persisted APIs for details, passwords and other sessions', async () => {
    const page = await read('apps/web/src/lib/Profile.svelte');
    assert.match(page, /\/v1\/account\/profile/);
    assert.match(page, /\/v1\/account\/password/);
    assert.match(page, /\/v1\/account\/sessions/);
    assert.match(page, /\/revoke/);
    assert.match(page, /profile\.version/);
    assert.match(page, /entry\.current/);
    assert.match(page, /role="tablist"/);
    assert.match(page, /event\.key === 'ArrowRight'/);
    assert.doesNotMatch(page, /profile photo|two.factor|API tokens/i);
});

test('attendance QR scan submits the token and location to the protected attendance API', async () => {
    const scan = await read('apps/web/src/routes/app/scan/+page.svelte');
    const api = await read('apps/web/src/lib/api.ts');
    const today = await read('apps/web/src/routes/app/today/+page.svelte');
    const locations = await read('apps/web/src/routes/admin/barcodes/+page.svelte');
    assert.match(scan, /checkIn\(location\.latitude, location\.longitude, result\)/);
    assert.match(scan, /checkOut\(location\.latitude, location\.longitude, result\)/);
    assert.match(scan, /class="scan-result"/);
    assert.match(scan, /QR terbaca/);
    assert.match(scan, /geolocation\.getCurrentPosition/);
    assert.match(scan, /onMount\(\(\) =>/);
    assert.match(scan, /await Promise\.all\(\[startScan\(\), refreshLocation\(\)\]\)/);
    assert.match(scan, /await import\('html5-qrcode'\)/);
    assert.match(scan, /Html5QrcodeSupportedFormats\.QR_CODE/);
    assert.match(scan, /formatsToSupport: \[Html5QrcodeSupportedFormats\.QR_CODE\]/);
    assert.match(scan, /if \(!\/\^\[a-f0-9\]\{64\}\$\/i\.test\(token\)\)/);
    assert.match(scan, /async function switchCamera\(\)/);
    assert.match(scan, /facingMode = facingMode === 'environment' \? 'user' : 'environment'/);
    assert.match(scan, /aria-labelledby="location-heading"/);
    assert.match(scan, /OpenStreetMap contributors/);
    assert.match(scan, /await import\('leaflet'\)/);
    assert.match(await read('apps/web/package.json'), /"html5-qrcode"/);
    assert.match(await read('apps/web/package.json'), /"leaflet"/);
    assert.match(scan, /function isGeolocationError/);
    assert.match(scan, /<details class="manual-entry"/);
    assert.doesNotMatch(scan, /PETUNJUK|Posisikan kode di dalam bingkai|Lokasi perangkat diverifikasi saat absensi dikirim/);
    assert.match(scan, /onclick=\{\(\) => void refreshLocation\(true\)\}/);
    assert.match(scan, /class:refreshing=\{locationLoading\}/);
    assert.match(scan, /maximumAge: 0/);
    assert.match(scan, /locationRequestSequence/);
    assert.match(scan, /location-refresh-spin/);
    assert.match(api, /JSON\.stringify\(\{ latitude, longitude, qr_token \}\)/);
    assert.match(today, /href="\/app\/scan"/);
    assert.match(locations, /QRCode\.toDataURL/);
    assert.match(locations, /refreshAttendanceQr\(\)/);
    assert.match(locations, /radius_meters/);
    assert.match(locations, /pemalsuan GPS/);
});

test('rewritten home keeps attendance compact and focused on its QR action', async () => {
    const today = await read('apps/web/src/routes/app/today/+page.svelte');
    assert.match(today, /class="panel attendance"/);
    assert.match(today, /Presensi hari ini/);
    assert.match(today, /href="\/app\/scan"/);
    assert.match(today, /timeline-copy/);
    assert.doesNotMatch(today, /QR lokasi kerja · validasi radius|Absensi tercatat setelah QR/);
});

test('PHP/Laravel legacy stack is completely removed and FE/BE run as separated Podman services', async () => {
    for (const file of ['package.json', 'apps/web/package.json']) {
        const pkg = JSON.parse(await read(file));
        const active = JSON.stringify([pkg.dependencies, pkg.devDependencies, pkg.scripts]);
        assert.doesNotMatch(active, /playwright|vercel|capacitor|adapter-auto|laravel/i);
    }
    for (const path of [
        'app', 'bootstrap', 'config', 'database', 'lang', 'public', 'resources', 'routes',
        'storage', 'stubs', 'vendor', 'artisan', 'composer.json', 'composer.lock',
        'playwright.config.ts', 'vercel.json', '.env.vercel.example', 'api/index.php', 'tests'
    ]) {
        await assert.rejects(access(resolve(root, path)), { code: 'ENOENT' });
    }
    assert.match(await read('apps/web/vite.config.ts'), /adapter-node/);
    assert.match(await read('apps/api/.env.example'), /DATABASE_URL=/);
    assert.match(await read('apps/web/.env.example'), /API_PROXY_TARGET=/);
    assert.match(await read('apps/api/Containerfile'), /paspapan-api/);
    assert.match(await read('apps/web/Containerfile'), /CMD \["node", "build"\]/);
    assert.match(await read('compose.yaml'), /paspapan-api/);
    assert.match(await read('compose.yaml'), /paspapan-web/);
    const featureCatalog = await read('apps/web/src/lib/legacy-features.ts');
    const workspaceCatalog = await read('apps/web/src/lib/legacy-workspaces.ts');
    const workspacePage = await read('apps/web/src/lib/LegacyFeature.svelte');
    assert.doesNotMatch(featureCatalog, /\/admin\/toko|\/admin\/commercial/i);
    assert.doesNotMatch(workspaceCatalog, /\bcommerce\b|TOKO & PENJUALAN/i);
    assert.match(workspacePage, /\/v1\/workspace-records/);
    assert.match(workspacePage, /\/v1\/admin\/activity-logs/);
    assert.match(workspacePage, /\/v1\/admin\/attendances/);
    assert.match(workspacePage, /\/v1\/payrolls/);
    assert.doesNotMatch(workspacePage, /Pratinjau UI|belum terhubung ke backend/);
});

test('brand text is outlined from the bundled font', async () => {
    for (const file of ['wordmark.svg', 'social-card.svg']) {
        const svg = await read(`apps/web/static/brand/${file}`);
        assert.doesNotMatch(svg, /<text\b/);
        assert.match(svg, /<path\b/);
    }
});

test('auth and profile screens share reusable UI primitives', async () => {
    const login = await read('apps/web/src/routes/login/+page.svelte');
    const register = await read('apps/web/src/routes/register/+page.svelte');
    const forgot = await read('apps/web/src/routes/forgot-password/+page.svelte');
    const profile = await read('apps/web/src/lib/Profile.svelte');
    for (const page of [login, register, forgot]) {
        assert.match(page, /AuthFrame/);
        assert.match(page, /FormField/);
        assert.match(page, /AlertBanner/);
    }
    assert.match(login, /PasswordField/);
    assert.match(register, /PasswordField/);
    assert.match(profile, /PasswordField/);
});
