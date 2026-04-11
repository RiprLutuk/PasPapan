<div align="center">

<img src="./public/hero-banner.png" alt="PasPapan Hero" width="800">

# **PasPapan** — Enterprise Workforce Management
**Solusi Terpadu Geofencing, Verifikasi Biometrik & Manajemen Payroll Indonesia.**

[![Lang-User](https://img.shields.io/badge/Localization-Bilingual_(EN/ID)-Red?style=flat-square&logo=google-translate)](./README.md) 
[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-4E56A6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

[Demo Langsung](#kredensial-demo) • [Instalasi](#instalasi-servervps) • [Fitur Utama](#fitur-unggulan)

</div>

---

## 🎯 Solusi Final HR & Payroll Anda
Stop budaya titip absen (buddy punching), blokir pengguna lokasi palsu (Fake GPS), dan otomatisasi perhitungan Payroll. **PasPapan** menjembatani celah antara pengawasan fisik karyawan dan fleksibilitas kerja jarak jauh (remote/hybrid).

## 🌟 <a id="fitur-unggulan"></a>Fitur Unggulan

### 🛡️ Keamanan Berlapis Tingkat Tinggi
* **Smart Geofencing** & **Anti-Fake GPS**: Radius penguncian tingkat presisi yang mendeteksi dan menolak aplikasi Mock Location.
* **Verifikasi Face ID**: Pengenalan wajah berbasis AI yang secara otomatis mencocokkan wajah karyawan untuk memblokir penipuan.
* **Device Identity Lock**: Fitur penguncian akses akun karyawan hanya untuk perangkat atau HP yang sudah tervalidasi.

### 📈 Penilaian Kinerja Kelas Enterprise (V2)
* **Hierarki KPI Karyawan**: Modul pendaftaran dan pemetaan KPI berbobot (Induk & Komponen Anak) untuk penilaian berbasis hasil.
* **Alur Kalibrasi Dinamis**: Penjadwalan cerdas sesi 1-on-1, penilaian subjektif atasan langsung, dan laporan tanda tangan digital Direktur.
* **Grafik Distribusi Skor**: Rekapitulasi kurva lonceng (Bell-curve) untuk membantu HR menyortir bias subjektivitas dalam pengisian nilai.

### 💰 Payroll Indonesia Otomatis (TER)
* **Satu-Klik Payroll**: Multi-pendekatan perhitungan gaji entah menggunakan Angka Tetap, Hitungan Harian, atau variabel Persentase Gaji Pokok.
* **Standardisasi BPJS & Pajak**: Injeksi otomatis BPJS Kesehatan, Ketenagakerjaan (JHT/JP), dan regulasi Pajak PPh 21 (TER Terbaru).
* **Integrasi Kasbon**: Lifecycle peminjaman karyawan yang terautomasi dan langsung mengikat sebagai potongan pada kalender penggajian.

### 🏢 Skalabilitas Eksekutif
* **Manajemen Siklus Aset**: Pemantauan langsung inventaris perusahaan (MacBook, Mobil Box, dll) mencakup 8 status serah-terima karyawan.
* **Otonomi Admin Regional**: Akses perwakilan multi-cabang (Role) yang diisolasi ketat sesuai yurisdiksi provinsi/kota tanpa bercampur ruang data pusat.
* **Approval Struktural**: Alur persetujuan Cuti dan Reimbursement secara paralel yang harus bergerak dari Head divisi menuju divisi Keuangan.

---

## 📸 Tampilan Aplikasi

<details>
<summary><b>💻 Admin Dashboard (Web)</b></summary>
<br>

| Dashboard & Monitoring | Data Absensi |
| :---: | :---: |
| ![Dashboard](./screenshots/admin/01_Dashboard.png) | ![Absensi](./screenshots/admin/02_DataAbsensi.png) |

| Persetujuan Cuti | Manajemen Lembur |
| :---: | :---: |
| ![Cuti](./screenshots/admin/03_PersetujuanCuti.png) | ![Lembur](./screenshots/admin/04_ManagementLembur.png) |

| Payroll & Allowances | Pengaturan App |
| :---: | :---: |
| ![Payroll](./screenshots/admin/09_Payroll.png) | ![Settings](./screenshots/admin/13_AppSettings.png) |

</details>

<details>
<summary><b>📱 Mobile App (Android/PWA)</b></summary>
<br>

| Home Dashboard | Face Verification |
| :---: | :---: |
| <img src="./screenshots/users/02_HomeFace.png" width="250"> | <img src="./screenshots/users/11_FaceID.png" width="250"> | 

| Permintaan Izin | Proses Reimburse |
| :---: | :---: |
| <img src="./screenshots/users/05_LeaveRequest.png" width="250"> | <img src="./screenshots/users/07_Reimbursement.png" width="250"> |

</details>

---

## 🚀 <a id="instalasi-servervps"></a>Instalasi (Server/VPS)

Proses _Deployment_ PasPapan ke lingkungan VPS Linux maupun Shared Hosting berbasis cPanel sangatlah efisien.

#### 1. Persiapan Lingkungan
```bash
git clone https://github.com/RiprLutuk/PasPapan.git
cd PasPapan

composer install --optimize-autoloader --no-dev
bun install
cp .env.example .env
nano .env # Set detail Database Anda beserta APP_ENV=production
```

#### 2. Build & Optimasi
```bash
bun run build
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link

php artisan optimize
```

#### 3. Hak Akses File (Server Linux)
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 🔄 Modul Auto-Updater
Perbarui versi PasPapan live server Anda tanpa gangguan/downtime hanya dengan 1 baris skrip:
```bash
bash update.sh
```

---

## 🧪 <a id="kredensial-demo"></a>Kredensial Demo
Rasakan uji kekuatan dan kelembutan flow UI produk pada kotak pasir terbatas.

**Akses Web:** [paspapan.pandanteknik.com](https://paspapan.pandanteknik.com)

| Peran Role | Login Email | Sandi / Password |
| :--- | :--- | :--- |
| **Admin** | `admin123@paspapan.com` | `12345678` |
| **User** | `user123@paspapan.com` | `12345678` |

---

## 🤝 Kredit & Pengembang

Awal sistem open source ini menggunakan inti core dasar yang sangat brilian oleh [Ikhsan3adi](https://github.com/ikhsan3adi). Kemudian didesain, dire-arsitektur ulang total agar bisa berskala untuk level Enterprise oleh **[RiprLutuk](https://github.com/RiprLutuk)**.

  <b>Bantu Kopi Developer</b><br>
  <img src="./screenshots/donation-qr.jpeg" width="160px" style="border-radius: 12px; margin-top: 15px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
  <p style="margin-top: 15px; font-weight: bold; font-size: 1.1em; color: #00AEDA; letter-spacing: 1px;">💳 GOPAY SUPPORT</p>
