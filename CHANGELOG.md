# Changelog

Semua perubahan penting PasPapan dicatat di file ini.

## [Unreleased]

### Keamanan & Quality

- Menyelaraskan gate CI/deploy dengan urutan test, UI rules, Pint, PHPStan, Composer audit, dan build frontend sebelum upload produksi.
- Memperketat pola upload file agar memakai label terhubung ke input `sr-only` tanpa click proxy atau overlay transparan.
- Menambah cakupan test route self-service untuk home, jadwal, Face ID enrollment, dan notifikasi agar akses tetap user-scoped.
- Memperluas exclude deploy untuk `.env`, `*.Source.php`, `secure_tools`, cache/session/view/log storage, `node_modules`, dan `tests`.
- Memperbarui PhpSpreadsheet ke rilis patch yang sudah lolos `composer audit`.
- Menyelaraskan workflow update manual dan `update.sh` agar memakai branch `main`.
- Menambah proteksi append-only dan integrity hash untuk audit log, guard eksplisit untuk `update.sh`, serta checklist permission produksi.

### Dokumentasi

- Menambahkan link demo Vercel `https://paspapan.vercel.app` di README dan panduan deployment Vercel.

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

[4.1.0]: https://github.com/RiprLutuk/PasPapan/releases/tag/v4.1.0
[4.0]: https://github.com/RiprLutuk/PasPapan/releases/tag/v4
