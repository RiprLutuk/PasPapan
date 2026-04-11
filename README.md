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

| Dashboard & Monitoring | Attendance Data |
| :---: | :---: |
| ![Dashboard](./screenshots/admin/01_Dashboard.png) | ![Attendance](./screenshots/admin/02_DataAbsensi.png) |

| Leave Approval | Overtime Management |
| :---: | :---: |
| ![Leave](./screenshots/admin/03_PersetujuanCuti.png) | ![Overtime](./screenshots/admin/04_ManagementLembur.png) |

| Payroll & Allowances | App Settings |
| :---: | :---: |
| ![Payroll](./screenshots/admin/09_Payroll.png) | ![Settings](./screenshots/admin/13_AppSettings.png) |

</details>

<details>
<summary><b>📱 Mobile App (Android/PWA)</b></summary>
<br>

| Home Dashboard | Face Verification |
| :---: | :---: |
| <img src="./screenshots/users/02_HomeFace.png" width="250"> | <img src="./screenshots/users/11_FaceID.png" width="250"> | 

| Leave Workflow | Reimbursements |
| :---: | :---: |
| <img src="./screenshots/users/05_LeaveRequest.png" width="250"> | <img src="./screenshots/users/07_Reimbursement.png" width="250"> |

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


