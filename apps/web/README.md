# PasPapan Web Service (`apps/web`)

Layanan frontend **PasPapan** berbasis **SvelteKit 3 (Svelte 5 Runes)** dan `@sveltejs/adapter-node`.

## Konfigurasi Environment (`apps/web/.env`)

Salin dari `.env.example`:
```bash
cp .env.example .env
```

Variabel yang tersedia:
- `WEB_HOST` — host dev/preview server (default: `127.0.0.1`)
- `WEB_PORT` — port dev/preview server (default: `4173`)
- `ORIGIN` — origin publik aplikasi web untuk verifikasi CSRF/proxy (contoh: `http://127.0.0.1:4173`)
- `API_PROXY_TARGET` — URL internal service Rust API untuk proxy `/v1/*` dan `/health` (default: `http://127.0.0.1:3000`, atau `http://api:3000` di dalam Podman network)
- `PUBLIC_API_BASE_URL` — opsional jika browser memanggil origin API secara langsung (kosongkan untuk menggunakan proxy same-origin `/v1`)

## Perintah

```bash
bun install
bun run dev      # Jalankan Vite dev server
bun run check    # Jalankan svelte-kit sync & svelte-check
bun run build    # Build production bundle ke ./build
bun run start    # Jalankan production server (node build)
```
