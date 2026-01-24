<div align="center">

![PasPapan Hero](./public/hero-banner.png)

# PasPapan - Enterprise Attendance System

**The Ultimate GPS Geofencing, Biometric Verification & Payroll Solution for Modern Enterprises.**

> Stop buddy punching, eliminate fake GPS attendance, and streamline your payroll in one powerful platform.

[![Lang-User](https://img.shields.io/badge/Language-Indonesian-red?style=flat&logo=google-translate&logoColor=white)](./README.id.md)
[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-4E56A6?style=flat&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-38B2AC?style=flat&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Capacitor](https://img.shields.io/badge/Capacitor-8.0-1199EE?style=flat&logo=capacitor&logoColor=white)](https://capacitorjs.com)

</div>

---

## Overview

**PasPapan** isn't just an attendance app; it's a complete **Workforce Management System**. Designed for the modern hybrid era, it bridges the gap between physical security and remote flexibility.

Whether your team is in the office, on the field, or working from home, PasPapan ensures every check-in is **verified, authentic, and actionable**.

---

## 📑 Table of Contents

- [System Workflow](#system-workflow)
- [Key Features](#key-features)
- [Application Previews](#application-previews)
- [Technology Stack](#technology-stack)
- [Installation (VPS / Server)](#installation)
- [Demo Credentials](#demo-credentials)
- [Troubleshooting](#troubleshooting)

---

## <a id="system-workflow"></a>🔄 System Workflow

1.  **Check-In Request**: User initiates attendance via Mobile App / PWA.
2.  **Validation Layer**:
    *   **GPS**: Verifies user is within permitted office radius (Geofencing).
    *   **Anti-Fake GPS**: Analyzes signal accuracy and variance.
    *   **Biometrics**: Scans Face ID matching user profile.
3.  **Data Processing**: Server records timestamp, coordinates, and photo evidence.
4.  **Administrative Action**: Supervisors receive notifications; data flows into Payroll calculation automatically.

---

## <a id="key-features"></a>Key Features

### 🛡️ Unbeatable Security & Validation
- **Smart Geofencing**: High-precision location locking ensures employees can *only* check in from designated zones.
- **Anti-Fake GPS Technology**: Advanced algorithms detect and block location spoofing, mock location apps, and signal manipulation.
- **Face ID Verification**: AI-powered facial recognition eliminates "buddy punching" forever.
- **Device Locking**: (Optional) Restrict accounts to specific trusted devices for maximum security.
- **Secure Photo Access**: Privacy-first file storage ensuring attendance photos are served through secure, authorized channels only (no public links).
- **Data Encryption**: Enterprise-grade protection for sensitive user data.

### 💼 Comprehensive HR Suite
- **Automated Payroll**: Say goodbye to spreadsheets. Auto-calculate salaries, overtime, and deductions with professional PDF payslip generation.
- **Smart Shift Management**: Flexible scheduling that adapts to your team's rotation.
- **Digital Workflow**: Streamlined approval chains for Leave, Overtime, and Reimbursement requests.

### 🚀 Enterprise-Grade Platform
- **Real-Time Analytics**: Make data-driven decisions with a powerful dashboard tracking attendance trends and anomalies.
- **Native Mobile Experience**: A lightning-fast, offline-capable app for Android & iOS (via PWA).
- **Global Ready**: Multi-language support (English & Indonesian) for diverse teams.

---

## <a id="application-previews"></a>📸 Application Previews

<details>
<summary><b>💻 Admin Dashboard (Web)</b></summary>
<br>

| Dashboard & Monitoring | Attendance Data |
| :---: | :---: |
| ![Dashboard](./screenshots/admin/01_Dashboard.png) | ![Attendance](./screenshots/admin/02_DataAbsensi.png) |

| Leave Approval | Overtime Management |
| :---: | :---: |
| ![Leave](./screenshots/admin/03_PersetujuanCuti.png) | ![Overtime](./screenshots/admin/04_ManagementLembur.png) |

| Shift Scheduling | Analytics Dashboard |
| :---: | :---: |
| ![Shift](./screenshots/admin/05_ManagemetShift.png) | ![Analytics](./screenshots/admin/06_DashboardAnalitik.png) |

| Calendar & Holidays | Announcements |
| :---: | :---: |
| ![Calendar](./screenshots/admin/07_LiburKalender.png) | ![Announcements](./screenshots/admin/08_Announcements.png) |

| Payroll Management | Reimbursements |
| :---: | :---: |
| ![Payroll](./screenshots/admin/09_Payroll.png) | ![Reimbursements](./screenshots/admin/10_Reimbursement.png) |

| Allowances & Deductions | Barcode Management |
| :---: | :---: |
| ![Allowances](./screenshots/admin/11_Allowances.png) | ![Barcode](./screenshots/admin/12_Barcode.png) |

| App Settings | Maintenance Mode |
| :---: | :---: |
| ![Settings](./screenshots/admin/13_AppSettings.png) | ![Maintenance](./screenshots/admin/14_Maintance.png) |

| User Import/Export | Attendance Export |
| :---: | :---: |
| ![Export Users](./screenshots/admin/15_ExportImportEmployee.png) | ![Export Attendance](./screenshots/admin/16_ExportImportAttendance.png) |

</details>

<details>
<summary><b>📱 Mobile App (Android/PWA)</b></summary>
<br>

| Login Screen | Home (Face Registered) | Home (New User) |
| :---: | :---: | :---: |
| <img src="./screenshots/users/01_Login.png" width="250"> | <img src="./screenshots/users/02_HomeFace.png" width="250"> | <img src="./screenshots/users/03_Home.png" width="250"> |

| Attendance History | Leave Request | Overtime Request |
| :---: | :---: | :---: |
| <img src="./screenshots/users/04_History.png" width="250"> | <img src="./screenshots/users/05_LeaveRequest.png" width="250"> | <img src="./screenshots/users/06_Overtime.png" width="250"> |

| Reimbursement | Payslip | Profile |
| :---: | :---: | :---: |
| <img src="./screenshots/users/07_Reimbursement.png" width="250"> | <img src="./screenshots/users/08_Payslip.png" width="250"> | <img src="./screenshots/users/09_Profile.png" width="250"> |

| Schedule | Face Registration | Scan QR |
| :---: | :---: | :---: |
| <img src="./screenshots/users/10_Schedule.png" width="250"> | <img src="./screenshots/users/11_FaceID.png" width="250"> | <img src="./screenshots/users/12_ScanQR.png" width="250"> |

| Scan Error | Selfie Evidence | Check-Out Success |
| :---: | :---: | :---: |
| <img src="./screenshots/users/13_ScanRQError.png" width="250"> | <img src="./screenshots/users/14_Selfi.png" width="250"> | <img src="./screenshots/users/15_CheckOut.png" width="250"> |

| After Check-Out | | |
| :---: | :---: | :---: |
| <img src="./screenshots/users/16_HomeAfterCheckOut.png" width="250"> | | |

</details>

---

## <a id="technology-stack"></a>Technology Stack

### ⚙️ Powerful Configuration
- **Dynamic Settings Engine**: Configure Timezones, Office Radius, Attendance Rules, and Branding directly from the Admin Panel.
- **Role-Based Access Control (RBAC)**: Strict segregation of duties between Super Admin, HR Admin, and Employees using dedicated middleware.
- **Multi-Tenant Ready**: Designed with granular scopes to support complex organizational structures.

---

## <a id="technology-stack"></a>Technology Architecture

**Built on a solid foundation of industry-standard security and performance.**

### 🔐 Security & Middleware Layer
- **Authentication**: Laravel Sanctum (API Tokens) & Jetstream (Session Management).
- **Authorization**: Custom Middleware Pipeline (`auth:sanctum`, `verified`, `role:admin/user`) ensures strict access control.
- **Protection**: CSRF Protection, XSS Sanitization, and SQL Injection prevention via Eloquent ORM.

### 🏗️ Backend Core
- **Framework**: Laravel 11.x (PHP 8.3)
- **Database**: MySQL / MariaDB (Optimized Indexing)
- **Queue System**: Database/Redis driver for asynchronous Email & Notification dispatch.

### 🎨 Frontend & Mobile
- **Web Interface**: Blade + Livewire 3 (Reactive components without API overhead).
- **Mobile Engine**: Capacitor 8 Bridge accessing Native Geolocation & Camera APIs.
- **Styling**: Tailwind CSS 3.4 (Utility-first, Dark Mode native).

---

## <a id="installation"></a>🛠️ Installation (VPS / Server)

### Prerequisites
- PHP 8.3 or higher
- Composer
- Node.js & NPM/Bun
- MySQL Server

### Setup Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/RiprLutuk/PasPapan.git
   cd PasPapan
   ```

2. **Install Dependencies**
   ```bash
   # Backend
   composer install

   # Frontend
   bun install  # or npm install
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your database credentials in the `.env` file.*

4. **Database Migration & Seeding**
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

5. **Run Application**
   ```bash
   # Terminal 1: Vite Dev Server
   bun run dev

   # Terminal 2: Laravel Server
   php artisan serve
   ```

### 💡 Production Tip (VPS)
For production servers, use:
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Mobile Build (Android)

```bash
bun run build
npx cap sync android
cd android
./gradlew assembleDebug
```
*Output APK located at: `android/app/build/outputs/apk/debug/app-debug.apk`*

---

## <a id="demo-credentials"></a>🧪 Demo & Credentials

**Experience the full application live:**
### 🌐 [paspapan.pandanteknik.com](https://paspapan.pandanteknik.com)

Use these accounts to explore the restricted demo environment:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin123@paspapan.com` | `12345678` |
| **User** | `user123@paspapan.com` | `12345678` |



## <a id="troubleshooting"></a>❓ Troubleshooting

**Q: GPS not working / Camera blocked?**
> A: Ensure you are serving the app via **HTTPS** (e.g., using Cloudflare Tunnel, Ngrok, or Valet Secure). Browsers block sensitive permissions on HTTP (except localhost).

**Q: Maps not loading?**
> A: This app uses OpenStreetMap/Leaflet which is free. Ensure your device has internet access to load map tiles.

---


### ☕ Traktir Developer Kopi

<img src="./screenshots/donation-qr.jpeg" width="180px" style="border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

---

---

### 💖 Special Thanks & Credits
This project was initially built upon the solid core foundation provided by:
*   [**Absensi Karyawan GPS Barcode**](https://github.com/ikhsan3adi/absensi-karyawan-gps-barcode) by [**Ikhsan3adi**](https://github.com/ikhsan3adi).
*   Modified and enhanced for enterprise scalability by [**RiprLutuk**](https://github.com/RiprLutuk) in collaboration with **Vibecode**.

Developed by <a href="https://github.com/RiprLutuk"><b>RiprLutuk</b></a>

