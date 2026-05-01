# Changelog

Semua perubahan penting PasPapan dicatat di file ini.

## [Unreleased]

Belum ada perubahan setelah `v4.2.0`.

## [4.2.0] - 2026-05-01

### Sorotan

- Memperketat enterprise license menjadi feature-aware untuk payroll, reporting, audit, analytics, asset management, appraisal, cash advance, attendance, face verification, dan system backup.
- Menambahkan cache validasi lisensi dan cache feature map agar menu, gate, policy, dan service binding tidak memverifikasi signature berulang dalam satu request.
- Mengoptimalkan runtime proteksi enterprise offline agar validasi modul tetap cepat pada halaman admin.
- Menonaktifkan remote time check default agar mode lisensi offline tidak menunggu timeout jaringan.
- Memperbaiki alur validasi lisensi offline tanpa mengekspos komponen internal developer ke deployment klien.
- Memperketat gate admin untuk dashboard, import/export, audit log, payroll, analytics, assets, appraisals, kasbon, dan system maintenance sesuai izin RBAC dan fitur lisensi.
- Menambahkan setup enterprise license eksplisit pada test otorisasi lama supaya test RBAC tetap menguji permission, bukan gagal karena feature gate.
- Menyelaraskan metadata rilis Android ke `versionName 4.2.0` dan `versionCode 42`.

### Keamanan & Quality

- Menyelaraskan gate CI/deploy dengan urutan test, UI rules, Pint, PHPStan, Composer audit, dan build frontend sebelum upload produksi.
- Memperketat pola upload file agar memakai label terhubung ke input `sr-only` tanpa click proxy atau overlay transparan.
- Menambah cakupan test route self-service untuk home, jadwal, Face ID enrollment, dan notifikasi agar akses tetap user-scoped.
- Memperluas exclude deploy untuk file rahasia, aset build internal, cache/session/view/log storage, `node_modules`, dan `tests`.
- Memperbarui PhpSpreadsheet ke rilis patch yang sudah lolos `composer audit`.
- Menyelaraskan workflow update manual dan `update.sh` agar memakai branch `main`.
- Menambah proteksi append-only dan integrity hash untuk audit log, guard eksplisit untuk `update.sh`, serta checklist permission produksi.

### Dokumentasi

- Menambahkan link demo Vercel `https://paspapan.vercel.app` di README dan panduan deployment Vercel.
- Menambahkan catatan enterprise offline, feature-gated license, dan optimasi runtime di README serta panduan fitur.

### APK Android

- Nama file: `PasPapan-v4.2.0.apk`
- ID aplikasi: `com.pandanteknik.paspapan`
- Nama versi: `4.2.0`
- Kode versi: `42`
- Tipe build: APK rilis bertanda tangan
- SHA-256: `624cb7c7d411f3c4f2c67521c01e190c039768f74f93aebe479359b2a1ef5145`

### Catatan Upgrade

- Jalankan migrasi database setelah menarik rilis ini.
- Pastikan `enterprise_license_key`, `app.company_name`, dan `app.support_contact` sesuai payload lisensi enterprise.
- Komponen internal penerbitan lisensi tidak perlu disertakan pada deployment klien.
- Jalankan `php artisan optimize:clear` lalu cache config/route/view ulang pada produksi setelah deploy.

## [4.1.0] - 2026-04-27

### Sorotan

- Menambahkan hardening akses self-service untuk halaman lembur dan kasbon agar akun admin tidak bisa membuka route karyawan.
- Menambahkan policy `Overtime` dan memperluas policy kasbon untuk akses list, detail, dan pembuatan request.
- Memperkuat cakupan test RBAC, route user, registrasi, concurrent login, asset lifecycle, dan shift swap.
- Menata ulang pipeline CI/deploy dengan UI rules check, Pint, PHPStan, audit Composer, build frontend, dan pruning dependency produksi.
- Memindahkan token maintenance Vercel ke konfigurasi service agar endpoint migrasi lebih mudah dikendalikan lewat config.
- Menambahkan scope filter absensi agar query report bisa dikomposisikan tanpa memutus kompatibilitas helper lama.
- Menyelaraskan metadata rilis Android ke `versionName 4.1.0` dan `versionCode 41`.

### APK Android

- Nama file: `PasPapan-v4.1.0.apk`
- ID aplikasi: `com.pandanteknik.paspapan`
- Nama versi: `4.1.0`
- Kode versi: `41`
- Tipe build: APK rilis bertanda tangan
- SHA-256: `628769627514be9996079041104d3a4285aa449eb517d7d589a69a938e66da11`

### Catatan Upgrade

- Jalankan migrasi database setelah menarik rilis ini.
- Build ulang aset frontend sebelum deployment produksi web.
- Pastikan queue worker dan scheduler Laravel tetap aktif untuk job maintenance, notifikasi, dan background process.
- Untuk Android, gunakan APK dari halaman GitHub Release `v4.1.0`.

## [4.0] - 2026-04-19

### Sorotan

- Dynamic QR attendance diperketat dengan validasi token terbaru dan konsumsi satu kali.
- Alur absensi mobile Capacitor Android ditingkatkan untuk scanner native, GPS, peta, bukti foto, dan anti mock location.
- Backup Center ditingkatkan dengan backup berbasis queue, riwayat backup, dan dukungan worker terjadwal.
- Dokumentasi deployment, operasi, Android build, dan kredensial sandbox demo publik diperluas.

[4.2.0]: https://github.com/RiprLutuk/PasPapan/releases/tag/v4.2.0
[4.1.0]: https://github.com/RiprLutuk/PasPapan/releases/tag/v4.1.0
[4.0]: https://github.com/RiprLutuk/PasPapan/releases/tag/v4
