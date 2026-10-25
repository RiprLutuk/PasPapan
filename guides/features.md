# Daftar Fitur & Endpoint PasPapan

Seluruh fitur inti dan operasional SDM di PasPapan berjalan di atas **SvelteKit (`apps/web`)** dan **Rust API (`apps/api`)**:

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
- `GET/POST /v1/requests` — Pengajuan cuti, lembur, koreksi absensi, dan reimbursement beserta lampiran berkas (`/app/requests`).
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
- `GET/POST /v1/workspace-records`, `POST /v1/workspace-records/{id}/status`, `POST /v1/workspace-records/{id}/delete` — Penyimpanan data operasional untuk modul Checklist HR, Dokumen, Penilaian Kinerja (KPI), Aset, Kasbon, Tukar Shift, WFH, Hari Libur, Master Data, Kolaborasi, dan Pengaturan beserta ekspor CSV.
