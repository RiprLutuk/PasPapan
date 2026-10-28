import type { LegacyFeature } from './legacy-features';

export type FeatureKind =
	| 'attendance'
	| 'communications'
	| 'finance'
	| 'people'
	| 'schedule'
	| 'reports'
	| 'settings'
	| 'security'
	| 'requests'
	| 'operations';

export type FeatureRecord = {
	id: string;
	title: string;
	detail: string;
	status: string;
	updated: string;
	amount?: string;
};

export type FeatureWorkspace = {
	kind: FeatureKind;
	domain: string;
	columns: [string, string, string];
};

const presentation: Record<FeatureKind, Omit<FeatureWorkspace, 'kind'>> = {
	attendance: { domain: 'KEHADIRAN & VERIFIKASI', columns: ['Catatan / titik', 'Anggota / lokasi', 'Kondisi'] },
	communications: { domain: 'KOMUNIKASI TIM', columns: ['Pesan / pengumuman', 'Pengirim', 'Pembaruan'] },
	finance: { domain: 'KEUANGAN', columns: ['Dokumen', 'Nilai', 'Status'] },
	people: { domain: 'SDM & PENGEMBANGAN', columns: ['Anggota / proses', 'Penanggung jawab', 'Status'] },
	schedule: { domain: 'JADWAL KERJA', columns: ['Hari & tanggal', 'Jam kerja', 'Penempatan'] },
	reports: { domain: 'LAPORAN & ANALITIK', columns: ['Laporan', 'Cakupan', 'Pembaruan'] },
	settings: { domain: 'PREFERENSI WORKSPACE', columns: ['Pengaturan', 'Nilai saat ini', 'Kondisi'] },
	security: { domain: 'AKSES & KEAMANAN', columns: ['Aktivitas', 'Pengguna / perangkat', 'Waktu'] },
	requests: { domain: 'PENGAJUAN & PERSETUJUAN', columns: ['Pengajuan', 'Periode', 'Status'] },
	operations: { domain: 'OPERASIONAL', columns: ['Pekerjaan / aset', 'Penanggung jawab', 'Kondisi'] }
};

export function featureKind(pathname: string, feature: LegacyFeature): FeatureKind {
	const path = pathname.toLowerCase();
	if (path.includes('barcode') || path.includes('face-enrollment') || path.includes('attendance')) return 'attendance';
	if (path.includes('announcements') || path.endsWith('/inbox') || path.includes('collaboration') || path === '/admin/notifications') return 'communications';
	if (path.includes('analytics') || path === '/admin/reports' || path.endsWith('/reports')) return 'reports';
	if (path.includes('/settings') || path.includes('companies') || path.includes('integrations')) return 'settings';
	if (path.includes('payroll') || path.includes('kasbon') || path.includes('cash-advance') || path.includes('reimbursement') || feature.group === 'Keuangan') return 'finance';
	if (feature.group === 'Keamanan' || path.includes('roles-permissions') || path.includes('user-sessions')) return 'security';
	if (feature.group === 'Jadwal' || path.includes('schedule') || path.includes('shift-swap') || path.includes('holidays')) return 'schedule';
	if (path.includes('kpi') || feature.group === 'SDM' || feature.group === 'Karier' || path.includes('appraisal') || path.includes('hr-')) return 'people';
	if (path.includes('wfh') || path.includes('offboarding') || feature.group === 'Pengajuan' || path.includes('request') || path === '/overtime' || path === '/apply-leave') return 'requests';
	return 'operations';
}

export function featureWorkspace(pathname: string, feature: LegacyFeature): FeatureWorkspace {
	const kind = featureKind(pathname, feature);
	return { kind, ...presentation[kind] };
}
