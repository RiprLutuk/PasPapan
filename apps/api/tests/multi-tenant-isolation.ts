// Dedicated negative multi-tenant isolation integration test suite.
// Verifies that resources belonging to Company A can NEVER be viewed, modified, deleted,
// approved, or scanned by users belonging to Company B.
import assert from 'node:assert/strict';

const base = process.env.TEST_API_URL ?? 'http://127.0.0.1:3000';
const origin = process.env.APP_ORIGIN ?? 'http://127.0.0.1:4173';
const database = process.env.DATABASE_URL!;
assert(database, 'Requires DATABASE_URL');
const url = new URL(database);
assert.equal(url.pathname, '/paspapan_rewrite');
assert(['127.0.0.1', 'localhost'].includes(url.hostname));
assert(['127.0.0.1', 'localhost'].includes(new URL(base).hostname));

const serviceKey = process.env.API_INTERNAL_KEY?.trim();
const users: string[] = [];
const companies: string[] = [];

async function call(
	path: string,
	cookie = '',
	body?: unknown,
	expected = 200,
	trusted = true,
	customHeaders: Record<string, string> = {}
) {
	const headers: Record<string, string> = {
		cookie,
		'content-type': 'application/json',
		...(trusted ? { Origin: origin } : {}),
		...(serviceKey ? { 'x-paspapan-service-key': serviceKey } : {}),
		...customHeaders
	};
	const response = await fetch(base + path, {
		method: body === undefined ? 'GET' : 'POST',
		headers,
		body: body === undefined ? undefined : JSON.stringify(body)
	});
	const text = await response.text();
	assert.equal(response.status, expected, `${path} returned ${response.status} instead of ${expected}: ${text}`);
	return {
		body: text && response.headers.get('content-type')?.includes('json') ? JSON.parse(text) : null,
		cookie: response.headers.get('set-cookie')?.split(';')[0] ?? ''
	};
}

async function createCompanyWithUser(prefix: string) {
	const suffix = crypto.randomUUID();
	const email = `${prefix}-${suffix}@example.test`;
	const password = 'TestCompanyPassword123!';
	const reg = await call('/v1/auth/register', '', {
		company_name: `Company ${prefix}`,
		company_slug: `co-${prefix}-${suffix}`.toLowerCase(),
		full_name: `Owner ${prefix}`,
		email,
		password
	});
	const session = (await call('/v1/auth/session', reg.cookie)).body;
	users.push(session.user.id);
	companies.push(session.company.id);
	return {
		cookie: reg.cookie,
		userId: session.user.id,
		companyId: session.company.id,
		email,
		password
	};
}

try {
	console.log('--- Setting up isolated Company Alpha and Company Beta fixtures ---');
	const companyA = await createCompanyWithUser('alpha');
	const companyB = await createCompanyWithUser('beta');

	// 1. Employee creation in Company A
	const empEmailA = `emp-a-${crypto.randomUUID()}@example.test`;
	const empA = (
		await call(
			'/v1/admin/employees',
			companyA.cookie,
			{
				full_name: 'Employee Alpha',
				email: empEmailA,
				password: 'EmployeePassword123!',
				employee_code: 'EMP-A-01',
				manager_user_id: companyA.userId
			},
			201
		)
	).body;
	users.push(empA.id);

	// Negative Test 1: Company B owner CANNOT view Company A employee detail
	await call(`/v1/admin/employees/${empA.id}`, companyB.cookie, undefined, 400);

	// Negative Test 2: Company B owner CANNOT modify Company A employee
	await call(
		`/v1/admin/employees/${empA.id}`,
		companyB.cookie,
		{
			full_name: 'Hacked Name',
			employee_code: 'HACKED-01',
			manager_user_id: null,
			phone: '0819999999',
			address: 'Cross Tenant',
			team: 'Security',
			job_title: 'Attacker',
			started_on: '2026-01-01',
			version: 0
		},
		400
	);

	// Negative Test 3: Company B owner CANNOT alter Company A employee status
	await call(
		`/v1/admin/employees/${empA.id}/status`,
		companyB.cookie,
		{ status: 'resigned', version: 0 },
		400
	);

	// 2. Attendance Location in Company A
	const locA = (
		await call(
			'/v1/admin/attendance-locations',
			companyA.cookie,
			{
				name: 'HQ Alpha',
				latitude: -6.2,
				longitude: 106.8,
				radius_meters: 100
			},
			201
		)
	).body;

	// Negative Test 4: Company B owner CANNOT toggle Company A attendance location
	await call(
		`/v1/admin/attendance-locations/${locA.id}/status`,
		companyB.cookie,
		{ active: false },
		400
	);

	// Negative Test 5: Company B user CANNOT check-in using Company A's QR token
	await call(
		'/v1/attendance/check-in',
		companyB.cookie,
		{
			latitude: -6.2,
			longitude: 106.8,
			qr_token: locA.qr_token
		},
		400
	);

	// 3. Private File in Company A
	const fileA = (
		await call(
			'/v1/files',
			companyA.cookie,
			{
				file_name: 'confidential_alpha.pdf',
				content_type: 'application/pdf',
				data_base64: 'JVBERi0xLjQKJcTl8uXrp/Og0MTGCjQgMCBvYmoKPDwgL0xlbmd0aCA1IDAgUiA+PgpzdHJlYW0KQmVuZ2luCg=='
			},
			201
		)
	).body;

	// Negative Test 6: Company B user CANNOT download Company A's private file
	await call(`/v1/files/${fileA.id}/download`, companyB.cookie, undefined, 400);

	// Negative Test 7: Company B user CANNOT delete Company A's private file
	await call(`/v1/files/${fileA.id}/delete`, companyB.cookie, {}, 400);

	// 4. Access Role in Company A
	const roleA = (
		await call(
			'/v1/admin/access/roles',
			companyA.cookie,
			{
				name: 'Custom HR Alpha',
				permissions: ['admin.employees.view', 'admin.employees.manage']
			},
			200
		)
	).body;

	// Negative Test 8: Company B user CANNOT delete Company A's access role
	await call(`/v1/admin/access/roles/${roleA.id}/delete`, companyB.cookie, {}, 400);

	// 5. Shift & Schedule in Company A
	const shiftA = (
		await call(
			'/v1/admin/shifts',
			companyA.cookie,
			{
				name: 'Morning Shift Alpha',
				starts_at: '08:00',
				ends_at: '17:00',
				crosses_midnight: false
			},
			200
		)
	).body;

	// Negative Test 9: Company B user cannot assign Company A employee to a shift
	await call(
		'/v1/admin/shift-assignments',
		companyB.cookie,
		{
			user_id: empA.id,
			shift_id: shiftA.id,
			work_date: '2026-11-01'
		},
		400
	);

	// 6. Workspace Records in Company A
	const recordA = (
		await call(
			'/v1/workspace-records',
			companyA.cookie,
			{
				module_key: 'assets',
				title: 'Alpha Laptop ThinkPad',
				detail: 'Serial 12345',
				status: 'In Use',
				amount: '15000000'
			},
			201
		)
	).body;

	// Negative Test 10: Company B user CANNOT update Company A workspace record status
	await call(
		`/v1/workspace-records/${recordA.id}/status`,
		companyB.cookie,
		{ status: 'Stolen' },
		400
	);

	// Negative Test 11: Company B user CANNOT delete Company A workspace record
	await call(`/v1/workspace-records/${recordA.id}/delete`, companyB.cookie, {}, 400);

	// 7. Security Header & Context Enforcements
	// Negative Test 12: Mutating request without trusted Origin is rejected with 403 UntrustedOrigin
	await call(
		'/v1/admin/shifts',
		companyA.cookie,
		{
			name: 'Illegal Origin Shift',
			starts_at: '09:00',
			ends_at: '18:00',
			crosses_midnight: false
		},
		403,
		false // untrusted
	);

	// Negative Test 13: Unauthenticated request is rejected with 401
	await call('/v1/auth/session', '', undefined, 401);

	console.log('PASS: All 13 Multi-Tenant Isolation Negative Security Tests Succeeded!');
} finally {
	// Cleanup test fixtures
	if (companies.length > 0) {
		const placeholders = companies.map((c) => `'${c}'`).join(',');
		Bun.spawnSync(['psql', database, '-v', 'ON_ERROR_STOP=1', '-c', `DELETE FROM companies WHERE id IN (${placeholders})`]);
	}
	if (users.length > 0) {
		const placeholders = users.map((u) => `'${u}'`).join(',');
		Bun.spawnSync(['psql', database, '-v', 'ON_ERROR_STOP=1', '-c', `DELETE FROM users WHERE id IN (${placeholders})`]);
	}
}
