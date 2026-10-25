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
    assert.equal(requiredPermission('/admin/employees/details', true), 'admin.employees.view');
    assert.equal(requiredPermission('/admin/attendance', true), 'admin.shifts.manage');
    assert.equal(requiredPermission('/admin/schedules', true), 'admin.schedules.manage');
    assert.equal(requiredPermission('/admin/schedules/2026/10', true), 'admin.schedules.manage');
    assert.equal(requiredPermission('/admin/masterdata/shift', true), 'admin.shifts.manage');
    assert.equal(requiredPermission('/admin/barcodes', true), 'admin.barcodes.manage');
    assert.equal(requiredPermission('/admin/barcodes/locations', true), 'admin.barcodes.manage');
    assert.equal(requiredPermission('/app/approvals', false), 'requests.approvals.view');
    assert.equal(requiredPermission('/app/today', false), null);
});
