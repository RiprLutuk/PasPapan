# Deployment

Target produksi paling lengkap untuk PasPapan adalah VPS. Shared hosting dan Vercel bisa dipakai, tetapi ada batasan operasional.

## Kebutuhan Sistem

Minimum produksi:

- PHP `8.2+`
- Composer `2.x`
- MySQL `8+` atau MariaDB setara
- Bun atau Node.js untuk build asset
- ekstensi PHP umum Laravel 11: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, `ctype`, `json`, `tokenizer`, `xml`

Direkomendasikan untuk VPS:

- Nginx atau Apache dengan document root ke `public/`
- Supervisor atau systemd untuk queue worker
- cron
- SSH

## Deploy VPS

### 1. Siapkan server

```bash
sudo mkdir -p /var/www/paspapan
sudo chown -R $USER:$USER /var/www/paspapan
cd /var/www/paspapan
```

Install PHP, Composer, MySQL/MariaDB, Bun atau Node.js, web server, dan Supervisor.

### 2. Ambil source dan install dependency

```bash
git clone https://github.com/RiprLutuk/PasPapan.git .
composer install --no-dev --optimize-autoloader
bun install
cp .env.example .env
php artisan key:generate
```

### 3. Environment produksi

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
SCHEDULE_QUEUE_WORKER=false
SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=local
BROADCAST_CONNECTION=log
ANNOUNCEMENT_REFRESH_MODE=auto
ANNOUNCEMENT_POLL_INTERVAL=60s

MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-mail-user
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

`BROADCAST_CONNECTION=log` adalah mode aman untuk instalasi tanpa WebSocket. Dengan `ANNOUNCEMENT_REFRESH_MODE=auto`, announcement dan notifikasi memakai fallback polling ringan. Untuk VPS yang siap WebSocket, aktifkan Reverb:

```dotenv
BROADCAST_CONNECTION=reverb
ANNOUNCEMENT_REFRESH_MODE=auto

REVERB_APP_ID=local-paspapan
REVERB_APP_KEY=change-me
REVERB_APP_SECRET=change-me-secret
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

Gunakan host publik pada `REVERB_HOST`. `REVERB_SERVER_HOST` dan `REVERB_SERVER_PORT` adalah alamat bind proses Reverb di server.

### 4. Build dan migrate

```bash
bun run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

`php artisan view:cache` tidak dijadikan default karena beberapa environment bisa terkena limit regex saat compile Blade Livewire.

### 5. Permission

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Sesuaikan `www-data` dengan user PHP-FPM server Anda.

### 6. Nginx

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

### 7. Supervisor Queue Worker

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

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start paspapan-worker:*
```

### 8. Scheduler

Tambahkan cron:

```cron
* * * * * cd /var/www/paspapan && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler diperlukan untuk backup terjadwal, cleanup run import/export, dan fallback queue worker jika `SCHEDULE_QUEUE_WORKER=true`.

### 9. Reverb WebSocket Opsional Untuk VPS

Reverb hanya cocok untuk VPS atau hosting yang mengizinkan proses long-running dan WebSocket/reverse proxy. Buat Supervisor process terpisah jika `BROADCAST_CONNECTION=reverb`.

Buat `/etc/supervisor/conf.d/paspapan-reverb.conf`:

```ini
[program:paspapan-reverb]
process_name=%(program_name)s
command=php /var/www/paspapan/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/paspapan/storage/logs/reverb.log
stopwaitsecs=10
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start paspapan-reverb
```

Jika memakai Nginx di HTTPS publik, proxy WebSocket ke Reverb:

```nginx
location /app/ {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_pass http://127.0.0.1:8080;
}
```

Lalu gunakan:

```dotenv
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

### 10. Checklist

- domain mengarah ke `public/`
- `storage/` dan `bootstrap/cache/` writable
- queue worker berjalan
- cron aktif
- login berhasil
- upload/download attachment berhasil
- queued job seperti backup atau export report berhasil
- akun demo/bootstrap sudah diaudit sebelum go-live

## Deploy Shared Hosting

Shared hosting bisa dipakai hanya jika provider memberi PHP 8.2+, MySQL/MariaDB, SSH atau terminal, cron, dan kemampuan mengarahkan document root ke `public/`.

### 1. Build lokal

```bash
composer install --no-dev --optimize-autoloader
bun install
bun run build
```

Jika host tidak bisa menjalankan Composer, upload juga `vendor/`. Jika host tidak bisa menjalankan Bun atau Node, upload hasil `public/build/`.

### 2. Upload dan document root

Upload project, lalu arahkan domain ke:

```text
/path/to/your-app/public
```

Jangan meratakan struktur Laravel ke `public_html` kecuali provider benar-benar memaksa dan risiko keamanannya dipahami.

### 3. Environment

Gunakan variabel produksi seperti VPS, tetapi biasanya:

```dotenv
QUEUE_CONNECTION=database
SCHEDULE_QUEUE_WORKER=true
SESSION_DRIVER=database
CACHE_STORE=database
BROADCAST_CONNECTION=log
ANNOUNCEMENT_REFRESH_MODE=auto
ANNOUNCEMENT_POLL_INTERVAL=60s
```

Shared hosting biasa tidak menjalankan Reverb karena butuh proses long-running `php artisan reverb:start` dan WebSocket port/proxy. Biarkan `BROADCAST_CONNECTION=log`; aplikasi akan tetap support announcement/notifikasi memakai fallback polling ringan.

### 4. Command Laravel

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

### 5. Cron

```cron
* * * * * cd /home/USER/path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler sudah memuat worker pendek saat `SCHEDULE_QUEUE_WORKER=true`. Jika build lama belum punya scheduler worker, pakai fallback:

```cron
* * * * * cd /home/USER/path-to-app && php artisan queue:work database --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1 >> /dev/null 2>&1
```

## Deploy Vercel

PasPapan bisa dideploy ke Vercel memakai komunitas runtime [`vercel-community/php`](https://github.com/vercel-community/php). Cocok untuk demo, staging, atau instalasi ringan. Untuk produksi penuh dengan queue worker, scheduler, backup otomatis, dan storage persisten, gunakan VPS.

### 1. File apa saja yang dibutuhkan?

Repository sudah menyertakan:

- [`vercel.json`](../vercel.json)
- [`api/index.php`](../api/index.php)
- [`api/php.ini`](../api/php.ini)
- [`.env.vercel.example`](../.env.vercel.example)
- [`.vercelignore`](../.vercelignore)

### 2. Bagaimana struktur entrypoint-nya?

`api/index.php` menjadi entrypoint Laravel untuk serverless function. Semua request non-static diarahkan ke file ini dari `vercel.json`.

### 3. Runtime PHP apa yang dipakai?

Konfigurasi saat ini:

```json
{
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.7.4",
      "memory": 1024,
      "maxDuration": 60
    }
  }
}
```

`vercel-php@0.7.4` memakai PHP 8.3.x dan cocok dengan requirement Composer project ini (`php: ^8.2`). Runtime `vercel-community/php` punya versi lebih baru, tetapi upgrade sebaiknya dites dulu.

### 4. Bagaimana routing Laravel di Vercel?

`vercel.json` mengirim asset public tertentu ke `public/`, lalu semua request lain ke `api/index.php`:

```json
{
  "routes": [
    { "src": "/(build|assets|js|models|temp)/(.*)", "dest": "/public/$1/$2" },
    { "src": "/(.*)", "dest": "/api/index.php" }
  ]
}
```

### 5. Bagaimana database-nya?

Vercel tidak menyediakan MySQL lokal. Pakai database MySQL/MariaDB eksternal seperti PlanetScale, Railway, Aiven, VPS database, atau provider managed database lain.

Jalankan migration dari mesin lokal atau CI yang bisa mengakses database:

```bash
cp .env.vercel.example .env
php artisan key:generate --show
php artisan migrate --force
```

Simpan output `APP_KEY` ke Vercel Environment Variables.

### 6. Environment variable apa yang wajib?

```dotenv
APP_NAME="PAS Papan"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-project.vercel.app
APP_STORAGE_PATH=/tmp/storage

LOG_CHANNEL=stderr
LOG_STACK=stderr

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

CACHE_STORE=array
SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

Default Vercel memakai `SESSION_DRIVER=cookie` dan `CACHE_STORE=array` supaya login/register tidak bergantung pada tabel `sessions` dan `cache` di serverless. Jika SMTP belum dikonfigurasi, gunakan `MAIL_MAILER=log` agar email verifikasi tidak membuat request register gagal.

### 7. Kenapa `QUEUE_CONNECTION=sync`?

Vercel serverless tidak menjalankan worker permanen. `sync` membuat job dieksekusi dalam request yang sama. Ini praktis untuk demo atau workload ringan, tetapi tidak cocok untuk proses besar.

### 8. Kenapa `APP_STORAGE_PATH=/tmp/storage`?

Filesystem serverless hanya writable di `/tmp`. `api/index.php` membuat direktori storage runtime di sana. File di `/tmp` bersifat ephemeral dan bisa hilang antar invocation.

### 9. Bagaimana deploy lewat dashboard?

1. Import repository GitHub ke Vercel.
2. Pilih framework preset `Other`.
3. Biarkan root directory di root repository.
4. Gunakan build command dari `vercel.json`: `bun run build`.
5. Deploy.

### 10. Bagaimana deploy lewat CLI?

```bash
npm i -g vercel
vercel login
vercel
vercel --prod
```

### 11. Apa yang harus dites setelah deploy?

- halaman login tampil
- login admin berhasil
- halaman admin yang membaca database berhasil
- flow absensi ringan berhasil
- SMTP berhasil jika email diaktifkan
- asset dari `public/build` tampil
- cek `Vercel > Functions > Logs` jika ada error 500

### 12. Apa batasan Vercel untuk PasPapan?

- upload ke local disk `/tmp` tidak persisten
- backup center berbasis file lokal tidak cocok
- scheduler Laravel tidak berjalan otomatis tanpa cron eksternal
- queue database tidak diproses tanpa worker eksternal
- export/import besar dan PDF berat dapat kena timeout function
- `php artisan storage:link` tidak relevan untuk filesystem serverless

Jika attachment perlu persisten di Vercel, pindahkan storage ke S3-compatible object storage dan sesuaikan `FILESYSTEM_DISK`, konfigurasi disk, serta flow download/upload.
