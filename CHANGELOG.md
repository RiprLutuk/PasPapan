# Changelog

## [5.0.0] — 2026-10-25

### Changed
- **Arsitektur Baru (SvelteKit 3 + Rust Axum + PostgreSQL 18)**: Seluruh platform dimigrasikan penuh ke SvelteKit (`apps/web`) dan Rust (`apps/api`, `crates/*`) dengan PostgreSQL 18 (`pgcrypto`, `pg_trgm`, `citext`, `btree_gist`).
- **Keamanan Komunikasi FE-BE (`API_INTERNAL_KEY` & `SESSION_SECRET`)**: Seluruh request `/v1/*` melewati proxy server SvelteKit dengan verifikasi *constant-time* header `x-paspapan-service-key` (`API_INTERNAL_KEY`) dan hashing sesi HMAC-SHA256 (`SESSION_SECRET`).
- **Pemisahan Service FE & BE**: `apps/api` dan `apps/web` memiliki konfigurasi `.env` dan `Containerfile` terpisah serta diorkestrasi menggunakan **Podman Machine** (`compose.yaml`).
- **Penghapusan Stack Legacy**: Seluruh kode PHP/Laravel, Livewire, Blade, serta modul Toko/POS & Commercial telah dihapus dari codebase.

### Added
- **Multi-Driver Object Storage (`local` / `r2` / `s3` / `minio`)**: Adapter penyimpanan objek di `crates/adapters/src/storage.rs` dengan dukungan AWS Signature Version 4 (`AWS4-HMAC-SHA256`) presigned URL serta endpoint `/v1/files` dan `/v1/storage/config`.
- **Absensi Dynamic QR 30-Detik + Geofence**: Validasi token QR dinamis yang berotasi setiap 30 detik dan radius koordinat GPS Haversine.
- **Kalender Jadwal Admin & Karyawan**: Endpoint `GET/POST /v1/admin/shift-assignments` dan `POST /v1/admin/shift-assignments/delete` terintegrasi penuh dengan kalender `/admin/schedules`.
- **Modul SDM, Payroll & Operasional End-to-End**: Endpoint `/v1/admin/overview`, `/v1/admin/attendances`, `/v1/admin/activity-logs`, `/v1/payrolls`, `/v1/admin/payrolls`, dan `/v1/workspace-records` beserta fitur ekspor CSV dan lampiran berkas.
