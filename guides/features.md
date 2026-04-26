# Fitur PasPapan

Dokumen ini merangkum cakupan produk dan catatan teknis fitur. README utama sengaja dibuat ringkas; detail produk ditempatkan di sini.

## Operasi Admin

Area admin mencakup:

- dashboard dan notifikasi
- direktori karyawan
- atasan langsung karyawan untuk routing approval, notifikasi, dan reporting tim
- data absensi dan reporting
- pusat laporan HR operasional di `System > Reports`
- approval cuti
- master jenis cuti di `Master Data > Leave Types`
- approval koreksi absensi
- approval tukar/perubahan shift di `Attendance > Shift Swap Approvals`
- manajemen lembur
- kalender libur
- shift dan jadwal kerja
- barcode dan Dynamic QR
- reimbursement
- payroll dan payroll settings
- kasbon
- dokumen karyawan
- lifecycle akun karyawan
- role-based access control untuk menu dan aksi admin
- import/export data user, absensi, dan activity log
- export report absensi PDF/XLSX via background job
- template import user dan absensi sesuai schema terbaru
- KPI settings
- analytics dashboard
- activity log
- pengumuman
- system maintenance, cache operations, backup center, restore center, dan cleanup tools

## Self-Service Karyawan

Sisi karyawan mencakup:

- status absensi di beranda
- scan check-in dan check-out
- riwayat absensi
- koreksi absensi dengan review supervisor dan admin
- koreksi check-in dan check-out dalam satu pengajuan, termasuk shift malam atau check-out setelah tengah malam
- pengajuan cuti
- pengajuan lembur
- reimbursement
- kasbon dan pantauan kasbon tim sesuai izin
- jadwal shift
- pengajuan swap/perubahan shift
- dokumen karyawan
- approval tim dan riwayat approval
- slip gaji
- Face ID enrollment
- aset pribadi dan pengembalian aset dengan OTP
- permintaan penghapusan akun
- penilaian performa
- notifikasi

## Absensi dan Lokasi

Workflow absensi mendukung:

- static barcode untuk deployment konvensional
- Dynamic QR dengan signed rotating token
- validasi latest-token dan konsumsi token sekali pakai
- scanner browser via camera APIs
- native Android scanner bridge saat berjalan di shell Capacitor
- GPS browser dan Capacitor Geolocation
- cached-location recovery
- preview peta dan handoff Google Maps
- bukti foto absensi
- Face ID verification jika diaktifkan
- anti-mock-location jika runtime Android menyediakan status tersebut
- secure attachment route untuk file sensitif

## Face ID

Face ID berjalan di browser saat enrollment dan capture absensi:

- memakai `face-api.js`
- TinyFaceDetector dan 68-point landmarks
- movement-based liveness check
- descriptor numerik, bukan raw selfie image
- descriptor 129-value baru dan legacy 128-value tetap diterima untuk kompatibilitas

Setting utama di UI adalah `attendance.require_face_verification`. Key lama `attendance.require_face_enrollment` masih didukung secara internal untuk backward compatibility.

## Dynamic QR

Dynamic QR dirancang untuk menghindari reuse QR statis:

- token memiliki payload bertanda tangan
- token punya issue time, expiry, dan nonce
- hanya token terbaru untuk barcode tersebut yang diterima
- token expired ditolak tanpa grace window
- token dikonsumsi setelah scan dynamic sukses
- cache store wajib berfungsi karena latest-token validation memakai cache

## Cuti dan Approval

`admin/leaves` menampilkan pengajuan cuti dari semua approval status secara default, dengan filter approval status dan request type. Pengajuan yang ditolak tetap mempertahankan tipe request asli di `status`, sedangkan keputusan review disimpan di `approval_status`.

Jenis cuti dikelola dari `Master Data > Leave Types`. Setiap jenis cuti dapat diberi kategori tahunan, sakit, atau khusus; dapat diwajibkan lampiran; dapat diaktifkan/nonaktifkan; dan hanya kategori yang ditandai memakai kuota yang mengurangi kuota cuti tahunan.

## Koreksi Absensi dan Jadwal

Koreksi absensi dipakai saat user perlu memperbaiki jam masuk, jam keluar, atau shift pada tanggal tertentu.

- jika user memiliki supervisor, request masuk ke review supervisor lebih dulu
- jika tidak ada supervisor, request langsung menunggu review admin
- form menerima nilai tanggal dan jam penuh
- satu pengajuan dapat memperbaiki check-in dan check-out sekaligus
- shift malam, shift yang berakhir tepat tengah malam, dan check-out tanggal berikutnya didukung
- snapshot jam aktual ditampilkan sebagai pembanding saat tersedia

Pengajuan tukar/perubahan shift bisa diajukan untuk tanggal yang sudah punya jadwal maupun tanggal kosong. Untuk tanggal kosong, jadwal baru dibuat atau diperbarui saat request disetujui.

## Struktur Atasan

Data karyawan mendukung `Direct Manager` eksplisit dari form create/edit employee. Field ini menjadi sumber utama untuk supervisor, bawahan, notifikasi approval, halaman approval, dan Team Kasbon.

Untuk kompatibilitas data lama, sistem masih memakai fallback divisi dan job level saat `Direct Manager` belum diisi. Form employee menolak assignment ke diri sendiri atau rantai atasan yang melingkar.

## Import, Export, dan Report Background

Import/export admin memakai model run yang bisa dipantau dari UI:

- export user
- export absensi
- export activity log
- import user
- import absensi
- export report absensi PDF/XLSX

Run list menampilkan status `queued`, `running`, `completed`, atau `failed`, progress row, file hasil, tombol download, dan ringkasan error.

Import user:

- bisa membuat user baru
- bisa memperbarui user berdasarkan `id`, email, atau NIP
- manager bisa dicocokkan lewat NIP atau email
- konflik NIP/email/phone dicatat sebagai error row
- row valid lain tetap bisa diproses

Template user mencakup field schema terbaru seperti status karyawan, bahasa, manager, kode wilayah, email verified, dan created timestamp. Template absensi memakai contoh datetime lengkap untuk `time_in` dan `time_out`, termasuk shift malam lintas tanggal.

Run terminal yang lebih lama dari 24 jam disembunyikan dari daftar terbaru dan dipangkas otomatis oleh scheduler.

## Modul Enterprise

Repository ini memuat modul dan penguncian enterprise untuk:

- payroll management lanjutan
- analytics dan reporting lanjutan
- appraisal berbasis KPI
- lifecycle aset perusahaan
- import/export
- RBAC menu dan izin aksi admin
- dokumen karyawan
- kasbon dan approval finance
- backup automation
- validasi lisensi enterprise dan hardware fingerprint

Generate fingerprint hardware server:

```bash
php artisan enterprise:hwid
```

## Performa Admin

Beberapa halaman yang rawan lambat pada data besar memakai query ter-paginate dan eager loading terarah:

- data absensi admin hanya memuat attendance untuk user yang tampil pada halaman aktif
- report absensi membatch record attendance sesuai user dan rentang tanggal
- ringkasan employee status memakai aggregate query
- activity log memakai range waktu agar indeks `created_at` bisa dipakai
- dashboard admin menghindari `whereIn` besar untuk scope global
- approval cuti dan shift swap memakai pagination query dan indeks tambahan

## Tech Scan

Backend:

- Laravel `11`
- PHP `8.2+`
- Livewire `3`
- Jetstream, Fortify, dan Sanctum
- MySQL atau MariaDB
- queue, cache, notification, dan session berbasis database sebagai default

Frontend:

- Tailwind CSS `3.4`
- Vite `7`
- Alpine via Blade dan Livewire screens
- Tom Select
- Chart.js
- Leaflet dan marker clustering
- SweetAlert2
- Heroicons via Blade UI Kit

Tool dokumen dan data:

- `maatwebsite/excel`
- `barryvdh/laravel-dompdf`
- `endroid/qr-code`
- `intervention/image`
- `ballen/distical`

Testing dan tooling:

- Pest `4`
- Laravel Pint
- Bun
- feature tests untuk attendance enforcement, Dynamic QR, backup jobs, maintenance security, leave approval, media access, queued report export, import/export retention, template import, overtime manager, dan koreksi absensi lintas hari
