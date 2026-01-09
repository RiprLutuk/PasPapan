![PasPapan Hero](./screenshots/paspapan-hero.png)

# PasPapan - Modern Attendance System
**Sistem Absensi Karyawan Berbasis GPS Geofencing, QR Code, & Reimbursement**

PasPapan adalah solusi presensi modern yang dirancang untuk efisiensi dan akurasi tinggi. Menggabungkan teknologi **GPS Geofencing** untuk validasi lokasi, **QR Code** dinamik untuk keamanan, serta sistem **Reimbursement** yang terintegrasi, aplikasi ini memastikan manajemen karyawan menjadi lebih mudah dan transparan.

Dibangun dengan stack teknologi terkini: **Laravel 11, Livewire, Tailwind CSS, dan Capacitor**, PasPapan siap digunakan baik sebagai Web App maupun Aplikasi Mobile Native (Android).

> **Support 2 Bahasa (Bilingual)**: Aplikasi ini mendukung penuh Bahasa Indonesia 🇮🇩 dan Bahasa Inggris 🇺🇸 yang dapat diganti secara instan.

---

## 🚀 Fitur Unggulan

> **Credit / Sumber Asli**: Inti dari aplikasi ini dikembangkan berdasarkan source code asli dari [ikhsan3adi/absensi-karyawan-gps-barcode](https://github.com/ikhsan3adi/absensi-karyawan-gps-barcode).

> **Note**: Pengembangan fitur dan perbaikan bug pada aplikasi ini dilakukan dengan bantuan **AI (Artificial Intelligence)**.

## 🌟 Fitur Lengkap

### 📱 User / Karyawan (Mobile & Web)
*   **Smart Attendance**:
    *   **GPS Geofencing**: Validasi radius lokasi kantor (anti-fake GPS).
    *   **QR Code Scan**: Scan QR dinamis untuk Masuk/Pulang.
    *   **Selfie Validation**: Validasi foto wajah saat absen.
*   **Leave Management (Cuti/Izin/Sakit)**:
    *   Pengajuan izin langsung dari aplikasi.
    *   Upload bukti foto/surat dokter.
    *   Tampilan sisa kuota cuti.
*   **Reimbursement System** (Baru!):
    *   Pengajuan klaim (Medical, Transport, dll).
    *   Upload bukti struk/invoice.
    *   Notifikasi status (Approved/Rejected) via Email & Aplikasi.
*   **Schedule & Shift**:
    *   Lihat jadwal kerja mingguan/bulanan.
    *   Support shift dinamis.
*   **Notifications**:
    *   Pusat notifikasi interaktif (Mark as Read).
    *   Notifikasi approval Cuti & Reimbursement real-time.

### 🖥️ Admin Dashboard
*   **Live Monitoring**:
    *   Pantau kehadiran hari ini secara real-time.
    *   Peta sebaran lokasi absensi karyawan.
*   **Approval Center**:
    *   Persetujuan Cuti/Izin.
    *   **Reimbursement Approval**: Review klaim, tolak/terima dengan catatan admin.
*   **Master Data Management**:
    *   Divisi, Jabatan, Karyawan, Shift, Hari Libur Nasional.
    *   **QR Barcodes**: Generate QR Code untuk berbagai lokasi kantor.
*   **Reporting (Laporan)**:
    *   Export Excel/PDF untuk rekap kehadiran, keterlambatan, dan payroll.
    *   Analytics Dashboard.

### 🛡️ System & Technical
*   **Queue-based Notifications**: Pengiriman email berjalan di background (Queue) agar aplikasi tetap cepat.
*   **Role Management**: Super Admin, Admin Unit, User.
*   **Maintenance Mode**: Mode perbaikan sistem yang aman.
*   **Backup & Restore**: Fitur backup database lengkap.
*   **PWA Ready**: Install sebagai aplikasi web ringan di iOS/Android.
*   **Capacitor Native**: Build menjadi APK Android sesungguhnya dengan akses hardware native.

---

## 🛠️ Teknologi (Tech Stack)

*   **Framework**: [Laravel 11](https://laravel.com) (PHP 8.3+)
*   **Frontend**: [Livewire 3](https://livewire.laravel.com), [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev)
*   **Database**: MySQL / MariaDB
*   **Mobile Engine**: [Capacitor](https://capacitorjs.com) (Android Native Runtime)
*   **Build Tool**: [Vite](https://vitejs.dev) & [Bun](https://bun.sh) (Recommended)

---

## ⚙️ Instalasi & Build Guide

### 1. Setup Environment (Developer)

```bash
# Clone repository
git clone https://github.com/RiprLutuk/PasPapan.git
cd PasPapan

# Setup Environment
cp .env.example .env
# Edit .env sesuaikan dengan database Anda

# Install Dependencies
composer install
bun install

# Generate Key & Migrate
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# Jalankan Server Development
bun run dev
php artisan serve
```

---

### 2. Build untuk Produksi (Web / Shared Hosting)

Langkah ini menghasilkan file siap upload ke hosting (cPanel/VPS).

1.  **Build Assets**:
    ```bash
    bun run build
    ```
2.  **Optimasi**:
    ```bash
    composer install --optimize-autoloader --no-dev
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```
3.  **Setup Queue (Penting!)**:
    Aplikasi ini menggunakan Queue untuk mengirim email. Di Shared Hosting, setup **Cron Job** berikut (set setiap menit `* * * * *`):
    ```bash
    cd /path/ke/project/anda && php artisan queue:work --stop-when-empty
    ```

---

### 3. Build APK Android (Siap Install)

Pastikan **Android Studio** dan **Java JDK 17** sudah terinstall.

1.  **Sync Aset Web**:
    Pastikan aset web sudah dibuild terbaru.
    ```bash
    bun run build
    npx cap sync android
    ```


2.  **Build APK (Siap Install)**:
    Kita gunakan build `debug` agar APK otomatis ditandatangani (signed) dan bisa langsung diinstall di HP.
    ```bash
    cd android
    ./gradlew assembleDebug
    ```

3.  **Lokasi File APK**:
    File APK yang siap install berada di:
    `android/app/build/outputs/apk/debug/app-debug.apk`

    > **Note**: Gunakan `assembleRelease` hanya jika Anda akan upload ke Play Store dan memiliki Keystore untuk signing manual.

---

## 💌 Dukungan & Kontribusi

Proyek ini Open Source. Jika membantu bisnis Anda, dukungan Anda sangat berarti!

<a href="https://github.com/RiprLutuk/PasPapan">
  <img src="https://img.shields.io/github/stars/RiprLutuk/PasPapan?style=social" alt="GitHub Stars">
</a>

### Traktir Kopi ☕
Jika aplikasi ini bermanfaat, Anda bisa memberikan dukungan seikhlasnya melalui QRIS (GoPay/OVO/Dana/BCA) di bawah ini:

<img src="./screenshots/donation-qr.png" width="200px" alt="QRIS Donation">

---

## 📄 Lisensi
[MIT License](LICENSE) - Bebas digunakan dan dimodifikasi.
