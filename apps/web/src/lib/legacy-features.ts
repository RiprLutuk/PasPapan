export type LegacyFeature = {
	title: string;
	eyebrow: string;
	description: string;
	group: string;
	primaryAction: string;
};

const features: Record<string, LegacyFeature> = {
	'/admin': { title: 'Workspace admin', eyebrow: 'ADMIN', description: 'Ringkasan pengelolaan tim dan operasional perusahaan.', group: 'Admin', primaryAction: 'Buka ringkasan' },
	'/admin/dashboard': { title: 'Dashboard admin', eyebrow: 'ADMIN', description: 'Ringkasan kehadiran, pengajuan, dan aktivitas tim.', group: 'Admin', primaryAction: 'Lihat ringkasan' },
	'/admin/accounting': { title: 'Accounting', eyebrow: 'KEUANGAN', description: 'Ringkasan jurnal, arus kas, dan transaksi perusahaan.', group: 'Keuangan', primaryAction: 'Catat transaksi' },
	'/admin/activity-logs': { title: 'Log aktivitas', eyebrow: 'KEAMANAN', description: 'Tinjau aktivitas pengguna dan perubahan data perusahaan.', group: 'Keamanan', primaryAction: 'Filter aktivitas' },
	'/admin/analytics': { title: 'Analitik', eyebrow: 'INSIGHT', description: 'Pantau tren kehadiran, pengajuan, dan aktivitas tim.', group: 'Laporan', primaryAction: 'Atur periode' },
	'/admin/announcements': { title: 'Pengumuman', eyebrow: 'KOMUNIKASI', description: 'Kelola informasi yang ditampilkan kepada anggota perusahaan.', group: 'Komunikasi', primaryAction: 'Buat pengumuman' },
	'/admin/api-integrations': { title: 'Integrasi API', eyebrow: 'PENGATURAN', description: 'Kelola koneksi layanan dan integrasi perusahaan.', group: 'Pengaturan', primaryAction: 'Tambah integrasi' },
	'/admin/appraisals': { title: 'Penilaian kinerja', eyebrow: 'SDM', description: 'Kelola siklus evaluasi dan sasaran kinerja karyawan.', group: 'SDM', primaryAction: 'Buat penilaian' },
	'/admin/assets': { title: 'Aset perusahaan', eyebrow: 'OPERASIONAL', description: 'Kelola inventaris aset dan penanggung jawabnya.', group: 'Operasional', primaryAction: 'Tambah aset' },
	'/admin/attendance-corrections': { title: 'Koreksi absensi', eyebrow: 'ABSENSI', description: 'Tinjau permintaan koreksi catatan kehadiran.', group: 'Absensi', primaryAction: 'Tinjau permintaan' },
	'/admin/attendances': { title: 'Data kehadiran', eyebrow: 'ABSENSI', description: 'Telusuri dan tinjau catatan kehadiran perusahaan.', group: 'Absensi', primaryAction: 'Filter kehadiran' },
	'/admin/barcodes': { title: 'Barcode & QR', eyebrow: 'ABSENSI', description: 'Kelola kode lokasi untuk proses kehadiran.', group: 'Absensi', primaryAction: 'Buat kode' },
	'/admin/collaboration': { title: 'Kolaborasi', eyebrow: 'TIM', description: 'Ruang kerja bersama untuk percakapan dan berkas tim.', group: 'Komunikasi', primaryAction: 'Buat ruang' },
	'/admin/command-center': { title: 'Command center', eyebrow: 'OPERASIONAL', description: 'Ringkasan status sistem dan aktivitas operasional.', group: 'Operasional', primaryAction: 'Lihat aktivitas' },
	'/admin/companies': { title: 'Perusahaan', eyebrow: 'PENGATURAN', description: 'Kelola organisasi dan pengaturan perusahaan.', group: 'Pengaturan', primaryAction: 'Tambah perusahaan' },
	'/admin/custom-forms': { title: 'Form kustom', eyebrow: 'OPERASIONAL', description: 'Rancang formulir internal untuk kebutuhan tim.', group: 'Operasional', primaryAction: 'Buat formulir' },
	'/admin/document-requests': { title: 'Permintaan dokumen', eyebrow: 'SDM', description: 'Tinjau permintaan dan dokumen karyawan.', group: 'SDM', primaryAction: 'Buat permintaan' },
	'/admin/document-templates': { title: 'Template dokumen', eyebrow: 'SDM', description: 'Kelola template surat dan dokumen perusahaan.', group: 'SDM', primaryAction: 'Tambah template' },
	'/admin/holidays': { title: 'Hari libur', eyebrow: 'JADWAL', description: 'Kelola kalender hari libur perusahaan.', group: 'Jadwal', primaryAction: 'Tambah hari libur' },
	'/admin/hr-checklists': { title: 'Checklist HR', eyebrow: 'SDM', description: 'Pantau proses onboarding dan offboarding karyawan.', group: 'SDM', primaryAction: 'Buat checklist' },
	'/admin/import-export/attendances': { title: 'Import & export absensi', eyebrow: 'DATA', description: 'Siapkan data absensi untuk impor atau ekspor.', group: 'Data', primaryAction: 'Pilih berkas' },
	'/admin/import-export/users': { title: 'Import & export karyawan', eyebrow: 'DATA', description: 'Siapkan data karyawan untuk impor atau ekspor.', group: 'Data', primaryAction: 'Pilih berkas' },
	'/admin/inbox': { title: 'Inbox tim', eyebrow: 'KOMUNIKASI', description: 'Tinjau pesan dan tindak lanjut operasional.', group: 'Komunikasi', primaryAction: 'Buat pesan' },
	'/admin/leaves': { title: 'Persetujuan cuti', eyebrow: 'SDM', description: 'Tinjau permintaan cuti dan status persetujuannya.', group: 'SDM', primaryAction: 'Tinjau permintaan' },
	'/admin/manage-kasbon': { title: 'Pengelolaan kasbon', eyebrow: 'KEUANGAN', description: 'Pantau pengajuan kasbon dan proses pelunasannya.', group: 'Keuangan', primaryAction: 'Tinjau pengajuan' },
	'/admin/masterdata/admin': { title: 'Akun admin', eyebrow: 'PENGATURAN', description: 'Kelola akses administrator perusahaan.', group: 'Pengaturan', primaryAction: 'Tambah admin' },
	'/admin/masterdata/division': { title: 'Divisi', eyebrow: 'MASTER DATA', description: 'Kelola struktur divisi perusahaan.', group: 'Master data', primaryAction: 'Tambah divisi' },
	'/admin/masterdata/education': { title: 'Pendidikan', eyebrow: 'MASTER DATA', description: 'Kelola pilihan tingkat pendidikan.', group: 'Master data', primaryAction: 'Tambah pendidikan' },
	'/admin/masterdata/job-title': { title: 'Jabatan', eyebrow: 'MASTER DATA', description: 'Kelola jabatan dan struktur pekerjaan.', group: 'Master data', primaryAction: 'Tambah jabatan' },
	'/admin/masterdata/leave-entitlements': { title: 'Jatah cuti', eyebrow: 'MASTER DATA', description: 'Kelola kebijakan dan saldo cuti karyawan.', group: 'Master data', primaryAction: 'Atur jatah cuti' },
	'/admin/masterdata/leave-types': { title: 'Jenis cuti', eyebrow: 'MASTER DATA', description: 'Kelola jenis cuti yang tersedia.', group: 'Master data', primaryAction: 'Tambah jenis cuti' },
	'/admin/masterdata/shift': { title: 'Master shift', eyebrow: 'JADWAL', description: 'Kelola pola shift dan jam kerja perusahaan.', group: 'Jadwal', primaryAction: 'Tambah shift' },
	'/admin/notifications': { title: 'Notifikasi admin', eyebrow: 'KOMUNIKASI', description: 'Tinjau pemberitahuan dan aktivitas perusahaan.', group: 'Komunikasi', primaryAction: 'Tandai dibaca' },
	'/admin/operational-health': { title: 'Kesehatan operasional', eyebrow: 'SISTEM', description: 'Pantau kesiapan komponen layanan dan antrean.', group: 'Sistem', primaryAction: 'Periksa status' },
	'/admin/operations': { title: 'Operasional', eyebrow: 'OPERASIONAL', description: 'Kelola aktivitas, proyek, dan kunjungan lapangan.', group: 'Operasional', primaryAction: 'Buat aktivitas' },
	'/admin/overtime': { title: 'Persetujuan lembur', eyebrow: 'SDM', description: 'Tinjau pengajuan dan realisasi lembur.', group: 'SDM', primaryAction: 'Tinjau pengajuan' },
	'/admin/payrolls': { title: 'Payroll', eyebrow: 'KEUANGAN', description: 'Kelola periode penggajian dan status pemrosesan.', group: 'Keuangan', primaryAction: 'Buat periode' },
	'/admin/profile': { title: 'Profil admin', eyebrow: 'AKUN', description: 'Tinjau informasi akun dan preferensi admin.', group: 'Akun', primaryAction: 'Edit profil' },
	'/admin/payrolls/settings': { title: 'Pengaturan payroll', eyebrow: 'KEUANGAN', description: 'Atur komponen dan kebijakan penggajian.', group: 'Keuangan', primaryAction: 'Simpan pengaturan' },
	'/admin/reimbursements': { title: 'Reimbursement', eyebrow: 'KEUANGAN', description: 'Tinjau klaim biaya dan bukti pengeluaran.', group: 'Keuangan', primaryAction: 'Tinjau klaim' },
	'/admin/reports': { title: 'Pusat laporan', eyebrow: 'LAPORAN', description: 'Pilih laporan perusahaan dan periode data.', group: 'Laporan', primaryAction: 'Pilih laporan' },
	'/admin/roles-permissions': { title: 'Peran & izin', eyebrow: 'KEAMANAN', description: 'Tinjau peran dan hak akses pengguna.', group: 'Keamanan', primaryAction: 'Tambah peran' },
	'/admin/schedules': { title: 'Penjadwalan tim', eyebrow: 'JADWAL', description: 'Atur jadwal kerja dan penempatan shift tim.', group: 'Jadwal', primaryAction: 'Buat jadwal' },
	'/admin/settings': { title: 'Pengaturan', eyebrow: 'PENGATURAN', description: 'Kelola preferensi dan kebijakan workspace.', group: 'Pengaturan', primaryAction: 'Simpan perubahan' },
	'/admin/settings/kpi': { title: 'Pengaturan KPI', eyebrow: 'SDM', description: 'Kelola indikator kinerja perusahaan.', group: 'SDM', primaryAction: 'Tambah indikator' },
	'/admin/shift-swaps': { title: 'Tukar shift', eyebrow: 'JADWAL', description: 'Tinjau permintaan pertukaran jadwal kerja.', group: 'Jadwal', primaryAction: 'Tinjau permintaan' },
	'/admin/system-maintenance': { title: 'Pemeliharaan sistem', eyebrow: 'SISTEM', description: 'Kelola tugas pemeliharaan dan kebersihan data.', group: 'Sistem', primaryAction: 'Jalankan pemeriksaan' },
	'/admin/user-sessions': { title: 'Sesi pengguna', eyebrow: 'KEAMANAN', description: 'Tinjau sesi aktif dan perangkat pengguna.', group: 'Keamanan', primaryAction: 'Periksa sesi' },
	'/appraisal': { title: 'Penilaian kinerja saya', eyebrow: 'WORKSPACE', description: 'Tinjau target dan hasil penilaian kinerja Anda.', group: 'Karier', primaryAction: 'Lihat penilaian' },
	'/apply-leave': { title: 'Pengajuan cuti', eyebrow: 'WORKSPACE', description: 'Ajukan dan pantau permintaan cuti Anda.', group: 'Pengajuan', primaryAction: 'Ajukan cuti' },
	'/attendance-history': { title: 'Riwayat kehadiran', eyebrow: 'WORKSPACE', description: 'Tinjau catatan masuk dan pulang Anda.', group: 'Absensi', primaryAction: 'Filter riwayat' },
	'/approvals/history': { title: 'Riwayat persetujuan', eyebrow: 'TIM', description: 'Tinjau keputusan pengajuan yang telah diproses.', group: 'Pengajuan', primaryAction: 'Filter riwayat' },
	'/attendance-corrections': { title: 'Koreksi absensi', eyebrow: 'WORKSPACE', description: 'Ajukan dan tinjau koreksi catatan kehadiran.', group: 'Absensi', primaryAction: 'Ajukan koreksi' },
	'/face-enrollment': { title: 'Pendaftaran wajah', eyebrow: 'ABSENSI', description: 'Kelola kesiapan verifikasi wajah untuk absensi.', group: 'Absensi', primaryAction: 'Mulai pendaftaran' },
	'/forms': { title: 'Formulir tim', eyebrow: 'WORKSPACE', description: 'Akses formulir internal yang dibagikan perusahaan.', group: 'Operasional', primaryAction: 'Buka formulir' },
	'/home': { title: 'Beranda', eyebrow: 'WORKSPACE', description: 'Ringkasan kehadiran dan pekerjaan Anda hari ini.', group: 'Workspace', primaryAction: 'Lihat hari ini' },
	'/hr-tasks': { title: 'Tugas HR', eyebrow: 'WORKSPACE', description: 'Tinjau tugas dan checklist yang ditugaskan kepada Anda.', group: 'Tugas', primaryAction: 'Lihat tugas' },
	'/my-assets': { title: 'Aset saya', eyebrow: 'WORKSPACE', description: 'Tinjau aset perusahaan yang menjadi tanggung jawab Anda.', group: 'Operasional', primaryAction: 'Lihat aset' },
	'/my-kasbon': { title: 'Kasbon saya', eyebrow: 'WORKSPACE', description: 'Tinjau pengajuan dan pelunasan kasbon Anda.', group: 'Keuangan', primaryAction: 'Ajukan kasbon' },
	'/my-performance': { title: 'Kinerja saya', eyebrow: 'WORKSPACE', description: 'Tinjau sasaran dan perkembangan kinerja Anda.', group: 'Karier', primaryAction: 'Lihat sasaran' },
	'/my-schedule': { title: 'Jadwal saya', eyebrow: 'WORKSPACE', description: 'Tinjau jadwal kerja dan penempatan shift Anda.', group: 'Jadwal', primaryAction: 'Lihat kalender' },
	'/my-tasks': { title: 'Tugas saya', eyebrow: 'WORKSPACE', description: 'Tinjau pekerjaan dan tindak lanjut yang ditugaskan.', group: 'Tugas', primaryAction: 'Lihat tugas' },
	'/notifications': { title: 'Notifikasi', eyebrow: 'WORKSPACE', description: 'Tinjau pembaruan dan perubahan status pengajuan.', group: 'Komunikasi', primaryAction: 'Tandai dibaca' },
	'/overtime': { title: 'Pengajuan lembur', eyebrow: 'WORKSPACE', description: 'Ajukan dan pantau status permintaan lembur.', group: 'Pengajuan', primaryAction: 'Ajukan lembur' },
	'/payroll': { title: 'Slip gaji', eyebrow: 'WORKSPACE', description: 'Tinjau periode dan dokumen penggajian Anda.', group: 'Keuangan', primaryAction: 'Pilih periode' },
	'/reimbursement': { title: 'Reimbursement saya', eyebrow: 'WORKSPACE', description: 'Kelola klaim penggantian biaya Anda.', group: 'Keuangan', primaryAction: 'Buat klaim' },
	'/my-reimbursements': { title: 'Reimbursement saya', eyebrow: 'KEUANGAN', description: 'Kelola klaim penggantian biaya dan bukti pengeluaran.', group: 'Keuangan', primaryAction: 'Buat klaim' },
	'/my-payslips': { title: 'Slip gaji saya', eyebrow: 'KEUANGAN', description: 'Tinjau periode dan rincian slip penghasilan Anda.', group: 'Keuangan', primaryAction: 'Pilih periode' },
	'/shift-swaps': { title: 'Tukar shift kerja', eyebrow: 'JADWAL', description: 'Ajukan atau tinjau pertukaran jadwal kerja dengan rekan.', group: 'Jadwal', primaryAction: 'Ajukan tukar shift' },
	'/my-warnings': { title: 'Surat peringatan HR', eyebrow: 'SDM', description: 'Tinjau surat pemberitahuan dan dokumen resmi dari HR.', group: 'SDM', primaryAction: 'Lihat surat' },
	'/my-kpis': { title: 'Capaian KPI saya', eyebrow: 'SDM', description: 'Tinjau target capaian dan evaluasi performa kerja.', group: 'SDM', primaryAction: 'Lihat capaian' },
	'/announcements': { title: 'Pengumuman internal', eyebrow: 'KOMUNIKASI', description: 'Papan informasi penting dan pengumuman perusahaan.', group: 'Komunikasi', primaryAction: 'Lihat pengumuman' },
	'/shift-swap-requests': { title: 'Permintaan tukar shift', eyebrow: 'WORKSPACE', description: 'Ajukan atau pantau pertukaran jadwal kerja.', group: 'Jadwal', primaryAction: 'Ajukan tukar shift' },
	'/team-kasbon': { title: 'Kasbon tim', eyebrow: 'TIM', description: 'Tinjau status kasbon anggota tim.', group: 'Keuangan', primaryAction: 'Tinjau kasbon' },
	'/user/profile': { title: 'Profil saya', eyebrow: 'AKUN', description: 'Tinjau informasi akun dan preferensi pribadi.', group: 'Akun', primaryAction: 'Edit profil' },
	'/wfh-requests': { title: 'Permintaan WFH', eyebrow: 'WORKSPACE', description: 'Ajukan dan pantau permintaan kerja dari rumah.', group: 'Pengajuan', primaryAction: 'Ajukan WFH' },
	'/cash-advance': { title: 'Pengajuan kasbon', eyebrow: 'KEUANGAN', description: 'Pengajuan kasbon karyawan dan rencana pengembalian.', group: 'Keuangan', primaryAction: 'Ajukan kasbon' },
	'/shift-swap': { title: 'Tukar shift kerja', eyebrow: 'JADWAL', description: 'Delegasi dan pertukaran jadwal kerja dengan rekan tim.', group: 'Jadwal', primaryAction: 'Ajukan tukar shift' },
	'/wfh': { title: 'Pengajuan WFH', eyebrow: 'PENGAJUAN', description: 'Pengajuan kerja dari rumah dan koordinasi agenda tim.', group: 'Pengajuan', primaryAction: 'Ajukan WFH' },
	'/hr-letters': { title: 'Surat & dokumen HR', eyebrow: 'SDM', description: 'Permintaan dokumen resmi dan surat keterangan kerja.', group: 'SDM', primaryAction: 'Ajukan dokumen' },
	'/assets': { title: 'Inventaris & aset', eyebrow: 'OPERASIONAL', description: 'Peminjaman dan pencatatan perangkat kerja karyawan.', group: 'Operasional', primaryAction: 'Tambah aset' },
	'/kpi': { title: 'Evaluasi KPI', eyebrow: 'SDM', description: 'Target sasaran dan indikator capaian performa kerja.', group: 'SDM', primaryAction: 'Simpan KPI' },
	'/offboarding': { title: 'Offboarding karyawan', eyebrow: 'SDM', description: 'Serah terima inventaris dan penyelesaian administrasi.', group: 'SDM', primaryAction: 'Catat offboarding' },
	'/admin/cash-advances': { title: 'Persetujuan kasbon', eyebrow: 'KEUANGAN', description: 'Tinjau dan kelola pengajuan kasbon karyawan.', group: 'Keuangan', primaryAction: 'Tinjau kasbon' },
	'/admin/kpi': { title: 'Penilaian KPI tim', eyebrow: 'SDM', description: 'Kelola indikator dan sasaran kinerja seluruh divisi.', group: 'SDM', primaryAction: 'Tambah indikator' }
};

const adminLinks = Object.entries(features)
	.filter(([path]) => path.startsWith('/admin/'))
	.map(([href, feature]) => ({ href, label: feature.title, group: feature.group }))
	.sort((a, b) => a.group.localeCompare(b.group, 'id') || a.label.localeCompare(b.label, 'id'));

const workspaceLinks = Object.entries(features)
	.filter(([path]) => !path.startsWith('/admin/'))
	.map(([href, feature]) => ({ href, label: feature.title, group: feature.group }))
	.sort((a, b) => a.group.localeCompare(b.group, 'id') || a.label.localeCompare(b.label, 'id'));

function humanizeSegment(segment: string): string {
	return decodeURIComponent(segment)
		.replace(/\.[a-z0-9]+$/i, '')
		.replace(/[-_]+/g, ' ')
		.replace(/\b\p{L}/gu, (letter) => letter.toLocaleUpperCase('id'));
}

export function legacyFeature(pathname: string): LegacyFeature {
	const path = pathname.replace(/\/+$/, '') || '/';
	const stripped = path.startsWith('/app/') ? path.replace(/^\/app/, '') : path;
	const exact = features[path] ?? features[stripped];
	if (exact) return exact;

	const matched = Object.entries(features)
		.filter(([prefix]) => path.startsWith(`${prefix}/`) || stripped.startsWith(`${prefix}/`))
		.sort(([a], [b]) => b.length - a.length)[0];
	if (matched) {
		const feature = matched[1];
		const basePrefix = path.startsWith(matched[0]) ? matched[0] : `/app${matched[0]}`;
		const detail = path.slice(basePrefix.length).split('/').filter(Boolean).map(humanizeSegment).join(' · ');
		return { ...feature, title: `${feature.title}${detail ? ` · ${detail}` : ''}` };
	}

	const segments = stripped.split('/').filter(Boolean);
	const title = segments.map(humanizeSegment).join(' · ') || 'Workspace';
	return {
		title,
		eyebrow: path.startsWith('/admin/') ? 'ADMIN' : 'WORKSPACE',
		description: 'Pencatatan dan pengelolaan data untuk modul ini.',
		group: path.startsWith('/admin/') ? 'Admin' : 'Workspace',
		primaryAction: 'Tambah data'
	};
}

export function getLegacyAdminLinks() {
	return adminLinks;
}

export function getLegacyWorkspaceLinks() {
	return workspaceLinks;
}
