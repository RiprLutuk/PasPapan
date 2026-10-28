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

Migrasi SQL berada di `apps/api/migrations/` (gunakan seluruh berkas berversi pada direktori ini):
```bash
# Menjalankan migrasi secara eksplisit
cargo run -p paspapan-api --bin migrate

# Membuat akun demo lokal (superuser@local.test, admin@local.test, user@local.test)
cargo run -p paspapan-api --bin seed_local
```
Jalankan perintah di atas dari root repository agar `apps/api/.env` terbaca. Seed hanya untuk database lokal; tidak digunakan sebagai bootstrap akun production. Jika `AUTO_MIGRATE=true`, API menjalankan migrasi yang belum terpasang pada startup untuk skema rewrite yang dikelola SQLx. Pada production, utamakan migrasi eksplisit setelah backup. Migrasi ini tidak mengimpor data Laravel.

API dan worker memakai database yang sama. `bun run dev:services` menjalankan keduanya; CLI `cargo run --bin paspapan-api` saja tidak menjalankan worker. Driver email `local` menulis spool pada path `MAIL_LOCAL_SPOOL`; email internet memerlukan driver/provider yang sesuai.

Deployment Pages/VPS dan volume persisten dijelaskan pada [runbook deployment](deployment.md).

## 3. Healthcheck & Bukti Verifikasi

- Endpoint kesehatan API: `GET http://127.0.0.1:3000/health` $\rightarrow$ `{"status":"ok"}`
- Kumpulkan bukti verifikasi lokal (`cargo test`, `svelte-check`, `vite build`, `node --test`):
  ```bash
  bun run evidence:review
  ```

### GPS acquisition

The scanner requests high-accuracy, uncached device locations. It accepts ≤20 m immediately, or finishes after 3 seconds without a better valid fix, with a 12-second overall limit. Accuracy comes from the browser/device and is never fabricated. A valid fix younger than 15 seconds can be reused on submission; older fixes require acquisition again. Server-side location radius checks remain enforced.
