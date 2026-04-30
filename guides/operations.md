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
