// Browser behavior tests with deterministic API fixtures.
// Actual PostgreSQL/API authorization is covered by requests-smoke.ts.
import { chromium } from '@playwright/test';
import assert from 'node:assert/strict';
const browser = await chromium.launch({ headless: true, ...(process.env.TEST_BROWSER_CHANNEL ? { channel: process.env.TEST_BROWSER_CHANNEL } : {}) });
try {
	const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
	page.setDefaultTimeout(10_000);
	const errors = [];
	page.on('pageerror', error => errors.push(error.message));
	const entries = [];
	await page.route('**/v1/auth/session', route => route.fulfill({ json: { user: { id: 'admin', full_name: 'Admin Test', email: 'admin@test.invalid' }, company: { id: 'company', name: 'Test Company', role: 'admin' } } }));
	await page.route('**/v1/requests', async route => {
		if (route.request().method() === 'POST') {
			const body = route.request().postDataJSON();
			entries.unshift({ ...body, id: 'request-one', status: 'pending_manager' });
			await route.fulfill({ json: entries[0] });
		} else await route.fulfill({ json: entries });
	});
	const base = process.env.TEST_WEB_URL ?? 'http://127.0.0.1:4273';
	await page.goto(base + '/app/requests');
	await page.getByRole('heading', { name: 'Belum ada pengajuan' }).waitFor();
	await page.getByLabel('Tanggal mulai').fill('2026-11-01');
	await page.getByLabel('Tanggal selesai').fill('2026-11-02');
	await page.getByLabel('Alasan').fill('Cuti keluarga');
	await page.getByRole('button', { name: 'Kirim pengajuan' }).click();
	await page.locator('article .badge').filter({ hasText: 'Menunggu manager' }).waitFor();
	assert.equal(entries.length, 1);
	await page.getByLabel('Status', { exact: true }).selectOption('approved');
	await page.getByRole('heading', { name: 'Tidak ada hasil' }).waitFor();
	await page.getByLabel('Status', { exact: true }).selectOption('');
	await page.getByLabel('Cari pengajuan').fill('keluarga');
	assert.equal(await page.locator('article').count(), 1);
	assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
	let pending = true;
	await page.route('**/v1/approvals', route => route.fulfill({ json: pending ? [{ ...entries[0], full_name: 'Employee Test' }] : [] }));
	await page.route('**/v1/requests/request-one/manager-decision', async route => {
		assert.equal(route.request().postDataJSON().decision, 'approved');
		pending = false;
		await route.fulfill({ status: 204 });
	});
	await page.goto(base + '/app/approvals');
	await page.getByText('Employee Test', { exact: true }).waitFor();
	await page.getByRole('button', { name: 'Setujui tahap manager' }).click();
	await page.getByRole('heading', { name: 'Tidak ada pengajuan menunggu' }).waitFor();
	await page.getByText('Keputusan tersimpan.', { exact: true }).waitFor();
	await page.setViewportSize({ width: 1440, height: 900 });
	assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
	assert.deepEqual(errors, []);
	console.log('PASS: mobile request form, approval action, empty states, 204 handling, no browser errors or overflow');
} finally { await browser.close(); }
