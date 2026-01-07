![PasPapan Hero](./screenshots/paspapan-hero.png)

# PasPapan - Modern Attendance System
**Sistem Absensi Karyawan Berbasis GPS Geofencing & QR Code**

PasPapan adalah solusi presensi modern yang dirancang untuk efisiensi dan akurasi tinggi. Menggabungkan teknologi **GPS Geofencing** untuk validasi lokasi dan **QR Code** dinamik untuk keamanan, aplikasi ini memastikan data kehadiran karyawan tercatat secara real-time dan valid.

Dibangun dengan stack teknologi terkini: **Laravel 11, Livewire, Tailwind CSS, dan Capacitor**, PasPapan siap digunakan baik sebagai Web App maupun Aplikasi Mobile Native (Android).

---

## 🚀 Fitur Unggulan

## 🌟 Fitur Lengkap

### 📱 User / Karyawan (Mobile & Web)
*   **Smart Attendance**:
    *   **GPS Geofencing**: Validasi radius lokasi kantor (anti-fake GPS).
    *   **QR Code Scan**: Scan QR dinamis untuk Masuk/Pulang.
    *   **Selfie Validation**: (Opsional) Capture foto saat absen.
*   **Leave Management (Cuti/Izin/Sakit)**:
    *   Pengajuan izin langsung dari aplikasi.
    *   Upload bukti foto/surat dokter.
    *   Status persetujuan real-time (Pending/Approved/Rejected).
*   **Attendance History**:
    *   Riwayat kehadiran bulanan.
    *   Status keterlambatan dan jam kerja.
*   **Profile**:
    *   Update foto profil.
    *   Ganti password mandiri.
    *   **Multi-language**: Dukungan Bahasa Indonesia & Inggris (Switchable).

### 🖥️ Admin Dashboard
*   **Live Monitoring**:
    *   Pantau kehadiran hari ini secara real-time.
    *   Peta sebaran lokasi absensi karyawan (Leaflet JS).
*   **Master Data Management**:
    *   **Divisi & Jabatan**: Kelola struktur organisasi.
    *   **Shift & Jadwal**: Atur jam kerja (Regular/Shift) dan hari libur.
    *   **Karyawan**: Kelola data akun, password, dan info kontak.
    *   **Lokasi (Barcodes)**: Generate QR Code untuk titik presensi berbeda.
*   **Approval System**:
    *   Validasi pengajuan izin/cuti/sakit karyawan.
*   **Reporting (Laporan)**:
    *   **Excel Export**: Laporan detail per periode (rekap kehadiran, terlambat, izin).
    *   **PDF Export**: Cetak laporan siap tanda tangan.
    *   **Analytics**: Grafik tren kedisiplinan dan kehadiran.

### 🛡️ Super Admin & System
*   **Role Management**: Pemisahan hak akses (Super Admin vs Admin vs User).
*   **System Maintenance**: Mode perbaikan (Maintenance Mode) yang bisa diaktifkan Super Admin untuk memblokir akses sementara.
*   **Application Settings**: Pengaturan radius default, zona waktu, nama aplikasi, dll.
*   **Activity Logs**: Catatan audit log aktivitas admin untuk keamanan.

### 🚀 Technical Highlights
*   **PWA Ready**: Bisa diinstall sebagai Web App (Service Worker + Manifest).
*   **Native Android (Capacitor)**:
    *   Akses Hardware (Kamera/GPS) lebih stabil.
    *   **Pull-to-Refresh** native experience.
    *   Splash Screen & App Icon terintegrasi.
*   **Dark Mode**: Mendukung tema gelap di seluruh halaman.
*   **Security**: CSRF Protection, Rate Limiting, & Sanctum Authentication.

---

## 🛠️ Teknologi (Tech Stack)

*   **Framework**: [Laravel 11](https://laravel.com) (PHP 8.3+)
*   **Frontend**: [Livewire 3](https://livewire.laravel.com), [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev)
*   **Database**: MySQL / MariaDB
*   **Mobile Engine**: [Capacitor](https://capacitorjs.com) (Android Native Runtime)
*   **Maps**: [Leaflet.js](https://leafletjs.com) & OpenStreetMap
*   **Build Tool**: [Vite](https://vitejs.dev) & [Bun](https://bun.sh) (Recommended)

---

## ⚙️ Instalasi & Setup

### 1. Web / Backend Setup
```bash
# Clone repository
git clone https://github.com/RiprLutuk/PasPapan.git
cd PasPapan

# Setup Environment
cp .env.example .env
# (Konfigurasi database di .env)

# Install Dependencies
composer install
bun install  # atau npm install

# Generate Key & Migrate
php artisan key:generate
php artisan migrate --seed

# Build Assets
bun run build

# Jalankan Server
php artisan serve
```

### 2. Mobile / Android Build
Pastikan Anda memiliki Android Studio dan SDK terinstall.
```bash
# Sync Aset Web ke Android
npx cap sync android

# Build APK (Release)
cd android
./gradlew assembleRelease

# Lokasi APK: android/app/build/outputs/apk/release/app-release-unsigned.apk
```

---

## 💌 Dukungan & Kontribusi

Proyek ini Open Source dan gratis digunakan. Jika aplikasi ini membantu bisnis atau pembelajaran Anda, dukungan Anda sangat berarti!

<a href="https://github.com/RiprLutuk/PasPapan">
  <img src="https://img.shields.io/github/stars/RiprLutuk/PasPapan?style=social" alt="GitHub Stars">
</a>

### Traktir Kopi ☕
Jika aplikasi ini bermanfaat, Anda bisa memberikan dukungan seikhlasnya melalui QRIS (GoPay/OVO/Dana/BCA) di bawah ini:

<img src="./screenshots/donation-qr.png" width="200px" alt="QRIS Donation">

---

## 📄 Lisensi
[MIT License](LICENSE) - Bebas digunakan dan dimodifikasi untuk keperluan pribadi maupun komersial.
