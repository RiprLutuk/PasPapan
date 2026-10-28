import assert from 'node:assert/strict';
import test from 'node:test';
import { legacyDestination } from '../src/lib/legacy-destinations.ts';

test('business workflows with dedicated modules do not fall back to generic workspace records', () => {
	for (const route of [
		'/attendance-corrections',
		'/overtime',
		'/reimbursement',
		'/wfh',
		'/cash-advance',
		'/shift-swap',
		'/admin/leaves',
		'/admin/reimbursements',
		'/admin/roles-permissions'
	]) {
		assert(legacyDestination(route), `${route} should resolve to its dedicated workflow`);
	}
});

test('native destinations never redirect to themselves and app aliases resolve once', () => {
	assert.equal(legacyDestination('/face-enrollment'),null);
	assert.equal(legacyDestination('/user/profile'),null);
	assert.equal(legacyDestination('/app/payroll'),null);
	assert.equal(legacyDestination('/app/my-payslips').destination,'/app/payroll');
	assert.equal(legacyDestination('/admin/profile').destination,'/user/profile');
	assert.equal(legacyDestination('/admin/import-export/attendances').destination,'/admin/attendances');
});
