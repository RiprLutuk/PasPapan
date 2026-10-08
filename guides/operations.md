# Operasi

Dokumen ini memuat operasi harian, background job, update, testing, dan Android build.

## Runtime Baseline

Baseline runtime resmi PasPapan adalah PHP 8.3+ untuk production, VPS, local development, dan CI. PHP 8.4 direkomendasikan untuk production baru.

Database default local/VPS adalah PostgreSQL 15+. MySQL/MariaDB tetap ada sebagai compatibility path, tetapi bukan baseline utama; lihat `guides/database-portability.md` sebelum migrasi engine.

Preflight install/update:

```bash
php -v
composer check-platform-reqs
composer check:database-portability
```

Optional local portability smoke:

```bash
composer check:database-portability:sqlite
PASPAPAN_PG_USER=lutuk PASPAPAN_PG_ADMIN_DB=absensi composer check:database-portability:pgsql
# Local socket-based MariaDB/MySQL compatibility path:
PASPAPAN_MYSQL_HOST=localhost PASPAPAN_MYSQL_USER=<local-user> PASPAPAN_MYSQL_PASSWORD= composer check:database-portability:mysql
```

## Queue

Default project:

- queue connection: `database`
- queue table: `jobs`
- failed jobs table: `failed_jobs`
- queue utama: `default`
- queue maintenance: `maintenance`

Jalankan worker:

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

## Scheduler

Scheduler saat ini:

Tugas command dijalankan melalui `Schedule::call(new ScheduledArtisanCommand(...))`
dan `Artisan::call()` dalam proses PHP scheduler. Cara ini tidak memerlukan
`proc_open`, tetap memakai nama tugas dan `withoutOverlapping()`, serta melaporkan
exit code command yang gagal. Worker fallback memproses antrean `maintenance,default`
dengan `--stop-when-empty --max-time=55 --tries=1`.

Artinya:

- cron wajib aktif
- queue worker wajib aktif
- legacy cron-only install bisa memakai fallback worker scheduler dengan `SCHEDULE_QUEUE_WORKER=true`
- VPS dengan Supervisor sebaiknya memakai `SCHEDULE_QUEUE_WORKER=false`
- run import/export `completed` dan `failed` yang lebih lama dari 12 jam akan dipangkas otomatis

Cron:

```cron
* * * * * cd /path/to/paspapan && php artisan schedule:run >> /dev/null 2>&1
```

### Shared hosting: proc_open, DOMDocument, dan finfo

Jika log berisi `The Process class relies on proc_open`, deploy perubahan
`routes/console.php`, `app/Support/ScheduledArtisanCommand.php`, dan `config/queue.php`.
Tidak perlu mengaktifkan `proc_open` untuk tugas scheduler aplikasi ini.

Error `Class "DOMDocument" not found` dan `Class "finfo" not found` menunjukkan
konfigurasi ekstensi PHP yang belum lengkap. Aktifkan **dom/XML** dan **fileinfo**
pada PHP Selector/cPanel untuk PHP web **dan PHP CLI yang digunakan cron**.
DOM dibutuhkan renderer console/PDF/XML; fileinfo dibutuhkan filesystem dan
validasi MIME upload. Jangan mengganti pemeriksaan MIME dengan ekstensi nama file.

Periksa memakai binary PHP yang sama dengan cron (ganti `php` dengan path binary
hosting jika perlu):

```bash
php -v
php --ini
php -r 'echo "DOM: ", class_exists("DOMDocument") ? "OK" : "MISSING", PHP_EOL, "fileinfo: ", class_exists("finfo") ? "OK" : "MISSING", PHP_EOL;'
php artisan config:clear
php artisan config:cache
php artisan schedule:list
```

Jika PHP web sudah benar tetapi cron masih error, perbaiki path binary PHP pada
cron agar menunjuk versi PHP 8.3+ dengan ekstensi tersebut. Minta provider hosting
mengaktifkan ekstensi jika PHP Selector tidak menyediakan pilihannya. Perubahan
kode scheduler tidak menggantikan kebutuhan ekstensi DOM dan fileinfo.

## Backup dan Maintenance

Backup center mendukung:

- generate dan download backup SQL langsung
- queue database backup job
- queue application backup job
- riwayat backup run
- hapus retained backup artifact
- policy backup harian atau mingguan
- cleanup file backup lama berdasarkan retention

Command terkait:

```bash
php artisan maintenance:scheduled-backups
php artisan maintenance:scheduled-backups --force
```

Jika Backup Center menampilkan job lama di `Queued`, jalankan:

```bash
php artisan queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1
```

Syarat backup queued berjalan baik:

- migration terbaru sudah diterapkan
- queue worker aktif
- scheduler cron aktif
- storage writable
- akses download/hapus backup hanya diberikan ke role dengan permission maintenance manage
- restore database hanya memakai backup SQL yang ditandatangani aplikasi dan konfirmasi eksplisit

Review destructive action sebelum maintenance:

- `update.sh` default mengikuti `main-vps`; jalankan dengan `PASPAPAN_UPDATE_CONFIRM=main-vps`
- server legacy yang masih mengikuti `main` wajib eksplisit: `PASPAPAN_RELEASE_BRANCH=main PASPAPAN_UPDATE_CONFIRM=main`
- jika ada perubahan lokal yang akan dibuang, wajib tambah `PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES=1`
- retained backup delete harus memakai dialog konfirmasi UI
- restore database harus mengetik `RESTORE` dan disarankan membuat backup baru terlebih dahulu
- batasi akses shell/SSH dan secret deploy hanya untuk operator produksi yang berwenang

## Import/Export Retention

Run import/export terminal dipangkas otomatis:

```bash
php artisan import-export-runs:prune-expired --hours=24
```

Command ini menghapus run `completed` atau `failed` yang melewati retention window, termasuk file hasil/source terkait jika masih ada. Run `queued` dan `running` tidak dipangkas.

## HR Checklist Operations

Modul HR Checklist berjalan langsung di database:

- migration membuat tabel `hr_checklist_templates`, `hr_checklist_template_items`, `hr_checklist_cases`, dan `hr_checklist_tasks`
- role `admin` dan `hr` mendapat permission `admin.hr_checklists.view` dan `admin.hr_checklists.manage`
- template default onboarding/offboarding dibuat otomatis saat service dijalankan pertama kali
- tidak membutuhkan Redis, Horizon, Reverb, atau worker long-running sebagai baseline

Setelah deploy, jalankan migration dan minta HR memeriksa:

- menu admin `Master Data > HR Checklists`
- halaman user `HR Tasks`
- assignment direct manager pada data karyawan, karena task manager memakai field tersebut dan fallback ke actor HR jika belum ada manager
- permission role di `Roles & Permissions` bila instalasi memakai role custom

Quality check terkait:

```bash
php artisan test tests/Feature/HrChecklistFlowTest.php
php artisan route:list --name=hr
```

Catatan PHP 8.5: Laravel 13 sudah memakai constant `Pdo\Mysql::ATTR_SSL_CA` di konfigurasi default vendor. Konfigurasi aplikasi PasPapan juga memakai constant baru saat tersedia, dengan fallback untuk PHP 8.3.

## Workflow Update

Urutan update manual yang aman:

```bash
git pull origin main-vps
composer install --no-dev --optimize-autoloader
bun install
bun run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan queue:restart
```

Repository juga menyertakan [`update.sh`](../update.sh).

Catatan:

- branch `main-vps` adalah target update untuk fitur lengkap VPS
- script melakukan `git reset --hard origin/${PASPAPAN_RELEASE_BRANCH:-main-vps}` setelah nilai `PASPAPAN_UPDATE_CONFIRM` sesuai
- script memaksa environment deployment sama dengan branch remote
- script memanggil `view:cache`, yang mungkin perlu dihapus jika environment terkena limit regex kompilasi Blade Livewire

Gunakan script hanya jika workflow hard reset memang aman untuk server Anda.

## Testing dan Quality

CI menjalankan quality gate berikut pada push ke `main-vps`, `main`, dan `develop`, serta pada pull request:

```bash
php artisan test
composer check:ui
./vendor/bin/pint --test
composer phpstan
composer audit
bun run build
```

CI tambahan:

- `.github/workflows/security.yml` menjalankan CodeQL untuk PHP dan JavaScript/TypeScript, Semgrep, Gitleaks, dan TruffleHog.
- `.github/workflows/e2e.yml` menjalankan Playwright smoke dengan PostgreSQL 16, migration, build frontend, dan Laravel dev server.
- `.github/workflows/database-portability.yml` menjalankan migration smoke PostgreSQL, SQLite, dan MySQL compatibility agar VPS multi-RDBMS tetap terjaga tanpa mengubah default PostgreSQL-first.

Untuk perubahan lokal kecil, jalankan test yang paling dekat dengan area yang diubah terlebih dahulu, lalu lanjutkan ke gate CI penuh sebelum merge/push rilis. Contoh:

```bash
php artisan test tests/Feature/HrChecklistFlowTest.php tests/Feature/UserMenuSmokeTest.php
./vendor/bin/pint --test --dirty
```

Smoke browser dan screenshot:

```bash
bun run e2e:smoke
bun run screenshots:desktop
```

`bun run screenshots:desktop` menyiapkan demo data lokal, login via token E2E, lalu menulis 62 halaman ke `screenshots/desktop-pages/` beserta `manifest.json`. Direktori hasil ini di-ignore dari Git; unggah sebagai artifact bila perlu dipertahankan.

Smoke APK/device:

```bash
bun run apk:smoke
bun run apk:e2e:attendance
bun run apk:e2e:document-upload
bun run screenshots:apk
```

`bun run apk:smoke` mengecek launch, permission kamera/GPS, barcode/photo readiness, screenshot, dan crash log. `bun run apk:e2e:attendance` mengecek build debug APK, check-in, upload foto, dan check-out. `bun run apk:e2e:document-upload` mengecek build debug APK, file push ke device, upload dokumen, dan status processed/uploaded.

`bun run screenshots:apk` memakai device ADB debug, WebView DevTools/CDP, dan katalog halaman yang sama dengan desktop. Output 62 screenshot berada di `screenshots/apk-pages/`, di-ignore dari Git, dan sebaiknya disimpan sebagai CI/release artifact. Untuk state khusus Face ID, script menghapus descriptor demo sebelum screenshot `user-home-before-face-id` agar card `Daftar Face ID Sekarang` benar-benar terekam, lalu halaman berikutnya masuk ke setup Face ID.

Catatan runtime CI:

- CI utama memakai PHP `8.3`, Node.js `20`, Bun `1.3.6`, PostgreSQL `16`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`, dan `BROADCAST_CONNECTION=log`.
- CI portability memakai PostgreSQL `16` untuk mendekati default VPS dan SQLite untuk test/local migration smoke.
- `composer check:ui` menjalankan `scripts/check-ui-rules.php`.
- `composer check:modern-stack` memastikan baseline Laravel 13, Livewire 4, Tailwind 4 CSS-first, Capacitor 8, PHP 8.3+, Node 20+, dan Bun 1.3.6+ tidak tercampur wording/config lama.
- `composer check:database-portability` menjalankan static guard multi-RDBMS; `composer check:database-portability:sqlite`, `composer check:database-portability:pgsql`, dan `composer check:database-portability:mysql` menjalankan migration smoke lokal sesuai engine yang tersedia.
- `composer phpstan` menjalankan PHPStan dengan memory limit `1G`.
- Review evidence bundle diupload dari workflow CI sebagai artifact `review-evidence-ci`, `review-evidence-playwright`, dan `review-evidence-apk-smoke`. Detail ada di `guides/reviewer-evidence.md`.
- Bundle lokal dapat dibuat dengan `bun run evidence:review`; tambahkan `RUN_E2E=1` atau `RUN_APK_SMOKE=1` bila server E2E/device ADB sudah siap.

## Android Build

PasPapan menyediakan shell Android berbasis Capacitor.

### 1. Cek URL backend

Wrapper Android membuka URL web dari [`capacitor.config.ts`](../capacitor.config.ts). Pastikan `server.url` mengarah ke environment yang benar.

### 2. Build frontend

```bash
bun run build
```

### 3. Sinkronkan asset

```bash
npx cap sync android
```

### 4. Build debug APK

```bash
cd android
./gradlew assembleDebug
```

Output:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

Konfigurasi Android saat ini:

- `minSdkVersion 24`
- `compileSdkVersion 35`
- `targetSdkVersion 35`

## iOS Preflight

PasPapan sudah menyertakan project Capacitor `ios/` dan preflight iOS. Preflight menjalankan `cap sync ios` lalu build simulator dengan `CODE_SIGNING_ALLOWED=NO`, sehingga cocok untuk CI macOS atau mesin release lokal.

```bash
bun run ios:assets
bun run ios:preflight
bun run ios:screenshot
```

iOS belum diklaim TestFlight/App Store release-ready sampai signing, provisioning profile, upload TestFlight, dan smoke test iPhone fisik selesai. Jika Xcode baru dipasang dari App Store, jalankan:

```bash
sudo xcode-select -s /Applications/Xcode.app/Contents/Developer
bun run ios:preflight
```

Detail gate iOS ada di [`guides/ios-release.md`](./ios-release.md).

### 5. Install APK dengan ADB

```bash
adb devices -l
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

Jika device `unauthorized`, buka kunci HP dan setujui dialog USB debugging.

### 6. Checklist regresi APK dan upload

Sebelum APK dibagikan ke user:

- login sebagai user biasa, buka `/home`, dan pastikan menu utama tampil
- buka kamera/scan attendance dan pastikan permission prompt muncul normal
- upload lampiran reimbursement dari galeri dan file picker
- upload dokumen dari Document Requests, lalu pastikan status berubah ke `Processing Upload` dan selesai setelah queue worker berjalan
- buka foto/lampiran yang baru diupload dari sisi user dan admin
- ulangi satu upload dengan koneksi lambat atau file mendekati batas ukuran
- cek `php artisan queue:failed` setelah skenario upload selesai

## Catatan Produksi

### Akun Demo

Sebelum go-live:

- audit semua akun admin dan demo
- ganti password
- hapus user demo yang tidak boleh ada di produksi
- jangan menjalankan seeder sembarang pada database produksi
- master wilayah Indonesia ikut jalur master-data (`DatabaseSeeder` dan `paspapan:seed-real`) dan import-nya idempotent; operator/automation tidak boleh refresh/truncate lewat seeder production. Penggantian dataset penuh tetap mungkin, tetapi harus lewat runbook migration/restore terpisah dengan backup, staging rehearsal, dan approval.
- gunakan `php artisan paspapan:seed-real` untuk master data aman: settings default tanpa menimpa value yang sudah diedit, wilayah Indonesia, libur nasional, job level/title, pendidikan, divisi, shift, payroll component, KPI, template dokumen karyawan, company awal bila belum ada, dan COA per company
- `paspapan:seed-real` tidak membuat data demo, produk demo, barcode lokasi demo, atau akun admin kecuali `BOOTSTRAP_ADMIN_SEEDING_ENABLED=true` diset secara sadar untuk bootstrap terkontrol
- gunakan `php artisan paspapan:seed-fake` hanya untuk lokal, QA, atau staging demo; command ini menjalankan master data real lebih dulu, lalu menambahkan akun/data/sample workflow demo

### Storage dan Attachment Privat

Aplikasi memakai secure attachment route untuk foto absensi dan file reimbursement. Pastikan:

- owner file adalah user deploy/runtime web yang benar
- direktori `storage/` dan `bootstrap/cache/` writable oleh aplikasi, tetapi tidak world-writable
- `.env`, backup SQL, log, cache, session, dan private attachment tidak berada di document root publik
- document root domain mengarah ke `public/`
- permission umum: direktori `0755` atau `0775`, file `0644`, file rahasia seperti `.env` `0600` atau `0640`
- `storage:link` tersedia jika dibutuhkan, tetapi hanya untuk aset yang memang publik
- file privat tidak terekspos langsung lewat web root
- backup dan export sensitif disimpan di private disk dan hanya diunduh lewat route terotorisasi

### Rencana Migrasi Attachment ke `local`

Target akhirnya adalah `FILESYSTEM_ATTACHMENT_DISKS=local`, tetapi jangan mematikan fallback `public` sebelum file lama dipindahkan dan diverifikasi. Surface yang harus dicek agar tidak muncul `404 Not Found`:

- foto/check-in/check-out attendance dari halaman riwayat absensi user, detail attendance admin, approval cuti, report export attendance, dan endpoint secure attendance photo
- lampiran pengajuan cuti dari route download attendance attachment
- lampiran reimbursement dari halaman user, admin reimbursement, dan approval tim
- dokumen karyawan yang diupload user dan dokumen PDF yang digenerate HR

Urutan migrasi aman:

1. Pastikan writer baru sudah menyimpan ke `local`: attendance attachment/photo, reimbursement attachment, dokumen karyawan, export/import run, report export, dan backup.
2. Biarkan sementara `FILESYSTEM_ATTACHMENT_DISKS=local,public` dan pantau log `Serving attachment from legacy public disk fallback.` untuk mengetahui file lama yang masih dibaca dari disk publik.
3. Salin file legacy dari `storage/app/public` ke path relatif yang sama di `storage/app` untuk prefix yang masih dipakai, terutama `attachments/`, `attendance_photos/`, `reimbursements/`, dan `employee-documents/`.
4. Jalankan smoke test manual di halaman yang membaca attachment: user attendance history, admin attendance detail, user reimbursement, admin reimbursement, team approvals, user document requests, dan admin document requests.
5. Jalankan test terkait media dan attachment:

```bash
php artisan test tests/Feature/AttendanceMediaAndApiTest.php tests/Feature/UserFlowAuditTest.php tests/Feature/EmployeeDocumentRequestFlowTest.php
```

6. Setelah log fallback public kosong selama satu siklus operasional, ubah environment produksi ke `FILESYSTEM_ATTACHMENT_DISKS=local`, lalu jalankan `php artisan config:cache`.
7. Simpan backup `storage/app/public` sampai masa rollback lewat. Jika ada halaman mulai 404, kembalikan sementara `FILESYSTEM_ATTACHMENT_DISKS=local,public`, sync file yang hilang, lalu ulangi verifikasi.

### Sinkronisasi Hari Libur

```bash
php artisan holidays:fetch --year=2026
```

Command ini memanggil API eksternal. Jalankan hanya pada environment yang boleh outbound network.

### Wrapper Android

Jika domain deployment berubah, review [`capacitor.config.ts`](../capacitor.config.ts), lalu jalankan ulang:

```bash
npx cap sync android
```
