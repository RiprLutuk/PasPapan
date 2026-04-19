<div align="center">

<img src="./public/hero-banner.png" alt="PasPapan Hero" width="880">

# PasPapan

Platform manajemen tenaga kerja yang berorientasi produksi untuk absensi aman, payroll, approval, appraisal, aset, reporting, dan operasi pemeliharaan sistem.

[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-4E56A6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)

</div>

## Ringkasan

PasPapan adalah platform workforce berbasis Laravel 11 untuk organisasi yang membutuhkan absensi, operasional HR, persiapan payroll, dan tooling maintenance dalam satu aplikasi deployable. Sistem ini dirancang untuk pola operasional Indonesia, termasuk absensi mobile, approval cuti/lembur, komponen BPJS dan PPh21, data karyawan regional, dan alur bilingual.

Aplikasi ini menyediakan:

- panel admin web untuk data karyawan, monitoring absensi, approval, reporting, master data, payroll, aset, pengumuman, settings, dan maintenance
- pengalaman employee mobile-first untuk check-in/check-out, cuti, lembur, reimbursement, slip gaji, aset pribadi, jadwal, notifikasi, dan akses performance review
- capture absensi aman memakai GPS, visualisasi peta, bukti foto, verifikasi Face ID, dynamic QR, dukungan native scanner, dan anti-mock-location jika runtime mendukung
- proteksi Dynamic QR yang hanya menerima token terbaru yang signed, menolak token expired, dan mengonsumsi token setelah scan dynamic berhasil
- handling attachment privat untuk foto absensi dan file reimbursement agar upload sensitif tidak disajikan langsung dari public web root
- notifikasi, backup job, email delivery, task maintenance, dan rutinitas terjadwal berbasis queue
- wrapper Android berbasis Capacitor untuk tim yang membutuhkan APK installable dengan backend tetap berjalan di aplikasi web Laravel
- alur enterprise-gated untuk modul lanjutan tertentu, dengan kontrol operasional lisensi dan hardware fingerprinting

Secara desain, runtime default aplikasi ini database-centric: MySQL atau MariaDB menyimpan data aplikasi, session, cache rows, queue jobs, failed jobs, settings, notifications, riwayat backup run, dan record audit-oriented.

## Daftar Isi

- [Cakupan Produk](#cakupan-produk)
- [Tech Scan](#tech-scan)
- [Default Runtime](#default-runtime)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Pengembangan Lokal](#pengembangan-lokal)
- [Build Android dan Install APK](#build-android-dan-install-apk)
- [Deploy Produksi di VPS](#deploy-produksi-di-vps)
- [Deploy Shared Hosting](#deploy-shared-hosting)
- [Queue, Scheduler, dan Job Latar Belakang](#queue-scheduler-dan-job-latar-belakang)
- [Backup dan Operasi Maintenance](#backup-dan-operasi-maintenance)
- [Catatan Absensi, Face ID, dan Dynamic QR](#catatan-absensi-face-id-dan-dynamic-qr)
- [Operasi Enterprise](#operasi-enterprise)
- [Workflow Update](#workflow-update)
- [Testing dan Quality](#testing-dan-quality)
- [Catatan Operasional](#catatan-operasional)
- [Demo](#demo)
- [Dukung Pengembangan](#dukung-pengembangan)
- [Kredit](#kredit)

## Cakupan Produk

### Operasi admin

Area admin saat ini mencakup modul:

- dashboard dan notifikasi
- direktori karyawan
- data absensi dan reporting
- approval cuti
- manajemen lembur
- kalender libur
- shift dan jadwal kerja
- barcode dan dynamic QR dengan validasi token terbaru dan konsumsi sekali pakai
- manajemen reimbursement
- pengaturan payroll dan proses payroll
- manajemen kasbon
- pengaturan KPI
- analytics dashboard
- activity log
- pengumuman
- pengaturan sistem
- system maintenance, operasi cache, backup center, restore center, dan cleanup tools

### Self-service karyawan

Sisi pengguna saat ini mencakup:

- status absensi di beranda
- scan check-in dan check-out
- riwayat absensi
- pengajuan cuti
- pengajuan lembur
- pengajuan reimbursement
- jadwal shift
- approval tim dan riwayat approval
- akses slip gaji
- akses kasbon
- enrollment wajah
- aset pribadi
- akses penilaian performa
- notifikasi

### Kontrol absensi dan lokasi

Workflow absensi mencakup:

- QR/barcode check-in dan check-out
- dukungan static barcode untuk deployment konvensional
- dynamic QR display dengan rotating signed token
- fallback web scanner lewat browser camera APIs
- native Android scanner bridge saat berjalan di shell Capacitor
- akuisisi GPS dengan cached-location recovery dan handling permission state
- preview lokasi di peta dan link handoff ke Google Maps
- capture foto sebagai bukti absensi
- enrollment dan verifikasi Face ID jika diaktifkan
- integrasi anti-mock-location untuk runtime Android yang menyediakan status mock location

### Modul enterprise

Repository ini juga memuat modul dan penguncian enterprise untuk:

- payroll management dan komponen payroll lanjutan
- analytics dan reporting lanjutan
- workflow appraisal berbasis KPI
- lifecycle aset perusahaan
- alur import/export
- otomasi backup pada system maintenance
- validasi lisensi enterprise dan fingerprint hardware

## Tech Scan

### Backend

- Laravel `11.51.0`
- PHP `8.2+`, saat ini teruji di workspace ini pada `8.3.30`
- Livewire `3.7`
- Laravel Jetstream, Fortify, dan Sanctum untuk authentication, profile, session, dan API token
- driver queue, cache, notification, dan session berbasis database sebagai default
- runtime berorientasi MySQL atau MariaDB
- model Eloquent memakai ULID untuk user dan beberapa business record jika dikonfigurasi
- abstraksi service layer untuk attendance storage, payroll calculation, reporting, audit, licensing, dan backup operations
- route middleware untuk segmentasi admin/user, localization, activity logging, dan akses authenticated/verified

### Absensi, keamanan, dan identitas

- `face-api.js` dimuat sebagai browser-side asset untuk Face ID enrollment dan verification
- Face ID memakai TinyFaceDetector, 68-point landmarks, movement-based liveness checks, dan numeric descriptors
- token Dynamic QR memakai signed payload dengan issue time, expiry, nonce, latest-token cache validation, dan konsumsi setelah scan
- geolocation memakai browser APIs pada web dan Capacitor Geolocation pada runtime Android
- anti-mock-location memakai plugin Capacitor saat tersedia
- download attachment melewati authorization checks, bukan public file URL langsung
- active-session dan role-aware access checks melindungi flow sensitif

### Frontend

- Tailwind CSS `3.4`
- Vite `7`
- interaksi Alpine-driven melalui Blade dan screen Livewire
- Tom Select untuk select admin yang lebih kaya
- Chart.js untuk visualisasi analitik
- Leaflet dan marker clustering untuk tampilan peta
- SweetAlert2 untuk feedback interaksi
- Heroicons melalui Blade UI Kit
- view Blade mobile-first untuk flow karyawan dan surface admin responsif

### Tool dokumen dan data

- `maatwebsite/excel` untuk import/export
- `barryvdh/laravel-dompdf` untuk ekspor PDF
- `endroid/qr-code` untuk alur barcode dan QR
- `intervention/image` untuk pemrosesan gambar
- `ballen/distical` dan helper aplikasi untuk kalkulasi jarak berbasis lokasi
- Laravel language packs dan JSON translation aplikasi untuk copy UI bilingual

### Wrapper mobile

- proyek Android Capacitor di [`android`](./android)
- runtime web mengarah ke URL aplikasi Laravel yang didefinisikan di [`capacitor.config.ts`](./capacitor.config.ts)
- plugin Capacitor untuk Android app lifecycle, browser handoff, camera, geolocation, splash screen, dan barcode scanning
- optional native scanner bridge dengan browser scanner fallback
- integrasi Android mock-location plugin untuk attendance trust signal yang lebih kuat
- build APK debug/release dibuat lewat Gradle dari project `android/`

### Operasi dan background processing

- database queue connection sebagai default dengan queue name `default` dan `maintenance`
- scheduler entry untuk scheduled backup check dan fallback worker pendek untuk shared hosting
- backup center mendukung direct SQL backup, queued database backup, queued application backup, retained artifacts, dan retention cleanup
- restore center menerima signed SQL backup yang dibuat oleh aplikasi
- settings disimpan di database dan dicache untuk performa runtime
- private storage dipakai untuk artifact absensi dan reimbursement yang sensitif

### Testing dan tooling developer

- Pest `4`
- Laravel Pint
- Bun untuk dependency frontend dan build asset
- build production Vite di `public/build`
- feature test terarah untuk attendance enforcement, dynamic QR, backup jobs, maintenance security, leave approval behavior, media access, dan user flows

## Default Runtime

Default runtime project saat ini penting untuk deployment:

- database: `mysql`
- queue connection: `database`
- cache store: `database`
- session driver: `database`
- filesystem disk: `local`
- mailer: `smtp` di config app, `log` di `.env.example`
- timezone: `Asia/Jakarta`
- locale: `id`

Secara operasional, ini berarti instalasi produksi yang bersih harus mengasumsikan:

- database tidak hanya menyimpan data aplikasi, tapi juga queue, cache, failed jobs, dan session
- queue worker bukan opsi tambahan kalau ingin background work stabil
- cron scheduler wajib aktif kalau ingin backup otomatis dan maintenance terjadwal berjalan
- cache store berbasis database menjadi bagian dari validasi keamanan latest-token untuk dynamic QR

## Kebutuhan Sistem

Kebutuhan minimum yang realistis untuk produksi:

- PHP `8.2` atau lebih baru
- Composer `2.x`
- MySQL `8+` atau MariaDB setara
- Node.js atau Bun untuk build asset
- ekstensi PHP umum untuk Laravel 11 + MySQL, terutama:
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `fileinfo`
  - `gd`
  - `zip`
  - `ctype`
  - `json`
  - `tokenizer`
  - `xml`

Direkomendasikan untuk VPS:

- Nginx atau Apache dengan document root diarahkan ke `public/`
- Supervisor atau systemd untuk queue worker
- akses cron
- akses SSH

## Pengembangan Lokal

### 1. Pasang dependency

```bash
git clone https://github.com/RiprLutuk/PasPapan.git
cd PasPapan

composer install
bun install
cp .env.example .env
php artisan key:generate
```

### 2. Konfigurasi environment

Edit `.env` dan set minimal:

```dotenv
APP_NAME=PasPapan
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### 3. Setup database

Untuk setup lokal normal:

```bash
php artisan migrate
```

Kalau ingin data master contoh dan admin bootstrap untuk eksplorasi lokal:

```bash
php artisan migrate --seed
```

### 4. Jalankan aplikasi

```bash
php artisan storage:link
php artisan serve
bun run dev
```

### 5. Opsional: worker lokal untuk tes queue

```bash
php artisan queue:work --queue=maintenance,default
```

## Build Android dan Install APK

PasPapan menyediakan shell Android berbasis Capacitor untuk membungkus aplikasi web menjadi aplikasi Android.

### 1. Cek URL target aplikasi mobile

Wrapper Android akan membuka URL web yang diatur di [`capacitor.config.ts`](./capacitor.config.ts). Pastikan `server.url` mengarah ke environment yang memang ingin dibuka oleh APK.

### 2. Build bundle frontend

```bash
bun run build
```

### 3. Sinkronkan asset web ke Android

```bash
npx cap sync android
```

### 4. Build debug APK

Dari root repository:

```bash
cd android
./gradlew assembleDebug
```

Output debug APK:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

Konfigurasi Android project saat ini:

- `minSdkVersion 24`
- `compileSdkVersion 35`
- `targetSdkVersion 35`

### 5. Install APK dengan ADB

Lihat dulu device yang terhubung:

```bash
adb devices -l
```

`adb` harus tersedia di `PATH`. Jika belum, install Android Platform Tools lalu tambahkan direktori platform-tools ke `PATH`, atau panggil full path `adb` sesuai sistem operasi Anda.

Kalau status device masih `unauthorized`, buka kunci HP lalu setujui dialog USB debugging sebelum lanjut.

Install atau timpa debug APK:

```bash
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

### 6. Catatan build mobile

- kalau Gradle mengeluh soal konflik `minSdkVersion`, cek kebutuhan plugin dulu sebelum memaksa downgrade
- kalau ADB tidak bisa start di shell yang dibatasi, jalankan dari terminal lokal biasa
- kalau aplikasi Android membuka backend yang salah, cek lagi [`capacitor.config.ts`](./capacitor.config.ts) lalu jalankan ulang `npx cap sync android`
- setelah mengubah dependency Android, build ulang APK dan jangan pakai output lama

## Deploy Produksi di VPS

Ini model deployment yang paling direkomendasikan untuk PasPapan.

### 1. Siapkan server

Install:

- PHP 8.2 atau lebih baru
- Composer
- MySQL atau MariaDB
- Bun atau Node.js
- Nginx atau Apache
- Supervisor

Buat direktori deployment, misalnya:

```bash
sudo mkdir -p /var/www/paspapan
sudo chown -R $USER:$USER /var/www/paspapan
cd /var/www/paspapan
```

### 2. Ambil source code dan install dependency

```bash
git clone https://github.com/RiprLutuk/PasPapan.git .
composer install --no-dev --optimize-autoloader
bun install
cp .env.example .env
php artisan key:generate
```

### 3. Konfigurasi environment produksi

Minimal review:

```dotenv
APP_NAME=PasPapan
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

QUEUE_CONNECTION=database
SCHEDULE_QUEUE_WORKER=true
SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-mail-user
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 4. Build aplikasi

```bash
bun run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

`php artisan view:cache` sengaja tidak saya jadikan default di sini. Kalau deployment Anda memang lolos kompilasi view tanpa issue, silakan tambahkan. Kalau kena limit regex kompilasi Blade Livewire, lewati langkah itu.

### 5. Atur permission

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Sesuaikan `www-data` kalau user PHP-FPM Anda berbeda.

### 6. Arahkan web root ke `public/`

Contoh konfigurasi Nginx:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/paspapan/public;
    index index.php;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Lalu enable site dan reload Nginx.

### 7. Jalankan queue worker dengan Supervisor

Aplikasi ini memakai database queue dan mendispatch job ke `default` dan `maintenance`. Jika Supervisor sudah aktif dan stabil, set `SCHEDULE_QUEUE_WORKER=false` di `.env` supaya scheduler tidak ikut menjalankan worker pendek.

Buat `/etc/supervisor/conf.d/paspapan-worker.conf`:

```ini
[program:paspapan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/paspapan/artisan queue:work database --queue=maintenance,default --sleep=3 --tries=3 --timeout=1800
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/paspapan/storage/logs/worker.log
stopwaitsecs=3600
```

Lalu jalankan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start paspapan-worker:*
```

### 8. Pasang scheduler

Tambahkan cron:

```cron
* * * * * cd /var/www/paspapan && php artisan schedule:run >> /dev/null 2>&1
```

Ini wajib untuk dispatch backup terjadwal dari [`routes/console.php`](./routes/console.php). Scheduler juga bisa menjalankan worker queue pendek setiap menit saat `SCHEDULE_QUEUE_WORKER=true`, yang berguna untuk shared hosting dan bisa dimatikan pada VPS yang memakai Supervisor.

### 9. Checklist pasca deploy

- pastikan domain mengarah ke `public/`
- pastikan `storage/` dan `bootstrap/cache/` writable
- pastikan queue worker berjalan
- pastikan cron aktif
- lakukan test login
- test upload absensi atau download attachment
- test aksi queued seperti backup job atau notifikasi
- audit lalu ganti atau hapus akun bootstrap/demo sebelum go-live

## Deploy Shared Hosting

Shared hosting bisa dipakai, tapi hanya jika providernya memberi kontrol yang cukup.

### Kemampuan hosting yang direkomendasikan

Minimal sebaiknya tersedia:

- PHP 8.2+
- MySQL atau MariaDB
- SSH atau terminal access
- cron access
- kemampuan mengarahkan document root domain ke direktori Laravel `public/`

Kalau host Anda tidak menyediakan cron atau akses CLI, PasPapan tetap bisa hidup tapi kualitas operasionalnya turun cukup jauh, terutama untuk queue dan backup terjadwal.

### Model deployment untuk shared hosting

Alur yang paling aman untuk shared hosting:

1. build di lokal
2. upload hasil build dan source yang dibutuhkan
3. jalankan Artisan command yang wajib di server

### 1. Build di lokal

Di mesin lokal:

```bash
composer install --no-dev --optimize-autoloader
bun install
bun run build
```

Kalau shared host tidak bisa menjalankan Composer, upload juga direktori `vendor/`.

Kalau shared host tidak bisa menjalankan Bun atau Node, upload hasil `public/build/` dari mesin lokal.

### 2. Upload project

Upload file aplikasi ke hosting, sambil menghindari file development yang tidak perlu.

### 3. Set document root

Domain atau subdomain harus diarahkan ke:

```text
/path/to/your-app/public
```

Jangan meratakan struktur Laravel ke `public_html` kecuali host benar-benar memaksa dan Anda paham tradeoff keamanannya. Solusi yang benar tetap mengarahkan document root ke `public/`.

### 4. Konfigurasi `.env`

Set variabel produksi seperti contoh VPS, terutama:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`
- kredensial database
- setting SMTP
- `QUEUE_CONNECTION=database`
- `SCHEDULE_QUEUE_WORKER=true` kecuali Anda punya worker terpisah
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`

### 5. Jalankan command Laravel di hosting

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

### 6. Tambahkan cron untuk scheduler

Gunakan cron manager dari panel hosting:

```cron
* * * * * cd /home/USER/path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Pemrosesan queue di shared hosting

Scheduler sudah memuat fallback worker pendek saat `SCHEDULE_QUEUE_WORKER=true`:

```php
Schedule::command('queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) env('SCHEDULE_QUEUE_WORKER', true));
```

Jika entry scheduler itu sudah ada dan cron menjalankan `schedule:run` tiap menit, Anda tidak perlu cron queue kedua. Jika deployment masih memakai build lama tanpa entry scheduler tersebut, gunakan cron fallback ini:

```cron
* * * * * cd /home/USER/path-to-app && php artisan queue:work database --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1 >> /dev/null 2>&1
```

Ini memang tidak sekuat Supervisor, tapi paling realistis untuk shared hosting.

### Batasan shared hosting

Anda harus mengantisipasi reliability yang lebih lemah kalau:

- host mematikan proses PHP yang berjalan agak lama
- cron tidak bisa jalan tiap menit
- symlink dinonaktifkan
- SSH access tidak tersedia

Untuk background jobs, backup terjadwal, dan maintenance yang stabil, VPS tetap target yang lebih tepat.

## Queue, Scheduler, dan Job Latar Belakang

PasPapan cukup bergantung pada background processing untuk kualitas operasional.

### Desain queue saat ini

- queue connection default: `database`
- queue table: `jobs`
- failed jobs table: `failed_jobs`
- job maintenance tambahan memakai nama queue `maintenance`
- urutan `maintenance` sebaiknya ditulis sebelum `default` agar backup job diproses cepat

### Command yang sering dipakai

Jalankan worker manual:

```bash
php artisan queue:work database --queue=maintenance,default --tries=3 --timeout=1800
```

Lihat failed jobs:

```bash
php artisan queue:failed
```

Retry failed jobs:

```bash
php artisan queue:retry all
```

Restart worker setelah deploy:

```bash
php artisan queue:restart
```

### Scheduler

Scheduler saat ini mengecek jendela backup maintenance otomatis dan dapat menguras queued backup job dengan worker pendek:

```php
Schedule::command('maintenance:scheduled-backups')->everyMinute()->withoutOverlapping();
Schedule::command('queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) env('SCHEDULE_QUEUE_WORKER', true));
```

Artinya:

- cron wajib aktif
- queue worker wajib aktif; di shared hosting fallback scheduler bisa menangani ini jika `SCHEDULE_QUEUE_WORKER=true`
- tabel `system_backup_runs` wajib sudah ada

## Backup dan Operasi Maintenance

Modul maintenance admin saat ini mendukung:

- generate dan download backup SQL langsung
- queue database backup job
- queue application backup job
- riwayat backup yang disimpan
- hapus retained backup artifact
- policy otomasi backup harian atau mingguan
- cleanup file backup lama berdasarkan retention

Backup SQL langsung dibuat saat itu juga. Backup database dan application yang masuk queue akan tetap berstatus `Queued` sampai worker memproses queue `maintenance`; ini perilaku normal, bukan tanda backup sudah selesai.

### Command terkait

Jalankan logika dispatch backup terjadwal secara manual:

```bash
php artisan maintenance:scheduled-backups
```

Paksa dispatch backup terjadwal saat itu juga:

```bash
php artisan maintenance:scheduled-backups --force
```

### Syarat operasional

Queued backup dan retention otomatis tidak akan berjalan benar kecuali semua kondisi ini terpenuhi:

- migration terbaru sudah diterapkan
- queue worker aktif
- scheduler cron aktif
- storage writable

Jika Backup Center menampilkan baris lama yang macet di `Queued`, jalankan:

```bash
php artisan queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1
```

Lalu refresh Backup Center. Jika sukses, entry akan berubah ke `Completed`; jika gagal, status menjadi `Failed` dengan pesan error.

## Catatan Absensi, Face ID, dan Dynamic QR

### Setting Face ID

Halaman Settings admin menampilkan `attendance.require_face_verification` sebagai kontrol utama Face ID untuk absensi. Key lama `attendance.require_face_enrollment` tetap didukung secara internal untuk backward compatibility, tetapi disembunyikan dari UI Settings karena verification otomatis mengharuskan enrollment saat user belum punya Face ID.

### Teknologi Face ID

Face ID berjalan di browser saat enrollment dan capture absensi:

- akses kamera memakai browser media APIs di web app dan runtime Android WebView pada APK
- deteksi wajah memakai `face-api.js` dengan TinyFaceDetector dan 68-point facial landmarks
- enrollment mewajibkan movement check bergaya liveness sebelum profil wajah disimpan
- profil yang disimpan berupa numeric face descriptor, bukan raw selfie image
- enrollment saat ini menyimpan lightweight 129-value geometry descriptor; legacy 128-value recognition descriptor tetap diterima untuk kompatibilitas
- verifikasi membandingkan descriptor live capture dengan descriptor tersimpan sebelum absensi bisa dilanjutkan saat Face ID verification aktif

### Model keamanan Dynamic QR

Token Dynamic QR dirancang untuk menghindari reuse QR statis:

- setiap token punya payload bertanda tangan, waktu terbit, waktu kedaluwarsa, dan nonce
- hanya token terbaru untuk barcode tersebut yang diterima
- token kedaluwarsa ditolak tanpa grace window
- setelah scan dynamic berhasil, token saat itu dikonsumsi sehingga screenshot tidak bisa dipakai ulang untuk scan sukses berikutnya
- cache store wajib berfungsi karena validasi latest-token berbasis cache

### Approval cuti

`admin/leaves` menampilkan pengajuan cuti dari semua approval status secara default, dengan filter approval status dan request type. Pengajuan yang ditolak tetap mempertahankan tipe request aslinya di `status` dan keputusan disimpan di `approval_status`, sehingga tetap muncul pada filter rejected.

## Operasi Enterprise

Fitur enterprise-gated bergantung pada enterprise license key yang tersimpan dan fingerprint hardware server. Simpan nilai lisensi dari halaman Settings admin atau tabel settings terkait, lalu clear cache aplikasi setelah mengubah setting identitas yang berkaitan dengan lisensi.

### Hardware fingerprint

Generate fingerprint hardware server untuk lisensi enterprise:

```bash
php artisan enterprise:hwid
```

## Workflow Update

### Urutan update manual yang aman

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
bun install
bun run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan queue:restart
```

### Script helper yang tersedia

Repository ini menyertakan [`update.sh`](./update.sh).

Catatan penting:

- script itu melakukan `git reset --hard origin/main`
- artinya environment deployment akan dipaksa persis sama dengan branch remote
- script itu juga memanggil `view:cache`, yang mungkin perlu dihapus kalau environment Anda kena limit regex kompilasi Livewire Blade

Gunakan script itu hanya kalau environment Anda memang aman untuk workflow hard reset.

## Testing dan Quality

Jalankan test suite:

```bash
php artisan test
```

Atau langsung dengan Pest:

```bash
./vendor/bin/pest
```

Style check:

```bash
./vendor/bin/pint
```

Verifikasi build frontend:

```bash
bun run build
```

## Catatan Operasional

### Akun bootstrap dan demo

Codebase ini masih memuat perilaku akun bootstrap/demo di migration dan seeder untuk keperluan demo dan evaluasi.

Sebelum deployment dibuka ke publik:

- audit semua akun admin dan demo yang ada
- ganti password segera
- hapus user demo yang tidak seharusnya ada di produksi
- jangan menjalankan seeder secara sembarang pada database produksi

### Storage bersama dan attachment privat

Aplikasi ini memakai secure attachment route untuk foto absensi dan file reimbursement. Pastikan:

- permission storage benar
- `storage:link` tersedia jika dibutuhkan
- jalur file privat tidak terekspos langsung lewat web root

### Sinkronisasi hari libur

Repository ini juga punya command sync hari libur:

```bash
php artisan holidays:fetch --year=2026
```

Command ini memanggil API eksternal, jadi jalankan hanya pada environment yang memang boleh outbound network.

### Wrapper Android

Shell Android di [`android`](./android) memakai URL web aplikasi dari [`capacitor.config.ts`](./capacitor.config.ts). Kalau domain deployment berubah, review config itu sebelum rilis mobile build baru.

## Demo

Gunakan platform di sandbox simulasi terbatas.

Link akses: [paspapan.pandanteknik.com](https://paspapan.pandanteknik.com)

| Role | Email Login | Password |
| --- | --- | --- |
| Admin | `admin123@paspapan.com` | `12345678` |
| User | `user123@paspapan.com` | `12345678` |

Anggap kredensial ini hanya untuk demo, bukan kredensial produksi.

## Dukung Pengembangan

Kalau project ini membantu tim Anda dan Anda ingin mendukung pengembangannya, silakan scan QR GoPay berikut.

<div align="center">
  <img src="./screenshots/donation-qr.jpeg" alt="QR Dukungan GoPay" width="220">
  <p><strong>GoPay Support</strong></p>
</div>

## Kredit

Berangkat dari fondasi open source yang diprakarsai oleh [Ikhsan3adi](https://github.com/ikhsan3adi), lalu diperluas dan diarahkan ulang ke bentuk produk saat ini oleh [RiprLutuk](https://github.com/RiprLutuk).

Kalau Anda menjaga dokumentasi bilingual, [`README.md`](./README.md) dan file ini sebaiknya selalu diperbarui berpasangan.
