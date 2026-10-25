import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getFeatureFormConfig } from '../src/lib/legacy-forms.ts';

test('face enrollment form config uses biometric labels and hides amount', () => {
	const config = getFeatureFormConfig('/face-enrollment', 'Pendaftaran wajah', 'Absensi');
	assert.equal(config.titleLabel, 'Nama Profil Wajah');
	assert.equal(config.showAmount, false);
	assert.equal(config.attachmentAccept, 'image/*');
	assert.equal(config.submitButtonText, 'Daftarkan Wajah');
	assert.ok(config.guideNote?.includes('biometrik'));
	assert.ok(config.statusOptions.includes('Siap Digunakan'));
});

test('KPI form config asks for KPI target and indicators, not currency', () => {
	const config = getFeatureFormConfig('/my-kpis', 'Capaian KPI saya', 'SDM');
	assert.equal(config.titleLabel, 'Indikator / Sasaran Kinerja (KPI)');
	assert.equal(config.showAmount, true);
	assert.equal(config.amountLabel, 'Target Capaian / Bobot');
	assert.ok(config.amountPlaceholder?.includes('95%'));
	assert.ok(config.statusOptions.includes('Tercapai'));
	assert.equal(config.submitButtonText, 'Simpan KPI');
});

test('kasbon and reimbursement form configs show currency amounts appropriately', () => {
	const kasbon = getFeatureFormConfig('/my-kasbon', 'Kasbon saya', 'Keuangan');
	assert.equal(kasbon.titleLabel, 'Keperluan Pengajuan Kasbon');
	assert.equal(kasbon.showAmount, true);
	assert.ok(kasbon.amountPlaceholder?.includes('Rp 1.500.000'));
	assert.equal(kasbon.submitButtonText, 'Ajukan Kasbon');

	const cashAdvance = getFeatureFormConfig('/cash-advance', 'Cash Advance', 'Workspace');
	assert.equal(cashAdvance.titleLabel, 'Keperluan Pengajuan Kasbon');
	assert.equal(cashAdvance.showAmount, true);
	assert.equal(cashAdvance.submitButtonText, 'Ajukan Kasbon');

	const reimb = getFeatureFormConfig('/reimbursement', 'Reimbursement saya', 'Keuangan');
	assert.equal(reimb.showAmount, true);
	assert.equal(reimb.submitButtonText, 'Ajukan Klaim');
});

test('shift swap form config asks for replacement details and dates', () => {
	const swap = getFeatureFormConfig('/shift-swaps', 'Tukar shift kerja', 'Jadwal');
	assert.equal(swap.titleLabel, 'Shift & Tanggal yang Ingin Ditukar');
	assert.equal(swap.amountLabel, 'Tanggal Shift Pengganti');
	assert.equal(swap.submitButtonText, 'Ajukan Tukar Shift');
});
