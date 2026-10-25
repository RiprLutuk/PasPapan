import type { LegacyFeature } from './legacy-features';

export type FeatureKind = 'attendance' | 'communications' | 'finance' | 'people' | 'schedule' | 'reports' | 'settings' | 'security' | 'requests' | 'operations';
export type FeatureRecord = { id: string; title: string; detail: string; status: string; updated: string; amount?: string };

export type FeatureWorkspace = {
	kind: FeatureKind;
	domain: string;
	summary: { label: string; value: string }[];
	columns: [string, string, string];
	records: FeatureRecord[];
	primaryAction: string;
	emptyAction: string;
};

const workspaceByKind: Record<FeatureKind, Omit<FeatureWorkspace, 'kind'>> = {
	attendance: {
		domain: 'KEHADIRAN & VERIFIKASI',
		summary: [{ label: 'Hadir hari ini', value: '38 / 42' }, { label: 'Titik QR aktif', value: '4' }, { label: 'Perlu koreksi', value: '2' }],
		columns: ['Catatan / titik', 'Anggota / lokasi', 'Kondisi'],
		records: [
			{ id: 'att-1', title: 'Masuk · 08.04', detail: 'Nadia Putri · Kantor pusat', status: 'Terverifikasi', updated: 'Hari ini' },
			{ id: 'att-2', title: 'QR kantor pusat', detail: 'Area lobi · Radius 100 m', status: 'Aktif', updated: 'Dipakai 38 kali' },
			{ id: 'att-3', title: 'Koreksi jam pulang', detail: 'Dimas Saputra · 23 Okt', status: 'Menunggu tinjauan', updated: 'Kemarin' }
		],
		primaryAction: 'Tinjau kehadiran',
		emptyAction: 'Lihat catatan'
	},
	communications: {
		domain: 'KOMUNIKASI TIM',
		summary: [{ label: 'Belum dibaca', value: '4' }, { label: 'Pengumuman aktif', value: '2' }, { label: 'Pesan pekan ini', value: '18' }],
		columns: ['Pesan / pengumuman', 'Pengirim', 'Pembaruan'],
		records: [
			{ id: 'com-1', title: 'Perubahan jadwal operasional', detail: 'Jadwal kantor · Berlaku 26 Okt', status: 'Belum dibaca', updated: '10.42' },
			{ id: 'com-2', title: 'Pengingat penutupan periode', detail: 'People Ops · Semua anggota', status: 'Dipublikasikan', updated: 'Kemarin' },
			{ id: 'com-3', title: 'Ruang koordinasi cabang', detail: 'Tim operasional · 6 anggota', status: 'Aktif', updated: '21 Okt' }
		],
		primaryAction: 'Buat pengumuman',
		emptyAction: 'Lihat kotak masuk'
	},
	finance: {
		domain: 'KEUANGAN',
		summary: [{ label: 'Perlu diproses', value: '8' }, { label: 'Nilai periode ini', value: 'Rp 24,8 jt' }, { label: 'Jatuh tempo', value: '3' }],
		columns: ['Dokumen', 'Nilai', 'Status'],
		records: [
			{ id: 'fin-1', title: 'Operasional kantor · Oktober', detail: 'Biaya operasional · 24 Okt 2026', status: 'Perlu ditinjau', updated: 'Hari ini', amount: 'Rp 4.250.000' },
			{ id: 'fin-2', title: 'Kasbon · Nadia Putri', detail: 'Pengajuan kasbon · 22 Okt 2026', status: 'Diproses', updated: '2 hari lalu', amount: 'Rp 1.500.000' },
			{ id: 'fin-3', title: 'Reimbursement transport', detail: 'Klaim biaya · 20 Okt 2026', status: 'Disetujui', updated: '4 hari lalu', amount: 'Rp 285.000' }
		],
		primaryAction: 'Catat transaksi',
		emptyAction: 'Buka ringkasan'
	},
	people: {
		domain: 'SDM & PENGEMBANGAN',
		summary: [{ label: 'Anggota tim', value: '42' }, { label: 'Perlu perhatian', value: '5' }, { label: 'Selesai periode ini', value: '18' }],
		columns: ['Anggota / proses', 'Penanggung jawab', 'Status'],
		records: [
			{ id: 'hr-1', title: 'Nadia Putri', detail: 'EMP-002 · Customer Operations', status: 'Menunggu tinjauan', updated: 'Hari ini' },
			{ id: 'hr-2', title: 'Dimas Saputra', detail: 'EMP-014 · Warehouse', status: 'Sedang berjalan', updated: 'Kemarin' },
			{ id: 'hr-3', title: 'Onboarding · Raka Wijaya', detail: 'Checklist hari ke-3 · People Ops', status: 'Selesai', updated: '21 Okt 2026' }
		],
		primaryAction: 'Buat proses HR',
		emptyAction: 'Lihat anggota'
	},
	schedule: {
		domain: 'JADWAL KERJA',
		summary: [{ label: 'Hari kerja', value: '5 hari' }, { label: 'Shift pagi', value: '08.00–17.00' }, { label: 'Permintaan', value: '2 menunggu' }],
		columns: ['Hari & tanggal', 'Jam kerja', 'Penempatan'],
		records: [
			{ id: 'day-1', title: 'Senin · 26 Okt', detail: 'Kantor pusat', status: 'Shift pagi', updated: '08.00–17.00' },
			{ id: 'day-2', title: 'Selasa · 27 Okt', detail: 'Kantor pusat', status: 'Shift pagi', updated: '08.00–17.00' },
			{ id: 'day-3', title: 'Rabu · 28 Okt', detail: 'Kerja dari rumah', status: 'WFH', updated: 'Fleksibel' }
		],
		primaryAction: 'Ajukan perubahan jadwal',
		emptyAction: 'Lihat kalender tim'
	},
	reports: {
		domain: 'LAPORAN & ANALITIK',
		summary: [{ label: 'Kehadiran periode ini', value: '96,4%' }, { label: 'Pengajuan selesai', value: '38' }, { label: 'Periode', value: 'Oktober 2026' }],
		columns: ['Laporan', 'Cakupan', 'Pembaruan'],
		records: [
			{ id: 'rep-1', title: 'Ringkasan kehadiran', detail: 'Tingkat hadir dan ketepatan waktu', status: 'Siap ditinjau', updated: 'Hari ini' },
			{ id: 'rep-2', title: 'Aktivitas pengajuan', detail: 'Cuti, lembur, dan reimbursement', status: 'Diperbarui', updated: 'Kemarin' },
			{ id: 'rep-3', title: 'Ringkasan operasional toko', detail: 'Penjualan, stok, dan retur', status: 'Siap ditinjau', updated: '21 Okt 2026' }
		],
		primaryAction: 'Atur laporan',
		emptyAction: 'Pilih laporan'
	},
	settings: {
		domain: 'PREFERENSI WORKSPACE',
		summary: [{ label: 'Profil perusahaan', value: 'Lengkap' }, { label: 'Pengaturan aktif', value: '12' }, { label: 'Perlu ditinjau', value: '1' }],
		columns: ['Pengaturan', 'Nilai saat ini', 'Kondisi'],
		records: [
			{ id: 'set-1', title: 'Informasi workspace', detail: 'Nama perusahaan dan zona waktu', status: 'Aktif', updated: 'Diperbarui 20 Okt' },
			{ id: 'set-2', title: 'Alur persetujuan', detail: 'Atasan langsung → admin', status: 'Aktif', updated: 'Diperbarui 18 Okt' },
			{ id: 'set-3', title: 'Notifikasi pengajuan', detail: 'Email dan pemberitahuan dalam aplikasi', status: 'Perlu ditinjau', updated: 'Diperbarui 12 Okt' }
		],
		primaryAction: 'Tambah pengaturan',
		emptyAction: 'Tinjau preferensi'
	},
	security: {
		domain: 'AKSES & KEAMANAN',
		summary: [{ label: 'Sesi aktif', value: '17' }, { label: 'Aktivitas hari ini', value: '86' }, { label: 'Perlu ditinjau', value: '1' }],
		columns: ['Aktivitas', 'Pengguna / perangkat', 'Waktu'],
		records: [
			{ id: 'sec-1', title: 'Masuk ke workspace', detail: 'Chrome · macOS', status: 'Berhasil', updated: '10.42 · Hari ini' },
			{ id: 'sec-2', title: 'Perubahan jadwal shift', detail: 'Admin · Kantor pusat', status: 'Dicatat', updated: '09.18 · Hari ini' },
			{ id: 'sec-3', title: 'Percobaan masuk gagal', detail: 'Perangkat tidak dikenal', status: 'Perlu ditinjau', updated: 'Kemarin' }
		],
		primaryAction: 'Tinjau akses',
		emptyAction: 'Periksa aktivitas'
	},
	requests: {
		domain: 'PENGAJUAN & TINDAK LANJ',
		summary: [{ label: 'Menunggu', value: '2' }, { label: 'Diproses', value: '1' }, { label: 'Selesai bulan ini', value: '6' }],
		columns: ['Pengajuan', 'Tanggal', 'Status'],
		records: [
			{ id: 'req-1', title: 'Cuti tahunan · 2 hari', detail: '26–27 Okt 2026 · Keperluan keluarga', status: 'Menunggu persetujuan', updated: 'Hari ini' },
			{ id: 'req-2', title: 'Lembur · Penutupan periode', detail: '23 Okt 2026 · 2 jam', status: 'Disetujui', updated: 'Kemarin' },
			{ id: 'req-3', title: 'Kerja dari rumah', detail: '21 Okt 2026 · Alamat terdaftar', status: 'Selesai', updated: '3 hari lalu' }
		],
		primaryAction: 'Buat pengajuan',
		emptyAction: 'Lihat panduan pengajuan'
	},
	operations: {
		domain: 'OPERASIONAL TIM',
		summary: [{ label: 'Sedang berjalan', value: '12' }, { label: 'Perlu tindakan', value: '4' }, { label: 'Selesai pekan ini', value: '27' }],
		columns: ['Pekerjaan / aset', 'Pemilik', 'Kondisi'],
		records: [
			{ id: 'ops-1', title: 'Pemeriksaan perangkat kantor', detail: 'Checklist bulanan · Lantai 2', status: 'Sedang berjalan', updated: 'Jatuh tempo 28 Okt' },
			{ id: 'ops-2', title: 'Laptop · AST-014', detail: 'Penanggung jawab: Nadia Putri', status: 'Digunakan', updated: 'Diperbarui 20 Okt' },
			{ id: 'ops-3', title: 'Permintaan dokumen karyawan', detail: 'Surat keterangan kerja · People Ops', status: 'Perlu tindakan', updated: 'Hari ini' }
		],
		primaryAction: 'Buat pekerjaan',
		emptyAction: 'Lihat daftar operasional'
	}
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
	const workspace = workspaceByKind[kind];
	return { kind, ...workspace, primaryAction: feature.primaryAction || workspace.primaryAction };
}
