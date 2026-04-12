<div align="center">

<img src="./public/hero-banner.png" alt="PasPapan Hero" width="800">

# **PasPapan** — Enterprise Workforce Management
**The complete Geofencing, Biometric Attendance, and Payroll platform.**

[![Lang-User](https://img.shields.io/badge/Localization-Bilingual_(EN/ID)-Red?style=flat-square&logo=google-translate)](./README.id.md) 
[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-4E56A6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

[Live Demo](#demo-credentials) • [Installation](#installation-production) • [Features](#key-features)

</div>

---

## 🎯 The Ultimate HR & Payroll Solution
Stop buddy punching, eliminate fake GPS attendance, and streamline your payroll. **PasPapan** bridges the gap between physical security and remote workforce management—designed and built for the modern hybrid era.

## 🌟 <a id="key-features"></a>Key Features

### 🛡️ Unbeatable Security
* **Smart Geofencing** & **Anti-Fake GPS**: Precision location locking eliminates GPS spoofing and mock location apps.
* **Face ID Verification**: AI-powered biometric facial recognition blocks buddy punching securely.
* **Device Identity Lock**: Restricts accounts to trusted devices preventing unauthorized sign-ins.

### 📈 Enterprise Performance Appraisals (V2)
* **Hierarchical KPIs**: Multi-level weighted KPI definitions mapping broad groups and specific child components.
* **Calibration Dynamics**: Automated 1-on-1 scheduling, Manager subjective evaluations, and HR Director signature workflows.
* **Visual Score Distributions**: Live bell-curve and statistical graphs to help HR detect grading biases dynamically.

### 💰 Automated Indonesian Payroll (TER)
* **One-Click Payroll**: Bulk calculation using fixed amounts, daily attendance multipliers, or basic salary percentage variables.
* **Tax & BPJS Standards**: Auto-injected BPJS Kesehatan, Ketenagakerjaan (JHT/JP), and PPh 21 (TER compliance).
* **Cash Advances (Kasbon)**: Automated loan lifecycle mapping directly to upcoming payroll deductions implicitly avoiding clerical errors.

### 🏢 Corporate Administration
* **Asset Lifecycle Management**: End-to-end tracking of assigned company properties (e.g. MacBooks, Vehicles) across 8 lifecycle phases.
* **Multi-Branch Autonomy**: Regional Admin roles siloed to specific operational territories enforcing strict boundaries.
* **Multi-Layered Approvals**: Digital leave and reimbursement workflows routed sequentially from division heads directly to the finance operations team.

---

## 📸 Application Previews

<details>
<summary><b>💻 Admin Dashboard (Web)</b></summary>
<br>

| Administration | Operations |
| :---: | :---: |
| ![Dashboard](./screenshots/admin/01_Dashboard.png) <br> **Dashboard** | ![Data Absensi](./screenshots/admin/02_DataAbsensi.png) <br> **Attendance Data** |
| ![Persetujuan Cuti](./screenshots/admin/03_PersetujuanCuti.png) <br> **Leave Approvals** | ![Management Lembur](./screenshots/admin/04_ManagementLembur.png) <br> **Overtime** |
| ![Management Shift](./screenshots/admin/05_ManagemetShift.png) <br> **Shift Management** | ![Dashboard Analitik](./screenshots/admin/06_DashboardAnalitik.png) <br> **Analytics** |
| ![Libur Kalender](./screenshots/admin/07_LiburKalender.png) <br> **Holiday Calendar** | ![Announcements](./screenshots/admin/08_Announcements.png) <br> **Announcements** |
| ![Payroll](./screenshots/admin/09_Payroll.png) <br> **Payroll Run** | ![Reimbursement](./screenshots/admin/10_Reimbursement.png) <br> **Reimbursements** |
| ![Allowances](./screenshots/admin/11_Allowances.png) <br> **Allowances** | ![Barcode](./screenshots/admin/12_Barcode.png) <br> **QR Code Print** |
| ![App Settings](./screenshots/admin/13_AppSettings.png) <br> **App Settings** | ![Maintenance](./screenshots/admin/14_Maintance.png) <br> **Maintenance** |
| ![Export/Import Employee](./screenshots/admin/15_ExportImportEmployee.png) <br> **Employee Export/Import** | ![Export/Import Attendance](./screenshots/admin/16_ExportImportAttendance.png) <br> **Attendance Export** |

</details>

<details>
<summary><b>📱 Mobile Interface (Employee)</b></summary>
<br>

| 📱 | 📱 | 📱 | 📱 |
| :---: | :---: | :---: | :---: |
| <img src="./screenshots/users/01_Login.png" width="200"><br>Login | <img src="./screenshots/users/02_HomeFace.png" width="200"><br>Face ID Demo | <img src="./screenshots/users/03_Home.png" width="200"><br>Home Tracker | <img src="./screenshots/users/04_History.png" width="200"><br>History Log |
| <img src="./screenshots/users/05_LeaveRequest.png" width="200"><br>Leave Request | <img src="./screenshots/users/06_Overtime.png" width="200"><br>Overtime Form | <img src="./screenshots/users/07_Reimbursement.png" width="200"><br>Reimbursement | <img src="./screenshots/users/08_Payslip.png" width="200"><br>Digital Payslip |
| <img src="./screenshots/users/09_Profile.png" width="200"><br>User Profile | <img src="./screenshots/users/10_Schedule.png" width="200"><br>Work Schedule | <img src="./screenshots/users/11_FaceID.png" width="200"><br>Face Scan | <img src="./screenshots/users/12_ScanQR.png" width="200"><br>QR Scanning |
| <img src="./screenshots/users/13_ScanRQError.png" width="200"><br>Invalid QR Warn | <img src="./screenshots/users/14_Selfi.png" width="200"><br>Location Selfie | <img src="./screenshots/users/15_CheckOut.png" width="200"><br>Checkout Note | <img src="./screenshots/users/16_HomeAfterCheckOut.png" width="200"><br>Ended Shift |

</details>

---

## 🚀 <a id="installation-production"></a>Installation (Server/VPS)

Deploying PasPapan to a Linux VPS or standard Shared Hosting is seamless.

#### 1. Setup Environment
```bash
git clone https://github.com/RiprLutuk/PasPapan.git
cd PasPapan

composer install --optimize-autoloader --no-dev
bun install
cp .env.example .env
nano .env # Set your Database details and APP_ENV=production
```

#### 2. Build & Optimize
```bash
bun run build
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link

php artisan optimize
```

#### 3. Permissions (Linux Servers)
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 🔄 Auto Updater
Update your live instances flawlessly without manual command-typing workflows:
```bash
bash update.sh
```

---

## 🧪 <a id="demo-credentials"></a>Demo & Credentials
Experience the platform in a restricted simulation sandbox.

**Access Link:** [paspapan.pandanteknik.com](https://paspapan.pandanteknik.com)

| Role | Email Login | Password |
| :--- | :--- | :--- |
| **Admin** | `admin123@paspapan.com` | `12345678` |
| **User** | `user123@paspapan.com` | `12345678` |

---

## 🤝 Credits & Maintainer

Built upon the core Open Source foundation initiated by [Ikhsan3adi](https://github.com/ikhsan3adi). Re-architected and transformed into a scalable Enterprise system by **[RiprLutuk](https://github.com/RiprLutuk)**.

  <b>Fuel the Innovation</b><br>
  <img src="./screenshots/donation-qr.jpeg" width="160px" style="border-radius: 12px; margin-top: 15px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
  <p style="margin-top: 15px; font-weight: bold; font-size: 1.1em; color: #00AEDA; letter-spacing: 1px;">💳 GOPAY SUPPORT</p>


