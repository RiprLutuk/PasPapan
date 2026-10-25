// Run against a dedicated LOCAL rewrite database and API. Creates isolated fixtures,
// then removes only those fixtures. Never point DATABASE_URL at Laravel.
import assert from 'node:assert/strict';
const base = process.env.TEST_API_URL ?? 'http://127.0.0.1:4301';
const origin = process.env.APP_ORIGIN ?? 'http://127.0.0.1:4173';
const database = process.env.DATABASE_URL;
assert(database && new URL(database).pathname === '/paspapan_rewrite', 'Requires local paspapan_rewrite database');
assert(['127.0.0.1', 'localhost'].includes(new URL(database).hostname));
assert(['127.0.0.1', 'localhost'].includes(new URL(base).hostname));
const users: string[] = [], companies: string[] = [];
const cors = await fetch(base + '/v1/auth/session', {
	method: 'OPTIONS',
	headers: {
		Origin: origin,
		'Access-Control-Request-Method': 'GET',
		'Access-Control-Request-Headers': 'content-type'
	}
});
assert.equal(cors.status, 200);
assert.equal(cors.headers.get('access-control-allow-origin'), origin);
assert.equal(cors.headers.get('access-control-allow-credentials'), 'true');
const deniedCors = await fetch(base + '/v1/auth/session', {
	method: 'OPTIONS',
	headers: {
		Origin: 'https://untrusted.example',
		'Access-Control-Request-Method': 'GET',
		'Access-Control-Request-Headers': 'content-type'
	}
});
assert.equal(deniedCors.headers.get('access-control-allow-origin'), null);
function sql(query: string) {
	const result = Bun.spawnSync(['psql', database!, '-v', 'ON_ERROR_STOP=1', '-Atqc', query]);
	assert.equal(result.exitCode, 0, result.stderr.toString());
	return result.stdout.toString().trim();
}
const serviceKey = process.env.API_INTERNAL_KEY?.trim();
async function call(path: string, cookie = '', body?: unknown, expected = 200, trusted = true) {
	const response = await fetch(base + path, {
		method: body === undefined ? 'GET' : 'POST',
		headers: {
			cookie,
			'content-type': 'application/json',
			...(trusted ? { Origin: origin } : {}),
			...(serviceKey ? { 'x-paspapan-service-key': serviceKey } : {})
		},
		body: body === undefined ? undefined : JSON.stringify(body)
	});
	const text = await response.text();
	assert.equal(response.status, expected, path + ': ' + text);
	return { body: text ? JSON.parse(text) : null, cookie: response.headers.get('set-cookie')?.split(';')[0] ?? '' };
}
async function account(label: string) {
	const suffix = crypto.randomUUID();
	const registered = await call('/v1/auth/register', '', {
		company_name: 'Smoke ' + label, company_slug: 'smoke-' + suffix,
		full_name: label, email: suffix + '@example.test', password: 'smoke-password-123'
	});
	const { body } = await call('/v1/auth/session', registered.cookie);
	users.push(body.user.id); companies.push(body.company.id);
	return { cookie: registered.cookie, id: body.user.id, company: body.company.id };
}
try {
	const owner = await account('Owner'), employee = await account('Employee');
	const manager = await account('Manager'), stranger = await account('Other manager');
	const foreign = await account('Foreign owner');
	const ownerCapabilities = (await call('/v1/auth/session', owner.cookie)).body.capabilities;
	assert(ownerCapabilities.includes('admin.employees.manage'));
	assert(ownerCapabilities.includes('admin.barcodes.manage'));
	assert(ownerCapabilities.includes('requests.approvals.admin'));
	assert(!ownerCapabilities.includes('admin.payroll.manage'));
	const employeeDraft = { full_name: 'New Employee', email: crypto.randomUUID() + '@example.test', password: 'employee-test-123', employee_code: 'TEST-NEW', manager_user_id: owner.id };
	const created = (await call('/v1/admin/employees', owner.cookie, employeeDraft, 201)).body;
	users.push(created.id);
	assert.equal(created.role, 'employee');
	const login = await call('/v1/auth/login', '', { email: employeeDraft.email, password: employeeDraft.password });
	assert.equal((await call('/v1/auth/session', login.cookie)).body.company.id, owner.company);
	await call('/v1/admin/attendance-locations', login.cookie, undefined, 403);
	await call('/v1/admin/attendance-locations', owner.cookie, {
		name: '  Kantor smoke test  ',
		latitude: -6.2,
		longitude: 106.8,
		radius_meters: 100
	}, 403, false);
	await call('/v1/admin/attendance-locations', owner.cookie, {
		name: 'Radius tidak valid', latitude: -6.2, longitude: 106.8, radius_meters: 10001
	}, 400);
	const qrLocation = (await call('/v1/admin/attendance-locations', owner.cookie, {
		name: '  Kantor smoke test  ',
		latitude: -6.2,
		longitude: 106.8,
		radius_meters: 100
	}, 201)).body;
	assert.equal(qrLocation.name, 'Kantor smoke test');
	assert.equal(qrLocation.qr_token.length, 64);
	assert(Date.parse(qrLocation.qr_expires_at) > Date.now());
	await call('/v1/admin/attendance-locations/refresh', login.cookie, {}, 403);
	await call('/v1/admin/attendance-locations/refresh', owner.cookie, {}, 403, false);
	const oldQr = qrLocation.qr_token;
	sql(`UPDATE attendance_locations SET qr_expires_at=now()-interval '1 second' WHERE id='${qrLocation.id}'`);
	await call('/v1/attendance/check-in', login.cookie, {
		latitude: -6.2, longitude: 106.8, qr_token: oldQr
	}, 400);
	const renewed = await Promise.all([1, 2].map(() => call('/v1/admin/attendance-locations/refresh', owner.cookie, {})));
	qrLocation.qr_token = renewed[0].body.find((item: any) => item.id === qrLocation.id).qr_token;
	assert.notEqual(qrLocation.qr_token, oldQr);
	assert.equal(renewed[1].body.find((item: any) => item.id === qrLocation.id).qr_token, qrLocation.qr_token);
	assert.equal((await call('/v1/admin/attendance-locations/refresh', foreign.cookie, {})).body.length, 0);
	await call('/v1/attendance/check-in', login.cookie, {
		latitude: -6.2, longitude: 106.8, qr_token: oldQr
	}, 400);
	await call('/v1/attendance/check-in', login.cookie, {
		latitude: -6.2, longitude: 106.802, qr_token: qrLocation.qr_token
	}, 400);
	const checkedIn = await call('/v1/attendance/check-in', login.cookie, {
		latitude: -6.2, longitude: 106.8, qr_token: qrLocation.qr_token.toUpperCase()
	}, 200);
	assert.match(checkedIn.body.check_in_time, /^\d{2}:\d{2}$/);
	assert.equal(checkedIn.body.check_out_time, null);
	await call('/v1/attendance/check-out', login.cookie, {
		latitude: -6.2, longitude: 106.802, qr_token: qrLocation.qr_token
	}, 400);
	const checkedOut = await call('/v1/attendance/check-out', login.cookie, {
		latitude: -6.2, longitude: 106.8, qr_token: qrLocation.qr_token
	}, 200);
	assert.match(checkedOut.body.check_in_time, /^\d{2}:\d{2}$/);
	assert.match(checkedOut.body.check_out_time, /^\d{2}:\d{2}$/);
	assert.equal(checkedOut.body.status, 'checked_out');
	await call('/v1/attendance/check-in', foreign.cookie, {
		latitude: -6.2, longitude: 106.8, qr_token: qrLocation.qr_token
	}, 400);
	await call(`/v1/admin/attendance-locations/${qrLocation.id}/status`, owner.cookie, { active: false }, 204);
	await call('/v1/attendance/check-in', login.cookie, {
		latitude: -6.2, longitude: 106.8, qr_token: qrLocation.qr_token
	}, 400);
	assert.equal(sql(`SELECT count(*) FROM audit_events WHERE company_id='${owner.company}' AND event_type LIKE 'attendance.location.%'`), '2');
	await call('/v1/admin/employees', login.cookie, { ...employeeDraft, email: crypto.randomUUID() + '@example.test' }, 403);
	await call('/v1/admin/employees', owner.cookie, { ...employeeDraft, manager_user_id: foreign.id }, 400);
	const duplicateEmail = crypto.randomUUID() + '@example.test';
	await call('/v1/admin/employees', owner.cookie, { ...employeeDraft, email: duplicateEmail }, 409);
	assert.equal(sql(`SELECT count(*) FROM users WHERE email='${duplicateEmail}'`), '0');
	for (const [user, role] of [[employee, 'employee'], [manager, 'manager'], [stranger, 'manager']] as const) {
		sql(`DELETE FROM employee_profiles WHERE user_id='${user.id}';
			DELETE FROM company_memberships WHERE user_id='${user.id}';
			INSERT INTO company_memberships(company_id,user_id,role) VALUES ('${owner.company}','${user.id}','${role}');
			INSERT INTO employee_profiles(company_id,user_id,employee_code) VALUES ('${owner.company}','${user.id}','${user.id}');`);
	}
	sql(`UPDATE employee_profiles SET manager_user_id='${manager.id}' WHERE user_id='${employee.id}' AND company_id='${owner.company}'`);
	// Sessions resolve current membership permissions, not a stale role at login.
	assert.deepEqual((await call('/v1/auth/session', employee.cookie)).body.capabilities, []);
	assert.deepEqual((await call('/v1/auth/session', manager.cookie)).body.capabilities,
		['requests.approvals.manager', 'requests.approvals.view']);
	for (const cookie of [employee.cookie, manager.cookie]) {
		for (const path of ['/v1/admin/employees', '/v1/admin/shifts']) {
			assert.equal((await call(path, cookie, undefined, 403)).body.error, 'permission denied');
		}
		await call('/v1/admin/shifts', cookie, { name: 'Denied', starts_at: '08:00', ends_at: '17:00', crosses_midnight: false }, 403);
		await call('/v1/admin/shift-assignments', cookie, { user_id: employee.id, shift_id: crypto.randomUUID(), work_date: '2026-11-01' }, 403);
	}
	const body = { kind: 'leave', starts_on: '2026-11-01', ends_on: '2026-11-02', reason: 'Family leave', minutes: null };
	await call('/v1/requests', '', undefined, 401);
	assert.equal((await call('/v1/requests', employee.cookie, body, 403, false)).body.error, 'untrusted request origin');
	await call('/v1/requests', employee.cookie, { ...body, starts_on: '2026-02-30' }, 400);
	await call('/v1/requests', employee.cookie, { ...body, ends_on: '2026-01-01' }, 400);
	const entry = (await call('/v1/requests', employee.cookie, body)).body;
	const decision = { decision: 'approved', note: 'Verified' };
	await call('/v1/approvals', employee.cookie, undefined, 403);
	assert.equal((await call('/v1/approvals', stranger.cookie)).body.length, 0);
	assert.equal((await call('/v1/requests', foreign.cookie)).body.length, 0);
	await call(`/v1/requests/${entry.id}/manager-decision`, foreign.cookie, decision, 400);
	await call(`/v1/requests/${entry.id}/manager-decision`, stranger.cookie, decision, 400);
	await call(`/v1/requests/${entry.id}/admin-decision`, owner.cookie, decision, 400);
	assert.equal((await call('/v1/approvals', manager.cookie)).body[0].id, entry.id);
	await call(`/v1/requests/${entry.id}/manager-decision`, manager.cookie, decision, 204);
	await call(`/v1/requests/${entry.id}/admin-decision`, manager.cookie, decision, 403);
	await call(`/v1/requests/${entry.id}/admin-decision`, owner.cookie, decision, 204);
	await call(`/v1/requests/${entry.id}/admin-decision`, owner.cookie, decision, 400);
	assert.equal((await call('/v1/requests', employee.cookie)).body[0].status, 'approved');
	assert.equal(sql(`SELECT count(*) FROM request_decisions WHERE request_id='${entry.id}'`), '2');
	assert.equal(sql(`SELECT count(*) FROM audit_events WHERE subject_id='${entry.id}'`), '3');
	assert.equal(sql(`SELECT count(*) FROM notifications WHERE user_id='${employee.id}'`), '2');
	await call('/v1/notifications/unread-count', '', undefined, 401);
	assert.equal((await call('/v1/notifications/unread-count', foreign.cookie)).body.count, 0);
	assert.equal((await call('/v1/notifications/unread-count', employee.cookie)).body.count, 2);
	const inbox = (await call('/v1/notifications', employee.cookie)).body;
	assert.equal(inbox.length, 2);
	await call('/v1/notifications/' + inbox[0].id + '/read', foreign.cookie, {}, 400);
	await call('/v1/notifications/' + inbox[0].id + '/read', employee.cookie, {}, 204);
	assert.equal((await call('/v1/notifications', employee.cookie)).body[0].read, true);
	assert.equal((await call('/v1/notifications/unread-count', employee.cookie)).body.count, 1);
	assert.equal((await call('/v1/attendance/history', foreign.cookie)).body.length, 0);
	assert.equal((await call('/v1/schedule', employee.cookie)).body.length, 0);
	const own = (await call('/v1/requests', owner.cookie, body)).body;
	await call(`/v1/requests/${own.id}/manager-decision`, owner.cookie, decision, 400);
	const reject = (await call('/v1/requests', employee.cookie, body)).body;
	await call(`/v1/requests/${reject.id}/manager-decision`, manager.cookie, { decision: 'rejected' }, 204);
	await call(`/v1/requests/${reject.id}/admin-decision`, owner.cookie, decision, 400);
	await call('/v1/auth/logout', login.cookie, {}, 204);
	await call('/v1/auth/session', login.cookie, undefined, 401);
	// Configurable access: isolated company roles, deny precedence and live revocation.
	const view = 'admin.employees.view', manage = 'admin.employees.manage';
	const assignment = { role_id: null as string | null, grants: [] as string[], denies: [] as string[] };
	sql(`UPDATE company_memberships SET role='admin' WHERE company_id='${owner.company}' AND user_id='${stranger.id}'`);
	await call('/v1/admin/employees', stranger.cookie);
	await call('/v1/admin/access', stranger.cookie, undefined, 403);
	assert(!(await call('/v1/auth/session', stranger.cookie)).body.capabilities.includes('admin.access.manage'));
	sql(`UPDATE company_memberships SET role='manager' WHERE company_id='${owner.company}' AND user_id='${stranger.id}'`);
	const scoped = (await call('/v1/requests', employee.cookie, body)).body;
	await call(`/v1/requests/${scoped.id}/manager-decision`, owner.cookie, decision, 204);
	await call(`/v1/admin/access/members/${manager.id}`, owner.cookie, { ...assignment, grants: ['requests.approvals.admin'] }, 204);
	assert((await call('/v1/approvals', manager.cookie)).body.some((item: { id: string }) => item.id === scoped.id));
	await call(`/v1/requests/${scoped.id}/admin-decision`, manager.cookie, decision, 204);
	await call(`/v1/admin/access/members/${manager.id}`, owner.cookie, assignment, 204);
	await call('/v1/admin/access', employee.cookie, undefined, 403);
	await call('/v1/admin/access/roles', manager.cookie, { name: 'Escalation', permissions: [view] }, 403);
	await call('/v1/admin/access/roles', owner.cookie, { name: 'Escalation', permissions: ['admin.access.manage'] }, 400);
	await call('/v1/admin/access/roles', owner.cookie, { name: 'Unknown', permissions: ['*'] }, 400);
	await call('/v1/admin/access/roles', owner.cookie, { name: 'CSRF', permissions: [view] }, 403, false);
	const customRole = (await call('/v1/admin/access/roles', owner.cookie, { name: 'HR viewer', permissions: [view] })).body;
	await call('/v1/admin/access/roles', owner.cookie, { name: 'hr VIEWER', permissions: [] }, 409);
	await call('/v1/admin/access/roles', foreign.cookie, { id: customRole.id, name: 'Cross tenant', permissions: [] }, 400);
	await call(`/v1/admin/access/members/${employee.id}`, foreign.cookie, { ...assignment, role_id: customRole.id }, 400);
	await call(`/v1/admin/access/members/${foreign.id}`, owner.cookie, assignment, 400);
	await call(`/v1/admin/access/members/${owner.id}`, owner.cookie, assignment, 400);
	const foreignRole = (await call('/v1/admin/access/roles', foreign.cookie, { name: 'Foreign role', permissions: [manage] })).body;
	await call(`/v1/admin/access/members/${employee.id}`, owner.cookie, { ...assignment, role_id: foreignRole.id }, 400);
	await call(`/v1/admin/access/members/${employee.id}`, owner.cookie, { ...assignment, grants: ['admin.access.manage'] }, 400);
	await call(`/v1/admin/access/members/${employee.id}`, owner.cookie, { ...assignment, role_id: customRole.id }, 204);
	await call(`/v1/admin/access/roles/${customRole.id}/delete`, owner.cookie, {}, 409);
	await call(`/v1/admin/access/roles/${customRole.id}/delete`, foreign.cookie, {}, 400);
	assert.deepEqual((await call('/v1/auth/session', employee.cookie)).body.capabilities, [view]);
	await call('/v1/admin/employees', employee.cookie);
	await call('/v1/admin/employees', employee.cookie, employeeDraft, 403);
	await call('/v1/admin/access', employee.cookie, undefined, 403);
	await call('/v1/admin/access/roles', owner.cookie, { id: customRole.id, name: 'HR viewer', permissions: [view, manage] });
	assert((await call('/v1/auth/session', employee.cookie)).body.capabilities.includes(manage));
	await call(`/v1/admin/access/members/${employee.id}`, owner.cookie, { ...assignment, role_id: customRole.id, grants: [manage], denies: [manage] }, 204);
	assert.deepEqual((await call('/v1/auth/session', employee.cookie)).body.capabilities, [view]);
	await call('/v1/admin/employees', employee.cookie, employeeDraft, 403);
	await call(`/v1/admin/access/members/${employee.id}`, owner.cookie, { ...assignment, role_id: customRole.id, denies: [view, manage] }, 204);
	await call('/v1/admin/employees', employee.cookie, undefined, 403);
	await call(`/v1/admin/access/members/${employee.id}`, owner.cookie, assignment, 204);
	assert.deepEqual((await call('/v1/auth/session', employee.cookie)).body.capabilities, []);
	assert.equal(sql(`SELECT count(*) FROM audit_events WHERE company_id='${owner.company}' AND event_type LIKE 'access.%'`), '8');
	assert.equal((await call('/v1/admin/access', owner.cookie)).body.roles.length, 1);
	assert.equal((await call('/v1/admin/access', foreign.cookie)).body.roles.length, 1);
	await call(`/v1/admin/access/roles/${customRole.id}/delete`, employee.cookie, {}, 403);
	await call(`/v1/admin/access/roles/${customRole.id}/delete`, owner.cookie, {}, 204);
	assert.equal((await call('/v1/admin/access', owner.cookie)).body.roles.length, 0);
	const ownerEvents = (await call('/v1/admin/access', owner.cookie)).body.events;
	assert.equal(ownerEvents.length, 9);
	assert.equal(ownerEvents[0].action, 'access.role.deleted');
	assert.equal(ownerEvents[0].changes.before.name, 'HR viewer');
	assert.equal(ownerEvents[0].changes.after, null);
	assert.equal((await call('/v1/admin/access', foreign.cookie)).body.events.length, 1);
	assert.equal(sql(`SELECT count(*) FROM audit_events WHERE company_id='${owner.company}' AND event_type='access.role.deleted'`), '1');
	console.log('PASS: QR attendance requires an active company location and coordinates inside its configured radius');
	console.log('PASS: request lifecycle, dates, CSRF, self-approval, manager scope, tenant isolation, audit and notifications');
	console.log('PASS: custom roles, live permission refresh/revocation, deny overrides, owner protection, access audit and cross-tenant denial');
} finally {
	// IDs are generated UUIDs returned by this run only; preserve all other local data.
	for (const id of companies) {
		sql(`DELETE FROM request_decisions WHERE company_id='${id}';
			DELETE FROM member_access WHERE company_id='${id}';
			DELETE FROM access_roles WHERE company_id='${id}';
			DELETE FROM audit_events WHERE company_id='${id}';
			DELETE FROM employee_profiles WHERE company_id='${id}';
			DELETE FROM companies WHERE id='${id}';`);
	}
	for (const id of users) sql(`DELETE FROM users WHERE id='${id}'`);
}
