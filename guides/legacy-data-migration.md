# Pemeriksaan migrasi data Laravel

`migration:preflight` memeriksa sumber PostgreSQL dalam mode baca-saja, tanpa menampilkan password, hash, rekening, atau data personal. Semua query dibatasi perusahaan yang dipilih. Koneksi diberi `default_transaction_read_only=on` dan batas waktu query. Tidak ada opsi apply pada perintah ini.

Simpan `LEGACY_DATABASE_URL` dalam environment operator atau file environment privat yang diabaikan Git. Jangan memasukkan URL berkredensial ke argumen perintah atau chat.

```sh
bun run migration:preflight --company-id 1
bun test scripts/legacy-preflight.test.ts
```

Laporan memuat jumlah data, hambatan pemetaan dan kebutuhan rekonsiliasi. Pemeriksaan lokal pada `absensi_pg17`, perusahaan 1, menemukan 35 akun, 3.540 presensi, 37 payroll, 6 reimbursement dan 0 jadwal. Sumber tidak diubah.

Sebelum membuat importer apply, tentukan:

- Tenant tujuan dan mapping ID lama ke UUID baru yang tersimpan dalam batch/jurnal agar retry idempoten.
- Akun pemilik tujuan secara eksplisit; tidak semua role lama dipromosikan otomatis.
- Reset password untuk 35 hash legacy yang tidak kompatibel dengan Argon2 backend baru. Session, token reset, API token dan bukti biometrik lama tidak dipindahkan sebagai autentikasi sah.
- Kebijakan enam payroll yang memiliki pecahan rupiah, sementara kalkulasi baru memakai rupiah penuh. Jangan membulatkan diam-diam atau mengubah slip terpublikasi.
- Pemetaan nama bank lama ke kode bank yang digunakan pada format ekspor perusahaan.
- Pemindahan lampiran/foto ke penyimpanan privat dengan manifest dan checksum, serta rekonsiliasi jumlah per perusahaan.

Tahap selanjutnya adalah dry-run pemetaan, review hasil, impor atomik per batch, dan rekonsiliasi sumber/tujuan. Database sumber tetap dipertahankan. Alat preflight ini belum merupakan importer atau sertifikasi bahwa semua data historis sudah dipindahkan.
