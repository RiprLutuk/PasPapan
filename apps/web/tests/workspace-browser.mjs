// Deterministic UI coverage. API/RBAC persistence is tested separately in requests-smoke.ts.
import { chromium, expect } from '@playwright/test';
import assert from 'node:assert/strict';
import { mkdtemp } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const base = process.env.TEST_WEB_URL ?? 'http://127.0.0.1:4273';
const screenshots = await mkdtemp(join(tmpdir(), 'paspapan-workspace-'));
const browser = await chromium.launch({ headless: true });
try {
	const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, geolocation: { latitude: -6.2, longitude: 106.8 }, permissions: ['geolocation'] });
	const page = await context.newPage();
	page.setDefaultTimeout(10_000);
	const errors = [];
	page.on('pageerror', error => errors.push(error.message));
	let role = 'admin', sessionStatus = 200, attendanceStatus = 'not_checked_in', employeeReads = 0, savedShift = false, assignment = null;
	let loginStatus = 401;
	let notificationRead = false;
	const people = [{ id: 'admin', full_name: 'Admin Test', email: 'admin@test.invalid', role: 'admin', employee_code: 'ADM-001' }, { id: 'employee', full_name: 'Nadia Putri', email: 'nadia@test.invalid', role: 'employee', employee_code: 'EMP-002' }];
	const shifts = [{ id: 'morning', name: 'Shift pagi', starts_at: '08:00', ends_at: '17:00', crosses_midnight: false }];
	await page.route('**/v1/**', async route => {
		const path = new URL(route.request().url()).pathname;
		const method = route.request().method();
		if (path === '/v1/auth/login') return route.fulfill({ status: loginStatus, json: loginStatus === 200 ? {} : { error: 'Email atau kata sandi salah.' } });
		if (path === '/v1/auth/session') return route.fulfill({ status: sessionStatus, json: sessionStatus === 200 ? { user: { id: 'admin', full_name: 'Admin Test', email: 'admin@test.invalid' }, company: { id: 'company', name: 'PasPapan Indonesia', role } } : { error: 'Test session error' } });
		if (path === '/v1/attendance/today') return route.fulfill({ json: { date: '2026-10-24', status: attendanceStatus, shift_name: 'Shift pagi', starts_at: '08:00', ends_at: '17:00' } });
		if (path === '/v1/attendance/check-in' || path === '/v1/attendance/check-out') {
			assert.equal(method, 'POST');
			assert.equal(route.request().postDataJSON().latitude, -6.2);
			attendanceStatus = path.endsWith('check-in') ? 'checked_in' : 'checked_out';
			return route.fulfill({ status: 204 });
		}
		if (path === '/v1/admin/employees') { employeeReads++; return route.fulfill({ json: people }); }
		if (path === '/v1/admin/shifts') {
			if (method === 'POST') { savedShift = true; const shift = { id: 'new-shift', ...route.request().postDataJSON() }; shifts.push(shift); return route.fulfill({ json: shift }); }
			return route.fulfill({ json: shifts });
		}
		if (path === '/v1/admin/shift-assignments') { assignment = route.request().postDataJSON(); return route.fulfill({ status: 204 }); }
		if (path === '/v1/auth/logout') return route.fulfill({ status: 204 });
		if (path === '/v1/notifications/notice/read') { notificationRead = true; return route.fulfill({ status: 204 }); }
		if (path === '/v1/notifications') return route.fulfill({ json: [{ id: 'notice', title: 'Pengajuan cuti', body: 'approved', read: notificationRead }] });
		if (path === '/v1/schedule') return route.fulfill({ json: [{ date: '2026-10-24', name: 'Shift pagi', starts_at: '08:00', ends_at: '17:00', crosses_midnight: false }] });
		if (path === '/v1/attendance/history') return route.fulfill({ json: [{ date: '2026-10-24', checked_in_at: '08:00', checked_out_at: '17:00' }] });
		if (['/v1/requests', '/v1/approvals'].includes(path)) return route.fulfill({ json: [] });
		throw new Error('Unexpected API request: ' + path);
	});
	const noOverflow = async () => assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, 'No horizontal overflow');
	await page.goto(base + '/app/today');
	await expect(page.getByRole('button', { name: 'Check-in sekarang' })).toBeVisible();
	await page.keyboard.press('Tab');
	await expect(page.getByRole('link', { name: 'Lewati ke konten' })).toBeFocused();
	await page.keyboard.press('Enter');
	await expect(page.locator('#main-content')).toBeFocused();
	assert.ok((await page.getByRole('button', { name: 'Check-in sekarang' }).boundingBox()).y < 600, 'Primary attendance action is above fold');
	await expect(page.getByRole('navigation', { name: 'Navigasi utama', exact: true }).getByRole('link', { name: 'Beranda' })).toHaveAttribute('aria-current', 'page');
	await page.screenshot({ path: join(screenshots, 'today-desktop.png'), fullPage: true });
	await page.getByRole('button', { name: 'Check-in sekarang' }).click();
	await expect(page.getByRole('button', { name: 'Check-out sekarang' })).toBeVisible();
	await page.getByRole('button', { name: 'Check-out sekarang' }).click();
	await expect(page.getByRole('status')).toHaveText('Kehadiran hari ini lengkap. Terima kasih!');
	await page.goto(base + '/admin/employees');
	await expect(page.getByRole('heading', { name: 'Nadia Putri' })).toBeVisible();
	await page.getByLabel('Cari employee').fill('EMP-002');
	await expect(page.getByRole('heading', { name: 'Nadia Putri' })).toBeVisible();
	await expect(page.getByRole('heading', { name: 'Admin Test' })).toHaveCount(0);
	await page.getByLabel('Cari employee').fill('missing');
	await expect(page.getByRole('heading', { name: 'Tidak ada hasil' })).toBeVisible();
	await page.getByLabel('Cari employee').fill('');
	await page.screenshot({ path: join(screenshots, 'employees-desktop.png'), fullPage: true });
	await page.goto(base + '/admin/attendance');
	await page.getByLabel('Nama shift').fill('Shift sore');
	await page.getByRole('button', { name: 'Simpan shift' }).click();
	await expect(page.getByRole('status')).toHaveText('Shift disimpan.');
	assert.equal(savedShift, true);
	await page.getByRole('button', { name: 'Tetapkan', exact: true }).click();
	await expect(page.getByRole('status')).toHaveText('Jadwal disimpan.');
	assert.equal(assignment.shift_id, 'new-shift');
	await page.screenshot({ path: join(screenshots, 'shifts-desktop.png'), fullPage: true });
	for (const width of [320, 390, 768, 1440]) {
		await page.setViewportSize({ width, height: 844 });
		for (const path of ['/app/today', '/app/requests', '/app/approvals', '/app/schedule', '/app/history', '/app/notifications', '/admin/employees', '/admin/attendance']) {
			await page.goto(base + path);
			await expect(page.locator('main h1')).toBeVisible();
			await noOverflow();
		}
	}
	await page.setViewportSize({ width: 390, height: 844 });
	attendanceStatus = 'not_checked_in';
	role = 'employee';
	await page.goto(base + '/app/today');
	await expect(page.getByRole('button', { name: 'Check-in sekarang' })).toBeVisible();
	await expect(page.getByRole('navigation', { name: 'Navigasi mobile', exact: true })).toBeVisible();
	await expect(page.getByRole('link', { name: 'Employee', exact: true })).toHaveCount(0);
	await page.screenshot({ path: join(screenshots, 'today-mobile.png'), fullPage: true });
	await page.getByRole('navigation', { name: 'Navigasi mobile', exact: true }).getByRole('link', { name: 'Jadwal', exact: true }).click();
	await expect(page.getByRole('heading', { name: 'Jadwal kerja' })).toBeVisible();
	await expect(page.getByRole('navigation', { name: 'Navigasi mobile', exact: true }).getByRole('link', { name: 'Jadwal', exact: true })).toHaveAttribute('aria-current', 'page');
	await expect(page.getByRole('heading', { name: 'Shift pagi', exact: true })).toBeVisible();
	await page.getByRole('navigation', { name: 'Navigasi mobile', exact: true }).getByRole('link', { name: 'Notifikasi', exact: true }).click();
	await expect(page.getByText('Pengajuan disetujui', { exact: true })).toBeVisible();
	await page.getByRole('button', { name: 'Tandai dibaca' }).click();
	await expect(page.getByText('Sudah dibaca', { exact: true })).toBeVisible();
	assert.equal(notificationRead, true);
	const before = employeeReads;
	await page.goto(base + '/admin/employees');
	await expect(page.getByRole('heading', { name: 'Akses terbatas' })).toBeVisible();
	assert.equal(employeeReads, before, 'Unauthorized admin content must not mount or fetch');
	await page.goto(base + '/app/approvals');
	await expect(page.getByRole('heading', { name: 'Akses terbatas' })).toBeVisible();
	sessionStatus = 503;
	await page.goto(base + '/app/today');
	await expect(page.getByRole('alert')).toContainText('Periksa koneksi');
	await expect(page.getByRole('link', { name: 'Masuk kembali' })).toHaveCount(0);
	sessionStatus = 401;
	await page.getByRole('button', { name: 'Coba lagi' }).click();
	await expect(page.getByRole('link', { name: 'Masuk kembali' })).toBeVisible();
	sessionStatus = 200;
	await page.goto(base + '/app/today');
	await page.getByRole('button', { name: 'Keluar' }).click();
	await expect(page).toHaveURL(base + '/login');
	await page.getByLabel('Email kerja', { exact: true }).fill('employee@test.invalid');
	await page.getByLabel('Kata sandi', { exact: true }).fill('test-password-only');
	await page.getByRole('button', { name: 'Tampilkan kata sandi' }).click();
	await expect(page.getByLabel('Kata sandi', { exact: true })).toHaveAttribute('type', 'text');
	await page.getByRole('button', { name: 'Sembunyikan kata sandi' }).click();
	await page.getByRole('button', { name: 'Masuk ke papan kerja' }).click();
	await expect(page.getByRole('alert')).toHaveText('Email atau kata sandi salah.');
	await noOverflow();
	await page.screenshot({ path: join(screenshots, 'login-mobile.png'), fullPage: true });
	loginStatus = 200;
	await page.getByRole('button', { name: 'Masuk ke papan kerja' }).click();
	await expect(page.getByRole('button', { name: 'Check-in sekarang' })).toBeVisible();
	assert.deepEqual(errors, []);
	console.log('PASS: compact workspace, GPS actions, employee filtering, CMS shifts/assignments, role visibility, session errors, logout, 8 routes × 4 viewports');
	console.log('Screenshots: ' + screenshots);
} finally { await browser.close(); }
