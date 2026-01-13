<div align="center">

![PasPapan Hero](./public/hero-banner.png)

# 🌍 PasPapan - Smart Attendance Ecosystem
**The Ultimate GPS Geofencing, QR Dynamic & Biometric Attendance Solution**

[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-4E56A6?style=for-the-badge&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Capacitor](https://img.shields.io/badge/Capacitor-Mobile-1199EE?style=for-the-badge&logo=capacitor&logoColor=white)](https://capacitorjs.com)
[![License](https://img.shields.io/badge/license-MIT-green?style=for-the-badge)](LICENSE)

---

<p align="center">
  <b>PasPapan</b> mendefinisikan ulang cara perusahaan mengelola kehadiran. Bukan sekadar absen, ini adalah ekosistem manajemen kehadiran yang <b>cerdas, aman, dan tanpa batas</b>.
  <br>
  Terintegrasi penuh antara <b>Web Admin Dashboard</b> yang powerful dan <b>Mobile Super App</b> yang intuitif.
</p>

</div>

---

## 🔥 Mengapa PasPapan?

Di era *hybrid working*, mesin fingerprint konvensional sudah usang. PasPapan menghadirkan teknologi masa depan hari ini:

-   **🌐 GPS Geofencing Premium**: Validasi lokasi akurasi tinggi. Karyawan hanya bisa absen di radius yang ditentukan. *Goodbye, Fake GPS!*
-   **📍 QR Radius Validation**: Scan QR Code khusus yang terikat dengan lokasi kantor. Absen hanya valid jika berada dalam jangkauan QR.
-   **📸 Selfie Evidence**: Fitur pelampiran foto selfie saat absen sebagai bukti kehadiran tambahan.
-   **💰 Integrated Reimbursement**: Bukan cuma absen. Ajukan klaim biaya medis, transport, dan dinas langsung dari aplikasi.

> **🌍 Global Ready (Bilingual)**: Satu klik untuk beralih antara **Bahasa Indonesia 🇮🇩** dan **English 🇺🇸**.

---

## 💎 Fitur Eksklusif

### 📱 Mobile Super App (Android)
Dirancang dengan antarmuka **Classy & Modern** untuk pengalaman pengguna terbaik.

| Fitur | Deskripsi |
| :--- | :--- |
| **📍 Smart Check-in/out** | Deteksi otomatis masuk/pulang dengan validasi lokasi & waktu server pusat. |
| **📅 Leave Management** | Ajukan cuti/sakit/izin dalam hitungan detik. Upload surat dokter langsung dari kamera. |
| **💸 Easy Reimbursement** | Klaim biaya operasional semudah upload story Instagram. |
| **📊 Personal Analytics** | Karyawan bisa melihat performa kehadiran mereka sendiri (Terlambat, Hadir, Alpha). |
| **🔔 Push Notifications** | Notifikasi real-time untuk status approval dan pengingat absen. |

### 🖥️ Enterprise Admin Dashboard
Pusat komando canggih untuk HR dan Manajemen.

-   **📡 Live Monitoring Center**: Pantau pergerakan dan lokasi tim secara real-time di peta interaktif.
-   **✅ Team Approval System**: Workflow persetujuan berjenjang (Supervisor -> Manager -> HR). Atasan langsung dapat menyetujui izin timnya.
-   **🗓️ Shift Management**: Atur jadwal shift fleksibel, auto-detect shift terdekat, dan kelola pertukaran shift.
-   **🏢 Multi-Office & Branch**: Kelola ratusan cabang dan pola kerja dinamis secara terpusat.
-   **📈 Executive Reports**: Export laporan kehadiran siap cetak (PDF/Excel) untuk penggajian.
-   **🛡️ Automated Audit**: Log aktivitas sistem lengkap (IP, Device, Location) untuk keamanan maksimal.

---

## 🛠️ Stack Teknologi (The Power Under The Hood)

Kami menggunakan perpaduan teknologi paling stabil dan modern saat ini:

*   **Core**: [Laravel 11](https://laravel.com) (PHP 8.3) - Pondasi server yang kokoh dan aman.
*   **Interactivity**: [Livewire 3](https://livewire.laravel.com) & [Alpine.js](https://alpinejs.dev) - Pengalaman SPA tanpa kompleksitas API.
*   **Style**: [Tailwind CSS](https://tailwindcss.com) - Desain antarmuka pixel-perfect.
*   **Mobile Engine**: [Capacitor](https://capacitorjs.com) - Performa native Android dengan akses kamera & lokasi level sistem.
*   **Database**: MySQL / MariaDB (Optimized Indexing).
*   **Maps**: [Leaflet.js](https://leafletjs.com) & OpenStreetMap - Gratis, tanpa biaya API Google Maps.
*   **UI Components**: [Tom Select](https://tom-select.js.org) (Searchable Dropdowns) & Custom Glassmorphism Design.
*   **Security**: Rate Limiting, IP Throttling, & HSTS Enabled.

---

## 🚀 Quick Start Guide

Ingin mencoba sekarang? Ikuti langkah mudah ini.

### 1. Persiapan (Developer Mode)

```bash
# Clone the magic
git clone https://github.com/RiprLutuk/PasPapan.git
cd PasPapan

# Setup Environment
cp .env.example .env
# ⚠️ Jangan lupa atur koneksi database di .env!

# Install Dependencies (Speed matters, use Bun!)
bun install
composer install

# Ignite!
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# Launch
bun run dev
php artisan serve
```

### 2. Build Mobile App (Android APK)

```bash
# Build aset web terbaru
bun run build

# Sinkronisasi ke Android project
npx cap sync android

# Build APK Debug (Siap Install di HP)
cd android
./gradlew assembleDebug
```
*APK Anda siap di: `android/app/build/outputs/apk/debug/app-debug.apk`*

---

## 🤝 Berkontribusi & Dukungan

Proyek ini adalah hasil dedikasi untuk komunitas Open Source. 

**Credits**:
- Core logic inspired by [ikhsan3adi](https://github.com/ikhsan3adi/absensi-karyawan-gps-barcode).
- Elevated & Supercharged by **PasPapan Team** powered by **AI**.

Jika aplikasi ini membantu bisnis atau pembelajaran Anda, pertimbangkan untuk memberikan ⭐️ **Star** di repo ini!

<div align="center">

### ☕ Traktir Developer Kopi
*Dukungan Anda menjaga server tetap menyala dan kode tetap terupdate.*

<img src="./screenshots/donation-qr.png" width="180px" style="border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

</div>

---

<p align="center">
  Built with ❤️ and ☕ lines of code by <a href="https://github.com/RiprLutuk"><b>RiprLutuk</b></a>
</p>
