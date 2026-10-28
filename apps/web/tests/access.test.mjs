import { test } from 'node:test';
import assert from 'node:assert/strict';
import { visibleOperations, requiredPermission } from '../src/lib/access.ts';

test('operational navigation fails closed and each permission grants only its own link', () => {
    assert.deepEqual(visibleOperations(), []);
    assert.deepEqual(visibleOperations(['*']), []);
    assert.deepEqual(visibleOperations(['admin.employees.view']).map(l => l.href), ['/admin/employees']);
    assert.deepEqual(visibleOperations(['requests.approvals.view']).map(l => l.href), ['/app/approvals']);
    assert.deepEqual(visibleOperations(['admin.access.manage']).map(l => l.href), ['/admin/access']);
    assert.deepEqual(visibleOperations(['admin.barcodes.manage']).map(l => l.href), ['/admin/barcodes']);
});

test('direct routes require the matching capability, not the dashboard or employee role', () => {
    assert.equal(requiredPermission('/admin/access', true), 'admin.access.manage');
    assert.equal(requiredPermission('/admin/api-docs', true), 'admin.access.manage');
    assert.equal(requiredPermission('/admin/overtime', true), 'requests.approvals.view');
    assert.equal(requiredPermission('/admin/reimbursements', true), 'requests.approvals.view');
    assert.equal(requiredPermission('/admin/employees/details', true), 'admin.employees.view');
    assert.equal(requiredPermission('/admin/attendance', true), 'admin.shifts.manage');
    assert.equal(requiredPermission('/admin/schedules', true), 'admin.schedules.manage');
    assert.equal(requiredPermission('/admin/schedules/2026/10', true), 'admin.schedules.manage');
    assert.equal(requiredPermission('/admin/masterdata/shift', true), 'admin.shifts.manage');
    assert.equal(requiredPermission('/admin/barcodes', true), 'admin.barcodes.manage');
    assert.equal(requiredPermission('/admin/barcodes/locations', true), 'admin.barcodes.manage');
	assert.equal(requiredPermission('/admin/attendances', true), 'admin.attendances.view');
	assert.equal(requiredPermission('/admin/activity-logs', true), 'admin.audit.view');
	assert.equal(requiredPermission('/admin/payrolls', true), 'admin.payroll.view');
	assert.equal(requiredPermission('/admin/system-info', true), 'admin.system.view');
    assert.equal(requiredPermission('/admin/assets', true), 'admin.assets.manage');
	assert.equal(requiredPermission('/admin/announcements', true), 'admin.announcements.manage');
    assert.equal(requiredPermission('/admin/unknown-module', true), 'admin.workspace_records.manage');
    assert.equal(requiredPermission('/app/approvals', false), 'requests.approvals.view');
    assert.equal(requiredPermission('/app/today', false), null);
});

test('legacy aliases and trailing slashes enforce their destination permissions', () => {
    assert.equal(requiredPermission('/admin/roles-permissions/', true), 'admin.access.manage');
    assert.equal(requiredPermission('/admin/masterdata/admin', true), 'admin.employees.view');
    assert.equal(requiredPermission('/approvals/history', false), 'requests.approvals.view');
    assert.equal(requiredPermission('/app/approvals/?kind=leave', false), 'requests.approvals.view');
    assert.equal(requiredPermission('/admin/integrations/', true), 'admin.access.manage');
    assert.equal(requiredPermission('/admin/profile', true), null);
});
