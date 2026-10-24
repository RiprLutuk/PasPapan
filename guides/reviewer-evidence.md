# Reviewer Evidence Bundle

> Pada branch rewrite, `.github` dihapus atas pilihan pemilik repo. Bagian CI di
> bawah adalah referensi pipeline lama, bukan workflow yang aktif. Verifikasi
> lokal rewrite tersedia di [STATUS.md](./rewrite/STATUS.md).

Evidence bundle adalah artifact CI yang berisi output command dan Playwright report agar reviewer tidak perlu percaya klaim checklist secara manual.

## CI Artifact

Workflow utama:

- `.github/workflows/laravel.yml` menghasilkan artifact `review-evidence-ci`.
- `.github/workflows/e2e.yml` menghasilkan artifact `review-evidence-playwright` dan `playwright-report`.

Isi `review-evidence-ci`:

- `php-artisan-test.log`
- `composer-phpstan.log`
- `pint-test.log`
- `composer-audit.log`
- `bun-audit.log`
- `bun-run-build.log`
- `php-artisan-rbac-audit.log`
- `composer-check-ui.log`
- `composer-check-modern-stack.log`
- `composer-check-enterprise-boundary.log`
- `summary.md`

Isi `review-evidence-playwright`:

- `playwright-smoke.log`
- `playwright-summary.md`
- `test-results/` bila ada failure context
- `playwright-report/` pada artifact terpisah

## Critical Regression Scope

Feature/unit tests tetap menjadi bukti utama untuk domain kritikal:

- login dan active account guard
- RBAC route/menu dan `php artisan rbac:audit`
- absensi check-in/check-out, offline sync, risk scoring, dan Dynamic QR
- upload/download attachment private dan path traversal guard
- payslip privacy dan payroll-sensitive audit trail
- approval cuti, lembur, reimbursement, kasbon, attendance correction, shift swap, dan HR checklist task
- import/export background run
- backup/restore drill dan checksum
- multi-company isolation

Playwright smoke menambahkan bukti browser untuk halaman utama admin/user. Wrapper Capacitor dan smoke native sudah dihentikan; PWA rewrite diuji terpisah di `apps/web/tests/pwa-browser.mjs`.
Authenticated Playwright smoke memakai akun demo `apk.demo.superadmin@paspapan.test` dan `apk.demo.user@paspapan.test` secara default. Jalankan `php scripts/prepare-apk-screenshots-demo.php` sebelum smoke lokal, atau override dengan `E2E_ADMIN_EMAIL`, `E2E_ADMIN_PASSWORD`, `E2E_USER_EMAIL`, dan `E2E_USER_PASSWORD`.

## Package Smoke Scope

Checklist ini dipakai saat review upgrade major Laravel/Livewire/Tailwind agar package kritikal tidak hanya lolos install:

- Jetstream/Fortify: login, logout, profile page, password update, browser session page, dan role-based redirect.
- Sanctum: `/api/user` token/session response, device token abilities, stateful web session, dan CSRF cookie flow bila dipakai WebView.
- Reverb/Echo: broadcasting auth route, private channel auth, dan fallback polling saat `BROADCAST_CONNECTION=log`.
- Maatwebsite Excel: user import, attendance import, report export, queued export run, dan cleanup temporary file.
- DomPDF: payslip PDF, employee document PDF, logo/font fallback, serta access control download.
- Intervention Image/media: attendance photo, profile/avatar/photo-like upload, JPG/PNG/WebP handling, dan unsafe filename rejection.
- Endroid QR/Dynamic QR: token generation, short TTL/expiry, no-store response header, scan success, dan expired/replayed token rejection.

## Manual Smoke Checklist

Jalankan ini di staging/VPS sebelum release production penuh:

- Authentication: login, logout, role-based redirect, active/inactive account guard.
- Layout/UI: dashboard shell, sidebar/navbar, dark mode, focus ring form, table filter, modal open/close.
- Livewire: search/filter, pagination, form submit, validation error, upload progress, upload completion.
- Attendance: check-in, check-out, GPS permission, photo upload, Dynamic QR generate/scan/expired, browser/PWA flow.
- Security/RBAC: admin-only page, manager approval page, employee self-service page, private attachment owner access, unauthorized attachment denial, payslip privacy.
- Payroll: payroll preparation, payslip PDF generation, payslip download, payroll access control.
- Operations: Excel import/export, queued job status, operational health page, backup/maintenance page, queue worker, scheduler.

## Local Reproduction

Untuk menghasilkan bundle lokal dengan struktur log yang sama:

```bash
bun run evidence:review
```

Output default berada di `storage/review-evidence/local/`. Playwright dan APK smoke dibuat opt-in karena membutuhkan server E2E atau device ADB:

```bash
RUN_E2E=1 bun run evidence:review
RUN_APK_SMOKE=1 bun run evidence:review
RUN_E2E=1 RUN_APK_SMOKE=1 bun run evidence:review
```

Command manual yang setara:

```bash
php artisan test
composer phpstan
./vendor/bin/pint --test
composer audit
bun audit
bun run build
php artisan rbac:audit
composer check:modern-stack
composer check:enterprise-boundary
php scripts/prepare-apk-screenshots-demo.php
bun run e2e:smoke
```

PWA rewrite diuji dengan build production dan `node apps/web/tests/pwa-browser.mjs`.
Native wrapper serta script APK/iOS tidak lagi menjadi target release branch ini.

## Runtime Baseline

- PHP `8.3+`; PHP `8.4` direkomendasikan untuk production baru.
- Node.js `20+`.
- Bun `1.3.6+`.
- Java `21` untuk build Android.
