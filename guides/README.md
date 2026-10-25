# Dokumentasi Teknis PasPapan

Panduan arsitektur, operasional, dan keamanan untuk platform **PasPapan (SvelteKit 3 + Rust Axum + PostgreSQL 17 + Podman Machine)**:

- **[Arsitektur Sistem (`architecture.md`)](architecture.md)** — Struktur workspace Rust & SvelteKit, batas domain, skema database, dan alur proxy FE-BE.
- **[Daftar Fitur & Endpoint (`features.md`)](features.md)** — Cakupan modul absensi QR dinamis, jadwal shift, pengajuan, RBAC, payroll, operasional SDM, dan Object Storage.
- **[Panduan Deployment & Podman Machine (`deployment.md`)](deployment.md)** — Cara menjalankan dan men-deploy service `db`, `api`, dan `web` secara terpisah menggunakan Podman Machine.
- **[Panduan Operasional & Object Storage (`operations.md`)](operations.md)** — Konfigurasi Object Storage (`local` / `r2` / `s3` / `minio`), migrasi database, dan healthcheck.
- **[Checklist Keamanan (`security-checklist.md`)](security-checklist.md)** — Standar keamanan sesi, Argon2id, isolasi multi-tenant, CSRF, dan validasi berkas.
