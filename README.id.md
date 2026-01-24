<div align="center">

![PasPapan Hero](./public/hero-banner.png)

# PasPapan - Enterprise Attendance System

**Solusi Absensi GPS Geofencing, Verifikasi Biometrik & Payroll Terlengkap untuk Perusahaan Modern.**

> Stop titip absen (buddy punching), hapus absensi GPS palsu, dan otomatisasi payroll Anda dalam satu platform canggih.

[![Lang-User](https://img.shields.io/badge/Language-English-blue?style=flat&logo=google-translate&logoColor=white)](./README.md)
[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-4E56A6?style=flat&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=flat&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Capacitor](https://img.shields.io/badge/Capacitor-8.0-1199EE?style=flat&logo=capacitor&logoColor=white)](https://capacitorjs.com)

</div>

---

## Ringkasan

**PasPapan** bukan sekadar aplikasi absensi; ini adalah **Sistem Manajemen Tenaga Kerja Lengkap**. Dirancang untuk era kerja hibrida modern, PasPapan menjembatani keamanan fisik kantor dengan fleksibilitas kerja jarak jauh (remote).

Baik tim Anda bekerja di kantor, di lapangan, atau dari rumah, PasPapan memastikan setiap check-in **terverifikasi, otentik, dan akurat**.

---

## 📑 Daftar Isi

- [Alur Kerja Sistem](#alur-kerja-sistem)
- [Fitur Unggulan](#fitur-unggulan)
- [Tampilan Aplikasi](#tampilan-aplikasi)
- [Teknologi](#teknologi)
- [Instalasi (VPS / Server)](#instalasi-vps--server)
- [Kredensial Demo](#kredensial-demo)
- [Pemecahan Masalah](#pemecahan-masalah)

---

## <a id="alur-kerja-sistem"></a>🔄 Alur Kerja Sistem

1.  **Permintaan Check-In**: Pengguna melakukan absensi via Mobile App / PWA.
2.  **Lapisan Validasi Canggih**:
    *   **GPS**: Memverifikasi pengguna berada dalam radius kantor yang diizinkan (Geofencing).
    *   **Anti-Fake GPS**: Menganalisis akurasi sinyal dan varians untuk mendeteksi lokasi palsu via Mock Location.
    *   **Biometrik**: Memindai Wajah (Face ID) yang cocok dengan profil pengguna.
3.  **Pemrosesan Data**: Server mencatat waktu, koordinat, dan bukti foto.
4.  **Aksi Administratif**: Supervisor menerima notifikasi; data mengalir ke perhitungan Gaji (Payroll) secara otomatis.

---

## <a id="fitur-unggulan"></a>Fitur Unggulan

### 🛡️ Keamanan & Validasi Tak Tertandingi
- **Smart Geofencing**: Penguncian lokasi presisi tinggi memastikan karyawan *hanya* bisa absen dari zona yang ditentukan.
- **Teknologi Anti-Fake GPS**: Algoritma canggih mendeteksi dan memblokir upaya pemalsuan lokasi, aplikasi Mock Location, dan manipulasi sinyal GPS.
- **Verifikasi Face ID**: Pengenalan wajah berbasis AI menghilangkan praktik "titip absen" selamanya.
- **Penguncian Perangkat**: (Opsional) Batasi akun hanya pada perangkat tepercaya tertentu untuk keamanan maksimal.
- **Akses Foto Aman**: Penyimpanan file yang mengutamakan privasi, memastikan foto absensi hanya dapat diakses melalui jalur aman dan terotorisasi (tanpa link publik).
- **Enkripsi Data**: Perlindungan kelas enterprise untuk data sensitif pengguna.

### 💼 Suite HR Lengkap
- **Payroll Otomatis**: Ucapkan selamat tinggal pada spreadsheet manual. Hitung gaji pokok, lembur, dan potongan secara otomatis dengan generasi slip gaji PDF profesional.
- **Manajemen Shift Pintar**: Penjadwalan fleksibel yang beradaptasi dengan rotasi tim Anda.
- **Alur Kerja Digital**: Sistem persetujuan berjenjang untuk Cuti, Lembur, dan Reimbursement yang efisien.

### 🚀 Platform Skala Enterprise
- **Analitik Real-Time**: Buat keputusan berbasis data dengan dashboard canggih yang melacak tren kehadiran dan anomali.
- **Pengalaman Mobile Native**: Aplikasi super cepat dan bisa offline untuk Android & iOS (via PWA).
- **Siap Global**: Dukungan multi-bahasa (Inggris & Indonesia) untuk tim yang beragam.

---

## <a id="tampilan-aplikasi"></a>📸 Tampilan Aplikasi

<details>
<summary><b>💻 Admin Dashboard (Web)</b></summary>
<br>

| Dashboard & Monitoring | Data Absensi |
| :---: | :---: |
| ![Dashboard](./screenshots/admin/01_Dashboard.png) | ![Absensi](./screenshots/admin/02_DataAbsensi.png) |

| Persetujuan Cuti | Manajemen Lembur |
| :---: | :---: |
| ![Cuti](./screenshots/admin/03_PersetujuanCuti.png) | ![Lembur](./screenshots/admin/04_ManagementLembur.png) |

| Penjadwalan Shift | Dashboard Analitik |
| :---: | :---: |
| ![Shift](./screenshots/admin/05_ManagemetShift.png) | ![Analitik](./screenshots/admin/06_DashboardAnalitik.png) |

| Kalender & Libur | Pengumuman |
| :---: | :---: |
| ![Kalender](./screenshots/admin/07_LiburKalender.png) | ![Pengumuman](./screenshots/admin/08_Announcements.png) |

| Manajemen Payroll | Reimbursement |
| :---: | :---: |
| ![Payroll](./screenshots/admin/09_Payroll.png) | ![Reimbursement](./screenshots/admin/10_Reimbursement.png) |

| Tunjangan & Potongan | Manajemen Barcode |
| :---: | :---: |
| ![Allowances](./screenshots/admin/11_Allowances.png) | ![Barcode](./screenshots/admin/12_Barcode.png) |

| Pengaturan Aplikasi | Mode Pemeliharaan |
| :---: | :---: |
| ![Settings](./screenshots/admin/13_AppSettings.png) | ![Maintenance](./screenshots/admin/14_Maintance.png) |

| Ekspor Karyawan | Ekspor Absensi |
| :---: | :---: |
| ![Export Users](./screenshots/admin/15_ExportImportEmployee.png) | ![Export Attendance](./screenshots/admin/16_ExportImportAttendance.png) |

</details>

<details>
<summary><b>📱 Mobile App (Android/PWA)</b></summary>
<br>

| Layar Login | Home (Wajah Terdaftar) | Home (Pengguna Baru) |
| :---: | :---: | :---: |
| <img src="./screenshots/users/01_Login.png" width="250"> | <img src="./screenshots/users/02_HomeFace.png" width="250"> | <img src="./screenshots/users/03_Home.png" width="250"> |

| Riwayat Absensi | Permintaan Cuti | Permintaan Lembur |
| :---: | :---: | :---: |
| <img src="./screenshots/users/04_History.png" width="250"> | <img src="./screenshots/users/05_LeaveRequest.png" width="250"> | <img src="./screenshots/users/06_Overtime.png" width="250"> |

| Reimbursement | Slip Gaji | Profil |
| :---: | :---: | :---: |
| <img src="./screenshots/users/07_Reimbursement.png" width="250"> | <img src="./screenshots/users/08_Payslip.png" width="250"> | <img src="./screenshots/users/09_Profile.png" width="250"> |

| Jadwal | Registrasi Wajah | Scan QR |
| :---: | :---: | :---: |
| <img src="./screenshots/users/10_Schedule.png" width="250"> | <img src="./screenshots/users/11_FaceID.png" width="250"> | <img src="./screenshots/users/12_ScanQR.png" width="250"> |

| Error Scan | Bukti Selfie | Check-Out Sukses |
| :---: | :---: | :---: |
| <img src="./screenshots/users/13_ScanRQError.png" width="250"> | <img src="./screenshots/users/14_Selfi.png" width="250"> | <img src="./screenshots/users/15_CheckOut.png" width="250"> |

| Setelah Check-Out | | |
| :---: | :---: | :---: |
| <img src="./screenshots/users/16_HomeAfterCheckOut.png" width="250"> | | |

</details>

---

## <a id="teknologi"></a>Teknologi

### ⚙️ Konfigurasi Canggih
- **Mesin Pengaturan Dinamis**: Atur Zona Waktu, Radius Kantor, Aturan Absensi, dan Branding langsung dari Panel Admin.
- **Kontrol Akses Berbasis Peran (RBAC)**: Pemisahan tugas yang ketat antara Super Admin, Admin HR, dan Karyawan menggunakan middleware khusus.
- **Struktur Fleksibel**: Dirancang dengan cakupan granular untuk mendukung struktur organisasi yang kompleks.

---

## <a id="teknologi"></a>Arsitektur Teknologi

**Dibangun di atas fondasi keamanan dan kinerja standar industri.**

### 🔐 Keamanan & Layer Middleware
- **Otentikasi**: Laravel Sanctum (Token API) & Jetstream (Manajemen Sesi).
- **Otorisasi**: Pipa Middleware Kustom (`auth:sanctum`, `verified`, `role:admin/user`) memastikan kontrol akses yang ketat.
- **Perlindungan**: Proteksi CSRF, Sanitasi XSS, dan pencegahan SQL Injection via Eloquent ORM.

### 🏗️ Inti Backend
- **Framework**: Laravel 11.x (PHP 8.3)
- **Database**: MySQL / MariaDB (Pengindeksan yang Dioptimalkan)
- **Sistem Antrean**: Driver Database/Redis untuk pengiriman Email & Notifikasi asinkron.

### 🎨 Frontend & Mobile
- **Antarmuka Web**: Blade + Livewire 3 (Komponen reaktif tanpa overhead API yang rumit).
- **Mesin Mobile**: Capacitor 8 Bridge mengakses API Geolokasi & Kamera Native.
- **Styling**: Tailwind CSS 3.4 (Utility-first, dukungan Dark Mode native).

---

## <a id="instalasi-vps--server"></a>🛠️ Instalasi (VPS / Server)

### Prasyarat
- PHP 8.3 atau lebih tinggi
- Composer
- Node.js & NPM/Bun
- MySQL Server

### Langkah Pengaturan

1. **Clone Repositori**
   ```bash
   git clone https://github.com/RiprLutuk/PasPapan.git
   cd PasPapan
   ```

2. **Instal Dependensi**
   ```bash
   # Backend
   composer install

   # Frontend
   bun install  # atau npm install
   ```

3. **Konfigurasi Lingkungan**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Atur kredensial database Anda di file `.env`.*

4. **Migrasi Database & Seeding**
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

5. **Jalankan Aplikasi**
   ```bash
   # Terminal 1: Vite Development Server
   bun run dev

   # Terminal 2: Laravel Server
   php artisan serve
   ```

### 💡 Tips Produksi (VPS)
Untuk server produksi, gunakan perintah optimasi:
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Build Mobile (Android)

```bash
bun run build
npx cap sync android
cd android
./gradlew assembleDebug
```
*Output APK located at: `android/app/build/outputs/apk/debug/app-debug.apk`*

---

---

## <a id="kredensial-demo"></a>🧪 Kredensial Demo

**Coba aplikasi lengkap secara langsung (Live Demo):**
### 🌐 [paspapan.pandanteknik.com](https://paspapan.pandanteknik.com)

Gunakan akun berikut untuk demo aplikasi:

| Peran | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin123@paspapan.com` | `12345678` |
| **User** | `user123@paspapan.com` | `12345678` |





## <a id="pemecahan-masalah"></a>❓ Pemecahan Masalah

**T: GPS tidak berfungsi / Kamera diblokir?**
> J: Pastikan Anda mengakses aplikasi melalui **HTTPS** (misalnya menggunakan Cloudflare Tunnel, Ngrok, atau Valet Secure). Browser memblokir izin sensitif pada HTTP (kecuali localhost).

**T: Peta tidak loading?**
> J: Aplikasi ini menggunakan OpenStreetMap/Leaflet yang gratis. Pastikan perangkat Anda memiliki akses internet untuk memuat tile peta.

---


### ☕ Traktir Developer Kopi

<img src="./screenshots/donation-qr.jpeg" width="180px" style="border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

---

---

### 💖 Kredit & Terima Kasih
Proyek ini dikembangkan menggunakan fondasi core yang solid dari:
*   [**Absensi Karyawan GPS Barcode**](https://github.com/ikhsan3adi/absensi-karyawan-gps-barcode) oleh [**Ikhsan3adi**](https://github.com/ikhsan3adi).
*   Dimodifikasi dan ditingkatkan untuk skala enterprise oleh [**RiprLutuk**](https://github.com/RiprLutuk) berkolaborasi dengan **Vibecode**.

Developed by <a href="https://github.com/RiprLutuk"><b>RiprLutuk</b></a>
