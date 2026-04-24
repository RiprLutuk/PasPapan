<div align="center">

<img src="./public/hero-banner.png" alt="PasPapan Hero" width="880">

# PasPapan

Production-oriented workforce management for secure attendance, payroll, approvals, appraisal, asset tracking, reporting, and maintenance operations.

[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-4E56A6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)

</div>

> Primary documentation: Bahasa Indonesia
>
> Dokumentasi utama Bahasa Indonesia: [`README.md`](./README.md)

## Overview

PasPapan is a Laravel 11 workforce platform for organizations that need attendance, HR operations, payroll preparation, and maintenance tooling in one deployable application. It is designed for Indonesian operational patterns, including mobile attendance, leave/overtime approvals, BPJS and PPh21-oriented payroll components, regional employee data, and bilingual user flows.

The application ships with:

- a web admin panel for employee data, attendance monitoring, approvals, reporting, master data, payroll, assets, announcements, settings, and maintenance
- a mobile-first employee experience for check-in/check-out, leave, overtime, reimbursement, payslips, personal assets, schedules, notifications, and performance review access
- secure attendance capture using GPS, map visualization, photo evidence, Face ID verification, dynamic QR, native scanner support, and anti-mock-location checks where the runtime supports it
- dynamic QR protection that accepts only the latest signed token, rejects expired tokens, and consumes a token after a successful dynamic scan
- private attachment handling for attendance photos and reimbursement files so sensitive uploads are not served directly from the public web root
- queue-backed notifications, backup jobs, email delivery, maintenance tasks, and scheduled routines
- a Capacitor Android wrapper for teams that need an installable APK while still running the Laravel web application as the backend
- enterprise-gated flows for selected advanced modules, with operational controls around licensing and hardware fingerprinting

The product is intentionally database-centric by default: MySQL or MariaDB stores application data, sessions, cache rows, queue jobs, failed jobs, settings, notifications, backup run history, and audit-oriented records.

## Contents

- [Product Scope](#product-scope)
- [Tech Scan](#tech-scan)
- [Runtime Defaults](#runtime-defaults)
- [Requirements](#requirements)
- [Local Development](#local-development)
- [Android Build and APK Install](#android-build-and-apk-install)
- [Production Deployment on VPS](#production-deployment-on-vps)
- [Shared Hosting Deployment](#shared-hosting-deployment)
- [Queue, Scheduler, and Background Jobs](#queue-scheduler-and-background-jobs)
- [Backup and Maintenance Operations](#backup-and-maintenance-operations)
- [Attendance, Face ID, and Dynamic QR Notes](#attendance-face-id-and-dynamic-qr-notes)
- [Enterprise Operations](#enterprise-operations)
- [Update Workflow](#update-workflow)
- [Testing and Quality](#testing-and-quality)
- [Operational Notes](#operational-notes)
- [Demo](#demo)
- [Support Development](#support-development)
- [Credits](#credits)

## Product Scope

### Admin operations

The admin surface currently includes modules for:

- dashboard and notifications
- employee directory
- attendance data and reporting
- leave approvals
- overtime management
- holiday calendar
- shifts and schedules
- barcode and dynamic QR management with latest-token validation and single-use consumption
- reimbursement management
- payroll settings and payroll processing
- cash advance management
- KPI settings
- analytics dashboard
- activity logs
- announcements
- system settings
- system maintenance, cache operations, backup center, restore center, and cleanup tools

### Employee self-service

The employee-facing side includes:

- home attendance status
- check-in/check-out scanning
- attendance history
- leave request
- overtime request
- reimbursement submission
- shift schedule
- shift swap/change request
- employee document requests
- team approvals and approval history
- payslip access
- cash advance access
- face enrollment
- personal assets
- performance review access
- notifications

### Attendance and location controls

Attendance workflows include:

- QR/barcode check-in and check-out
- static barcode support for conventional deployments
- dynamic QR display with rotating signed tokens
- web scanner fallback through browser camera APIs
- native Android scanner bridge when running inside the Capacitor shell
- GPS acquisition with cached-location recovery and permission-state handling
- location preview maps and Google Maps handoff links
- photo capture for attendance evidence
- Face ID enrollment and verification when enabled
- anti-mock-location integration for Android runtimes that expose mock-location state

### Enterprise modules

This repository includes enterprise-oriented modules and gates around:

- payroll management and advanced payroll components
- analytics and advanced reporting surfaces
- KPI-based appraisal workflows
- company asset lifecycle tracking
- import/export flows
- system maintenance backup automation
- enterprise license validation and hardware fingerprinting

## Tech Scan

### Backend

- Laravel `11.51.0`
- PHP `8.2+` required, currently tested in this workspace on `8.3.30`
- Livewire `3.7`
- Laravel Jetstream, Fortify, and Sanctum for authentication, profile, sessions, and API tokens
- database-first queue, cache, notification, and session drivers by default
- MySQL or MariaDB oriented runtime
- Eloquent models using ULIDs for users and related business records where configured
- service-layer abstractions for attendance storage, payroll calculation, reporting, audit, licensing, and backup operations
- route middleware for admin/user segmentation, localization, activity logging, and verified/authenticated access

### Attendance, security, and identity

- `face-api.js` is loaded as a browser-side asset for Face ID enrollment and verification
- Face ID uses TinyFaceDetector, 68-point landmarks, movement-based liveness checks, and numeric descriptors
- dynamic QR tokens use signed payloads with issue time, expiry, nonce, latest-token cache validation, and post-scan consumption
- geolocation uses browser APIs on web and Capacitor Geolocation in the Android runtime
- anti-mock-location checks use a Capacitor plugin where available
- attachment downloads are routed through authorization checks instead of public file URLs
- active-session and role-aware access checks protect sensitive flows

### Frontend

- Tailwind CSS `3.4`
- Vite `7`
- Alpine-driven interactions through Blade and Livewire screens
- Tom Select for richer admin select inputs
- Chart.js for analytics visualizations
- Leaflet and marker clustering for map-based views
- SweetAlert2 for interaction feedback
- Heroicons through Blade UI Kit
- mobile-first Blade views for employee flows and responsive admin surfaces

### Document and data tooling

- `maatwebsite/excel` for import/export
- `barryvdh/laravel-dompdf` for PDF exports
- `endroid/qr-code` for barcode and QR flows
- `intervention/image` for image handling
- `ballen/distical` and application helpers for distance-oriented location calculations
- Laravel language packs and app-level JSON translations for bilingual UI copy

### Mobile wrapper

- Capacitor Android project under [`android`](./android)
- Web runtime served from the Laravel application URL defined in [`capacitor.config.ts`](./capacitor.config.ts)
- Capacitor plugins for Android app lifecycle, browser handoff, camera, geolocation, splash screen, and barcode scanning
- optional native scanner bridge with browser scanner fallback
- Android mock-location plugin integration for stronger attendance trust signals
- release/debug APK builds generated through Gradle from the `android/` project

### Operations and background processing

- database queue connection by default with `default` and `maintenance` queue names
- scheduler entries for scheduled backup checks and a short-lived queue worker fallback for shared hosting
- backup center supports direct SQL backup, queued database backup, queued application backup, retained artifacts, and retention cleanup
- restore center accepts signed SQL backups generated by the application
- settings are stored in the database and cached for runtime performance
- private storage is used for sensitive attendance and reimbursement artifacts

### Testing and developer tooling

- Pest `4`
- Laravel Pint
- Bun for frontend dependency management and builds
- Vite production builds under `public/build`
- targeted feature tests for attendance enforcement, dynamic QR, backup jobs, maintenance security, leave approval behavior, media access, and user flows

## Runtime Defaults

The current project defaults are important for deployment planning:

- database: `mysql`
- queue connection: `database`
- cache store: `database`
- session driver: `database`
- filesystem disk: `local`
- mailer: `smtp` in app config, `log` in `.env.example`
- timezone: `Asia/Jakarta`
- locale: `id`

Operationally, this means a clean production install should assume:

- the database is not only application storage but also queue, cache, failed job, and session infrastructure
- queue workers are not optional if you want reliable background work
- a scheduler cron must be installed if you want automated backups and scheduled maintenance flows
- the database cache store is part of runtime security for dynamic QR latest-token validation

## Requirements

Minimum practical requirements for a production deployment:

- PHP `8.2` or newer
- Composer `2.x`
- MySQL `8+` or MariaDB equivalent
- Node.js or Bun for asset builds
- required PHP extensions for a standard Laravel 11 + MySQL install, especially:
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

Recommended for VPS:

- Nginx or Apache with the document root pointed at `public/`
- Supervisor or systemd for queue workers
- cron access
- SSH access

## Local Development

### 1. Install dependencies

```bash
git clone https://github.com/RiprLutuk/PasPapan.git
cd PasPapan

composer install
bun install
cp .env.example .env
php artisan key:generate
```

### 2. Configure the environment

Edit `.env` and set at minimum:

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

### 3. Run database setup

For a normal local setup:

```bash
php artisan migrate
```

If you want sample master data and example admins for local exploration:

```bash
php artisan migrate --seed
```

### 4. Build and run

```bash
php artisan storage:link
php artisan serve
bun run dev
```

### 5. Optional worker for local queue testing

```bash
php artisan queue:work --queue=maintenance,default
```

## Android Build and APK Install

PasPapan ships with a Capacitor Android shell for packaging the web application as an Android app.

### 1. Review the mobile app target URL

The Android wrapper loads the web URL configured in [`capacitor.config.ts`](./capacitor.config.ts). Confirm that `server.url` points to the environment you actually want the APK to open.

### 2. Build the frontend bundle

```bash
bun run build
```

### 3. Sync web assets into Android

```bash
npx cap sync android
```

### 4. Build the debug APK

From the repository root:

```bash
cd android
./gradlew assembleDebug
```

Debug APK output:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

The current Android project is configured with:

- `minSdkVersion 24`
- `compileSdkVersion 35`
- `targetSdkVersion 35`

### 5. Install the APK with ADB

List connected devices first:

```bash
adb devices -l
```

`adb` must be available in your `PATH`. If it is not, install Android Platform Tools and either add the platform-tools directory to `PATH` or call the full `adb` path for your operating system.

If the device shows as `unauthorized`, unlock the phone and approve the USB debugging prompt before continuing.

Install or replace the debug APK:

```bash
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

### 6. Common mobile build notes

- if Gradle reports `minSdkVersion` conflicts, review plugin requirements before forcing older values
- if ADB cannot start inside a restricted shell, run it from a normal local terminal session
- if the Android app opens the wrong backend, recheck [`capacitor.config.ts`](./capacitor.config.ts) and run `npx cap sync android` again
- after changing Android dependencies, rebuild the APK instead of reusing an older output file

## Production Deployment on VPS

This is the recommended deployment model for PasPapan.

### 1. Prepare the server

Install:

- PHP 8.2 or newer
- Composer
- MySQL or MariaDB
- Bun or Node.js
- Nginx or Apache
- Supervisor

Create a deployment directory, for example:

```bash
sudo mkdir -p /var/www/paspapan
sudo chown -R $USER:$USER /var/www/paspapan
cd /var/www/paspapan
```

### 2. Fetch the code and install dependencies

```bash
git clone https://github.com/RiprLutuk/PasPapan.git .
composer install --no-dev --optimize-autoloader
bun install
cp .env.example .env
php artisan key:generate
```

### 3. Configure production environment

At minimum review:

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

### 4. Build the application

```bash
bun run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

`php artisan view:cache` is intentionally not part of the recommended default here. If your deployment is known to pass view compilation cleanly, you can add it. If it fails with Livewire Blade compilation limits, skip it.

### 5. Set permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Adjust `www-data` if your PHP-FPM user is different.

### 6. Point the web root to `public/`

Sample Nginx site:

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

Then enable the site and reload Nginx.

### 7. Start queue workers with Supervisor

The app uses database queues and dispatches work to both `default` and `maintenance`. If Supervisor is active and stable, set `SCHEDULE_QUEUE_WORKER=false` in `.env` to avoid also starting the short-lived scheduler fallback worker.

Create `/etc/supervisor/conf.d/paspapan-worker.conf`:

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

Then run:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start paspapan-worker:*
```

### 8. Register the scheduler

Add a cron entry:

```cron
* * * * * cd /var/www/paspapan && php artisan schedule:run >> /dev/null 2>&1
```

This is required for scheduled backup dispatching from [`routes/console.php`](./routes/console.php). It can also run a short-lived queue worker every minute when `SCHEDULE_QUEUE_WORKER=true`, which is useful for shared hosting and can be disabled on VPS deployments with Supervisor.

### 9. Final post-deploy checklist

- confirm the domain points to `public/`
- confirm `storage/` and `bootstrap/cache/` are writable
- confirm queue workers are running
- confirm cron is installed
- run a test login
- test attendance upload or attachment download
- test a queued action such as backup job or notification flow
- remove or rotate bootstrap/demo accounts before public launch

## Shared Hosting Deployment

Shared hosting can work, but only if the provider gives you enough control.

### Recommended hosting capabilities

You should have:

- PHP 8.2+
- MySQL or MariaDB
- SSH or terminal access
- cron access
- ability to set the domain document root to the Laravel `public/` directory

If your host does not provide cron or CLI access, PasPapan will run with serious operational limitations, especially around queues and scheduled backups.

### Deployment model for shared hosting

The safest shared-hosting flow is:

1. build locally
2. upload the built app
3. run only the required Artisan commands on the host

### 1. Build locally

On your local machine:

```bash
composer install --no-dev --optimize-autoloader
bun install
bun run build
```

If the shared host cannot run Composer, upload the `vendor/` directory with the project.

If the shared host cannot run Bun or Node, upload the generated `public/build/` directory from your local machine.

### 2. Upload the project

Upload the application files to the host, excluding development-only clutter where appropriate.

### 3. Set the document root

Your domain or subdomain must point to:

```text
/path/to/your-app/public
```

Do not flatten Laravel into `public_html` unless your host absolutely forces that model and you understand the security tradeoffs. The correct solution is always to point the document root to `public/`.

### 4. Configure `.env`

Set the same production variables as the VPS example, especially:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`
- database credentials
- SMTP mail settings
- `QUEUE_CONNECTION=database`
- `SCHEDULE_QUEUE_WORKER=true` unless you have a separate worker process
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`

### 5. Run Laravel commands on the host

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

### 6. Add cron for scheduler

Use the hosting control panel cron manager:

```cron
* * * * * cd /home/USER/path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Queue processing on shared hosting

The scheduler includes a short-lived queue worker fallback when `SCHEDULE_QUEUE_WORKER=true`:

```php
Schedule::command('queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) env('SCHEDULE_QUEUE_WORKER', true));
```

If that scheduler entry is present and cron runs `schedule:run` every minute, you do not need a second queue cron. If you are deploying an older build without that scheduler entry, use this fallback cron:

```cron
* * * * * cd /home/USER/path-to-app && php artisan queue:work database --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1 >> /dev/null 2>&1
```

This is not as strong as Supervisor, but it is the practical fallback for shared hosting.

### Shared hosting limitations

You should expect weaker reliability if:

- the host kills long-running PHP processes aggressively
- cron cannot run every minute
- symlinks are disabled
- SSH access is unavailable

For stable background jobs, scheduled backups, and lower operational friction, VPS is the better target.

## Queue, Scheduler, and Background Jobs

PasPapan relies on background processing for operational quality.

### Current queue design

- default queue connection: `database`
- queue table: `jobs`
- failed jobs table: `failed_jobs`
- additional maintenance jobs use the `maintenance` queue name
- `maintenance` should be listed before `default` when backup jobs should be processed promptly

### Common commands

Start a worker manually:

```bash
php artisan queue:work database --queue=maintenance,default --tries=3 --timeout=1800
```

Inspect failed jobs:

```bash
php artisan queue:failed
```

Retry failed jobs:

```bash
php artisan queue:retry all
```

Restart workers after deploy:

```bash
php artisan queue:restart
```

### Scheduler

The scheduler currently checks automated maintenance backups and can drain queued backup jobs with a short-lived worker:

```php
Schedule::command('maintenance:scheduled-backups')->everyMinute()->withoutOverlapping();
Schedule::command('queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) env('SCHEDULE_QUEUE_WORKER', true));
```

That means:

- cron is required
- queue workers are required; on shared hosting the scheduler fallback can provide this if `SCHEDULE_QUEUE_WORKER=true`
- the `system_backup_runs` table must exist

## Backup and Maintenance Operations

The admin maintenance module now supports:

- direct SQL backup generation and download
- queued database backup jobs
- queued application backup jobs
- retained backup history
- deletion of retained backup artifacts
- backup automation policy for daily or weekly execution
- retention-based cleanup of old backup files

Direct SQL backups are generated immediately. Queued database and application backups remain in `Queued` until a worker processes the `maintenance` queue; this is expected behavior, not a completed backup.

### Related commands

Run scheduled backup dispatch logic manually:

```bash
php artisan maintenance:scheduled-backups
```

Force a scheduled backup dispatch immediately:

```bash
php artisan maintenance:scheduled-backups --force
```

### Operational requirement

Queued backups and automated retention do not work correctly unless all of the following are true:

- latest migrations have been applied
- queue workers are running
- scheduler cron is running
- writable storage is available

If Backup Center shows old rows stuck in `Queued`, run:

```bash
php artisan queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1
```

Then refresh the Backup Center. A successful run should move the entry to `Completed`; failures should move to `Failed` with an error message.

## Attendance, Face ID, and Dynamic QR Notes

### Face ID settings

The admin Settings page exposes `attendance.require_face_verification` as the main Face ID attendance control. The older `attendance.require_face_enrollment` key is still supported internally for backward compatibility, but it is hidden from the Settings UI because verification already implies enrollment when a user has no registered Face ID.

### Face ID technology

Face ID runs in the browser during enrollment and attendance capture:

- camera access uses the browser media APIs in the web app and the Android WebView runtime in the APK
- face detection uses `face-api.js` with TinyFaceDetector and 68-point facial landmarks
- enrollment requires a liveness-style movement check before saving the face profile
- the saved profile is a numeric face descriptor, not a raw selfie image
- current enrollment stores a lightweight 129-value geometry descriptor; legacy 128-value recognition descriptors are still accepted for compatibility
- verification compares the live capture descriptor with the stored descriptor before attendance can continue when Face ID verification is enabled

### Dynamic QR security model

Dynamic QR tokens are designed to avoid static QR reuse:

- every generated token has a signed payload, issued time, expiry time, and nonce
- only the latest generated token for a barcode is accepted
- expired tokens are rejected with no grace window
- after a successful dynamic scan, the current token is consumed so a screenshot cannot be reused for another successful scan
- the cache store must be working because latest-token validation is cache-backed

### Leave approvals

`admin/leaves` shows leave requests across all approval statuses by default, with filters for approval status and request type. Rejected leave requests keep their original request type in `status` and store the decision in `approval_status`, so they remain visible under the rejected filter.

## Enterprise Operations

Enterprise-gated features depend on the saved enterprise license key and the server hardware fingerprint. Keep the license value in the admin Settings screen or the corresponding settings table, and clear application caches after changing license-related identity settings.

### Hardware fingerprint

Generate the server hardware fingerprint for enterprise licensing:

```bash
php artisan enterprise:hwid
```

## Update Workflow

### Safe manual update sequence

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

### Included helper script

This repository includes [`update.sh`](./update.sh).

Important:

- it performs `git reset --hard origin/main`
- it assumes the deployment should exactly match the remote branch
- it calls `view:cache`, which you may want to remove if your deployment hits Livewire Blade regex limits

Use that script only if the environment is disposable enough for a hard reset workflow.

## Testing and Quality

Run the automated test suite:

```bash
php artisan test
```

Or directly with Pest:

```bash
./vendor/bin/pest
```

Style checks:

```bash
./vendor/bin/pint
```

Frontend build verification:

```bash
bun run build
```

## Operational Notes

### Bootstrap and demo accounts

This codebase includes bootstrap/demo account behavior in migrations and seeders for demonstration and evaluation flows.

Before opening a public deployment:

- audit all existing admin and demo users
- rotate passwords immediately
- remove demo-only users that should not exist in production
- do not run seeders blindly on a production database

### Shared storage and private attachments

The application uses secure attachment routes for attendance photos and reimbursement files. Verify:

- storage permissions are correct
- `storage:link` exists where needed
- private file paths are not exposed directly through the web root

### Holiday sync

The repository includes a holiday fetch command:

```bash
php artisan holidays:fetch --year=2026
```

This reaches an external API, so use it where outbound network access is allowed.

### Android wrapper

The Android shell under [`android`](./android) uses the remote web application URL from [`capacitor.config.ts`](./capacitor.config.ts). If you deploy to a new domain, review that config before shipping a mobile build.

## Demo

Experience the platform in a restricted simulation sandbox.

Access Link: [paspapan.pandanteknik.com](https://paspapan.pandanteknik.com)

| Role | Email Login | Password |
| --- | --- | --- |
| Admin | `admin123@paspapan.com` | `12345678` |
| User | `user123@paspapan.com` | `12345678` |

Treat these as demo-only credentials, not production credentials.

## Support Development

If this project helps your team and you want to support continued development, you can scan the GoPay QR below.

<div align="center">
  <img src="./screenshots/donation-qr.jpeg" alt="GoPay Support QR" width="220">
  <p><strong>GoPay Support</strong></p>
</div>

## Credits

Built on an open source foundation initiated by [Ikhsan3adi](https://github.com/ikhsan3adi), then expanded and reworked into the current product direction by [RiprLutuk](https://github.com/RiprLutuk).

If you maintain bilingual documentation, keep the Indonesian source in [`README.md`](./README.md) aligned first, then update this English companion file.
