export type FeatureFormConfig = {
	titleLabel: string;
	titlePlaceholder: string;
	detailLabel: string;
	detailPlaceholder: string;
	showAmount: boolean;
	amountLabel?: string;
	amountPlaceholder?: string;
	attachmentLabel: string;
	attachmentAccept?: string;
	attachmentHint?: string;
	guideNote?: string;
	statusOptions: string[];
	defaultStatus: string;
	submitButtonText: string;
};

export function getFeatureFormConfig(
	pathname: string,
	featureTitle = '',
	group = ''
): FeatureFormConfig {
	const path = pathname.toLowerCase();
	const title = featureTitle.toLowerCase();
	const grp = group.toLowerCase();

	// 1. Pendaftaran Wajah / Biometrik
	if (path.includes('face-enrollment') || path.includes('wajah') || title.includes('wajah') || title.includes('face')) {
		return {
			titleLabel: 'Nama Profil Wajah',
			titlePlaceholder: 'Misal: Foto Utama Wajah (Tampak Depan)',
			detailLabel: 'Catatan & Kondisi Pengambilan',
			detailPlaceholder: 'Misal: Diambil tanpa kacamata/masker, pencahayaan ruangan cukup terang',
			showAmount: false,
			attachmentLabel: 'Unggah Foto Wajah (Tampak Depan)',
			attachmentAccept: 'image/*',
			attachmentHint: 'Format JPG/PNG wajah jelas dan tidak blur untuk verifikasi biometrik',
			guideNote: 'Pendaftaran wajah digunakan untuk verifikasi kehadiran biometrik. Pastikan foto wajah tegak lurus ke kamera dengan pencahayaan memadai.',
			statusOptions: ['Siap Digunakan', 'Menunggu Verifikasi', 'Terverifikasi'],
			defaultStatus: 'Siap Digunakan',
			submitButtonText: 'Daftarkan Wajah'
		};
	}

	// 2. KPI & Penilaian Kinerja
	if (
		path.includes('kpi') ||
		path.includes('appraisal') ||
		path.includes('my-performance') ||
		title.includes('kpi') ||
		title.includes('kinerja') ||
		title.includes('sasaran')
	) {
		return {
			titleLabel: 'Indikator / Sasaran Kinerja (KPI)',
			titlePlaceholder: 'Misal: SLA Respons Tiket < 15 Menit / Delivery Tepat Waktu',
			detailLabel: 'Tolok Ukur & Rencana Capaian',
			detailPlaceholder: 'Misal: Capaian minimal 95% per kuartal berjalan dengan review bulanan',
			showAmount: true,
			amountLabel: 'Target Capaian / Bobot',
			amountPlaceholder: 'Misal: 95% atau Skor 4.5 atau 20 Tiket',
			attachmentLabel: 'Dokumen Pendukung / Bukti Capaian (opsional)',
			attachmentHint: 'Unggah berkas KPI, matriks penilaian, atau bukti capaian bila ada',
			guideNote: 'Tetapkan sasaran kinerja terukur beserta tolok ukur capaian untuk evaluasi performa.',
			statusOptions: ['Aktif', 'Tercapai', 'Sedang Berjalan', 'Perlu Evaluasi'],
			defaultStatus: 'Aktif',
			submitButtonText: 'Simpan KPI'
		};
	}

	// 3. Kasbon
	if (path.includes('kasbon') || title.includes('kasbon')) {
		return {
			titleLabel: 'Keperluan Pengajuan Kasbon',
			titlePlaceholder: 'Misal: Biaya darurat keluarga / pengobatan mendesak',
			detailLabel: 'Rencana Pelunasan & Keterangan',
			detailPlaceholder: 'Misal: Pemotongan gaji pada 1 periode berikutnya',
			showAmount: true,
			amountLabel: 'Nominal Kasbon (Rp)',
			amountPlaceholder: 'Contoh: Rp 1.500.000',
			attachmentLabel: 'Bukti Pendukung / Berkas Pengajuan (opsional)',
			attachmentHint: 'Lampirkan kuitansi atau dokumen pendukung bila ada',
			statusOptions: ['Menunggu tinjauan', 'Diproses', 'Disetujui', 'Lunas', 'Ditolak'],
			defaultStatus: 'Menunggu tinjauan',
			submitButtonText: 'Ajukan Kasbon'
		};
	}

	// 4. Reimbursement
	if (path.includes('reimbursement') || title.includes('reimbursement')) {
		return {
			titleLabel: 'Keperluan Klaim Biaya',
			titlePlaceholder: 'Misal: Transportasi & konsumsi kunjungan dinas',
			detailLabel: 'Rincian Pengeluaran',
			detailPlaceholder: 'Misal: Kunjungan klien PT Maju Bersama pada 24 Okt',
			showAmount: true,
			amountLabel: 'Total Nominal Klaim (Rp)',
			amountPlaceholder: 'Contoh: Rp 350.000',
			attachmentLabel: 'Lampiran Struk / Kuitansi Pembayaran',
			attachmentHint: 'Unggah bukti pembayaran fisik/digital yang valid',
			statusOptions: ['Menunggu tinjauan', 'Diproses', 'Disetujui', 'Ditolak'],
			defaultStatus: 'Menunggu tinjauan',
			submitButtonText: 'Ajukan Klaim'
		};
	}

	// 5. Tukar Shift
	if (path.includes('shift-swap') || title.includes('tukar shift')) {
		return {
			titleLabel: 'Shift & Tanggal yang Ingin Ditukar',
			titlePlaceholder: 'Misal: Shift Pagi (28 Okt) ditukar ke Shift Sore',
			detailLabel: 'Rekan Pengganti & Alasan',
			detailPlaceholder: 'Misal: Bertukar dengan Budi Santoso karena ada agenda mendadak',
			showAmount: true,
			amountLabel: 'Tanggal Shift Pengganti',
			amountPlaceholder: 'Contoh: 30 Okt 2026',
			attachmentLabel: 'Bukti Persetujuan Rekan (opsional)',
			statusOptions: ['Menunggu persetujuan', 'Disetujui', 'Ditolak'],
			defaultStatus: 'Menunggu persetujuan',
			submitButtonText: 'Ajukan Tukar Shift'
		};
	}

	// 6. WFH
	if (path.includes('wfh') || title.includes('wfh')) {
		return {
			titleLabel: 'Tanggal & Agenda Kerja WFH',
			titlePlaceholder: 'Misal: WFH 28-29 Okt · Fokus Penyelesaian Modul',
			detailLabel: 'Rencana Capaian & Komunikasi',
			detailPlaceholder: 'Misal: Menuntaskan refactor UI, siaga koordinasi tim via chat',
			showAmount: true,
			amountLabel: 'Lokasi Bekerja',
			amountPlaceholder: 'Misal: Rumah (Depok) / Coworking Space',
			attachmentLabel: 'Dokumen / Rencana Kerja (opsional)',
			statusOptions: ['Menunggu persetujuan', 'Disetujui', 'Selesai'],
			defaultStatus: 'Menunggu persetujuan',
			submitButtonText: 'Ajukan WFH'
		};
	}

	// 7. Aset & Inventaris
	if (path.includes('asset') || title.includes('aset') || title.includes('inventaris')) {
		return {
			titleLabel: 'Nama Aset / Perangkat',
			titlePlaceholder: 'Misal: Laptop MacBook Air M2 / Monitor Dell 27"',
			detailLabel: 'Kondisi & Catatan Penggunaan',
			detailPlaceholder: 'Misal: Kondisi baik, kelengkapan unit & charger orisinal',
			showAmount: true,
			amountLabel: 'Nomor Seri / Tag Aset',
			amountPlaceholder: 'Contoh: AST-2026-089',
			attachmentLabel: 'Foto Fisik Aset / Berkas Serah Terima',
			attachmentHint: 'Foto fisik perangkat atau bukti serah terima inventaris',
			statusOptions: ['Tersedia', 'Dipinjam', 'Perlu Pemeliharaan', 'Diarsipkan'],
			defaultStatus: 'Tersedia',
			submitButtonText: 'Simpan Aset'
		};
	}

	// 8. Pengumuman
	if (path.includes('announcement') || title.includes('pengumuman')) {
		return {
			titleLabel: 'Judul Pengumuman',
			titlePlaceholder: 'Misal: Pengumuman Hari Libur Nasional & Jam Operasional',
			detailLabel: 'Isi Pengumuman',
			detailPlaceholder: 'Tuliskan informasi lengkap yang ingin disampaikan kepada tim...',
			showAmount: true,
			amountLabel: 'Target Audiens / Divisi (opsional)',
			amountPlaceholder: 'Contoh: Seluruh Karyawan / Operasional',
			attachmentLabel: 'Lampiran Poster / Berkas (opsional)',
			statusOptions: ['Dipublikasikan', 'Draft', 'Diarsipkan'],
			defaultStatus: 'Dipublikasikan',
			submitButtonText: 'Terbitkan Pengumuman'
		};
	}

	// 9. Koreksi Absensi
	if (path.includes('attendance-correction') || title.includes('koreksi')) {
		return {
			titleLabel: 'Tanggal & Sesi yang Dikoreksi',
			titlePlaceholder: 'Misal: 25 Okt 2026 · Jam Masuk',
			detailLabel: 'Alasan Koreksi Kehadiran',
			detailPlaceholder: 'Misal: Terlewat tap-in karena langsung membantu bongkar muatan',
			showAmount: true,
			amountLabel: 'Jam Sebenarnya',
			amountPlaceholder: 'Contoh: 08.05 WIB',
			attachmentLabel: 'Bukti Pendukung (Foto Lapangan / Surat Tugas)',
			statusOptions: ['Menunggu persetujuan', 'Disetujui', 'Ditolak'],
			defaultStatus: 'Menunggu persetujuan',
			submitButtonText: 'Ajukan Koreksi'
		};
	}

	// 10. Barcode & Titik Kehadiran
	if (path.includes('barcode') || title.includes('barcode') || title.includes('qr')) {
		return {
			titleLabel: 'Nama Titik / Lokasi Barcode',
			titlePlaceholder: 'Misal: Titik Masuk Lobi Utama Lantai 1',
			detailLabel: 'Keterangan Penempatan & Radius',
			detailPlaceholder: 'Misal: Ditempel di dinding samping lift, radius 50m',
			showAmount: true,
			amountLabel: 'Radius / Kode Titik',
			amountPlaceholder: 'Contoh: 100m / QR-LOBBY-01',
			attachmentLabel: 'Foto Lokasi Penempatan (opsional)',
			statusOptions: ['Aktif', 'Nonaktif', 'Perlu Pemeliharaan'],
			defaultStatus: 'Aktif',
			submitButtonText: 'Buat Titik Barcode'
		};
	}

	// 11. Surat HR & Dokumen
	if (
		path.includes('document') ||
		path.includes('warning') ||
		path.includes('checklist') ||
		title.includes('surat') ||
		title.includes('dokumen')
	) {
		return {
			titleLabel: 'Jenis Dokumen / Surat',
			titlePlaceholder: 'Misal: Surat Keterangan Kerja (SKK) / Paklaring',
			detailLabel: 'Keperluan & Rincian Pengajuan',
			detailPlaceholder: 'Misal: Untuk administrasi pengajuan KPR bank',
			showAmount: true,
			amountLabel: 'Nomor Surat / Tenggat (opsional)',
			amountPlaceholder: 'Contoh: 31 Okt 2026 / SKK-2026-012',
			attachmentLabel: 'Lampiran Berkas Pendukung (opsional)',
			statusOptions: ['Menunggu tinjauan', 'Diproses', 'Selesai', 'Diarsipkan'],
			defaultStatus: 'Menunggu tinjauan',
			submitButtonText: 'Ajukan Dokumen'
		};
	}

	// 12. Payroll
	if (path.includes('payroll') || grp.includes('keuangan')) {
		return {
			titleLabel: 'Nama / Keterangan Transaksi',
			titlePlaceholder: 'Misal: Pembayaran Operasional Cabang / Penggajian',
			detailLabel: 'Rincian & Catatan',
			detailPlaceholder: 'Misal: Pembayaran vendor atau pengeluaran operasional',
			showAmount: true,
			amountLabel: 'Nominal (Rp)',
			amountPlaceholder: 'Contoh: Rp 2.500.000',
			attachmentLabel: 'Lampiran Bukti Transaksi (opsional)',
			statusOptions: ['Perlu ditinjau', 'Diproses', 'Disetujui', 'Selesai'],
			defaultStatus: 'Perlu ditinjau',
			submitButtonText: 'Simpan Transaksi'
		};
	}

	// 13. Default Fallback
	return {
		titleLabel: 'Nama atau Judul',
		titlePlaceholder: 'Masukkan nama atau judul catatan…',
		detailLabel: 'Rincian / Keterangan',
		detailPlaceholder: 'Tambahkan keterangan atau catatan…',
		showAmount: false,
		attachmentLabel: 'Lampiran Berkas (opsional)',
		attachmentHint: 'Unggah berkas pendukung bila diperlukan',
		statusOptions: ['Aktif', 'Menunggu tinjauan', 'Diproses', 'Disetujui', 'Selesai'],
		defaultStatus: 'Aktif',
		submitButtonText: 'Simpan'
	};
}
