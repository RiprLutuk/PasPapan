# Release Checklist (SvelteKit + Rust + Podman)

Gunakan daftar periksa ini sebelum merilis versi baru **PasPapan** ke lingkungan produksi:

## 1. Kualitas Kode & Pengujian
- [ ] `cargo test --workspace` lulus tanpa error.
- [ ] `bun run check` (`svelte-check`) menghasilkan `0 errors and 0 warnings`.
- [ ] `bun run build` berhasil membangun bundle `@sveltejs/adapter-node`.
- [ ] `bun run test:web` lulus seluruh pengujian desain, akses RBAC, brand assets, dan batasan cache PWA.

## 2. Konfigurasi Service & Environment
- [ ] `apps/api/.env` memiliki `DATABASE_URL`, `APP_ORIGIN` (HTTPS origin produksi), dan `SESSION_COOKIE_SECURE=true`.
- [ ] `apps/api/.env` mengonfigurasi Object Storage (`STORAGE_DRIVER=r2` / `s3` / `minio` atau `local` dengan persistent volume).
- [ ] `apps/web/.env` memiliki `ORIGIN` dan `API_PROXY_TARGET` yang mengarah ke service `paspapan-api`.

## 3. Database & Migrasi
- [ ] Backup database PostgreSQL telah diambil sebelum deployment.
- [ ] Migrasi `apps/api/migrations/0001` s/d `0008` telah dijalankan (`paspapan-migrate` atau `AUTO_MIGRATE=true`).

## 4. Container & Runtime
- [ ] Container `paspapan-db`, `paspapan-api`, dan `paspapan-web` berjalan sehat (`podman compose ps` / `GET /health` mengembalikan `{"status":"ok"}`).
