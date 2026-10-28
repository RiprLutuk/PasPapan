# Deployment PasPapan

Panduan ini memisahkan **FE di Cloudflare Pages** dari **API dan worker di VPS**. Semua contoh bersifat manual; workflow GitHub saat ini melakukan pemeriksaan, bukan deployment BE.

## Topologi dan batas konfigurasi

```text
Browser / PWA
  → https://paspapan.demo.pandanteknik.com atau https://paspapan.pages.dev
  → Pages Functions: proxy /v1/* dan cookie sesi
  → https://api.paspapan.pandanteknik.com
  → Tunnel / reverse proxy HTTPS → 127.0.0.1:3000 di VPS
  → PostgreSQL dan penyimpanan privat

Worker VPS → database yang sama → outbox / pengumuman / mailer
```

| Konfigurasi | Lokasi | Keterangan |
| --- | --- | --- |
| `DATABASE_URL` | API dan worker | Tidak masuk browser/Pages |
| `API_INTERNAL_KEY` | API dan runtime Pages Functions | Nilai sama; secret server, bukan `PUBLIC_*` |
| `SESSION_SECRET` | API | Stabil antar deployment; perubahan dapat membatalkan sesi |
| `INTEGRATION_MASTER_KEY` | API | Base64 32 byte; diperlukan untuk kredensial provider terenkripsi |
| `APP_ORIGIN` | API | Daftar origin FE, tanpa path |
| `API_PROXY_TARGET` | Runtime Pages Functions | URL HTTPS API yang dapat dicapai dari Cloudflare |
| `SESSION_COOKIE_SECURE` | API | `true` untuk production HTTPS |
| Storage/mailer | API dan worker sesuai kebutuhannya | Volume/bucket persisten; driver `local` bukan pengiriman email internet |

Gunakan hostname Anda sendiri jika mengadaptasi panduan. FE tidak perlu menjalankan Node/container web di VPS bila sudah berada di Pages.

## 1. Menyiapkan API dan worker pada VPS Linux

Contoh berikut memakai Podman rootless dengan `--network host`, sesuai topologi VPS yang menyimpan PostgreSQL pada port host. Ini **berbeda** dari jaringan Compose. Jalankan blok shell dengan Bash di VPS.

### Checkout dan konfigurasi privat

Siapkan Git, Podman, sertifikat CA, dan `curl`. Database harus sudah tersedia. Clone repository ke direktori kerja operator jika belum ada, lalu gunakan branch `rewrite/svelte-rust`.

Simpan konfigurasi di luar checkout agar tidak tertimpa saat update:

```bash
mkdir -p "$HOME/.config/paspapan"
test -f "$HOME/.config/paspapan/api.env" || cp apps/api/.env.example "$HOME/.config/paspapan/api.env"
chmod 700 "$HOME/.config/paspapan"
chmod 600 "$HOME/.config/paspapan/api.env"
```

Edit `api.env`; nilai `<...>` berikut adalah placeholder yang harus diganti:

```dotenv
API_BIND=127.0.0.1:3000
DATABASE_URL=postgres://<user>:<password>@127.0.0.1:5433/paspapan
APP_ORIGIN=https://paspapan.pages.dev,https://paspapan.demo.pandanteknik.com
API_INTERNAL_KEY=<random-secret-yang-sama-dengan-Pages>
SESSION_SECRET=<random-secret-berbeda>
INTEGRATION_MASTER_KEY=<base64-32-byte-acak>
SESSION_COOKIE_SECURE=true
AUTO_MIGRATE=false
RUST_LOG=info
STORAGE_DRIVER=local
STORAGE_LOCAL_ROOT=/var/lib/paspapan/objects
MAIL_DRIVER=local
MAIL_LOCAL_SPOOL=/var/lib/paspapan/objects/mail
```

Port `5433` adalah contoh deployment existing, bukan port wajib PostgreSQL. Sesuaikan host/port dengan database Anda. Untuk password dengan karakter khusus, gunakan encoding URL yang sesuai.

Untuk membuat secret baru **sekali saat bootstrap**, jalankan `openssl rand -hex 32` secara terpisah untuk service key dan session secret. Master key dibuat dengan `openssl rand -base64 32`. Simpan hasilnya ke pengelola secret/config privat, bukan Git atau chat. Jangan mengganti master key saat deploy ulang: kredensial provider yang sudah terenkripsi memerlukan key yang sama. Backup key secara terpisah dari database.

`MAIL_DRIVER=local` pada contoh menyimpan email ke volume. Sebelum memakai email internet/reset password lewat inbox, konfigurasikan driver mail yang benar serta jalankan worker. Opsi storage dan mail ada di `.env.example` dan [panduan operasional](operations.md).

### Build image dari commit yang jelas

Dari checkout repository di VPS:

```bash
git fetch origin
revision=$(git rev-parse origin/rewrite/svelte-rust)
release=$(git rev-parse --short=12 "$revision")
release_dir="$HOME/paspapan/releases/$release"
image="localhost/paspapan-api:$release"
mkdir -p "$release_dir"
git archive "$revision" | tar -x -C "$release_dir"
podman build --jobs 2 -t "$image" -f "$release_dir/apps/api/Containerfile" "$release_dir"
```

Image berisi API, worker dan CLI migrasi. Build tidak memerlukan `.env` production; jangan memasukkan secret ke build argument. Build dahulu sambil API lama tetap berjalan. Pastikan commit tersebut sudah melalui pemeriksaan sebelum dipromosikan.

### Backup sebelum perubahan skema/update

Untuk database pada container existing `paspapan-postgres`:

```bash
umask 077
backup_dir="$HOME/paspapan/backups/$release"
mkdir -p "$backup_dir"
podman exec paspapan-postgres pg_dump -U paspapan -d paspapan -Fc > "$backup_dir/database.dump"
podman exec -i paspapan-postgres pg_restore -l < "$backup_dir/database.dump" > /dev/null
```

Sesuaikan nama container, user dan database. Untuk database eksternal, gunakan alat backup operator yang sesuai. Simpan juga snapshot object storage/volume dan konfigurasi privat. Dump database saja tidak memuat foto, lampiran atau master key.

Pada instalasi baru yang databasenya kosong, backup existing tidak diperlukan. Pada production existing, pastikan backup valid sebelum migrasi. Jangan menjalankan `seed_local` pada production.

### Migrasi dan startup pertama

Variabel `image`, `revision` dan `release` di bawah berasal dari blok build. Gunakan volume `paspapan_storage` secara konsisten untuk API dan worker:

```bash
config="$HOME/.config/paspapan/api.env"
podman volume exists paspapan_storage || podman volume create paspapan_storage
podman run --rm --network host --env-file "$config" "$image" paspapan-migrate
```

Jika container bernama `paspapan-api`/`paspapan-worker` sudah ada, lakukan [prosedur update](#4-update-dan-rollback) terlebih dahulu. Blok berikut untuk nama container yang belum dipakai:

```bash
podman run -d --name paspapan-api --restart unless-stopped --network host \
  --label "org.opencontainers.image.revision=$revision" \
  --env-file "$config" \
  -v paspapan_storage:/var/lib/paspapan/objects \
  --health-cmd 'curl -fsS http://127.0.0.1:3000/health' \
  --health-interval 30s --health-retries 3 --health-start-period 20s \
  "$image"
```

Tunggu health API merespons `{"status":"ok"}` sebelum menjalankan worker:

```bash
curl -fsS http://127.0.0.1:3000/health
podman run -d --name paspapan-worker --restart unless-stopped --network host \
  --label "org.opencontainers.image.revision=$revision" \
  --env-file "$config" \
  -v paspapan_storage:/var/lib/paspapan/objects \
  "$image" paspapan-worker
podman ps --filter name=paspapan
podman logs --tail 30 paspapan-api
podman logs --tail 30 paspapan-worker
```

Gunakan volume yang sama dengan deployment lama. Mengganti nama volume dapat membuat lampiran tampak hilang walaupun database tetap ada. Contoh `--network host` mengakses database host melalui `127.0.0.1`; jangan memakainya begitu saja untuk Podman Machine macOS.

Atur startup setelah reboot memakai pengelolaan service host, misalnya systemd/Quadlet dan konfigurasi rootless yang sesuai. Opsi `--restart` bukan pengganti pemeriksaan bahwa aplikasi, database dan Tunnel benar-benar kembali setelah reboot.

### Publikasikan API HTTPS

Arahkan reverse proxy atau Cloudflare Tunnel untuk `api.paspapan.pandanteknik.com` ke `http://127.0.0.1:3000`. API tidak perlu membuka port 3000 untuk semua jaringan publik. Database dan direktori storage juga tidak perlu dipublikasikan.

```bash
curl -fsS https://api.paspapan.pandanteknik.com/health
```

`/health` memeriksa respons API, bukan membuktikan semua integrasi vendor berfungsi. Gunakan panel sistem dan uji alur aplikasi setelah deploy.

## 2. Deploy FE ke Cloudflare Pages

### Git integration

Buka proyek **PasPapan** di Workers & Pages. Hubungkan repository jika belum, lalu gunakan konfigurasi berikut:

| Pengaturan | Nilai untuk repo ini |
| --- | --- |
| Production branch | `rewrite/svelte-rust` |
| Root directory | `apps/web` |
| Build command | `bun install --frozen-lockfile && bun run build` |
| Output directory | `.svelte-kit/cloudflare` |
| Build variables | `ADAPTER=cloudflare`, `NODE_VERSION=24`, `BUN_VERSION=1.3.6` atau versi Bun repository yang dipilih |

Jika root proyek sengaja dibiarkan di root repository, **semua path harus ikut berubah**: build `bun install --cwd apps/web --frozen-lockfile && ADAPTER=cloudflare bun run build`, output `apps/web/.svelte-kit/cloudflare`. Jangan mencampurkan kedua konfigurasi root/output tersebut. File `wrangler.jsonc` berada di `apps/web`, sehingga konfigurasi root `apps/web` lebih langsung.

Runtime Pages Functions membutuhkan:

- Variable `API_PROXY_TARGET=https://api.paspapan.pandanteknik.com`.
- Secret `API_INTERNAL_KEY` yang sama dengan API VPS.
- Konfigurasi Production dan Preview yang sesuai. Origin preview hanya bekerja bila diizinkan API.

`ORIGIN` boleh tidak diisi pada Pages karena proxy dapat memakai origin request. Bila diisi, nilainya harus sesuai domain yang diizinkan API. Secret runtime tidak boleh dipindah menjadi variabel `PUBLIC_*` atau ditanam dalam bundle browser.

`apps/web/wrangler.jsonc` sudah memuat `nodejs_compat`. Adapter Cloudflare dipilih melalui `ADAPTER=cloudflare` atau lingkungan `CF_PAGES`. Pertahankan bundle Functions `_worker.js` yang dihasilkan adapter; jangan hanya mengunggah isi folder aset statis.

Rujukan resmi: [build configuration](https://developers.cloudflare.com/pages/configuration/build-configuration/), [runtime bindings dan secrets](https://developers.cloudflare.com/pages/functions/bindings/).

### Deploy manual lewat CLI

Untuk operator yang sudah login Wrangler dan memiliki akses proyek:

```bash
cd apps/web
bun install --frozen-lockfile
ADAPTER=cloudflare bun run build
bunx wrangler pages deploy .svelte-kit/cloudflare --project-name paspapan --branch rewrite/svelte-rust
bunx wrangler pages deployment list --project-name paspapan
```

Deploy manual memakai hasil build lokal; pastikan source-nya commit yang dimaksud. Periksa hasil deployment, bukan hanya keberhasilan push atau URL yang mulai dibuat.

### Domain kustom

Tambahkan `paspapan.demo.pandanteknik.com` pada **Custom domains** proyek Pages dan ikuti verifikasi DNS-nya. Tambahkan origin HTTPS domain tersebut ke `APP_ORIGIN` API. Domain FE tetap mengarah ke Pages, bukan ke container web VPS. Lihat [panduan domain resmi](https://developers.cloudflare.com/pages/configuration/custom-domains/).

Cloudflare Access adalah lapisan tambahan di luar login PasPapan. Bila dipakai, cocokkan aplikasi Access dan hostname/callback-nya. Jangan memaksa semua endpoint API melewati redirect login HTML tanpa menyiapkan autentikasi server proxy yang sesuai.

## 3. Verifikasi setelah deploy

```bash
curl -fsS https://api.paspapan.pandanteknik.com/health
curl -sS -o /dev/null -w '%{http_code}\n' https://paspapan.pages.dev/login
curl -sS -o /dev/null -w '%{http_code}\n' https://paspapan.demo.pandanteknik.com/login
curl -sS -o /dev/null -w '%{http_code}\n' https://paspapan.demo.pandanteknik.com/v1/auth/session
```

Ekspektasi tanpa sesi: login `200`, session API `401`. Redirect Access atau penolakan WAF harus ditangani sebagai lapisan berbeda. Respons `401` membuktikan guard dapat dijangkau; itu belum membuktikan login pengguna berhasil.

Lanjutkan uji browser memakai akun yang memang diizinkan: login, reload halaman terautentikasi, penolakan halaman tanpa izin, unggah/unduh lampiran dan satu alur operasional yang relevan. Jangan membagikan password atau cookie dalam log verifikasi.

Bandingkan `/_app/version.json` domain kustom dengan URL deployment Pages untuk memastikan keduanya menunjuk build yang sama. Pada VPS, periksa image/tag dan label revisi API/worker. Kedua service harus memakai rilis yang kompatibel dengan FE.

## 4. Update dan rollback

1. Build image bertag commit baru sambil service lama masih aktif.
2. Backup dan tinjau perubahan migrasi. Migrasi SQL yang sudah terpasang tidak diedit ulang.
3. Jalankan CLI migrasi baru. Jika gagal, hentikan rollout dan periksa error.
4. Simpan container lama dengan nama lain, kemudian jalankan blok startup menggunakan image/env/volume yang benar:

   ```bash
   podman stop paspapan-worker paspapan-api
   podman rename paspapan-api "paspapan-api-before-$release"
   podman rename paspapan-worker "paspapan-worker-before-$release"
   ```

5. Verifikasi API sehat dan worker benar-benar berjalan. Metode ganti container ini mempunyai jeda layanan; bukan deployment zero-downtime.
6. Simpan image/container lama sampai verifikasi aplikasi selesai. Jangan menghapus volume ketika membersihkan container lama.

Jika aplikasi baru gagal, hentikan container baru sebelum menghidupkan container lama. Contoh setelah nama lama disimpan seperti di atas:

```bash
podman stop paspapan-worker paspapan-api
podman rm paspapan-worker paspapan-api
podman rename "paspapan-api-before-$release" paspapan-api
podman rename "paspapan-worker-before-$release" paspapan-worker
podman start paspapan-api paspapan-worker
```

Rollback image **tidak membatalkan migrasi SQL**. Pastikan skema tetap kompatibel dengan binary lama; pemulihan dump database adalah operasi terpisah yang memerlukan rekonsiliasi data sejak backup. Jangan otomatis menimpa database production untuk memperbaiki error UI.

Untuk FE, promosikan deployment Pages sukses sebelumnya bila perlu. GitHub CI dan Pages merupakan jalur berbeda; `.github/workflows/ci.yml` belum memiliki deployment VPS otomatis.

## Opsi lokal dengan Podman Compose

Dari root repository:

```bash
bun run podman:init
# Edit apps/api/.env dan apps/web/.env sesuai jaringan container.
bun run podman:up
bun run podman:status
bun run podman:logs
bun run podman:down
```

- Compose membuat API, worker dan web; database tidak dibuat oleh file ini.
- API/worker perlu `DATABASE_URL` yang dapat dicapai dari container. `host.containers.internal` disediakan sebagai alamat host, tetapi PostgreSQL harus menerima koneksi dari jaringan tersebut. `127.0.0.1` di container bukan host.
- Web container meneruskan request ke `http://api:3000`; API menerima port host lokal 3000. UI ada di 4173. `WEB_HOST`/`WEB_PORT` dipakai Vite; adapter Node memakai `HOST`/`PORT`, yang sudah diatur oleh Compose.
- Gunakan HTTPS dan `ORIGIN` yang benar bila adapter Node ini dipakai di luar lokal. Bind/paparan port web Compose perlu disesuaikan untuk jaringan deployment Anda.
- `podman:down` menghentikan/menghapus container jaringan aplikasi. Jangan menambahkan penghapusan volume persisten tanpa tujuan pemulihan yang jelas.
