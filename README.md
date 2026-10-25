# PasPapan — Workforce Management Platform

**Hadir. Terhubung. Tertata.**

PasPapan adalah platform manajemen kehadiran (GPS + Dynamic QR), penjadwalan shift, pengajuan & persetujuan berjenjang, direktori SDM, penggajian (payroll), dan operasional tim berbasis **SvelteKit 3 (Svelte 5 Runes)** dan **Rust (Axum + SQLx + PostgreSQL 18)** dengan arsitektur service terpisah yang siap dijalankan di **Podman Machine**.

---

## Arsitektur & Teknologi

| Layer | Teknologi | Direktori |
| --- | --- | --- |
| **Frontend Service (FE)** | SvelteKit 3, Svelte 5 Runes, Vite 8, `@sveltejs/adapter-node`, PWA, Plus Jakarta Sans | `apps/web/` |
| **Backend Service (BE)** | Rust (2024 Edition), Axum 0.8, SQLx 0.8, Argon2id, HMAC-SHA256 Session & Service Key | `apps/api/`, `apps/worker/`, `crates/` |
| **Database** | PostgreSQL 18 (`pgcrypto`, `pg_trgm`, `citext`, `btree_gist`, Multi-tenant `company_id` isolation) | `apps/api/migrations/` |
| **Object Storage** | Multi-driver (`local` filesystem, Cloudflare `r2`, AWS `s3`, `minio` dengan AWS SigV4) | `crates/adapters/src/storage.rs` |
| **Containerization** | Podman / Podman Machine & Compose (`paspapan-api`, `paspapan-web` terhubung ke PostgreSQL 18 existing) | `compose.yaml`, `apps/*/Containerfile` |

---

## Fitur Utama

1. **Absensi GPS + Dynamic QR (30-detik rotasi)**:
   - Validasi jarak Haversine terhadap titik lokasi kerja (`attendance_locations`) dan token QR dinamis yang kadaluarsa setiap 30 detik.
   - Pemindaian kamera langsung (`html5-qrcode`) + peta lokasi (`leaflet`) di `/app/scan` dan layar proyeksi QR di `/admin/barcodes`.
2. **Penjadwalan Shift & Kalender Kerja**:
   - Pengelolaan master shift (`/admin/attendance`), kalender penugasan shift bulanan per karyawan (`/admin/schedules`), serta jadwal & riwayat kehadiran bulanan karyawan (`/app/schedule`, `/app/history`).
3. **Pengajuan, Lampiran Object Storage & Persetujuan Berjenjang**:
   - Pengajuan Cuti (`leave`), Lembur (`overtime` dengan durasi menit), Koreksi Absensi (`attendance_correction`), dan Reimbursement (`reimbursement`) beserta unggahan berkas bukti ke Object Storage (`local` / `r2` / `s3` / `minio`) di `/app/requests`.
   - Alur persetujuan 2 tahap (`Manager` $\rightarrow$ `Admin/Owner`) dengan validasi hirarki atasan langsung, larangan self-approval, catatan keputusan, dan notifikasi otomatis di `/app/approvals`.
4. **Direktori Karyawan & Profil Akun**:
   - Pencarian server-side, filter role & status kerja, manajemen atasan langsung, optimistic locking (`version`), pencabutan sesi otomatis saat nonaktif (`/admin/employees`), serta manajemen profil, kata sandi, dan sesi perangkat aktif (`/user/profile`).
5. **RBAC Kustom & Audit Log**:
   - Pembuatan peran kustom per perusahaan, grant/deny override per anggota, dan pencatatan audit event di `/admin/access` & `/admin/activity-logs`.
6. **Payroll & Modul Operasional SDM**:
   - Pengelolaan periode penggajian dan slip gaji (`/admin/payrolls`, `/payroll`), kasbon, dokumen SDM, checklist HR, penilaian kinerja (KPI), aset, hari libur, tukar shift, WFH, dan ekspor CSV.

---

## Konfigurasi `.env` Terpisah (FE & BE)

Setiap service memiliki file `.env` tersendiri:

### 1. Backend (`apps/api/.env`)
Salin dari `apps/api/.env.example`:
```env
API_BIND=127.0.0.1:3000
DATABASE_URL=postgres://lutuk@127.0.0.1:1921/paspapan_rewrite
APP_ORIGIN=http://127.0.0.1:4173,http://localhost:4173
API_INTERNAL_KEY=paspapan-local-internal-service-key-change-me
SESSION_SECRET=paspapan-local-session-secret-change-me
RUST_LOG=info
SESSION_COOKIE_SECURE=false
AUTO_MIGRATE=true

# Object Storage (local | r2 | s3 | minio)
STORAGE_DRIVER=local
STORAGE_LOCAL_ROOT=./var/objects
STORAGE_BUCKET=paspapan
STORAGE_REGION=auto
STORAGE_ENDPOINT=https://account-id.r2.cloudflarestorage.com
STORAGE_ACCESS_KEY_ID=
STORAGE_SECRET_ACCESS_KEY=
STORAGE_PUBLIC_BASE_URL=
STORAGE_FORCE_PATH_STYLE=true
```

### 2. Frontend (`apps/web/.env`)
Salin dari `apps/web/.env.example`:
```env
WEB_HOST=127.0.0.1
WEB_PORT=4173
ORIGIN=http://127.0.0.1:4173
API_PROXY_TARGET=http://127.0.0.1:3000
API_INTERNAL_KEY=paspapan-local-internal-service-key-change-me
```

---

## Menjalankan dengan Podman Machine (Direkomendasikan)

```bash
# 1. Inisialisasi & jalankan Podman Machine serta siapkan .env masing-masing service
bun run podman:init

# 2. Build & jalankan container aplikasi (paspapan-api & paspapan-web, menggunakan PostgreSQL 18 existing)
bun run podman:up

# 3. Cek status & log container
bun run podman:status
bun run podman:logs

# 4. Hentikan container
bun run podman:down
```
- **Web (FE)**: `http://localhost:4173`
- **API (BE)**: `http://localhost:3000/health`

---

## Menjalankan Secara Lokal (Tanpa Container)

Pastikan PostgreSQL berjalan dan `apps/api/.env` serta `apps/web/.env` sudah dikonfigurasi:

```bash
# Install dependensi frontend
bun install
bun install --cwd apps/web

# Jalankan migrasi & akun demo lokal (opsional)
cargo run -p paspapan-api --bin migrate
cargo run -p paspapan-api --bin seed-local

# Jalankan service API (BE) dan Web (FE) sekaligus
bun run dev:services
```

Atau jalankan masing-masing di terminal terpisah:
```bash
bun run dev:api   # Rust API di http://127.0.0.1:3000
bun run dev:web   # SvelteKit Web di http://127.0.0.1:4173
```

---

## Pengujian & Verifikasi Kualitas

```bash
# Unit test Rust workspace (RBAC, geofence Haversine, Object Storage local/S3/R2 SigV4)
bun run test:api

# Typecheck SvelteKit (svelte-check)
bun run check

# Production build SvelteKit (@sveltejs/adapter-node)
bun run build

# Test suite desain, akses, brand assets, dan service worker PWA
bun run test:web
```

---

## Dokumentasi Lanjutan

Lihat direktori [`guides/`](guides/README.md):
- [Arsitektur Sistem](guides/architecture.md)
- [Daftar Fitur & Endpoint](guides/features.md)
- [Panduan Deployment & Podman Machine](guides/deployment.md)
- [Operasional & Object Storage](guides/operations.md)
- [Checklist Keamanan](guides/security-checklist.md)
