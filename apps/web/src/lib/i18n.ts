import { getLocale } from './paraglide/runtime.js';

const english: Record<string, string> = {
	'Divisi karyawan': 'Employee division',
	"Tanggal pengajuan": "Request date",
	"Template aktif": "Active template",
	"Tulis isi surat untuk melihat pratinjau.": "Write the letter to see a preview.",
	"Judul dokumen": "Document title",
	"Menggunakan data contoh. Hasil surat mengikuti data karyawan yang mengajukan.": "Uses sample data. The generated letter uses the requesting employee’s details.",
	"Klik untuk menyisipkan. Data diisi dari profil karyawan saat pengajuan dibuat.": "Click to insert. Values come from the employee profile when the request is created.",
	"Sisipkan data otomatis": "Insert automatic fields",
	"Tulis isi surat, lalu sisipkan data otomatis di bawah.": "Write the letter, then insert automatic fields below.",
	"Contoh: Surat keterangan kerja": "Example: Employment certificate",
	"Edit template": "Edit template",
	"Template tersimpan": "Saved templates",
	"Template berhasil disimpan.": "Template saved successfully.",
	"Isi dokumen maksimal 20.000 karakter.": "Document content is limited to 20,000 characters.",
	"Staf operasional": "Operations staff",
	"Memperbarui lokasi…": "Updating location\u2026",
	"Sesi belum dapat diperpanjang. Coba lagi.": "Could not extend your session. Try again.",
	"Memperpanjang…": "Extending\u2026",
	"Masuk ulang": "Sign in again",
	"Simpan pekerjaan Anda, lalu masuk ulang.": "Save your work, then sign in again.",
	"Lanjutkan aktivitas agar tetap masuk.": "Continue your activity to stay signed in.",
	"Batas waktu sesi hampir habis": "Session time limit approaching",
	"Tidak ada aktivitas": "No recent activity",
	'Sesi Anda segera berakhir.': 'Your session will expire soon.',
	"Batas sesi": "Session limit",
	"Berakhir jika tidak aktif": "Idle expiry",
	"Aktivitas terakhir": "Last activity",
	"Sesi Anda telah berakhir. Silakan masuk kembali.": "Your session has expired. Please sign in again.",
	"Lanjutkan sesi": "Stay signed in",
	"Sesi segera berakhir karena tidak ada aktivitas.": "Your session will expire soon due to inactivity.",
	"Halaman belum dapat dimuat. Coba lagi atau kembali ke beranda.": "This page could not be loaded. Try again or return home.",
	"Terjadi kendala pada layanan": "Something went wrong",
	"Koneksi ke layanan sedang terganggu. Coba lagi setelah beberapa saat.": "The service cannot be reached. Please try again shortly.",
	"Layanan belum tersedia": "Service unavailable",
	"Silakan masuk kembali untuk melanjutkan.": "Please sign in again to continue.",
	"Sesi berakhir": "Session expired",
	"Akun Anda tidak memiliki izin untuk membuka halaman ini.": "Your account does not have permission to open this page.",
	"Akses dibatasi": "Access restricted",
	"Halaman yang Anda cari tidak tersedia. Periksa alamat atau kembali ke beranda.": "This page is unavailable. Check the address or return home.",
	"Halaman tidak ditemukan": "Page not found",
	'Audiens divisi':'Division audience',
	'Seluruh perusahaan':'Entire company',
	'Tayang mulai (opsional)':'Visible from (optional)',
	'Template checklist':'Checklist template',
	'Tanpa template':'No template',
	'Simpan sebagai template':'Save as template',
	'Riwayat serah terima':'Handover history',
	'Masih dipinjam':'Still assigned',
	'Undangan tukar shift':'Shift swap invitations',
	'Saya menyetujui pertukaran':'I consent to this swap',
	'Jam masuk koreksi':'Corrected check-in time',
	'Jam pulang koreksi':'Corrected check-out time',
	'Rekan sudah menyetujui':'Colleague has consented',
	'Menunggu persetujuan rekan':'Awaiting colleague consent',
	'Perusahaan aktif': 'Active company',
	'Hak akses mengikuti perusahaan yang dipilih.': 'Permissions follow the selected company.',
	'Pindah': 'Switch',
	'Gagal berpindah perusahaan.': 'Could not switch company.',
	'Konfigurasi layanan aktif dan kesehatan koneksi.': 'Active service configuration and connection health.',
	'Konfigurasi layanan dikelola pada environment deployment.': 'Service configuration is managed through the deployment environment.',
	'Pengiriman email': 'Email delivery',
	'Koneksi aktif': 'Active connections',
	'Catatan kehadiran dalam zona waktu perusahaan.': 'Attendance records in the company time zone.',
	'Menampilkan hingga 200 catatan terbaru untuk periode ini.': 'Showing up to 200 latest records for this period.',
	'Belum ada catatan kehadiran': 'No attendance records yet',
	'Setujui': 'Approve',
	'Divisi': 'Divisions',
	'Pendidikan': 'Education',
	'Hari libur': 'Holidays',
	'Divisi, jabatan, cuti & hari libur': 'Divisions, job titles, leave and holidays',
	'Permintaan & dokumen resmi': 'Requests and official documents',
	'Onboarding & offboarding': 'Onboarding & offboarding',
	'Permintaan dokumen diperbarui': 'Document request updated',
	'Lihat status dan dokumen di halaman surat HR.': 'View the status and document on the HR documents page.',
	'Slip gaji tersedia': 'Payslip available',
	'Slip gaji baru tersedia di halaman slip gaji.': 'A new payslip is available on the payslips page.',
	'Pengumuman baru tersedia di halaman pengumuman.': 'A new announcement is available on the announcements page.',
	'Checklist HR': 'HR checklists',
	'Langkah onboarding dan serah terima offboarding.': 'Onboarding steps and offboarding handovers.',
	'Buat checklist': 'Create checklist',
	'Langkah checklist (satu per baris)': 'Checklist steps (one per line)',
	'Maksimal 30 langkah; offboarding memerlukan pengembalian seluruh aset.': 'Up to 30 steps; offboarding requires all assigned assets to be returned.',
	'Daftar checklist HR': 'HR checklists',
	'Belum ada checklist': 'No checklists yet',
	'Berjalan': 'In progress',
	'langkah selesai': 'steps completed',
	'Selesaikan proses': 'Complete process',
	'Proses selesai dan terkunci.': 'Process completed and locked.',
	'Permintaan dan penerbitan dokumen resmi karyawan.': 'Requests and issuance of official employee documents.',
	'Dokumen yang dibutuhkan': 'Required document',
	'Keperluan': 'Purpose',
	'Daftar dokumen HR': 'HR documents',
	'Belum ada permintaan dokumen': 'No document requests yet',
	'Diterbitkan': 'Issued',
	'Unduh dokumen': 'Download document',
	'Dokumen resmi (PDF)': 'Official document (PDF)',
	'Berkas siap diterbitkan': 'Document ready to issue',
	'Terbitkan dokumen': 'Issue document',
	'Gunakan dokumen PDF.': 'Use a PDF document.',
	'Target terukur, bukti capaian, dan penilaian.': 'Measurable goals, progress evidence and reviews.',
	'Tambah target': 'Add goal',
	'Target baru': 'New goal',
	'Target': 'Target',
	'Satuan': 'Unit',
	'Batas waktu': 'Due date',
	'Daftar target KPI': 'Performance goals',
	'Belum ada target KPI': 'No performance goals yet',
	'Target dari penilai akan muncul di sini.': 'Goals assigned by your reviewer will appear here.',
	'Menunggu penilaian': 'Awaiting review',
	'Perlu revisi': 'Needs revision',
	'Capaian': 'Progress',
	'Bukti capaian': 'Progress evidence',
	'Catatan penilai': 'Reviewer notes',
	'Kirim untuk penilaian': 'Submit for review',
	'Minta revisi': 'Request revision',
	'Draft': 'Draft',
	'Simpan': 'Save',
	'Edit': 'Edit',
	'Tandai dibaca': 'Mark as read',
	'Aset saya': 'My assets',
	'Kondisi': 'Condition',
	'Tersedia': 'Available',
	'Periode': 'Period',
	'Nama': 'Name',
	'Tanggal': 'Date',
	'Belum ada data': 'No data yet',
	'Jatah cuti': 'Leave allowance',
	'Jenis cuti': 'Leave type',
	'Tahun': 'Year',
	'hari': 'days',
	'Batalkan pengajuan': 'Cancel request',
	'Pilih jenis cuti': 'Choose a leave type',
	'hari tersedia': 'days available',
	'Kuota dihitung per hari kalender; pengajuan menunggu ikut mencadangkan saldo.': 'Allowance uses calendar days; pending requests reserve days too.',
	'Atur pengecualian kuota tahunan per karyawan.': 'Override annual leave allowances for individual employees.',
	'Jatah cuti tersimpan.': 'Leave allowance saved.',
	'Atur jatah cuti': 'Set leave allowance',
	'Jumlah hari': 'Number of days',
	'Tanpa pengecualian, kuota mengikuti jatah tahunan pada jenis cuti.': 'Without an override, the leave type annual allowance applies.',
	'Kuota khusus karyawan': 'Employee allowance overrides',
	'Belum ada pengecualian kuota.': 'No allowance overrides yet.',
	'Master data organisasi': 'Organization reference data',
	'Struktur tim, kebijakan cuti, dan kalender perusahaan.': 'Team structure, leave policies and company calendar.',
	'Kategori master data': 'Reference data categories',
	'Jatah tahunan (hari)': 'Annual allowance (days)',
	'hari / tahun': 'days / year',
	'Edit data': 'Edit entry',
	'Periode penggajian dan rincian penghasilan.': 'Payroll periods and earnings details.',
	'Payroll tersimpan.': 'Payroll saved.',
	'Buat draft': 'Create draft',
	'Belum ada slip gaji': 'No payslips yet',
	'Slip tersedia setelah payroll dipublikasikan.': 'Payslips appear after payroll is published.',
	'Rincian gaji karyawan': 'Employee salary details',
	'Gaji kotor (Rp)': 'Gross pay (IDR)',
	'Potongan (Rp)': 'Deductions (IDR)',
	'Gaji bersih': 'Net pay',
	'Simpan rincian': 'Save details',
	'Rincian penggajian': 'Payroll details',
	'Tambahkan rincian gaji sebelum publikasi.': 'Add salary details before publishing.',
	'Gaji kotor': 'Gross pay',
	'Potongan': 'Deductions',
	'Publikasikan slip gaji': 'Publish payslips',
	'Tutup periode': 'Close period',
	'Periode dipublikasikan terkunci untuk menjaga riwayat penggajian.': 'Published periods are locked to preserve payroll history.',
	'Ditutup': 'Closed',
	'Perangkat kerja dan serah terima aset.': 'Work equipment and asset handovers.',
	'Perubahan tersimpan.': 'Changes saved.',
	'Edit aset': 'Edit asset',
	'Kode aset': 'Asset code',
	'Nama aset': 'Asset name',
	'Nomor seri': 'Serial number',
	'Baik': 'Good',
	'Rusak': 'Damaged',
	'Perawatan': 'Maintenance',
	'Tidak digunakan': 'Retired',
	'Cari aset…': 'Search assets…',
	'Cari aset': 'Search assets',
	'Daftar aset': 'Asset directory',
	'Belum ada aset': 'No assets yet',
	'Aset yang diserahkan akan tercatat di sini.': 'Assigned assets will appear here.',
	'Penerimaan dikonfirmasi': 'Receipt confirmed',
	'Menunggu konfirmasi penerimaan': 'Awaiting receipt confirmation',
	'Kelola aset': 'Manage asset',
	'Kondisi pengembalian': 'Return condition',
	'Catatan pengembalian': 'Return notes',
	'Konfirmasi pengembalian': 'Confirm return',
	'Serahkan kepada': 'Assign to',
	'Pilih karyawan': 'Choose an employee',
	'Serahkan aset': 'Assign asset',
	'Konfirmasi penerimaan': 'Confirm receipt',
	'Informasi terbaru untuk tim.': 'Latest updates for the team.',
	'Pengumuman tersimpan.': 'Announcement saved.',
	'Edit pengumuman': 'Edit announcement',
	'Isi pengumuman': 'Announcement content',
	'Dipublikasikan': 'Published',
	'Diarsipkan': 'Archived',
	'Daftar pengumuman': 'Announcements',
	'Cari pengumuman…': 'Search announcements…',
	'Cari pengumuman': 'Search announcements',
	'Belum ada pengumuman': 'No announcements yet',
	'Informasi terbaru dari perusahaan akan muncul di sini.': 'Company updates will appear here.',
	'Sudah dibaca': 'Read',
	'pembaca': 'readers',
	'Semua': 'All',
	'Utama': 'Main',
	'Akun': 'Account',
	'Terdaftar': 'Enrolled',
	'Profil biometrik wajah': 'Face biometric profile',
	'Profil biometrik berhasil didaftarkan.': 'Biometric profile enrolled successfully.',
	'Profil wajah tersimpan setelah seluruh tantangan gerakan selesai.': 'The face profile was saved after all movement challenges were completed.',
	'Descriptor wajah tersimpan': 'Face descriptor stored',
	'Profil biometrik terdaftar': 'Biometric profile enrolled',
	'Model': 'Model',
	'Admin': 'Admin',
	'Workspace': 'Workspace',
	'Layanan SDM': 'HR Services',
	'Layanan Mandiri': 'Self Service',
	'Operasional & Admin': 'Operations & Admin',
	'MODUL ADMIN': 'ADMIN MODULES',
	'Keuangan & Klaim': 'Finance & Claims',
	'Kehadiran & Shift': 'Attendance & Shifts',
	'Komunikasi & SDM': 'Communication & HR',
	'Operasional Inti': 'Core Operations',
	'SDM & Kehadiran': 'HR & Attendance',
	'Keuangan & Aset': 'Finance & Assets',
	'Organisasi & Audit': 'Organization & Audit',
	'Keamanan & DB': 'Security & DB',
	'Slip Gaji': 'Payslips',
	'Kasbon': 'Cash Advance',
	'Tukar Shift': 'Shift Swap',
	'Nominal (Rp)': 'Amount (IDR)',
	'Rekan tukar shift': 'Shift swap colleague',
	'Pilih rekan': 'Choose a colleague',
	'Dengan': 'With',
	'Lampiran': 'Attachment',
	'Izin WFH': 'WFH Request',
	'Biometrik': 'Biometrics',
	'Pengumuman': 'Announcements',
	'Surat HR': 'HR Letters',
	'Inventaris': 'Inventory',
	'Evaluasi KPI': 'KPI Review',
	'Offboarding': 'Offboarding',
	'Dashboard Admin': 'Admin Dashboard',
	'Karyawan': 'Employees',
	'Master Shift': 'Shift Master',
	'Penjadwalan': 'Scheduling',
	'Lokasi & QR': 'Locations & QR',
	'Log Presensi': 'Attendance Log',
	'Manajemen Lembur': 'Overtime Management',
	'Kuota Cuti': 'Leave Quotas',
	'Tukar Shift Tim': 'Team Shift Swaps',
	'Payroll & Slip': 'Payroll & Payslips',
	'Reimbursement': 'Reimbursement',
	'Persetujuan Kasbon': 'Cash Advance Approvals',
	'Aset & Inventaris': 'Assets & Inventory',
	'Siaran Pengumuman': 'Announcement Broadcasts',
	'Penilaian KPI': 'KPI Reviews',
	'Audit Trail': 'Audit Trail',
	'Storage & Sistem': 'Storage & System',
	'Monitor Database': 'Database Monitor',
	'Peran & Izin': 'Roles & Permissions',
	'Kotak Masuk Notifikasi': 'Notification Inbox',
	'Jadwal kerja': 'Work Schedule',
	'Riwayat kehadiran': 'Attendance History',
	'Notifikasi': 'Notifications',
	'Kembali ke Beranda': 'Back to Home',
	'Beranda': 'Home',
	'hadir': 'present',
	'lengkap': 'complete',
	'shift': 'shifts',
	'Izin': 'Leave',
	'baru': 'new',
	'Coba lagi': 'Try again',
	'Kalender kehadiran': 'Attendance calendar',
	'Bulan sebelumnya': 'Previous month',
	'Bulan berikutnya': 'Next month',
	'Hadir': 'Present',
	'Hari ini': 'Today',
	'Masuk': 'Check-in',
	'Pulang': 'Check-out',
	'Lengkap': 'Complete',
	'Memuat riwayat…': 'Loading history…',
	'Belum ada catatan kehadiran pada bulan ini.': 'No attendance records for this month.',
	'Daftar catatan bulan ini': 'Records for this month',
	'Jadwal mendatang': 'Upcoming schedule',
	'Memuat jadwal…': 'Loading schedule…',
	'Belum ada shift kerja mendatang yang terjadwal.': 'No upcoming work shifts are scheduled.',
	'Shift kerja': 'Work shift',
	'Lintas hari': 'Overnight',
	'Daftar notifikasi': 'Notification list',
	'Cari notifikasi': 'Search notifications',
	'Cari notifikasi…': 'Search notifications…',
	'Belum dibaca': 'Unread',
	'Baca semua': 'Mark all read',
	'Notif perangkat': 'Device notifications',
	'Memuat notifikasi…': 'Loading notifications…',
	'Tidak ada notifikasi yang cocok.': 'No matching notifications.',
	'Belum ada notifikasi baru.': 'No new notifications.',
	'Baru': 'New',
	'Dibaca': 'Mark read',
	'Menunggu persetujuan manager': 'Waiting for manager approval',
	'Menunggu persetujuan admin': 'Waiting for admin approval',
	'Pengajuan disetujui': 'Request approved',
	'Pengajuan ditolak': 'Request rejected',
	'Cuti': 'Leave',
	'Lembur': 'Overtime',
	'Koreksi absensi': 'Attendance correction',
	'Menunggu manager': 'Waiting for manager',
	'Menunggu admin': 'Waiting for admin',
	'Disetujui': 'Approved',
	'Ditolak': 'Rejected',
	'Dibatalkan': 'Cancelled',
	'menit': 'minutes',
	'jam': 'hours',
	'Data tidak dapat dimuat.': 'Data could not be loaded.',
	'Gagal membaca berkas.': 'Could not read the file.',
	'Gagal mengunggah lampiran.': 'Could not upload the attachment.',
	'Pengajuan berhasil dikirim.': 'Request submitted successfully.',
	'Pengajuan gagal dikirim.': 'Could not submit the request.',
	'Keputusan tersimpan.': 'Decision saved.',
	'Keputusan gagal disimpan.': 'Could not save the decision.',
	'Pengajuan & izin': 'Requests & leave',
	'Persetujuan tim': 'Team approvals',
	'Cuti, lembur, koreksi absensi, dan reimbursement.': 'Leave, overtime, attendance corrections, and reimbursements.',
	'Tinjau dan putuskan pengajuan anggota tim.': 'Review and decide team requests.',
	'Total': 'Total',
	'Menunggu': 'Pending',
	'Tambah pengajuan': 'Add request',
	'Pengajuan baru': 'New request',
	'Lampiran opsional': 'Optional attachment',
	'Jenis': 'Type',
	'Mulai': 'Start',
	'Selesai': 'End',
	'Durasi lembur (menit)': 'Overtime duration (minutes)',
	'Alasan': 'Reason',
	'Jelaskan alasan atau rincian biaya…': 'Explain the reason or expense details…',
	'Lampiran berkas / bukti (opsional)': 'File / evidence attachment (optional)',
	'Mengunggah berkas…': 'Uploading file…',
	'Terunggah': 'Uploaded',
	'Batal': 'Cancel',
	'Menyimpan…': 'Saving…',
	'Kirim pengajuan': 'Submit request',
	'Daftar persetujuan': 'Approval list',
	'Riwayat pengajuan': 'Request history',
	'Cari pengajuan': 'Search requests',
	'Cari alasan, jenis, atau nama…': 'Search reason, type, or name…',
	'Status': 'Status',
	'Semua status': 'All statuses',
	'Memuat pengajuan…': 'Loading requests…',
	'Tidak ada pengajuan menunggu': 'No pending requests',
	'Belum ada pengajuan': 'No requests yet',
	'Pengajuan yang membutuhkan keputusan Anda akan muncul di sini.': 'Requests requiring your decision will appear here.',
	'Ajukan cuti, lembur, koreksi absensi, atau reimbursement baru.': 'Submit a new leave, overtime, attendance correction, or reimbursement request.',
	'Buat Pengajuan Baru': 'Create New Request',
	'Tidak ada hasil': 'No results',
	'Coba kata kunci atau status lain.': 'Try another keyword or status.',
	'Durasi': 'Duration',
	'Ditinjau oleh': 'Reviewed by',
	'Catatan': 'Note',
	'Catatan keputusan': 'Decision note',
	'Tambahkan catatan persetujuan/penolakan…': 'Add an approval/rejection note…',
	'Setujui tahap': 'Approve stage',
	'Tolak': 'Reject',
	'Total tercatat': 'Total records',
	'Aktif / selesai': 'Active / complete',
	'Perlu tindak lanjut': 'Needs follow-up',
	'Aktif': 'Active',
	'Gagal memuat data modul.': 'Could not load module data.',
	'Gagal menyimpan data.': 'Could not save data.',
	'Gagal memperbarui status.': 'Could not update status.',
	'Gagal menghapus data.': 'Could not delete data.',
	'Memuat…': 'Loading…',
	'data': 'records',
	'Ringkasan': 'Summary',
	'Daftar': 'List',
	'Ekspor CSV': 'Export CSV',
	'Cari data': 'Search data',
	'Ketik untuk mencari…': 'Type to search…',
	'Data belum tersedia': 'Data is not available',
	'Belum ada data pada tampilan ini': 'No data in this view',
	'Ubah kata pencarian atau filter status.': 'Change the search term or status filter.',
	'Tambahkan data pertama untuk modul ini.': 'Add the first record for this module.',
	'Tindakan': 'Actions',
	'Ubah status': 'Change status',
	'Hapus': 'Delete',
	'RINGKASAN MODUL': 'MODULE SUMMARY',
	'Tambah data': 'Add data',
	'Judul': 'Title',
	'Detail': 'Details',
	'Nilai/Waktu': 'Value/Time',
	'Pencatatan dan pengelolaan data untuk modul ini.': 'Record and manage data for this module.'
	,'Presensi & ringkasan hari ini': 'Attendance & today’s summary'
	,'Kalender shift kerja': 'Work shift calendar'
	,'Check-in & check-out QR lokasi': 'Location QR check-in & check-out'
	,'Izin, cuti, lembur & klaim': 'Requests, leave, overtime & claims'
	,'Catatan kehadiran bulanan': 'Monthly attendance records'
	,'Rincian pendapatan bulanan': 'Monthly income details'
	,'Pengajuan pinjaman karyawan': 'Employee cash advance request'
	,'Delegasi jadwal dengan rekan': 'Swap schedules with colleagues'
	,'Lapor kerja jarak jauh': 'Report remote work'
	,'Verifikasi profil wajah': 'Face profile verification'
	,'Papan informasi perusahaan': 'Company information board'
	,'Surat keterangan & dokumen': 'Letters & documents'
	,'Pinjaman perangkat kerja': 'Work equipment loans'
	,'Target & capaian kinerja': 'Performance targets & results'
	,'Serah terima & clearance': 'Handover & clearance'
	,'Ringkasan kehadiran & antrean': 'Attendance & queue summary'
	,'Direktori & profil anggota': 'Member directory & profiles'
	,'Pengaturan jam kerja': 'Work hour settings'
	,'Penugasan shift bulanan': 'Monthly shift assignments'
	,'Titik geofence & kode QR': 'Geofence points & QR codes'
	,'Rekap kehadiran seluruh tim': 'Team attendance recap'
	,'Persetujuan jam lembur': 'Overtime hour approvals'
	,'Jatah cuti tahunan': 'Annual leave allowance'
	,'Persetujuan tukar jadwal': 'Schedule swap approvals'
	,'Periode gaji & potongan': 'Payroll periods & deductions'
	,'Verifikasi klaim biaya': 'Expense claim verification'
	,'Pencairan & cicilan kasbon': 'Cash advance disbursement & installments'
	,'Daftar perangkat kantor': 'Office equipment list'
	,'Publikasi info internal': 'Internal information publishing'
	,'Evaluasi performa tim': 'Team performance review'
	,'Jejak aktivitas sistem': 'System activity trail'
	,'Konfigurasi berkas & objek': 'File & object configuration'
	,'Kesehatan koneksi & pool': 'Connection & pool health'
	,'Matriks hak akses': 'Access rights matrix'
	,'Dokumentasi endpoint & koleksi': 'Endpoint docs & collections'
	,'Navigasi utama workspace': 'Main workspace navigation'
	,'Pemberitahuan & pembaruan status': 'Notifications & status updates'
	,'Keluar': 'Sign out'
	,'Keluar dari akun': 'Sign out of account'
	,'Lewati ke konten': 'Skip to content'
	,'Masuk kembali': 'Sign in again'
	,'Memuat papan kerja…': 'Loading workspace…'
	,'Akses terbatas': 'Restricted access'
	,'Peran Anda tidak memiliki akses ke halaman ini.': 'Your role does not have access to this page.'
	,'Kembali ke beranda': 'Back to home'
	,'Tutup': 'Close'
	,'Tutup form': 'Close form'
	,'Pengajuan': 'Request'
	,'Ringkasan pengajuan': 'Request summary'
	,'Durasi (menit)': 'Duration (minutes)'
	,'kehadiran tercatat': 'attendance recorded'
	,'tidak ada catatan kehadiran': 'no attendance record'
	,'Gagal memuat data.': 'Could not load data.'
	,'Gagal menyimpan. Coba tandai dibaca lagi.': 'Could not save. Try marking it as read again.'
	,'Tercatat': 'Recorded'
	,'Tersimpan di database': 'Stored in database'
	,'Object storage': 'Object storage'
	,'Ditampilkan': 'Displayed'
	,'Tutup menu': 'Close menu'
	,'Tutup notifikasi': 'Close notification'
	,'Buka': 'Open'
	,'Nama / data utama': 'Name / primary data'
	,'Keterangan': 'Description'
	,'Nilai / status': 'Value / status'
	,'Nilai / target (opsional)': 'Value / target (optional)'
	,'Contoh: Rp 1.500.000': 'Example: Rp 1,500,000'
	,'Terunggah:': 'Uploaded:'
	,'Berkas pendukung (opsional)': 'Supporting file (optional)'
	,'Daftar ·': 'List ·'
	,'Gagal mengunggah berkas.': 'Could not upload the file.'
	,'Status absensi tidak dapat dimuat.': 'Attendance status could not be loaded.'
	,'Izin lokasi ditolak. Aktifkan izin lokasi untuk absensi.': 'Location permission was denied. Enable location access for attendance.'
	,'Lokasi belum ditemukan. Periksa GPS dan coba lagi.': 'Location not found. Check GPS and try again.'
	,'Pencarian lokasi melewati batas waktu. Coba lagi.': 'Location lookup timed out. Try again.'
	,'Perangkat ini tidak menyediakan GPS.': 'This device does not provide GPS.'
	,'Izin kamera ditolak. Izinkan kamera di browser lalu coba lagi.': 'Camera permission was denied. Allow camera access in the browser and try again.'
	,'Kamera tidak ditemukan pada perangkat ini.': 'No camera was found on this device.'
	,'Kamera belum aktif. Periksa izin kamera lalu coba lagi.': 'Camera is not active. Check camera permission and try again.'
	,'QR ini bukan kode lokasi absensi.': 'This QR is not an attendance location code.'
	,'Lokasi tidak tersedia. Aktifkan GPS lalu coba lagi.': 'Location is unavailable. Enable GPS and try again.'
	,'Check-out tercatat.': 'Check-out recorded.'
	,'Check-in tercatat.': 'Check-in recorded.'
	,'Absensi tidak dapat disimpan.': 'Attendance could not be saved.'
	,'Token QR harus berisi 64 karakter heksadesimal.': 'The QR token must contain 64 hexadecimal characters.'
	,'Memuat absensi…': 'Loading attendance…'
	,'Absensi selesai': 'Attendance complete'
	,'Check-in dan check-out hari ini sudah tercatat.': 'Today’s check-in and check-out have been recorded.'
	,'Pemindai QR': 'QR scanner'
	,'QR Absen': 'Attendance QR'
	,'Jadwal belum ditentukan': 'Schedule has not been assigned'
	,'Beralih kamera': 'Switch camera'
	,'Beralih': 'Switch'
	,'Kamera sedang memindai QR': 'Camera is scanning a QR code'
	,'Ketuk untuk membuka kamera': 'Tap to open the camera'
	,'Menghubungkan kamera…': 'Connecting camera…'
	,'Menyiapkan kamera…': 'Preparing camera…'
	,'Arahkan QR ke bingkai': 'Align the QR code within the frame'
	,'QR terbaca': 'QR code detected'
	,'Kamera belum aktif': 'Camera is not active'
	,'Konfirmasi check-out': 'Confirm check-out'
	,'Konfirmasi check-in': 'Confirm check-in'
	,'Lokasi': 'Location'
	,'siap diverifikasi': 'ready to verify'
	,'belum tersedia': 'not available'
	,'Absen pulang': 'Check out'
	,'Absen masuk': 'Check in'
	,'Scan lagi': 'Scan again'
	,'Masukkan token manual': 'Enter token manually'
	,'Token QR': 'QR token'
	,'Lanjut': 'Continue'
	,'Lokasi Saat Ini': 'Current Location'
	,'Perbarui lokasi': 'Refresh location'
	,'Tutup peta': 'Close map'
	,'Lihat peta': 'View map'
	,'Meningkatkan akurasi GPS… terbaik': 'Improving GPS accuracy… best'
	,'Akurasi': 'Accuracy'
	,'diperbarui pukul': 'updated at'
	,'Mencari lokasi…': 'Finding location…'
	,'Lokasi belum tersedia.': 'Location is not available.'
	,'Peta lokasi saat ini': 'Current location map'
	,'Pendaftaran Wajah': 'Face Enrollment'
	,'Hadap Depan': 'Face Forward'
	,'Posisikan wajah lurus di dalam oval': 'Position your face straight within the oval'
	,'Kedip Mata': 'Blink'
	,'Kedipkan kedua mata Anda secara wajar': 'Blink both eyes naturally'
	,'Tengok Kiri': 'Turn Left'
	,'Tengok kepala perlahan ke arah kiri ◀': 'Slowly turn your head to the left ◀'
	,'Tengok Kanan': 'Turn Right'
	,'Tengok kepala perlahan ke arah kanan ▶': 'Slowly turn your head to the right ▶'
	,'Tahan Posisi': 'Hold Position'
	,'Kembali hadap depan dan tahan 2 detik': 'Face forward again and hold for 2 seconds'
	,'Tahap Liveness': 'Liveness Stage'
	,'Verifikasi gerakan': 'Movement verification'
	,'dari': 'of'
	,'Wajah terkunci': 'Face locked'
	,'Cari wajah': 'Find face'
	,'Balik kamera depan/belakang': 'Switch front/rear camera'
	,'Kedipkan Mata': 'Blink Your Eyes'
	,'Arahkan Wajah': 'Align Your Face'
	,'Instruksi saat ini': 'Current instruction'
	,'Batalkan verifikasi': 'Cancel verification'
	,'Snapshot Wajah': 'Face snapshot'
	,'Terverifikasi': 'Verified'
	,'Pendaftaran Selesai': 'Enrollment Complete'
	,'Wajah Anda berhasil diverifikasi dan siap digunakan untuk validasi presensi.': 'Your face has been verified and is ready for attendance validation.'
	,'Wajah Terverifikasi Jelas': 'Face Clearly Verified'
	,'Gerakan Refleks Alami Valid': 'Natural Reflex Movement Valid'
	,'Siap Digunakan untuk Presensi': 'Ready for Attendance'
	,'Foto Ulang': 'Retake Photo'
	,'Simpan Data Wajah': 'Save Face Data'
	,'Foto Profil Wajah': 'Face Profile Photo'
	,'Wajah aktif untuk presensi': 'Face active for attendance'
	,'Wajah Aktif untuk Presensi': 'Face Active for Attendance'
	,'Tersimpan': 'Saved'
	,'Hapus Data Wajah': 'Delete Face Data'
	,'Pindai Ulang': 'Scan Again'
	,'Pendaftaran Wajah Presensi': 'Attendance Face Enrollment'
	,'Daftarkan wajah Anda untuk verifikasi identitas kehadiran yang cepat, praktis, dan akurat saat melakukan scan presensi.': 'Enroll your face for fast, practical, and accurate identity verification when recording attendance.'
	,'Petunjuk Pengambilan:': 'Capture Guidelines:'
	,'Posisikan Wajah Tegak': 'Keep Your Face Upright'
	,'Pegang ponsel sejajar mata dan posisikan wajah di dalam bingkai oval.': 'Hold the phone at eye level and position your face within the oval frame.'
	,'Pencahayaan yang Cukup': 'Use Adequate Lighting'
	,'Pastikan ruangan terang dan hindari sorotan lampu dari belakang kepala.': 'Ensure the room is bright and avoid backlighting behind your head.'
	,'Ikuti Arahan Gerakan': 'Follow the Movement Instructions'
	,'Lakukan kedipan mata dan tengok santai sesuai panduan di layar.': 'Blink and turn your head naturally as instructed on screen.'
	,'Mulai Pendaftaran Wajah': 'Start Face Enrollment'
	,'Posisikan wajah Anda di dalam bingkai oval.': 'Position your face within the oval frame.'
	,'Menyiapkan pemindai wajah…': 'Preparing face scanner…'
	,'Mengaktifkan sensor kamera…': 'Activating camera sensor…'
	,'Perangkat hanya memiliki satu kamera aktif.': 'The device only has one active camera.'
	,'Menyiapkan pemindai wajah… Mohon tunggu sebentar.': 'Preparing face scanner… Please wait.'
	,'Wajah tidak terdeteksi. Posisikan wajah Anda di dalam oval.': 'Face not detected. Position your face within the oval.'
	,'Dekatkan wajah Anda ke kamera.': 'Move your face closer to the camera.'
	,'Posisikan wajah Anda tepat di tengah oval.': 'Position your face in the center of the oval.'
	,'Verifikasi selesai. Mengambil foto…': 'Verification complete. Capturing photo…'
	,'Wajah tidak terdeteksi. Posisikan wajah di dalam oval.': 'Face not detected. Position your face within the oval.'
	,'Buka kedua mata dan lihat ke kamera.': 'Open both eyes and look at the camera.'
	,'Buka kembali kedua mata.': 'Open both eyes again.'
	,'Buka mata dan lihat ke kamera.': 'Open your eyes and look at the camera.'
	,'Hadapkan wajah lurus ke kamera.': 'Face straight toward the camera.'
	,'Kedipkan kedua mata sampai tertutup.': 'Blink until both eyes close.'
	,'Kembali hadap depan dan tahan posisi.': 'Face forward again and hold your position.'
	,'Kembali hadap lurus ke kamera.': 'Face straight toward the camera again.'
	,'Lihat kamera dengan kedua mata terbuka.': 'Look at the camera with both eyes open.'
	,'Lihat kamera, lalu kedipkan kedua mata sekali.': 'Look at the camera, then blink both eyes once.'
	,'Posisikan wajah tegak dan buka mata secara natural.': 'Keep your face upright and open your eyes naturally.'
	,'Sekarang kedipkan kedua mata.': 'Now blink both eyes.'
	,'Sekarang tengokkan kepala ke kanan.': 'Now turn your head to the right.'
	,'Tahan posisi tanpa bergerak.': 'Hold still.'
	,'Tahan sebentar di posisi kanan.': 'Hold briefly in the right position.'
	,'Tahan sebentar di posisi kiri.': 'Hold briefly in the left position.'
	,'Tahan wajah lurus untuk kalibrasi.': 'Hold your face straight for calibration.'
	,'Tengokkan kepala perlahan ke kanan.': 'Slowly turn your head to the right.'
	,'Tengokkan kepala perlahan ke kiri.': 'Slowly turn your head to the left.'
	,'Verifikasi gerakan selesai.': 'Movement verification complete.'
	,'Memuat data pendaftaran wajah…': 'Loading face enrollment data…'
	,'Cari koreksi presensi…': 'Search attendance corrections…'
	,'Cari jadwal shift kerja…': 'Search work shifts…'
	,'Cari slip gaji bulanan…': 'Search monthly payslips…'
	,'Cari pengajuan izin…': 'Search leave requests…'
	,'Cari tukar shift rekan…': 'Search colleague shift swaps…'
	,'Cari pengajuan izin WFH…': 'Search WFH requests…'
	,'Cari riwayat kehadiran…': 'Search attendance history…'
	,'Cari pengajuan cuti…': 'Search leave requests…'
	,'Cari kasbon karyawan…': 'Search employee cash advances…'
	,'Cari kuota cuti & izin…': 'Search leave quotas & requests…'
	,'Cari surat keterangan HR…': 'Search HR letters…'
	,'Cari jadwal kerja…': 'Search work schedules…'
	,'Cari pengumuman kantor…': 'Search office announcements…'
	,'Cari persetujuan tim…': 'Search team approvals…'
	,'Cari profil & keamanan…': 'Search profile & security…'
	,'Cari riwayat presensi…': 'Search attendance history…'
	,'Cari ubah kata sandi…': 'Search password settings…'
	,'Cari sesi perangkat aktif…': 'Search active device sessions…'
	,'Cari biometrik wajah…': 'Search face biometrics…'
	,'Cari slip gaji & THP…': 'Search payslips & take-home pay…'
	,'Cari direktori karyawan…': 'Search employee directory…'
	,'Cari lokasi & kode QR…': 'Search locations & QR codes…'
	,'Cari penjadwalan shift…': 'Search shift scheduling…'
	,'Cari payroll & audit log…': 'Search payroll & audit logs…'
	,'Cari scan QR presensi…': 'Search attendance QR scan…'
	,'Cari slip gaji & kasbon…': 'Search payslips & cash advances…'
	,'Cari pengajuan cuti & WFH…': 'Search leave & WFH requests…'
	,'Cari tukar shift kerja…': 'Search work shift swaps…'
	,'Cari inventaris & surat HR…': 'Search inventory & HR letters…'
	,'Cari fitur…': 'Search features…'
	,'Jumlah notifikasi tidak dapat dimuat.': 'Notification count could not be loaded.'
	,'Sesi berakhir. Silakan masuk kembali.': 'Your session has expired. Please sign in again.'
	,'Papan kerja belum dapat dimuat. Periksa koneksi lalu coba lagi.': 'The workspace could not be loaded. Check your connection and try again.'
	,'Gagal keluar. Coba lagi.': 'Could not sign out. Try again.'
	,'Sesi tidak dapat diverifikasi. Muat ulang atau masuk kembali.': 'The session could not be verified. Reload or sign in again.'
	,'Tampilkan menu': 'Show menu'
	,'Sembunyikan menu': 'Hide menu'
	,'Cari fitur atau halaman': 'Search features or pages'
	,'Cari menu atau fitur…': 'Search menus or features…'
	,'Tidak ditemukan hasil untuk': 'No results found for'
	,'Tutup menu profil': 'Close profile menu'
	,'Menu profil dan layanan': 'Profile and services menu'
	,'Dashboard admin': 'Admin Dashboard'
	,'Employee': 'Employees'
	,'QR & lokasi absensi': 'Attendance QR & locations'
	,'Persetujuan': 'Approvals'
	,'Hak akses': 'Access control'
	,'Tim': 'Team'
	,'Keamanan': 'Security'
	,'Rekap kehadiran': 'Attendance recap'
	,'Kehadiran': 'Attendance'
	,'Hari libur kerja': 'Work holidays'
	,'Penggajian & slip': 'Payroll & payslips'
	,'Persetujuan kasbon': 'Cash advance approvals'
	,'Inventaris aset': 'Asset inventory'
	,'Log audit sistem': 'System audit log'
	,'Keuangan': 'Finance'
	,'Operasional': 'Operations'
	,'Karyawan aktif': 'Active employees'
	,'Hadir hari ini': 'Present today'
	,'Master shift': 'Shift master'
	,'Menunggu approval': 'Pending approval'
	,'Dashboard admin tidak dapat dimuat.': 'The admin dashboard could not be loaded.'
	,'Kehadiran, pengajuan, dan operasional tim.': 'Team attendance, requests, and operations.'
	,'Layar QR': 'QR display'
	,'Ringkasan admin': 'Admin overview'
	,'NAVIGASI OPERASIONAL': 'OPERATIONAL NAVIGATION'
	,'Modul SDM, kehadiran & keuangan': 'HR, attendance & finance modules'
	,'modul': 'modules'
	,'Belum ada akses operasional. Hubungi owner perusahaan.': 'No operational access yet. Contact the company owner.'
	,'Menghubungkan…': 'Connecting…'
	,'Semua siap': 'All systems ready'
	,'aktif': 'active'
	,'koneksi': 'connections'
	,'Ukuran DB & Pool': 'DB Size & Pool'
	,'berkas': 'files'
	,'Buka detail': 'Open details'
	,'Memuat telemetri server.': 'Loading server telemetry.'
	,'Data tim dan koneksi database berjalan normal.': 'Team data and database connections are operating normally.'
	,'Kehadiran terbaru': 'Recent attendance'
	,'AKTIVITAS PRESENSI': 'ATTENDANCE ACTIVITY'
	,'Nonaktif': 'Inactive'
	,'Resign': 'Resigned'
	,'Gagal memuat employee.': 'Could not load employees.'
	,'Employee tersimpan dan dapat login menggunakan kata sandi yang ditetapkan.': 'Employee saved and can sign in using the assigned password.'
	,'Gagal menyimpan employee.': 'Could not save employee.'
	,'TIM & AKSES': 'TEAM & ACCESS'
	,'Manajemen karyawan': 'Employee Management'
	,'Kelola akun kerja, peran, dan hubungan atasan langsung dalam perusahaan.': 'Manage work accounts, roles, and direct reporting relationships.'
	,'Tambah karyawan': 'Add employee'
	,'Direktori karyawan': 'Employee directory'
	,'Daftar anggota yang dapat Anda akses': 'Members you can access'
	,'hasil': 'results'
	,'Total hasil': 'Total results'
	,'Peran di halaman': 'Roles on page'
	,'Filter aktif': 'Active filters'
	,'AKUN BARU': 'NEW ACCOUNT'
	,'Buat akun dengan identitas kerja dan akses awal yang tepat.': 'Create an account with the correct work identity and initial access.'
	,'Nama lengkap': 'Full name'
	,'Kode karyawan': 'Employee code'
	,'Kata sandi awal': 'Initial password'
	,'Atasan': 'Manager'
	,'Belum ditetapkan': 'Not assigned'
	,'Buat akun karyawan': 'Create employee account'
	,'Kata sandi awal harus memiliki minimal 12 karakter.': 'The initial password must be at least 12 characters.'
	,'ANGGOTA PERUSAHAAN': 'COMPANY MEMBERS'
	,'Cari karyawan': 'Search employees'
	,'Nama, email, atau kode…': 'Name, email, or code…'
	,'Peran': 'Role'
	,'Semua peran': 'All roles'
	,'Status kerja': 'Employment status'
	,'Cari': 'Search'
	,'Memuat daftar karyawan…': 'Loading employee directory…'
	,'Belum ada karyawan': 'No employees yet'
	,'Ubah kata kunci atau filter untuk melihat anggota lain.': 'Change the keyword or filter to view other members.'
	,'Tambahkan karyawan pertama melalui formulir.': 'Add the first employee using the form.'
	,'Daftar anggota perusahaan akan muncul di sini.': 'Company members will appear here.'
	,'Coba nama, email, atau kode lain.': 'Try another name, email, or code.'
	,'Identitas & kontak': 'Identity & contact'
	,'Tanpa kode karyawan': 'No employee code'
	,'Halaman direktori': 'Directory pages'
	,'Sebelumnya': 'Previous'
	,'Halaman': 'Page'
	,'Berikutnya': 'Next'
	,'Buka dashboard admin': 'Open admin dashboard'
	,'Lihat employee': 'View employees'
	,'Tambah & edit employee': 'Add & edit employees'
	,'Ubah status kerja employee': 'Change employee status'
	,'Lihat & buat shift': 'View & create shifts'
	,'Kelola QR lokasi absensi': 'Manage attendance location QR'
	,'Tetapkan jadwal': 'Assign schedules'
	,'Lihat inbox persetujuan': 'View approval inbox'
	,'Putuskan tahap manager': 'Decide manager stage'
	,'Putuskan tahap admin': 'Decide admin stage'
	,'Jangkau pengajuan seluruh perusahaan': 'Access company-wide requests'
	,'Kelola hak akses (owner)': 'Manage access control (owner)'
	,'Role disimpan': 'Role saved'
	,'Akses anggota diubah': 'Member access changed'
	,'Role dihapus': 'Role deleted'
	,'Tidak ada konfigurasi': 'No configuration'
	,'Tidak ada': 'None'
	,'Role custom': 'Custom role'
	,'Role bawaan': 'Default role'
	,'Izinkan': 'Allow'
	,'Gagal memuat akses.': 'Could not load access settings.'
	,'Role tersimpan. Berlaku pada permintaan berikutnya.': 'Role saved. It applies to subsequent requests.'
	,'Gagal menyimpan role.': 'Could not save role.'
	,'Akses anggota tersimpan. Sesi aktif mengikuti perubahan ini.': 'Member access saved. Active sessions follow this change.'
	,'Gagal menyimpan akses.': 'Could not save access.'
	,'Role dihapus. Perubahan tercatat di audit.': 'Role deleted. The change was recorded in the audit log.'
	,'Gagal menghapus role.': 'Could not delete role.'
	,'KEAMANAN': 'SECURITY'
	,'Role perusahaan dan pengecualian anggota.': 'Company roles and member exceptions.'
	,'Muat ulang': 'Reload'
	,'Memuat hak akses…': 'Loading access control…'
	,'Role perusahaan': 'Company roles'
	,'Buat role baru': 'Create new role'
	,'Nama role': 'Role name'
	,'Permission role': 'Role permissions'
	,'Simpan role': 'Save role'
	,'Hapus role': 'Delete role'
	,'Role yang masih dipakai anggota tidak bisa dihapus.': 'A role still assigned to members cannot be deleted.'
	,'Ya, hapus role': 'Yes, delete role'
	,'Role custom menggantikan permission bawaan. Role kosong berarti tanpa akses admin.': 'A custom role replaces default permissions. An empty role means no admin access.'
	,'Akses anggota': 'Member access'
	,'Anggota': 'Member'
	,'Pilih anggota': 'Select member'
	,'Akses owner dilindungi dan tidak dapat diubah di sini.': 'Owner access is protected and cannot be changed here.'
	,'Role akses': 'Access role'
	,'Bawaan': 'Default'
	,'Pengecualian anggota': 'Member exceptions'
	,'Ikuti role': 'Follow role'
	,'Simpan akses': 'Save access'
	,'Tolak mengalahkan izin role. Pilih role bawaan dan “Ikuti role” untuk mengembalikan akses awal.': 'Deny overrides role permissions. Select the default role and “Follow role” to restore initial access.'
	,'Akses tersimpan': 'Saved access'
	,'Pilih anggota untuk meninjau akses efektifnya.': 'Select a member to review their effective access.'
	,'Permission hanya mencakup modul rewrite yang sudah terhubung. Persetujuan tetap melarang self-approval; tanpa jangkauan perusahaan, hanya bawahan langsung.': 'Permissions only cover connected rewrite modules. Approvals still prohibit self-approval; without company scope, only direct reports are included.'
	,'Riwayat perubahan akses': 'Access change history'
	,'Riwayat perubahan': 'Change history'
	,'terbaru': 'latest'
	,'Sebelum': 'Before'
	,'Sesudah': 'After'
	,'Belum ada perubahan akses.': 'No access changes yet.'
	,'Total shift': 'Total shifts'
	,'Shift harian': 'Day shifts'
	,'Shift lintas hari': 'Overnight shifts'
	,'Durasi tidak tersedia': 'Duration unavailable'
	,'CMS tidak dapat dimuat.': 'CMS could not be loaded.'
	,'Shift disimpan.': 'Shift saved.'
	,'Shift tidak dapat disimpan.': 'Shift could not be saved.'
	,'Jadwal disimpan.': 'Schedule saved.'
	,'Jadwal tidak dapat disimpan.': 'Schedule could not be saved.'
	,'Shift & jadwal': 'Shifts & schedules'
	,'Atur pola jam kerja dan tetapkan shift untuk karyawan.': 'Configure work patterns and assign employee shifts.'
	,'Kalender bulanan': 'Monthly calendar'
	,'Tambah shift': 'Add shift'
	,'Memuat data shift…': 'Loading shift data…'
	,'Direktori shift': 'Shift directory'
	,'Pola jam kerja perusahaan': 'Company work patterns'
	,'POLA KERJA': 'WORK PATTERNS'
	,'Daftar shift': 'Shift list'
	,'Cari shift': 'Search shifts'
	,'Cari nama shift…': 'Search shift name…'
	,'Jenis shift': 'Shift type'
	,'Semua shift': 'All shifts'
	,'Daftar shift belum tersedia': 'Shift list is unavailable'
	,'Coba muat ulang untuk mengambil data terbaru.': 'Reload to retrieve the latest data.'
	,'Belum ada shift': 'No shifts yet'
	,'Buat pola jam kerja terlebih dahulu agar dapat digunakan pada jadwal.': 'Create a work pattern before using it in schedules.'
	,'Shift tidak ditemukan': 'Shift not found'
	,'Ubah pencarian atau pilih jenis shift lain.': 'Change the search or choose another shift type.'
	,'Nama shift': 'Shift name'
	,'Rentang waktu': 'Time range'
	,'Berakhir pada hari berikutnya': 'Ends the next day'
	,'Dalam hari yang sama': 'Within the same day'
	,'Harian': 'Daytime'
	,'Hari berikutnya': 'Next day'
	,'Hari yang sama': 'Same day'
	,'POLA BARU': 'NEW PATTERN'
	,'Buat shift': 'Create shift'
	,'Tentukan nama dan rentang jam yang akan digunakan saat menyusun jadwal.': 'Set the name and time range used when building schedules.'
	,'Contoh: Shift pagi': 'Example: Morning shift'
	,'Shift berakhir pada hari berikutnya': 'Shift ends the next day'
	,'Simpan shift': 'Save shift'
	,'PRATINJAU': 'PREVIEW'
	,'hari berikutnya': 'next day'
	,'PENEMPATAN SHIFT': 'SHIFT ASSIGNMENT'
	,'Tetapkan jadwal karyawan': 'Assign employee schedule'
	,'Pilih karyawan, pola shift, dan tanggal kerja untuk membuat penetapan.': 'Select an employee, shift pattern, and work date to create an assignment.'
	,'Tanggal kerja': 'Work date'
	,'Buat shift terlebih dahulu untuk mulai menetapkan jadwal.': 'Create a shift before assigning schedules.'
	,'Belum ada karyawan yang tersedia untuk dijadwalkan.': 'No employees are available for scheduling.'
	,'AKSES TERBATAS': 'RESTRICTED ACCESS'
	,'Pengelolaan shift tidak tersedia': 'Shift management is unavailable'
	,'Anda belum memiliki izin untuk melihat atau mengelola pola shift perusahaan.': 'You do not have permission to view or manage company shift patterns.'
	,'Kembali ke dashboard admin': 'Back to admin dashboard'
	,'Data penjadwalan tidak dapat dimuat.': 'Scheduling data could not be loaded.'
	,'Jadwal bulanan tidak dapat dimuat.': 'Monthly schedule could not be loaded.'
	,'Perubahan jadwal gagal disimpan.': 'Schedule changes could not be saved.'
	,'Penjadwalan tim': 'Team Scheduling'
	,'Shift & pola kerja': 'Shifts & work patterns'
	,'Atur shift kerja dan hari tanpa penugasan karyawan dalam kalender bulanan.': 'Manage work shifts and unassigned days in a monthly calendar.'
	,'Kelola pola shift': 'Manage shift patterns'
	,'Memuat kalender penjadwalan…': 'Loading scheduling calendar…'
	,'Belum ada karyawan aktif': 'No active employees'
	,'Tambahkan karyawan terlebih dahulu sebelum menyusun jadwal bulanan.': 'Add employees before creating a monthly schedule.'
	,'Buka direktori karyawan': 'Open employee directory'
	,'Pilihan kalender': 'Calendar options'
	,'Bulan': 'Month'
	,'Navigasi bulan': 'Month navigation'
	,'Bulan ini': 'This month'
	,'KALENDER KERJA': 'WORK CALENDAR'
	,'hari terjadwal': 'scheduled days'
	,'Atur jadwal': 'Edit schedule'
	,'belum dijadwalkan': 'not scheduled'
	,'Kosong': 'Empty'
	,'Atur': 'Edit'
	,'Shift ditetapkan': 'Shift assigned'
	,'Belum dijadwalkan': 'Not scheduled'
	,'Tutup dialog': 'Close dialog'
	,'Belum ada pola shift tersedia. Buat pola shift terlebih dahulu di halaman Shift & jadwal.': 'No shift patterns are available. Create one on the Shifts & schedules page first.'
	,'Kosongkan jadwal hari ini': 'Clear today’s schedule'
	,'Kosongkan penugasan shift (hari libur / tanpa shift)': 'Clear shift assignment (day off / no shift)'
	,'Simpan jadwal': 'Save schedule'
	,'Penugasan shift': 'Shift assignment for'
	,'karyawan': 'employee'
	,'pada': 'on'
	,'dihapus.': 'was removed.'
	,'Jadwal': 'Schedule for'
	,'berhasil disimpan.': 'was saved successfully.'
	,'QR gagal diperbarui. Periksa koneksi.': 'QR could not be refreshed. Check your connection.'
	,'Deteksi lokasi membutuhkan HTTPS atau localhost dan browser yang mendukung lokasi.': 'Location detection requires HTTPS or localhost and a browser with location support.'
	,'Izin lokasi ditolak. Izinkan lokasi di browser, lalu coba lagi.': 'Location permission was denied. Allow location access in the browser, then try again.'
	,'Pencarian lokasi melewati batas waktu. Coba lagi di area terbuka.': 'Location lookup timed out. Try again in an open area.'
	,'Lokasi belum tersedia. Aktifkan layanan lokasi atau isi koordinat manual.': 'Location is unavailable. Enable location services or enter coordinates manually.'
	,'Lokasi absensi tidak dapat dimuat.': 'Attendance locations could not be loaded.'
	,'Lokasi dan QR absensi berhasil dibuat.': 'Attendance location and QR created successfully.'
	,'Lokasi absensi tidak dapat dibuat.': 'Attendance location could not be created.'
	,'QR dinonaktifkan.': 'QR disabled.'
	,'QR diaktifkan.': 'QR enabled.'
	,'Status QR tidak dapat diperbarui.': 'QR status could not be updated.'
	,'ABSENSI · LOKASI': 'ATTENDANCE · LOCATION'
	,'Buat QR lokasi kerja dan tentukan radius yang berlaku untuk check-in dan check-out.': 'Create a work location QR and set the radius for check-in and check-out.'
	,'QR berganti setiap 30 detik. Tampilkan di layar lokasi kerja; screenshot kedaluwarsa ditolak. Berbagi QR secara langsung dan pemalsuan GPS masih memerlukan kontrol tambahan.': 'The QR rotates every 30 seconds. Display it at the workplace; expired screenshots are rejected. Live QR sharing and GPS spoofing still require additional controls.'
	,'TITIK TERDAFTAR': 'REGISTERED POINTS'
	,'Lokasi kerja': 'Work locations'
	,'lokasi': 'locations'
	,'Memuat lokasi absensi…': 'Loading attendance locations…'
	,'Belum ada lokasi QR': 'No QR locations yet'
	,'Tambahkan titik kerja dan radius sebelum karyawan dapat mencatat absensi dengan QR.': 'Add a work point and radius before employees can record attendance by QR.'
	,'Memperbarui QR…': 'Refreshing QR…'
	,'QR nonaktif': 'QR inactive'
	,'Radius': 'Radius'
	,'QR dinamis': 'Dynamic QR'
	,'detik': 'seconds'
	,'Tampilkan QR': 'Display QR'
	,'Nonaktifkan QR': 'Disable QR'
	,'Aktifkan QR': 'Enable QR'
	,'TITIK BARU': 'NEW POINT'
	,'Buat lokasi QR': 'Create QR location'
	,'Masukkan koordinat pusat lokasi dan radius maksimum absensi.': 'Enter the location center coordinates and maximum attendance radius.'
	,'Gunakan lokasi saya': 'Use my location'
	,'Akurasi perangkat': 'Device accuracy'
	,'Pastikan titik ini pusat lokasi kerja sebelum disimpan.': 'Make sure this point is the center of the workplace before saving.'
	,'Nama lokasi': 'Location name'
	,'Contoh: Kantor pusat': 'Example: Head office'
	,'Radius maksimum (meter)': 'Maximum radius (meters)'
	,'Buat QR dan lokasi': 'Create QR and location'
	,'Gunakan layar dengan koneksi internet. QR dinamis tidak untuk dicetak.': 'Use an internet-connected display. Dynamic QR codes are not for printing.'
	,'Berubah dalam': 'Changes in'
	,'Menunggu QR terbaru…': 'Waiting for the latest QR…'
	,'Lokasi nonaktif': 'Location inactive'
	,'Tutup layar QR': 'Close QR display'
	,'menonaktifkan': 'disable'
	,'mengaktifkan': 'enable'
	,'Yakin ingin': 'Are you sure you want to'
	,'WORKSPACE': 'WORKSPACE'
	,'PENGAJUAN': 'REQUESTS'
	,'SDM': 'HR'
	,'KEUANGAN': 'FINANCE'
	,'OPERASIONAL': 'OPERATIONS'
	,'KOMUNIKASI': 'COMMUNICATIONS'
	,'SISTEM': 'SYSTEM'
	,'JADWAL': 'SCHEDULE'
	,'DATA': 'DATA'
	,'PENGATURAN': 'SETTINGS'
	,'Absensi': 'Attendance'
	,'Komunikasi': 'Communication'
	,'Karier': 'Career'
	,'Pengajuan kasbon': 'Cash Advance Request'
	,'Pengajuan kasbon karyawan dan rencana pengembalian.': 'Employee cash advance requests and repayment plans.'
	,'Ajukan kasbon': 'Request Cash Advance'
	,'Tukar shift kerja': 'Work Shift Swap'
	,'Delegasi dan pertukaran jadwal kerja dengan rekan tim.': 'Delegate and swap work schedules with teammates.'
	,'Ajukan tukar shift': 'Request Shift Swap'
	,'Pengajuan WFH': 'WFH Request'
	,'Pengajuan kerja dari rumah dan koordinasi agenda tim.': 'Work-from-home requests and team agenda coordination.'
	,'Ajukan WFH': 'Request WFH'
	,'Surat & dokumen HR': 'HR Letters & Documents'
	,'Permintaan dokumen resmi dan surat keterangan kerja.': 'Official document and employment letter requests.'
	,'Ajukan dokumen': 'Request Document'
	,'Inventaris & aset': 'Inventory & Assets'
	,'Peminjaman dan pencatatan perangkat kerja karyawan.': 'Employee work equipment loans and records.'
	,'Tambah aset': 'Add Asset'
	,'Target sasaran dan indikator capaian performa kerja.': 'Performance targets and achievement indicators.'
	,'Simpan KPI': 'Save KPI'
	,'Offboarding karyawan': 'Employee Offboarding'
	,'Serah terima inventaris dan penyelesaian administrasi.': 'Inventory handover and administrative clearance.'
	,'Catat offboarding': 'Record Offboarding'
	,'Pengumuman internal': 'Internal Announcements'
	,'Papan informasi penting dan pengumuman perusahaan.': 'Important information and company announcements.'
	,'Lihat pengumuman': 'View Announcements'
	,'Payroll': 'Payroll'
	,'Kelola periode penggajian dan status pemrosesan.': 'Manage payroll periods and processing status.'
	,'Buat periode': 'Create Period'
	,'Tinjau klaim biaya dan bukti pengeluaran.': 'Review expense claims and supporting evidence.'
	,'Tinjau klaim': 'Review Claims'
	,'Tinjau dan kelola pengajuan kasbon karyawan.': 'Review and manage employee cash advance requests.'
	,'Tinjau kasbon': 'Review Cash Advances'
	,'Aset perusahaan': 'Company Assets'
	,'Kelola inventaris aset dan penanggung jawabnya.': 'Manage asset inventory and assigned custodians.'
	,'Kelola informasi yang ditampilkan kepada anggota perusahaan.': 'Manage information shown to company members.'
	,'Buat pengumuman': 'Create Announcement'
	,'Penilaian KPI tim': 'Team KPI Review'
	,'Kelola indikator dan sasaran kinerja seluruh divisi.': 'Manage performance indicators and targets across divisions.'
	,'Tambah indikator': 'Add Indicator'
	,'Log aktivitas': 'Activity Log'
	,'Tinjau aktivitas pengguna dan perubahan data perusahaan.': 'Review user activity and company data changes.'
	,'Filter aktivitas': 'Filter Activity'
	,'Pengaturan': 'Settings'
	,'Kelola preferensi dan kebijakan workspace.': 'Manage workspace preferences and policies.'
	,'Simpan perubahan': 'Save Changes'
	,'Pemeliharaan sistem': 'System Maintenance'
	,'Kelola tugas pemeliharaan dan kebersihan data.': 'Manage maintenance tasks and data hygiene.'
	,'Jalankan pemeriksaan': 'Run Check'
	,'Data kehadiran': 'Attendance Data'
	,'Telusuri dan tinjau catatan kehadiran perusahaan.': 'Browse and review company attendance records.'
	,'Filter kehadiran': 'Filter Attendance'
	,'Import & export absensi': 'Import & Export Attendance'
	,'Siapkan data absensi untuk impor atau ekspor.': 'Prepare attendance data for import or export.'
	,'Pilih berkas': 'Choose File'
	,'Email kerja wajib diisi.': 'Work email is required.'
	,'Format email kerja belum valid.': 'Enter a valid work email.'
	,'Jika email terdaftar, instruksi pemulihan akan dikirim.': 'If the email is registered, recovery instructions will be sent.'
	,'Pemulihan kata sandi belum tersedia.': 'Password recovery is currently unavailable.'
	,'Pulihkan akses': 'Recover access'
	,'Lupa kata sandi?': 'Forgot your password?'
	,'Masukkan email kerja Anda untuk menerima instruksi pemulihan.': 'Enter your work email to receive recovery instructions.'
	,'Email kerja': 'Work email'
	,'nama@perusahaan.com': 'name@company.com'
	,'Mengirim…': 'Sending…'
	,'Kirim instruksi': 'Send instructions'
	,'Kembali ke halaman masuk': 'Back to sign in'
	,'Nama perusahaan wajib diisi.': 'Company name is required.'
	,'Alamat workspace wajib diisi.': 'Workspace address is required.'
	,'Gunakan minimal 3 karakter huruf kecil, angka, atau tanda hubung.': 'Use at least 3 lowercase letters, numbers, or hyphens.'
	,'Nama pemilik wajib diisi.': 'Owner name is required.'
	,'Kata sandi wajib diisi.': 'Password is required.'
	,'Kata sandi minimal 12 karakter.': 'Password must be at least 12 characters.'
	,'Workspace belum dapat dibuat.': 'The workspace could not be created.'
	,'Buat workspace': 'Create workspace'
	,'Daftarkan perusahaan dan akun pemilik untuk mulai menggunakan PasPapan.': 'Register your company and owner account to start using PasPapan.'
	,'Nama perusahaan': 'Company name'
	,'Alamat workspace': 'Workspace address'
	,'Nama Anda': 'Your name'
	,'Kata sandi': 'Password'
	,'Minimal 12 karakter.': 'At least 12 characters.'
	,'Membuat workspace…': 'Creating workspace…'
	,'Sudah memiliki akun?': 'Already have an account?'
	,'Token pemulihan wajib diisi.': 'Recovery token is required.'
	,'Format token pemulihan tidak valid.': 'The recovery token format is invalid.'
	,'Kata sandi baru wajib diisi.': 'New password is required.'
	,'Kata sandi baru minimal 12 karakter.': 'New password must be at least 12 characters.'
	,'Konfirmasi kata sandi wajib diisi.': 'Password confirmation is required.'
	,'Konfirmasi kata sandi belum cocok.': 'Password confirmation does not match.'
	,'Kata sandi berhasil diperbarui. Mengalihkan ke halaman masuk…': 'Password updated. Redirecting to sign in…'
	,'Kata sandi belum dapat diperbarui.': 'The password could not be updated.'
	,'Atur ulang kata sandi': 'Reset password'
	,'Buat kata sandi baru minimal 12 karakter untuk akun kerja Anda.': 'Create a new password of at least 12 characters for your work account.'
	,'Token pemulihan': 'Recovery token'
	,'Tempel kode token dari email': 'Paste the token from the email'
	,'Kata sandi baru': 'New password'
	,'Konfirmasi kata sandi baru': 'Confirm new password'
	,'Simpan kata sandi baru': 'Save new password'
	,'Data karyawan gagal dimuat.': 'Employee data could not be loaded.'
	,'Data karyawan tersimpan.': 'Employee data saved.'
	,'Gagal menyimpan karyawan.': 'Could not save the employee.'
	,'Status kerja diperbarui.': 'Employment status updated.'
	,'Gagal mengubah status kerja.': 'Could not update employment status.'
	,'Detail karyawan': 'Employee details'
	,'Muat ulang data': 'Reload data'
	,'Memuat karyawan…': 'Loading employee…'
	,'Identitas & penempatan': 'Identity & placement'
	,'Tim / divisi': 'Team / division'
	,'Jabatan': 'Job title'
	,'Atasan langsung': 'Direct manager'
	,'Atasan nonaktif — pilih pengganti': 'Inactive manager — choose a replacement'
	,'Tanggal mulai': 'Start date'
	,'Telepon': 'Phone'
	,'Alamat': 'Address'
	,'Simpan karyawan': 'Save employee'
	,'Informasi hanya dapat dilihat. Akun owner dilindungi.': 'This information is read-only. Owner accounts are protected.'
	,'Nonaktif/resign menghentikan akses perusahaan ini. Setelah aktif kembali, karyawan harus login ulang. Riwayat tetap disimpan.': 'Inactive or resigned status revokes access to this company. Reactivated employees must sign in again. History is retained.'
	,'Saya konfirmasi perubahan status.': 'I confirm this status change.'
	,'Ubah status kerja': 'Update employment status'
	,'Perubahan status memerlukan izin terpisah; status sendiri dan owner dilindungi.': 'Status changes require separate permission; your own status and owner accounts are protected.'
	,'Email dan kata sandi tidak diubah melalui form karyawan.': 'Email and password are not changed through the employee form.'
	,'Gagal memuat metadata API Docs Superadmin.': 'Could not load Superadmin API Docs metadata.'
	,'DOKUMENTASI INTERNAL SUPERADMIN': 'SUPERADMIN INTERNAL DOCUMENTATION'
	,'Panduan arsitektur keamanan, referensi header wajib, pengujian endpoint interaktif, dan ekspor koleksi Postman/OpenAPI.': 'Security architecture guide, required header reference, interactive endpoint testing, and Postman/OpenAPI exports.'
	,'Unduh Postman Collection': 'Download Postman Collection'
	,'Unduh Postman Env': 'Download Postman Environment'
	,'Status runtime keamanan API': 'API security runtime status'
	,'Akses Dokumen': 'Document Access'
	,'Aktif (Constant-Time)': 'Active (Constant-Time)'
	,'Siaga': 'Standby'
	,'Panduan integrasi dan standar header': 'Integration guide and header standards'
	,'Standar Header & Keamanan': 'Header & Security Standards'
	,'Panduan Alur Integrasi': 'Integration Flow Guide'
	,'Cara Pakai di Postman': 'Using Postman'
	,'Katalog endpoint API': 'API endpoint catalog'
	,'Cari path atau deskripsi endpoint…': 'Search endpoint path or description…'
	,'Filter kategori': 'Filter category'
	,'Filter HTTP method': 'Filter HTTP method'
	,'Semua Method': 'All Methods'
	,'Playground interaktif endpoint': 'Interactive endpoint playground'
	,'Publik': 'Public'
	,'Publik (Wajib Origin)': 'Public (Origin Required)'
	,'Terautentikasi': 'Authenticated'
	,'Terautentikasi (Wajib Origin)': 'Authenticated (Origin Required)'
	,'Salin cURL': 'Copy cURL'
	,'Hit Endpoint Sekarang': 'Run Endpoint Now'
	,'Path / Query Target (menggunakan sesi browser Superadmin saat ini)': 'Path / Query Target (uses the current Superadmin browser session)'
	,'Contoh Header Wajib': 'Required Header Example'
	,'Contoh cURL Siap Pakai': 'Ready-to-use cURL Example'
	,'Request Body JSON (dapat diedit sebelum menjalankan endpoint)': 'JSON Request Body (editable before running the endpoint)'
	,'Hasil Live Hit:': 'Live Hit Result:'
	,'Contoh Respons Standar': 'Standard Response Example'
	,'Semua Kategori': 'All Categories'
	,'Auth & Sesi': 'Authentication & Sessions'
	,'Akun & Keamanan': 'Account & Security'
	,'Absensi & Dynamic QR': 'Attendance & Dynamic QR'
	,'Pengajuan & Approval': 'Requests & Approvals'
	,'Admin & RBAC': 'Admin & RBAC'
	,'Informasi profil tidak dapat dimuat.': 'Profile information could not be loaded.'
	,'Sesi perangkat tidak dapat dimuat.': 'Device sessions could not be loaded.'
	,'Nama lengkap wajib diisi.': 'Full name is required.'
	,'Informasi profil berhasil disimpan.': 'Profile information saved.'
	,'Profil tidak dapat disimpan.': 'Profile could not be saved.'
	,'Kata sandi saat ini wajib diisi.': 'Current password is required.'
	,'Konfirmasi kata sandi baru belum cocok.': 'New password confirmation does not match.'
	,'Kata sandi diperbarui. Sesi di perangkat lain telah dikeluarkan.': 'Password updated. Sessions on other devices have been signed out.'
	,'Kata sandi tidak dapat diperbarui.': 'Password could not be updated.'
	,'Sesi perangkat tidak dapat dikeluarkan.': 'The device session could not be signed out.'
	,'Tema mengikuti perangkat diterapkan.': 'System appearance applied.'
	,'Tema gelap diterapkan.': 'Dark appearance applied.'
	,'Tema terang diterapkan.': 'Light appearance applied.'
	,'Pengaturan profil': 'Profile settings'
	,'Tambahkan nomor telepon': 'Add a phone number'
	,'Tambahkan alamat': 'Add an address'
	,'Proteksi akun aktif': 'Account protection is active'
	,'Mutasi akun memerlukan origin tepercaya dan konfirmasi kredensial.': 'Account changes require a trusted origin and credential confirmation.'
	,'Perbarui kata sandi': 'Update password'
	,'Minimal 12 karakter; sesi lain akan dicabut.': 'At least 12 characters; other sessions will be revoked.'
	,'Tinjau perangkat aktif': 'Review active devices'
	,'Periksa dan keluarkan sesi yang tidak dikenal.': 'Review and sign out unrecognized sessions.'
	,'Memuat sesi perangkat…': 'Loading device sessions…'
	,'Tidak ada sesi aktif.': 'No active sessions.'
	,'Perangkat ini': 'This device'
	,'Sesi perangkat': 'Device session'
	,'Saat ini': 'Current'
	,'Berakhir': 'Expires'
	,'Hanya sesi perangkat ini yang sedang aktif.': 'Only this device session is active.'
	,'Menu operasional': 'Operations menu'
	,'OPERASIONAL & ADMIN': 'OPERATIONS & ADMIN'
	,'KEAMANAN AKUN': 'ACCOUNT SECURITY'
	,'Keluarkan sesi perangkat?': 'Sign out device session?'
	,'Perangkat lain akan diminta masuk kembali. Masukkan kata sandi Anda untuk mengonfirmasi.': 'The other device will be asked to sign in again. Enter your password to confirm.'
	,'Kata sandi saat ini': 'Current password'
	,'Mengeluarkan…': 'Signing out…'
	,'Keluarkan sesi': 'Sign out session'
	,'Email atau kata sandi tidak sesuai.': 'Incorrect email or password.'
	,'MEMBUKA WORKSPACE': 'OPENING WORKSPACE'
	,'Mengalihkan ke halaman yang sesuai…': 'Redirecting to the appropriate page…'
	,'Lanjutkan': 'Continue'
	,'Masukkan kata sandi': 'Enter password'
	,'Minimal 12 karakter': 'At least 12 characters'
	,'karakter lagi': 'more characters'
	,'Lemah': 'Weak'
	,'Cukup': 'Fair'
	,'Kuat': 'Strong'
	,'Sangat kuat': 'Very strong'
	,'Sembunyikan kata sandi': 'Hide password'
	,'Tampilkan kata sandi': 'Show password'
	,'Gagal memuat data pendaftaran wajah dari server.': 'Could not load face enrollment data from the server.'
	,'Kamera tidak dapat diakses. Mohon berikan izin akses kamera.': 'Camera access failed. Please allow camera access.'
	,'Gagal mengakses kamera setelah peralihan. Silakan coba lagi.': 'Could not access the camera after switching. Please try again.'
	,'Wajah tidak terdeteksi! Posisikan wajah Anda tepat di dalam bingkai sebelum mengambil foto.': 'Face not detected. Position your face inside the frame before capturing.'
	,'Wajah tidak terdeteksi. Pastikan wajah Anda terlihat jelas di dalam kamera.': 'Face not detected. Make sure your face is clearly visible in the camera.'
	,'Data wajah tidak terdeteksi lengkap. Silakan ulangi pemindaian.': 'Complete face data was not detected. Please repeat the scan.'
	,'Data wajah berhasil didaftarkan dan siap digunakan untuk presensi!': 'Face data enrolled successfully and ready for attendance verification.'
	,'Gagal menyimpan data wajah ke server.': 'Could not save face data to the server.'
	,'Hapus data pendaftaran wajah Anda dari sistem?': 'Remove your face enrollment data from the system?'
	,'Pendaftaran wajah berhasil dihapus dari sistem.': 'Face enrollment removed successfully.'
	,'Gagal menghapus data wajah.': 'Could not remove face data.'
	,'berhasil disalin ke clipboard.': 'copied to clipboard.'
	,'Gagal menyalin ke clipboard.': 'Could not copy to the clipboard.'
	,'Gagal menghubungi endpoint.': 'Could not reach the endpoint.'
	,'Berkas': 'File'
	,'berhasil diunduh.': 'downloaded successfully.'
	,'Perintah cURL': 'cURL command'
	,'LAPIS 1 · SERVICE-TO-SERVICE GUARD': 'LAYER 1 · SERVICE-TO-SERVICE GUARD'
	,'Seluruh request ke Rust API wajib menyertakan service key yang cocok. Proxy SvelteKit menyisipkan header ini secara otomatis.': 'Every request to the Rust API must include the matching service key. The SvelteKit proxy injects this header automatically.'
	,'LAPIS 2 · CSRF & ORIGIN GUARD': 'LAYER 2 · CSRF & ORIGIN GUARD'
	,'Header Origin pada Endpoint Mutasi (POST)': 'Origin Header on Mutating Endpoints (POST)'
	,'Setiap endpoint POST memvalidasi Origin terhadap APP_ORIGIN. Origin yang tidak cocok ditolak dengan 403.': 'Every POST endpoint validates Origin against APP_ORIGIN. A mismatched origin is rejected with 403.'
	,'LAPIS 3 · AUTENTIKASI SESI': 'LAYER 3 · SESSION AUTHENTICATION'
	,'Cookie diterbitkan saat login/register, disimpan sebagai hash HMAC-SHA256, dan selalu diperiksa terhadap status aktif karyawan.': 'The cookie is issued during login/register, stored as an HMAC-SHA256 hash, and always checked against active employee status.'
	,'LANGKAH 1 · LOGIN & SESI': 'STEP 1 · LOGIN & SESSION'
	,'Panggil POST /v1/auth/login': 'Call POST /v1/auth/login'
	,'Kirim email dan password beserta Origin, simpan cookie sesi, lalu verifikasi capability melalui endpoint sesi.': 'Send email and password with Origin, retain the session cookie, then verify capabilities through the session endpoint.'
	,'LANGKAH 2 · DYNAMIC QR & GEOFENCE': 'STEP 2 · DYNAMIC QR & GEOFENCE'
	,'Rotasi 30 Detik + Haversine Check-In': '30-Second Rotation + Haversine Check-In'
	,'Layar kantor merotasi token setiap 30 detik. Karyawan mengirim token hasil pindai bersama koordinat GPS saat check-in.': 'The office display rotates its token every 30 seconds. Employees submit the scanned token with GPS coordinates at check-in.'
	,'LANGKAH 3 · OBJECT STORAGE & SSE': 'STEP 3 · OBJECT STORAGE & SSE'
	,'Unggah lampiran melalui endpoint file. Untuk pembaruan real-time tanpa polling, gunakan EventSource pada stream peristiwa.': 'Upload attachments through the file endpoint. For real-time updates without polling, use EventSource on the event stream.'
	,'LANGKAH 1 · IMPOR KOLEKSI': 'STEP 1 · IMPORT COLLECTION'
	,'Unduh koleksi dan environment Postman': 'Download the Postman collection and environment'
	,'Impor kedua berkas JSON ke Postman, lalu aktifkan environment PasPapan Local & Staging.': 'Import both JSON files into Postman, then activate the PasPapan Local & Staging environment.'
	,'LANGKAH 2 · OTOMATISASI COOKIE': 'STEP 2 · COOKIE AUTOMATION'
	,'Jalankan login pertama kali': 'Run login for the first time'
	,'Cookie jar Postman menyimpan cookie sesi secara otomatis sehingga endpoint terautentikasi dapat langsung digunakan.': 'Postman’s cookie jar stores the session cookie automatically so authenticated endpoints can be used immediately.'
	,'LANGKAH 3 · API LANGSUNG VS PROXY': 'STEP 3 · DIRECT API VS PROXY'
	,'Variabel baseUrl & serviceKey': 'baseUrl & serviceKey Variables'
	,'Gunakan baseUrl frontend untuk proxy SvelteKit, atau alamat API langsung dengan serviceKey yang sesuai.': 'Use the frontend baseUrl for the SvelteKit proxy, or the direct API address with the matching serviceKey.'
	,'Shift & Jadwal': 'Shifts & Schedules'
	,'Karyawan & Hirarki': 'Employees & Hierarchy'
	,'Payroll & Slip Gaji': 'Payroll & Payslips'
	,'Object Storage': 'Object Storage'
	,'Liveness check server Rust API (bebas autentikasi untuk container healthcheck).': 'Rust API server liveness check (authentication-free for container health checks).'
	,'Daftarkan perusahaan baru beserta akun Superadmin (Owner) pertama dan otomatis membuat sesi.': 'Register a new company with its first Superadmin (Owner) account and create a session automatically.'
	,'Autentikasi email & kata sandi (Argon2id) dan terbitkan cookie HttpOnly paspapan_session.': 'Authenticate email and password with Argon2id and issue the HttpOnly paspapan_session cookie.'
	,'Ambil profil pengguna aktif, perusahaan, peran, dan daftar capability efektif.': 'Get the active user profile, company, role, and effective capability list.'
	,'Kirim permintaan bantuan pemulihan akses ke Owner/Admin perusahaan dan catat di outbox & audit log.': 'Send an access recovery request to the company Owner/Admin and record it in the outbox and audit log.'
	,'Cabut sesi saat ini di database dan hapus cookie paspapan_session.': 'Revoke the current database session and remove the paspapan_session cookie.'
	,'Ambil detail profil pribadi beserta versi optimistic locking (version).': 'Get personal profile details with the optimistic-locking version.'
	,'Simpan pembaruan profil pribadi dengan proteksi optimistic concurrency (version).': 'Save personal profile updates with optimistic-concurrency protection.'
	,'Ganti kata sandi akun, cabut seluruh sesi perangkat lain, dan rotasi token sesi aktif.': 'Change the account password, revoke every other device session, and rotate the active session token.'
	,'Daftar seluruh sesi login aktif milik pengguna beserta penanda sesi saat ini (current).': 'List all active login sessions for the user, including the current-session marker.'
	,'Status presensi hari ini menurut zona waktu perusahaan beserta jadwal shift aktif.': 'Today’s attendance status in the company timezone with the active shift schedule.'
	,'Check-in presensi dengan validasi jarak geofence Haversine & token QR dinamis (30 detik).': 'Attendance check-in with Haversine geofence validation and a dynamic 30-second QR token.'
	,'Check-out presensi dengan validasi koordinat GPS & token QR dinamis lokasi kerja.': 'Attendance check-out with GPS coordinate validation and the workplace dynamic QR token.'
	,'Daftar titik lokasi absensi perusahaan beserta token QR aktif dan radius meter.': 'List company attendance locations with active QR tokens and radius in meters.'
	,'Tambah titik lokasi presensi baru dengan koordinat GPS dan radius meter (10–5000m).': 'Add an attendance location with GPS coordinates and a 10–5000 meter radius.'
	,'Rotasi token QR dinamis (30 detik) untuk seluruh lokasi aktif perusahaan.': 'Rotate 30-second dynamic QR tokens for every active company location.'
	,'Daftar seluruh master shift kerja perusahaan.': 'List all company work-shift templates.'
	,'Buat master shift kerja baru (mendukung shift lintas tengah malam / crosses_midnight).': 'Create a work-shift template with overnight-shift support.'
	,'Tetapkan atau perbarui penugasan shift karyawan pada tanggal tertentu (YYYY-MM-DD).': 'Assign or update an employee shift on a specific date (YYYY-MM-DD).'
	,'Daftar jadwal shift kerja milik pengguna yang sedang login.': 'List the signed-in user’s work-shift schedule.'
	,'Direktori karyawan dengan pencarian trigram (pg_trgm), filter peran/status, dan paginasi.': 'Employee directory with trigram search, role/status filters, and pagination.'
	,'Tambahkan anggota karyawan baru beserta kode NIP dan atasan langsung (manager_user_id).': 'Add an employee with an employee number and direct manager.'
	,'Daftar pengajuan cuti, lembur, koreksi absensi, dan reimbursement milik pengguna.': 'List the user’s leave, overtime, attendance-correction, and reimbursement requests.'
	,'Kirim pengajuan baru (leave, overtime, attendance_correction, reimbursement).': 'Submit a new leave, overtime, attendance-correction, or reimbursement request.'
	,'Kotak masuk persetujuan 2 tahap (Manager -> Admin/Owner) untuk bawahan atau perusahaan.': 'Two-stage approval inbox (Manager → Admin/Owner) for direct reports or the company.'
	,'Daftar periode penggajian dan rincian slip gaji (THP, gaji pokok, potongan).': 'List payroll periods and payslip details, including take-home pay, base salary, and deductions.'
	,'Terbitkan atau perbarui perhitungan slip gaji karyawan pada periode tertentu.': 'Issue or update an employee payslip calculation for a specific period.'
	,'Informasi driver Object Storage aktif (local, r2, s3, minio), bucket, dan region.': 'Active Object Storage driver, bucket, and region information.'
	,'Daftar berkas privat perusahaan di Object Storage beserta URL unduhan / Presigned SigV4 URL.': 'List private company files in Object Storage with download or presigned SigV4 URLs.'
	,'Unggah berkas (Base64 payload maks 10 MB) ke driver Object Storage aktif (local/r2/s3/minio).': 'Upload a file with a Base64 payload up to 10 MB to the active Object Storage driver.'
	,'Konfigurasi RBAC perusahaan: peran kustom, override grant/deny anggota, dan 50 audit akses.': 'Company RBAC configuration: custom roles, member grant/deny overrides, and the latest 50 access audits.'
	,'Telemetri real-time PostgreSQL 18 (pg_stat_activity, pg_stat_database, pg_extension) & SQLx Pool.': 'Real-time PostgreSQL 18 and SQLx pool telemetry.'
	,'Server-Sent Events (text/event-stream) untuk sinkronisasi notifikasi & presensi real-time tanpa reload.': 'Server-Sent Events for real-time notification and attendance synchronization without reloading.'
};

Object.assign(english, {
  "Verifikasi tidak tersedia.": "Verification is unavailable.",
  "Bukti provider diterima. Simpan untuk validasi server.": "Provider evidence received. Save to validate it on the server.",
  "Verifikasi melalui provider": "Verify with provider",
  "Buka kembali verifikasi": "Reopen verification",
  "Provider biometrik belum dikonfigurasi.": "The biometric provider is not configured.",
  "Pratinjau": "Preview",
  "Foto siap diverifikasi": "Photo ready for verification",
  "Pendaftaran memerlukan verifikasi liveness dari provider server.": "Enrollment requires liveness verification by the server provider.",
  "Belum diverifikasi server": "Not verified by the server",
  "Siapkan verifikasi": "Prepare verification",
  "Aturan kalkulasi payroll": "Payroll calculation rules",
  "Aturan perusahaan memakai gaji pokok sebagai dasar. Tarif BPJS dan pajak harus disetujui perusahaan sebelum digunakan.": "Company rules use base salary as their basis. BPJS and tax rates require company approval before use.",
  "Nama aturan": "Rule name",
  "Biaya perusahaan": "Employer cost",
  "Tarif (basis poin)": "Rate (basis points)",
  "Nominal tetap (Rp)": "Fixed amount (IDR)",
  "Batas dasar (Rp, opsional)": "Basis cap (IDR, optional)",
  "Tambah aturan": "Add rule",
  "Simpan aturan": "Save rules",
  "Gunakan aturan payroll": "Use payroll rules",
  "Gaji pokok (Rp)": "Base salary (IDR)",
  "Lembur (menit)": "Overtime (minutes)",
  "Tarif lembur per jam (Rp)": "Hourly overtime rate (IDR)",
  "Rincian kalkulasi": "Calculation breakdown",
  "WFH disetujui hari ini": "WFH approved today",
  "Presensi WFH tetap mencatat lokasi GPS.": "WFH attendance still records GPS coordinates.",
  "Presensi keluar WFH": "WFH check-out",
  "Presensi masuk WFH": "WFH check-in",
  "Pencairan dan cicilan": "Disbursements and installments",
  "Pencairan": "Disbursement",
  "Cicilan": "Repayment",
  "Nominal (Rp)": "Amount (IDR)",
  "Referensi transaksi": "Transaction reference",
  "Catat transaksi": "Record transaction",
  "Catat hanya transaksi yang sudah dilakukan. Pencatatan ini tidak mentransfer uang.": "Record completed transactions only. Recording does not transfer money.",
  "Belum ada pengajuan keuangan yang disetujui.": "No approved financial requests yet.",
  "Kalender perhitungan cuti": "Leave calculation calendar",
  "Perubahan kalender berlaku untuk pengajuan baru. Kuota pengajuan lama tetap memakai perhitungan saat dibuat.": "Calendar changes apply to new requests. Existing requests keep their original charged days.",
  "Kecualikan hari libur perusahaan": "Exclude company holidays",
  "Simpan kalender": "Save calendar",
  "Tanda tangan diverifikasi provider": "Signature verified by provider",
  "Sisa tahun lalu yang disetujui (hari)": "Approved carryover from last year (days)",
  "Siklus penilaian": "Appraisal cycles",
  "Tutup siklus": "Close cycle",
  "Nama siklus": "Cycle name",
  "Buat siklus": "Create cycle",
  "Tanpa siklus": "No cycle",
  "Bobot": "Weight",
  "Nilai berbobot memakai capaian yang disetujui; target belum disetujui bernilai nol. Bobot dinormalisasi per karyawan dan siklus.": "Weighted scores use approved results; unapproved goals score zero. Weights are normalized per employee and cycle.",
  "Tren kehadiran": "Attendance trend",
  "14 hari terakhir · zona waktu perusahaan": "Last 14 days · company time zone",
  "Aktivitas kehadiran perusahaan": "Company attendance activity",
  "Antrean pengajuan": "Request queue",
  "Belum ada aktivitas tercatat.": "No activity recorded yet.",
  "Buka monitor sistem": "Open system monitor",
  "Buka persetujuan": "Open approvals",
  "Cache hit": "Cache hit",
  "Data kesehatan sistem belum tersedia.": "System health data is unavailable.",
  "Deadlock kumulatif": "Cumulative deadlocks",
  "Diperbarui": "Updated",
  "Error server": "Server errors",
  "Jam": "Hour",
  "Jejak audit terbaru perusahaan": "Latest company audit events",
  "Kesehatan sistem": "System health",
  "Latensi rata-rata": "Average latency",
  "Lihat data grafik": "View chart data",
  "Lihat semua": "View all",
  "Lokasi aktif": "Active locations",
  "Menunggu keputusan manager atau admin": "Awaiting a manager or admin decision",
  "Menunggu lock": "Waiting for locks",
  "Pengukuran dalam proses API aktif; direset saat restart.": "Measured by this API process; resets on restart.",
  "Perbandingan presensi dan jadwal harian": "Daily attendance and schedule comparison",
  "Pool terpakai": "Pool in use",
  "Presensi terbaru": "Recent attendance",
  "Request": "Requests",
  "Request API per jam": "API requests per hour",
  "Request terautentikasi perusahaan · 24 jam terakhir": "Company authenticated requests · last 24 hours",
  "Sistem": "System",
  "Snapshot koneksi dan database": "Connection and database snapshot",
  "Terjadwal": "Scheduled",
  "Tidak ada pengajuan tertunda.": "No pending requests.",
  "Traffic API": "API traffic",
  "Ringkasan direktori": "Directory summary",
  "Ringkasan shift": "Shift summary",
  "Log error API": "API error log",
  "20 error 5xx terbaru dari request perusahaan dalam proses API aktif": "Latest 20 company request 5xx errors recorded by this API process",
  "Endpoint": "Endpoint",
  "Belum ada error server yang tercatat.": "No server errors recorded yet.",
  "Impor presensi": "Import attendance",
  "Waktu": "Time",
  "Total karyawan": "Total employees",
  "Pegawai": "Employee",
  "Email kerja dikelola oleh admin workspace.": "Your work email is managed by the workspace admin.",
  "Pemilik": "Owner",
  "Manajer": "Manager",
  "Peta tidak dapat dimuat. Koordinat GPS tetap tersedia.": "The map could not load. GPS coordinates are still available.",
  "Buka peta": "Open map",
  "Impor presensi perusahaan": "Import company attendance",
  "Impor tidak menimpa catatan yang sudah ada. Semua baris divalidasi sebelum disimpan.": "Import never overwrites existing records. All rows are validated before saving.",
  "Berkas maksimal 1 MB.": "Maximum file size is 1 MB.",
  "Gunakan array JSON berisi 1–200 catatan.": "Use a JSON array with 1–200 records.",
  "Validasi berhasil. Data belum disimpan.": "Validation passed. No data has been saved.",
  "Impor berhasil.": "Import completed.",
  "Berkas JSON": "JSON file",
  "Validasi": "Validate",
  "Simpan impor": "Save import",
  "Ekspor gagal.": "Export failed.",
  "Halaman presensi": "Attendance pagination",
  "Nilai dan depresiasi": "Value and depreciation",
  "Nilai buku": "Book value",
  "Metode garis lurus berdasarkan bulan penuh, dibatasi nilai sisa.": "Straight-line depreciation by completed months, capped at residual value.",
  "Tanggal perolehan": "Acquisition date",
  "Harga perolehan (Rp)": "Acquisition cost (IDR)",
  "Nilai sisa (Rp)": "Residual value (IDR)",
  "Masa manfaat (bulan)": "Useful life (months)",
  "Sudah masuk": "Checked in",
  "Belum tercatat": "Not recorded",
  "Lokasi kerja": "Work location",
  "Template dokumen": "Document templates",
  "Isi dokumen": "Document content",
  "Simpan template": "Save template",
  "Template baru": "New template",
  "Tanpa template": "No template",
  "Pratinjau dokumen": "Document preview",
  "Cetak / Simpan PDF": "Print / Save PDF",
  "Pilih Simpan sebagai PDF, lalu unggah hasilnya untuk penerbitan. Tanda tangan memerlukan provider.": "Choose Save as PDF, then upload the result for issuance. Signing requires a provider.",
  "Izinkan popup untuk membuat PDF.": "Allow popups to generate a PDF.",
  "Font dokumen tidak tersedia.": "Document font is unavailable.",
  "PDF tidak dapat dibuat.": "Could not generate the PDF.",
  "Rekening dan format ekspor bank": "Bank accounts and export format",
  "Rekening karyawan": "Employee bank accounts",
  "Kode karyawan": "Employee code",
  "Nama karyawan": "Employee name",
  "Kode bank": "Bank code",
  "Nomor rekening": "Account number",
  "Nama pemilik rekening": "Account holder name",
  "Mata uang": "Currency",
  "Awal periode": "Period start",
  "Akhir periode": "Period end",
  "Konfigurasi tersimpan.": "Configuration saved.",
  "Simpan rekening": "Save account",
  "Format CSV bank": "Bank CSV format",
  "Cocokkan urutan kolom dan judul dengan template bank. Ekspor tidak mentransfer uang. Rekening dikunci sebagai snapshot saat payroll dipublikasikan.": "Match column order and headings to the bank template. Export does not transfer funds. Accounts are snapshotted when payroll is published.",
  "Pemisah kolom": "Column separator",
  "Koma": "Comma",
  "Titik koma": "Semicolon",
  "Data": "Data",
  "Judul kolom": "Column heading",
  "Naikkan kolom": "Move column up",
  "Turunkan kolom": "Move column down",
  "Tambah kolom": "Add column",
  "Saya telah mencocokkan format dengan template bank.": "I have checked the format against the bank template.",
  "Simpan format bank": "Save bank format",
  "Ekspor bank": "Export bank file",
  "Metode": "Method",
  "Tarif tetap": "Flat rate",
  "Tarif efektif per lapisan": "Effective rate by bracket",
  "Progresif marginal": "Marginal progressive",
  "Dasar perhitungan": "Calculation basis",
  "Penghasilan kena pajak": "Taxable income",
  "Kategori pajak (opsional)": "Tax category (optional)",
  "Termasuk penghasilan kena pajak": "Included in taxable income",
  "Batas atas meningkat. Baris terakhir tanpa batas; tarif dalam basis poin.": "Upper limits must increase. The last bracket is unlimited; rates use basis points.",
  "Batas penghasilan (Rp)": "Income limit (IDR)",
  "Tanpa batas": "Unlimited",
  "Tambah lapisan": "Add bracket",
  "Prorata, THR, dan penyesuaian": "Proration, THR and adjustments",
  "Hari kerja (opsional)": "Working days (optional)",
  "Hari dibayar": "Paid days",
  "Sertakan THR": "Include THR",
  "Masa kerja untuk THR (bulan)": "THR service duration (months)",
  "Nominal tambahan dan potongan memakai rupiah penuh. Perhitungan mengikuti aturan perusahaan, bukan tarif pajak bawaan.": "Adjustments and deductions use whole rupiah. Calculations follow company rules; no tax rates are provided by default.",
  "Tambah penyesuaian": "Add adjustment",
  "Pilih kategori": "Choose a category",
  "Penghasilan": "Earnings",
  "Pilih dasar dan metode perhitungan setiap aturan. Tarif BPJS dan pajak harus disetujui perusahaan sebelum digunakan.": "Choose the basis and calculation method for each rule. Company approval is required for BPJS and tax rates before use.",
  "Gaji pokok": "Base salary",
  "Tukar shift": "Swap shifts",
  "Koreksi": "Correction",
  "Segarkan": "Refresh",
  "Sesi aktif": "Active sessions",
  "Pengirim": "Sender",
  "Kembali": "Back",
  "TIM & OPERASIONAL": "TEAM & OPERATIONS",
  "Terverifikasi": "Verified",
  "Perlu verifikasi ulang": "Verification required",
  "Terverifikasi provider": "Provider verified",
  "Profil ini perlu diverifikasi ulang oleh layanan perusahaan.": "This profile needs to be verified again by the company service.",
  "Pendaftaran wajah memerlukan layanan verifikasi perusahaan yang aktif.": "Face enrollment requires an active company verification service.",
  "Verifikasi wajah belum tersedia. Hubungi admin.": "Face verification is unavailable. Contact your administrator.",
  "Persetujuan penggunaan data wajah diperlukan.": "Consent to use face data is required.",
  "Saya setuju menyimpan foto dan data wajah serta mengirimkannya ke layanan verifikasi perusahaan.": "I consent to storing my photo and face data and sending them to the company verification service."
});

Object.assign(english, {
  "Konfigurasi integrasi tersimpan. Tantangan lama telah dibatalkan.": "Integration saved. Previous challenges have been invalidated.",
  "Integrasi layanan": "Service integrations",
  "Ganti layanan per perusahaan. Satu provider aktif untuk setiap jenis verifikasi.": "Manage services per company. One active provider per verification type.",
  "Tambah provider": "Add provider",
  "Penyimpanan kredensial belum siap. Master key perlu dikonfigurasi di server satu kali.": "Credential storage is unavailable. Configure the server master key once.",
  "Jenis layanan": "Service type",
  "Biometrik wajah": "Face biometrics",
  "Tanda tangan dokumen": "Document signatures",
  "Nama layanan": "Service name",
  "Protokol integrasi": "Integration protocol",
  "Bridge verifikasi bertanda tangan": "Signed verification bridge",
  "URL layanan": "Service URL",
  "Kredensial yang tersimpan tidak ditampilkan kembali. Kosongkan untuk mempertahankan kredensial lama.": "Stored credentials are never displayed again. Leave blank to retain the existing credential.",
  "Hapus kredensial tersimpan": "Remove stored credential",
  "Aktifkan provider ini": "Activate this provider",
  "Aktivasi mengganti provider sebelumnya untuk jenis layanan ini dan membatalkan tantangan yang belum selesai.": "Activation replaces the previous provider for this service and invalidates unfinished challenges.",
  "Belum ada provider yang dikelola panel": "No panel-managed providers yet",
  "Tambahkan layanan yang mendukung protokol integrasi. Setiap vendor baru memerlukan adapter yang kompatibel.": "Add a service supporting the integration protocol. Each new vendor needs a compatible adapter.",
  "Edit provider": "Edit provider",
  "Provider baru": "New provider",
  "Kredensial pengganti (opsional)": "Replacement credential (optional)",
  "Kredensial layanan": "Service credential",
  "Kredensial tersimpan": "Credential stored",
  "Belum ada kredensial": "No credential stored",
  "Provider dan kredensial perusahaan": "Company providers and credentials"
});

export function uiText(value: string): string {
	return getLocale() === 'en' ? (english[value] ?? value) : value;
}

export function localeTag(): string {
	return getLocale() === 'en' ? 'en-US' : 'id-ID';
}
