![PasPapan Hero](./screenshots/paspapan-hero.png)

# PasPapan - Modern Attendance System
**Sistem Absensi Karyawan Berbasis GPS Geofencing & QR Code**

PasPapan adalah solusi presensi modern yang dirancang untuk efisiensi dan akurasi tinggi. Menggabungkan teknologi **GPS Geofencing** untuk validasi lokasi dan **QR Code** dinamik untuk keamanan, aplikasi ini memastikan data kehadiran karyawan tercatat secara real-time dan valid.

Dibangun dengan stack teknologi terkini: **Laravel 11, Livewire, Tailwind CSS, dan Capacitor**, PasPapan siap digunakan baik sebagai Web App maupun Aplikasi Mobile Native (Android).

---

## 🚀 Fitur Unggulan

### 📍 Smart Location Validation (GPS Geofencing)
*   **Radius Protection**: Sistem akan menolak absensi jika karyawan berada di luar radius kantor yang ditentukan (misal: 50 meter).
*   **Anti-Fake GPS**: Deteksi dini penggunaan aplikasi Fake GPS untuk integritas data.
*   **Real-time Tracking**: Memantau lokasi karyawan saat melakukan scan masuk/pulang.

### 📸 Advanced QR & Barcode Scanner
*   **Dynamic QR Code**: QR Code untuk absensi (shift) dapat diganti secara berkala oleh admin untuk mencegah titip absen.
*   **Multi-Camera Support**: Mendukung penggunaan kamera depan dan belakang dengan fitur mirroring alami.
*   **Fast Scanning**: Menggunakan library scanning modern yang cepat dan akurat.

### 📱 Native Mobile Experience (Android)
*   **Pull-to-Refresh**: Refresh data dengan gestur tarik layar yang halus (Material Design style).
*   **Native Features**: Integrasi mendalam dengan hardware HP (Kamera & GPS) melalui Capacitor layer.
*   **Scrollable Menu**: Navigasi mobile yang responsif dan mudah digunakan.

### 🎨 Premium UI/UX
*   **Dark Mode**: Tampilan yang nyaman di mata dengan dukungan mode gelap otomatis maupun manual.
*   **Responsive Dashboard**: Grafik dan tabel laporan yang menyesuaikan ukuran layar (Desktop/Tablet/Mobile).
*   **Interactive Components**: Menggunakan Tom Select untuk filter yang cepat dan Toast notification yang informatif.

### 📊 Comprehensive Reporting
*   **Excel Export**: Download laporan absensi bulanan/tahunan dalam format Excel (.xlsx) dengan sekali klik.
*   **Activity Logs**: Pantau aktivitas admin dan perubahan data penting dalam log sistem.
*   **Live Dashboard**: Ringkasan kehadiran hari ini (Hadir, Izin, Sakit, Terlambat) secara real-time.

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
