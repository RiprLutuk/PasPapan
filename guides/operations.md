# Panduan Operasional & Object Storage

## 1. Konfigurasi Object Storage (`local` / `r2` / `s3` / `minio`)

Service `apps/api` mewajibkan inisialisasi `ObjectStorage` saat startup (`crates/adapters/src/storage.rs`). Pilih driver melalui `STORAGE_DRIVER` di `apps/api/.env`:

### Mode Lokal (`STORAGE_DRIVER=local`)
Digunakan untuk pengembangan lokal atau deployment single-node dengan volume Podman:
```env
STORAGE_DRIVER=local
STORAGE_LOCAL_ROOT=./var/objects
```

### Mode Cloudflare R2 (`STORAGE_DRIVER=r2`)
```env
STORAGE_DRIVER=r2
STORAGE_BUCKET=paspapan
STORAGE_REGION=auto
STORAGE_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
STORAGE_ACCESS_KEY_ID=<access_key_id>
STORAGE_SECRET_ACCESS_KEY=<secret_access_key>
STORAGE_FORCE_PATH_STYLE=true
```

### Mode AWS S3 / MinIO (`STORAGE_DRIVER=s3` atau `minio`)
```env
STORAGE_DRIVER=s3
STORAGE_BUCKET=paspapan
STORAGE_REGION=ap-southeast-1
STORAGE_ENDPOINT=https://s3.ap-southeast-1.amazonaws.com
STORAGE_ACCESS_KEY_ID=<access_key_id>
STORAGE_SECRET_ACCESS_KEY=<secret_access_key>
STORAGE_FORCE_PATH_STYLE=true
```

## 2. Migrasi Database & Seeding Lokal

Migrasi SQL berada di `apps/api/migrations/` (`0001_identity.sql` s/d `0008_workforce_modules.sql`):
```bash
# Menjalankan migrasi secara eksplisit
cargo run -p paspapan-api --bin migrate

# Membuat akun demo lokal (superuser@local.test, admin@local.test, user@local.test)
cargo run -p paspapan-api --bin seed-local
```
Jika `AUTO_MIGRATE=true` di `apps/api/.env`, binary `paspapan-api` otomatis menjalankan migrasi saat startup pada database baru.

## 3. Healthcheck & Bukti Verifikasi

- Endpoint kesehatan API: `GET http://127.0.0.1:3000/health` $\rightarrow$ `{"status":"ok"}`
- Kumpulkan bukti verifikasi lokal (`cargo test`, `svelte-check`, `vite build`, `node --test`):
  ```bash
  bun run evidence:review
  ```
