# Panduan Deployment & Podman Machine

PasPapan dirancang agar Frontend (`apps/web`) dan Backend (`apps/api`) berjalan sebagai service terpisah dengan file `.env` masing-masing dan dibungkus menggunakan **Podman Machine** (`compose.yaml`).

## 1. Menjalankan dengan Podman Machine (macOS / Linux)

```bash
# Inisialisasi Podman Machine (4 CPU, 4 GB RAM, 30 GB Disk) & buat .env default
bun run podman:init

# Build image & jalankan container paspapan-api dan paspapan-web (terhubung ke PostgreSQL 18 existing)
bun run podman:up

# Lihat status container
bun run podman:status

# Lihat log real-time
bun run podman:logs
```

### Rincian Container (`compose.yaml`)
1. **`paspapan-api`**: Dibangun dari `apps/api/Containerfile`, membaca `apps/api/.env`, terhubung ke instance PostgreSQL 18 existing (`host.containers.internal:5432` atau `DATABASE_URL`), otomatis menjalankan migrasi saat `AUTO_MIGRATE=true`, memverifikasi `API_INTERNAL_KEY`, dan menyimpan objek lokal di volume `paspapan_objects`. Port host di-bind khusus ke loopback (`127.0.0.1:3000:3000`).
2. **`paspapan-web`**: Dibangun dari `apps/web/Containerfile` (`@sveltejs/adapter-node`), membaca `apps/web/.env`, dan meneruskan `/v1/*` ke `http://api:3000` dengan header `x-paspapan-service-key`.

## 2. Deployment Produksi dengan Cloudflare R2 / AWS S3

Pada `apps/api/.env` di server produksi:
```env
API_BIND=0.0.0.0:3000
DATABASE_URL=postgres://user:password@db-host:5432/paspapan
APP_ORIGIN=https://app.perusahaan.com
API_INTERNAL_KEY=<random-64-char-hex-shared-with-web>
SESSION_SECRET=<random-64-char-hex-pepper>
SESSION_COOKIE_SECURE=true
AUTO_MIGRATE=true

STORAGE_DRIVER=r2
STORAGE_BUCKET=paspapan-prod
STORAGE_REGION=auto
STORAGE_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
STORAGE_ACCESS_KEY_ID=<r2_access_key>
STORAGE_SECRET_ACCESS_KEY=<r2_secret_key>
STORAGE_FORCE_PATH_STYLE=true
```

Pada `apps/web/.env` di server produksi:
```env
WEB_HOST=0.0.0.0
WEB_PORT=4173
ORIGIN=https://app.perusahaan.com
API_PROXY_TARGET=http://api:3000
API_INTERNAL_KEY=<random-64-char-hex-shared-with-api>
```
