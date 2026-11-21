# Rust Anything: Laravel parity workflow

PasPapan sudah berjalan dengan SvelteKit, Rust API, Rust worker, dan PostgreSQL.
Dokumen ini memakai `rust-anything` sebagai **evidence gate** saat memeriksa atau
menutup parity dengan implementasi Laravel historis. Tool ini bukan compiler
Laravel/Livewire ke Rust.

## Batas tool saat ini

`rust-anything analyze` dapat membaca PHP dan memetakan risiko migrasi. Namun
bridge unit PHP ke Rust/FFI belum tersedia. Subcommand `app` pada versi saat ini
berfokus pada CLI, bukan merekam HTTP server Laravel secara langsung.

Karena itu, sumber kebenaran parity PasPapan adalah:

1. kontrak HTTP Rust di `apps/api/src/main.rs` dan test integration;
2. workflow Svelte di `apps/web`;
3. matriks domain di [rewrite-parity.md](rewrite-parity.md);
4. test/fixture yang merepresentasikan perilaku Laravel sebelum domain ditutup.

## Analisis Laravel (read-only)

Jalankan pada checkout Laravel lokal, bukan pada VPS dan bukan dengan
credential production:

```sh
rust-anything analyze ../paspapan-lara --lang php --whole-app \
  --out /tmp/paspapan-laravel-rust-anything-analysis.md
```

Hasil analisis dipakai untuk mencari dependensi Laravel yang belum tercakup,
bukan sebagai urutan port literal. Model Eloquent, Livewire, Blade, middleware,
dan queue Laravel secara alami akan terlihat sebagai blocked; penggantinya
sudah berada pada Axum, SQLx, SvelteKit, dan worker Rust.

## Gate untuk domain yang belum tersertifikasi

Untuk setiap gap parity:

1. tulis kasus acceptance HTTP dan RBAC di `apps/api/tests/` atau script
   `scripts/test-api-integration.ts`;
2. tambah unit test untuk aturan bisnis murni di modul Rust terkait;
3. implementasikan API dan halaman Svelte tanpa fallback Laravel;
4. jalankan gate berikut pada database rewrite terpisah:

```sh
cargo fmt --check
cargo clippy --workspace -- -D warnings
cargo test --workspace
bun run check
bun run test:web
bun run build
bun run test:integration
```

5. perbarui [rewrite-parity.md](rewrite-parity.md) hanya setelah semua gate
   lulus.

`rust-anything record`, `app record`, dan replay dapat menjalankan side effect.
Jangan pernah menjalankannya dengan database Laravel production, SMTP
production, object storage production, atau token production.

## Bukan bagian dari otomatisasi

Migrasi data Laravel membutuhkan mapping eksplisit dan preflight baca-saja;
lihat [legacy-data-migration.md](legacy-data-migration.md). Password legacy,
sesi, token, bukti biometrik, data generik tanpa makna domain, dan kredensial
provider tidak boleh disalin sebagai data yang otomatis dipercaya Rust.
