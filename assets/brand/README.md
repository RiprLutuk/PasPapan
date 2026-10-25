# Brand Assets (`assets/brand`)

Sumber master SVG untuk identitas visual **PasPapan** (`assets/brand/mark.svg`).

## Regenerasi Aset

Jalankan dari root repository:
```bash
bun run brand:build
bun run brand:check
```

Script `scripts/build-brand-assets.mjs` mengekspor ikon PWA, favicon, wordmark (dengan font `Plus Jakarta Sans` yang di-outline), dan social card secara deterministik ke `apps/web/static/brand/`.
