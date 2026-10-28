# Daftar Fitur & Endpoint PasPapan

Platform berjalan di atas **SvelteKit (`apps/web`)** dan **Rust API (`apps/api`)**. [Status parity](rewrite-parity.md) mencatat workflow yang tersedia dan batas implementasinya.

## 1. Autentikasi & Akun
- `POST /v1/auth/register` — Registrasi workspace perusahaan & akun pemilik (`owner`).
- `POST /v1/auth/login` — Login dengan Argon2id dan pembuatan cookie sesi `paspapan_session`.
- `POST /v1/auth/password-reset` — Permintaan pemulihan akses akun.
- `POST /v1/auth/logout` — Pencabutan sesi aktif.
- `GET /v1/auth/session` — Informasi pengguna, perusahaan, role, dan daftar `capabilities`.
- `GET/POST /v1/account/profile` — Pembaruan profil dengan optimistic locking (`version`).
- `POST /v1/account/password` — Pergantian kata sandi dan pencabutan sesi perangkat lain.
- `GET /v1/account/sessions` & `POST /v1/account/sessions/{id}/revoke` — Manajemen sesi perangkat.

## 2. Absensi GPS + Dynamic QR (30-Detik Expiry)
- `GET /v1/attendance/today` — Status check-in/check-out dan shift hari ini (`/app/today`).
- `POST /v1/attendance/check-in` & `POST /v1/attendance/check-out` — Validasi token QR dinamis yang belum kadaluarsa dan jarak koordinat GPS Haversine (`/app/scan`).
- `GET /v1/attendance/history` — Riwayat kehadiran bulanan (`/app/history`).
- `GET/POST /v1/admin/attendance-locations`, `POST /v1/admin/attendance-locations/refresh`, `POST /v1/admin/attendance-locations/{id}/status` — Manajemen lokasi & layar QR dinamis (`/admin/barcodes`).

## 3. Jadwal Kerja & Shift
- `GET /v1/schedule` — Jadwal shift mendatang karyawan (`/app/schedule`).
- `GET/POST /v1/admin/shifts` — Master shift kerja (`/admin/attendance`).
- `GET/POST /v1/admin/shift-assignments` & `POST /v1/admin/shift-assignments/delete` — Kalender penugasan shift bulanan (`/admin/schedules`).

## 4. Direktori Karyawan & RBAC Kustom
- `GET /v1/admin/employee-directory`, `GET/POST /v1/admin/employees`, `GET/POST /v1/admin/employees/{id}`, `POST /v1/admin/employees/{id}/status` — Direktori, pencarian, detail, dan status kerja karyawan (`/admin/employees`).
- `GET /v1/admin/access`, `POST /v1/admin/access/roles`, `POST /v1/admin/access/roles/{id}/delete`, `POST /v1/admin/access/members/{id}` — Manajemen peran kustom & izin anggota (`/admin/access`).

## 5. Pengajuan, Persetujuan & Notifikasi
- `GET/POST /v1/requests` — Workflow domain untuk cuti, lembur, koreksi absensi, reimbursement, WFH, kasbon, dan tukar shift. Nominal, rekan tukar, serta lampiran disimpan sebagai kolom/relasi tervalidasi (`/app/requests`).
- `GET /v1/approvals`, `POST /v1/requests/{id}/manager-decision`, `POST /v1/requests/{id}/admin-decision` — Kotak masuk persetujuan berjenjang (`/app/approvals`).
- `GET /v1/notifications`, `GET /v1/notifications/unread-count`, `POST /v1/notifications/{id}/read` — Pusat notifikasi (`/app/notifications`).

## 6. Object Storage (`local` / `r2` / `s3` / `minio`)
- `GET /v1/storage/config` — Status konfigurasi driver Object Storage aktif.
- `GET/POST /v1/files`, `GET /v1/files/{id}/download`, `POST /v1/files/{id}/delete` — Unggah, unduh (direct stream atau SigV4 presigned URL), dan hapus berkas perusahaan.

## 7. Payroll, Laporan & Modul Operasional SDM
- `GET /v1/admin/overview` — Ringkasan metrik real-time dashboard admin (`/admin/dashboard`).
- `GET /v1/admin/attendances` — Data kehadiran seluruh karyawan perusahaan (`/admin/attendances`).
- `GET /v1/admin/activity-logs` — Log audit aktivitas perusahaan (`/admin/activity-logs`).
- `GET /v1/payrolls` & `POST /v1/admin/payrolls` — Periode penggajian & slip gaji (`/admin/payrolls`, `/payroll`).
- `GET/POST /v1/workspace-records`, `POST /v1/workspace-records/{id}/status`, `POST /v1/workspace-records/{id}/delete` — Penyimpanan transisional untuk sisa modul legacy. Akses admin memerlukan `admin.workspace_records.manage`; data karyawan privat terhadap pengguna lain. Pengajuan, pengumuman, aset, payroll, master data, KPI, dokumen HR, dan checklist mempunyai API domain sendiri.
- `GET /v1/biometrics/profile`, `POST /v1/biometrics/enroll`, `POST /v1/biometrics/delete` — Profil biometrik privat. Pendaftaran memerlukan receipt provider bertanda tangan yang terikat tenant/pengguna/hash foto, kedaluwarsa 10 menit dan sekali pakai. Tanpa provider, pendaftaran ditolak. Descriptor browser tidak dipakai untuk otorisasi presensi.
- `GET /v1/providers/status`, `POST /v1/providers/challenges` — Integrasi bridge biometrik dan verifikasi tanda tangan PDF. Konfigurasi dan protokol di [provider-integrations.md](provider-integrations.md).
- `GET/POST /v1/admin/integrations` — Super admin/owner mengelola provider dinamis per perusahaan, kredensial terenkripsi, aktivasi/rotasi dan pembatalan challenge lama. Format bank dan kebijakan payroll juga hanya dapat diubah owner.
- `GET/POST /v1/admin/payroll-policy` — Aturan payroll berversi dengan snapshot perhitungan. `GET /v1/admin/request-payments` dan `GET/POST /v1/requests/{id}/payments` mencatat pencairan dan cicilan eksternal.
- `GET /v1/admin/payroll-bank`, `POST /v1/admin/payroll-bank/accounts`, `POST /v1/admin/payroll-bank/settings`, `POST /v1/admin/payrolls/{id}/bank-export` — Rekening privat, pemetaan kolom CSV dan ekspor snapshot payroll terpublikasi.
- `GET/POST /v1/admin/work-calendar` — Kalender perhitungan cuti. Kuota mendukung carry-over yang ditetapkan admin dan snapshot hari terpakai.
- `POST /v1/admin/attendances/import` — Impor JSON atomik dengan dry-run dan izin `admin.attendances.manage`. Daftar log menerima `offset`, `limit`, dan `search`.
- `GET /v1/attendance/policy` — Hak WFH hari ini, dihitung dari pengajuan yang sudah disetujui. Mode `wfh` tetap memerlukan GPS dan dicatat terpisah dari presensi QR di lokasi.
- `GET /v1/performance-cycles`, `POST /v1/admin/performance-cycles` — Siklus KPI, penutupan terkunci, bobot target dan skor capaian.
- `GET/POST /v1/admin/assets/{id}/valuation` — Nilai perolehan/sisa dan depresiasi garis lurus per bulan penuh.

## 8. Modul Domain Terintegrasi
- `GET /v1/announcements`, `POST /v1/announcements/{id}/read`, `GET/POST /v1/admin/announcements` — Publikasi dan tanda baca.
- `GET /v1/assets`, `POST /v1/assets/{id}/acknowledge`, `GET/POST /v1/admin/assets`, `POST /v1/admin/assets/{id}/assign`, `POST /v1/admin/assets/{id}/return` — Serah terima inventaris.
- `GET /v1/organization/reference-data`, `GET/POST /v1/admin/organization/reference-data` — Master data organisasi.
- `GET /v1/leave-balances`, `GET/POST /v1/admin/organization/leave-entitlements` — Saldo dan pengecualian jatah cuti.
- `GET /v1/performance-goals`, `POST /v1/performance-goals/{id}/submit`, `GET/POST /v1/admin/performance-goals`, `POST /v1/admin/performance-goals/{id}/review` — Target dan review KPI.
- `GET/POST /v1/hr-documents`, `GET /v1/admin/hr-documents`, `POST /v1/admin/hr-documents/{id}/decision` — Permintaan dan penerbitan PDF resmi.
- `GET /v1/hr-document-templates`, `POST /v1/admin/hr-document-templates` — Template berversi, snapshot variabel dan cetak/simpan PDF melalui browser.
- `GET /v1/hr-checklists`, `POST /v1/hr-checklists/{id}/items/{item_id}`, `GET/POST /v1/admin/hr-checklists`, `POST /v1/admin/hr-checklists/{id}/complete` — Onboarding/offboarding.
- `GET/POST /v1/admin/hr-checklist-templates` — Template checklist reusable; perubahan template tidak mengganti langkah checklist yang sudah dibuat.
