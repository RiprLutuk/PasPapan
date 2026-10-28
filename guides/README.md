# Panduan PasPapan

Mulai dari [README utama](../README.md) untuk setup lokal, struktur repository dan alur penggunaan. Runtime saat ini adalah SvelteKit + Rust; FE production berada di Cloudflare Pages, API/worker di VPS, dengan PostgreSQL terpisah.

| Panduan | Dipakai untuk |
| --- | --- |
| [Arsitektur](architecture.md) | Pembagian service, modul, proxy dan tenant |
| [Fitur dan endpoint](features.md) | Menemukan API dan cakupan modul yang diimplementasikan |
| [Deployment](deployment.md) | Pages, VPS, env privat, API/worker, backup, verifikasi dan rollback |
| [Operasional dan storage](operations.md) | Driver berkas, migrasi dan pemeriksaan lokal |
| [RBAC](rbac-audit.md) | Base role, custom role, deny/grant dan batas akses perusahaan |
| [Integrasi provider dan payroll](provider-integrations.md) | Kontrak verifier, master key, kebijakan payroll, ekspor bank dan PDF |
| [Dashboard dan telemetry](dashboard-telemetry.md) | Definisi metrik, periode pengukuran, privasi dan izin panel |
| [Keamanan](security-checklist.md) | Kontrol autentikasi, sesi, CSRF dan data privat |
| [Parity rewrite](rewrite-parity.md) | Cakupan rewrite, validasi dan batas kesetaraan historis |
| [Migrasi legacy](legacy-data-migration.md) | Preflight baca-saja dan kebutuhan mapping sebelum impor Laravel |
| [Sistem desain](ui/DESIGN-SYSTEM.md) | Layout compact, komponen, tema, font lokal dan ikon |
| [Brand assets](../assets/brand/README.md) | Sumber logo dan ekspor aset |

Dokumentasi konfigurasi memakai placeholder. Kredensial production tetap berada di konfigurasi privat masing-masing service, bukan pada panduan atau repository.
