# Pusat Dokumentasi PasPapan

Mulai dari halaman ini kalau bingung harus membaca file yang mana. README utama dibuat sebagai landing page; detail teknis dan runbook ditempatkan di folder `guides/`.

## Branch Release

| Branch | Catatan |
| --- | --- |
| `main-vps` | Jalur terbaru dan direkomendasikan untuk fitur lengkap: PostgreSQL, queue worker, scheduler, storage privat, backup, Reverb, import/export, Android/iOS wrapper, HR/finance/ops/commercial/collaboration. |
| `main` | Jalur legacy/shared-hosting ringan. Cocok untuk instalasi lama, tetapi tidak menjadi target fitur penuh. |

Untuk instalasi baru, mulai dari `main-vps`. Gunakan `main` hanya jika benar-benar membutuhkan compatibility shared-hosting lama.

## Nilai Jual `main-vps`

`main-vps` adalah jalur yang paling cocok kalau calon pengguna mencari aplikasi HRIS Indonesia yang bisa jalan di server sendiri, bukan hanya aplikasi absensi basic. Fokusnya adalah membantu perusahaan menggabungkan HR, absensi, approval, payroll preparation, finance workflow, operasional lapangan, CRM ringan, accounting foundation, dan laporan dalam satu sistem.

Yang membuat `main-vps` lebih menarik dibanding branch `main`:

- fitur lebih lengkap untuk HR, finance, operasional, commercial, collaboration, reporting, Android, dan iOS
- PostgreSQL-first untuk VPS, tetapi tetap ada portability guard untuk SQLite/MySQL compatibility
- queue worker, scheduler, backup, private file storage, Reverb, import/export background, dan operational health dashboard
- multi-company isolation untuk mencegah data bocor antar perusahaan/cabang
- workflow karyawan dan manager lebih kaya: approval center, team attendance, team kasbon, WFH, reimbursement, document request, HR checklist, dan task
- security production lebih matang: RBAC, policy/gate, private attachment, audit trail, signed backup, release checklist, dan enterprise boundary
- lebih mudah dicari oleh pasar lokal karena fiturnya relevan dengan kebutuhan Indonesia: absensi GPS/foto/QR, cuti/lembur/WFH, reimbursement, kasbon, payroll/PPh21/Coretax foundation, slip gaji PDF, laporan Excel/PDF, dan multi cabang

## Jalur Cepat

| Kebutuhan | Baca |
| --- | --- |
| Mau tahu fitur PasPapan | [features.md](./features.md) |
| Mau install di VPS | [deployment.md](./deployment.md) |
| Mau operasikan queue, scheduler, backup, Reverb | [operations.md](./operations.md) |
| Mau cek stack resmi | [modern-stack.md](./modern-stack.md) |
| Mau cek kesiapan fitur | [feature-maturity.md](./feature-maturity.md) |
| Mau integrasi mesin absensi | [attendance-integration.md](./attendance-integration.md) |
| Mau rilis Android/iOS | [operations.md](./operations.md) dan [ios-release.md](./ios-release.md) |
| Mau audit security | [security-model.md](./security-model.md) dan [security-checklist.md](./security-checklist.md) |
| Mau cek multi-company isolation | [multi-company-isolation.md](./multi-company-isolation.md) |
| Mau lanjut add-on Toko/POS premium | [premium-toko-pos-addon.md](./premium-toko-pos-addon.md) |
| Mau baca PRD Toko/POS premium | [premium-toko-pos-prd.md](./premium-toko-pos-prd.md) |
| Mau tracking task Toko/POS | [premium-toko-pos-task-tracking.md](./premium-toko-pos-task-tracking.md) |
| Mau cek release evidence | [reviewer-evidence.md](./reviewer-evidence.md) |
| Mau refactor/cleansing low-effort high-impact | [refactor-cleansing-prd.md](./refactor-cleansing-prd.md) |

## Produk

- [features.md](./features.md) berisi cakupan fitur HR, attendance, payroll, operations, commercial, accounting, collaboration, mobile, dan enterprise gate.
- [feature-maturity.md](./feature-maturity.md) menjelaskan prioritas saat ini: pematangan logic, UX, security, data integrity, test, dan operasi.
- [premium-toko-pos-addon.md](./premium-toko-pos-addon.md) menjelaskan boundary premium, scope, dan mapping legacy `toko-pandan` ke core Laravel.
- [premium-toko-pos-prd.md](./premium-toko-pos-prd.md) menjelaskan target produk, struktur menu, role, scope migrasi, dan acceptance criteria add-on Toko/POS.
- [premium-toko-pos-task-tracking.md](./premium-toko-pos-task-tracking.md) menjadi checklist kerja add-on Toko/POS dari discovery sampai cutover.
- [roadmap-10-coverage.md](./roadmap-10-coverage.md) mencatat area yang sudah kuat dan gap yang masih butuh evidence.

## Deployment Dan Operasi

- [deployment.md](./deployment.md) adalah panduan install/deploy. VPS + PostgreSQL adalah baseline production penuh.
- [operations.md](./operations.md) berisi queue, scheduler, backup, import/export retention, update workflow, Android, iOS, dan catatan produksi.
- [database-portability.md](./database-portability.md) menjelaskan PostgreSQL baseline dan MySQL/MariaDB compatibility path.
- [runbooks.md](./runbooks.md) berisi langkah cepat saat queue down, scheduler down, backup gagal, disk penuh, import/export macet, atau attendance risk incident.

## Security Dan Compliance

- [security-model.md](./security-model.md) merangkum route protection, policy/gate, feature lock, enterprise license, dan area sensitif.
- [security-checklist.md](./security-checklist.md) adalah checklist release security.
- [file-upload-security.md](./file-upload-security.md) fokus pada attachment, MIME, private disk, path traversal, dan secure download.
- [backup-restore-threat-model.md](./backup-restore-threat-model.md) menjelaskan risiko backup/restore.
- [attendance-threat-model.md](./attendance-threat-model.md) dan [attendance-risk-scoring.md](./attendance-risk-scoring.md) menjelaskan fraud absensi dan scoring.
- [compliance-retention.md](./compliance-retention.md) berisi retention data personal, audit, backup, payroll, dan attachment.
- [rbac-matrix.md](./rbac-matrix.md) berisi ringkasan permission.

## Integrasi

- [attendance-integration.md](./attendance-integration.md) menjelaskan inbound API untuk mesin absensi/gateway seperti Solution/SBG dengan API key, HMAC, idempotency, dan employee code mapping.
- [dependency-policy.md](./dependency-policy.md) menjelaskan kebijakan dependency dan audit.
- [enterprise-packaging.md](./enterprise-packaging.md) menjelaskan boundary OSS vs artifact enterprise obfuscated salted.

## Architecture Decision Records

ADR berada di [adr/](./adr/):

- [attachment-private-disk.md](./adr/attachment-private-disk.md)
- [database-centric-runtime.md](./adr/database-centric-runtime.md)
- [dynamic-qr-cache-validation.md](./adr/dynamic-qr-cache-validation.md)
- [enterprise-license-offline.md](./adr/enterprise-license-offline.md)
- [multi-company-scope-strategy.md](./adr/multi-company-scope-strategy.md)
- [shared-hosting-queue-strategy.md](./adr/shared-hosting-queue-strategy.md)

## Evidence

- [reviewer-evidence.md](./reviewer-evidence.md) dipakai reviewer untuk melihat artifact CI, command validasi, dan manual smoke checklist.
- Snapshot branch yang sudah tidak relevan sebaiknya tidak dijadikan sumber utama. Gunakan README, release checklist, dan reviewer evidence terbaru.
