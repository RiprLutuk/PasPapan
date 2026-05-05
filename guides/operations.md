# Operasi

Dokumen ini memuat operasi harian, background job, update, testing, dan Android build.

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

```php
Schedule::command('maintenance:scheduled-backups')->everyMinute()->withoutOverlapping();
Schedule::command('import-export-runs:prune-expired --hours=24')->hourly()->withoutOverlapping();
Schedule::command('queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) env('SCHEDULE_QUEUE_WORKER', true));
```

Artinya:

- cron wajib aktif
- queue worker wajib aktif
- shared hosting bisa memakai fallback worker scheduler dengan `SCHEDULE_QUEUE_WORKER=true`
- VPS dengan Supervisor sebaiknya memakai `SCHEDULE_QUEUE_WORKER=false`
- run import/export `completed` dan `failed` yang lebih lama dari 24 jam akan dipangkas otomatis

Cron:

```cron
* * * * * cd /path/to/paspapan && php artisan schedule:run >> /dev/null 2>&1
```

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

- `update.sh` wajib dijalankan dengan `PASPAPAN_UPDATE_CONFIRM=main`
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

Catatan PHP 8.5: entrypoint CLI dan web menonaktifkan `E_DEPRECATED` pada PHP 8.5+ untuk menahan warning vendor Laravel `PDO::MYSQL_ATTR_SSL_CA`. Konfigurasi aplikasi sendiri sudah memakai constant baru saat tersedia. Hapus workaround entrypoint setelah versi Laravel yang dipakai tidak lagi memuat constant lama di default config vendor.

## Workflow Update

Urutan update manual yang aman:

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

Repository juga menyertakan [`update.sh`](../update.sh).

Catatan:

- script melakukan `git reset --hard origin/main` setelah `PASPAPAN_UPDATE_CONFIRM=main`
- script memaksa environment deployment sama dengan branch remote
- script memanggil `view:cache`, yang mungkin perlu dihapus jika environment terkena limit regex kompilasi Blade Livewire

Gunakan script hanya jika workflow hard reset memang aman untuk server Anda.

## Testing dan Quality

CI menjalankan quality gate berikut pada push ke `main` dan `develop`, serta pada pull request:

```bash
php artisan test --without-tty
composer check:ui
./vendor/bin/pint --test
composer phpstan
composer audit
bun run build
```

Untuk perubahan lokal kecil, jalankan test yang paling dekat dengan area yang diubah terlebih dahulu, lalu lanjutkan ke gate CI penuh sebelum merge/push rilis. Contoh:

```bash
php artisan test tests/Feature/HrChecklistFlowTest.php tests/Feature/UserMenuSmokeTest.php
./vendor/bin/pint --test --dirty
```

Catatan runtime CI:

- CI memakai PHP `8.3`, MySQL `8.0`, Bun `1.2.21`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`, dan `BROADCAST_CONNECTION=log`.
- `composer check:ui` menjalankan `scripts/check-ui-rules.php`.
- `composer phpstan` menjalankan PHPStan dengan memory limit `1G`.

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
