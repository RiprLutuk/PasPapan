# Panduan Kontribusi (Contributing)

Terima kasih telah berkontribusi pada **PasPapan** (SvelteKit + Rust Workforce Platform).

## Prasyarat Lingkungan Pengembangan

- **Rust**: `1.89+` (Edition 2024)
- **Bun**: `1.3.6+` & **Node.js**: `20+`
- **PostgreSQL**: `18+` (atau melalui **Podman Machine**)
- **Podman & Podman Compose**: untuk menjalankan stack container `db`, `api`, dan `web`

## Alur Pengembangan

1. Salin konfigurasi `.env` masing-masing service:
   ```bash
   cp apps/api/.env.example apps/api/.env
   cp apps/web/.env.example apps/web/.env
   ```
2. Jalankan layanan menggunakan Podman Machine (`bun run podman:up`) atau secara lokal (`bun run dev:services`).
3. Pastikan seluruh pemeriksaan kualitas lulus sebelum membuat Pull Request:
   ```bash
   cargo test --workspace
   bun run check
   bun run build
   bun run test:web
   ```

## Konvensi Commit

Gunakan prefix Conventional Commits:
- `feat:` fitur baru pada `apps/web`, `apps/api`, atau `crates/*`
- `fix:` perbaikan bug
- `refactor:` penataan kode tanpa mengubah perilaku eksternal
- `docs:` pembaruan dokumentasi
- `test:` penambahan atau perbaikan pengujian
- `build:` / `chore:` perubahan konfigurasi build, container, atau dependensi
