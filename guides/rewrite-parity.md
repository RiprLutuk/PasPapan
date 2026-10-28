# Status rewrite dan pengujian lokal

Matriks ini mencatat workflow rewrite yang terhubung ke BE dan FE beserta batas operasionalnya. Halaman generik `workspace_records` tidak dihitung sebagai parity domain.

| Domain | Implementasi saat ini | Batas operasional dan konfigurasi |
| --- | --- | --- |
| Akun dan RBAC | Login, reset password, profil, sesi, role/grant/deny, izin per domain, pembatasan autentikasi di DB, pemilihan tenant dan rotasi sesi | Pembatasan autentikasi memakai jendela 15 menit; pemilihan tenant terikat sesi |
| Presensi | GPS, QR dinamis, riwayat, pagination/filter server, ekspor CSV lintas halaman, impor JSON dengan dry-run atomik, realisasi koreksi | Ekspor masih disusun di browser per batch; belum streaming jutaan baris. Impor maksimal 200 baris per batch dan tidak menimpa data |
| Shift | Master shift, penugasan, kalender, jadwal pribadi, persetujuan rekan, pertukaran otomatis dengan deteksi jadwal berubah | Pertukaran hanya satu hari dan dua jadwal yang sudah ditetapkan |
| Pengajuan | Tujuh jenis, dua tahap persetujuan, pembatalan, lampiran privat, buku pencairan dan cicilan, WFH terhubung presensi | Buku pembayaran mencatat transaksi eksternal, tidak mentransfer uang. Tidak ada pemotongan kasbon otomatis ke payroll |
| Cuti | Jenis/kuota, pengecualian, reservasi, kalender hari kerja/libur, snapshot hari terpakai, carry-over tahunan yang ditetapkan admin | Carry-over ditetapkan eksplisit; belum transfer otomatis sisa tahun sebelumnya atau kedaluwarsa tengah tahun |
| Pengumuman | Draft, versi, publikasi/arsip, audiens divisi, jadwal tayang, notifikasi idempoten, tanda baca/jumlah pembaca | Worker harus aktif untuk pengiriman terjadwal tanpa kunjungan pengguna |
| Aset | Inventaris, penugasan/penerimaan/pengembalian, timeline serah terima, valuasi dan depresiasi garis lurus | Depresiasi memakai bulan penuh dan nilai sisa; bukan jurnal akuntansi |
| Payroll | Periode terkunci, aturan berversi, kontribusi/tunjangan/potongan, lembur, prorata, THR, kategori/tarif efektif/progresif, snapshot perhitungan dan rekening, pemetaan/ekspor CSV bank | Tidak ada tarif resmi bawaan. Perusahaan mengonfigurasi tarif/kategori dan menyetujui format bank. Ekspor bukan transfer uang; rekonsiliasi pajak tahunan tidak otomatis |
| Organisasi | Master data, relasi divisi/jabatan dengan foreign key tenant, backfill nama lama, pilihan ID di profil, sinkronisasi nama dan versi saat master diganti | Klien lama dapat mengirim nama yang cocok dengan master; nama baru harus dikonfigurasi dahulu. Referensi nonaktif tetap dapat dipertahankan pada profil lama |
| KPI | Target, bukti, review, bobot, siklus appraisal dan skor gabungan, penutupan sesudah semua target disetujui | Skor capaian dibatasi 100%; bobot dinormalisasi per karyawan/siklus, target belum disetujui bernilai nol |
| Dokumen HR | Template berversi, snapshot variabel terisi, pratinjau/cetak PDF A4 dengan font lokal, penerbitan PDF privat, penolakan, audit/notifikasi, receipt tanda tangan terikat hash | PDF dibuat melalui dialog cetak browser lalu diunggah untuk penerbitan. Tanda tangan sebenarnya memakai provider; dokumen biasa tidak dilabeli bertanda tangan |
| Onboarding/offboarding | Checklist, template reusable dengan snapshot langkah, penutupan terkunci, pemeriksaan aset | Status kerja tetap diubah eksplisit di direktori karyawan |
| Biometrik | Consent, profil privat, tantangan provider bertanda tangan, expiry dan bukti sekali pakai; pendaftaran menolak konfigurasi/bukti yang tidak sah. Provider dinamis dikelola super admin/owner di `/admin/integrations` | Vendor nyata harus mendukung adapter yang dipakai. Kredensial terenkripsi membutuhkan master key server; descriptor browser bukan otorisasi presensi |

Workflow aplikasi pada matriks ini sudah mempunyai implementasi BE dan FE. Integrasi provider mengikuti [kontrak bridge](provider-integrations.md); konfigurasi kosong sengaja tidak mengaktifkan verifikasi. Hal ini tidak berarti seluruh fitur historis Laravel di luar matriks atau migrasi seluruh data legacy telah disertifikasi setara.

## Pemeriksaan penerimaan

- Akun memilih tenant secara eksplisit; perpindahan tenant mematikan token sesi lama, termasuk uji isolasi dan pembatasan autentikasi.
- Koreksi disetujui mengubah catatan presensi; tukar shift memerlukan consent rekan dan memperbarui dua penugasan secara atomik.
- WFH harus disetujui untuk tanggal perusahaan saat ini. GPS tetap disimpan, sedangkan mode kantor memakai QR aktif dan radius lokasi.
- Kalender cuti menghitung hari kerja/libur dan menyimpan snapshot hari terpakai. Perubahan kalender tidak mengubah pemakaian pengajuan lama.
- Pencairan/cicilan menolak pengajuan belum disetujui, pencatatan sendiri, referensi duplikat dan nominal di atas sisa yang sah.
- Template dokumen membatasi variabel, mengisi data tenant yang tepat dan mempertahankan snapshot setelah template diedit. PDF browser diuji dengan font lokal; teks HTML tidak dieksekusi.
- Payroll menguji batas tarif efektif/progresif, prorata, THR, penghasilan tidak kena pajak, kategori yang tidak lengkap, snapshot berversi, dan rekening ekspor yang tidak berubah sesudah publikasi.
- Verifikasi provider diuji dengan receipt lokal bertanda tangan dan penolakan receipt palsu, salah tenant/hash, atau dipakai ulang. Tes protokol tidak menggantikan pengujian sertifikasi vendor.

## Menjalankan lokal

Konfigurasi tetap dipisah di `apps/api/.env` dan `apps/web/.env`. Gunakan database rewrite, bukan database Laravel lama.

```sh
bun run dev:services
```

Frontend: `http://127.0.0.1:4173`. API mengikuti `API_BIND` di `apps/api/.env` (default `127.0.0.1:3000`). `AUTO_MIGRATE=true` menerapkan migrasi saat API mulai.

```sh
cargo fmt --check
cargo clippy --workspace -- -D warnings
cargo test --workspace
bun run check
bun run test:web
bun run build
bun run test:integration
```

Tes integrasi memerlukan API aktif serta database lokal bernama `paspapan_rewrite`. Set `TEST_API_URL` jika port berbeda. Suite membuat fixture perusahaan terisolasi dan membersihkannya sesudah tes. Jangan menjalankannya terhadap production.

Data generik lama tidak dihapus. Konversi data domain perlu mapping eksplisit; judul/deskripsi bebas tidak bisa dianggap mempunyai employee, nominal, tanggal, atau status persetujuan yang sah. Jalankan alat [preflight baca-saja](legacy-data-migration.md) sebelum menyusun batch impor.

## Bukti validasi lokal — 28 Oktober 2026

- Rust: formatting, Clippy seluruh target, dan 26 unit test workspace lulus.
- Frontend: pemeriksaan tipe Svelte tanpa error/warning dan 45 tes web lulus.
- Build adapter Node dan Cloudflare berhasil. SDK wajah masih menghasilkan peringatan ukuran chunk; modul tersebut dimuat sesuai kebutuhan dan tidak dimuat ketika layanan verifikasi nonaktif.
- Integrasi tanpa provider environment: 305 pemeriksaan HTTP operasional; akun/direktori 86 pemeriksaan; isolasi tenant 13 tes negatif; suite pengajuan/RBAC juga lulus.
- Integrasi dengan kunci provider khusus pengujian: 323 pemeriksaan HTTP operasional lulus, termasuk receipt biometrik/tanda tangan, expiry, hash, pencegahan pemakaian ulang, rotasi konfigurasi dan pergantian provider bersamaan dengan pembuatan tantangan. Ini uji kontrak, bukan layanan vendor sungguhan.
- Panel integrasi diuji pada viewport 390 px: simpan/edit berhasil, kredensial tidak diisi kembali, font lokal aktif dan tidak ada overflow. Dua tes preflight migrasi juga lulus; sumber legacy hanya dibaca.
- Browser: edit/simpan rincian payroll berbasis aturan, panel pengaturan bank dan tarif, serta log/impor presensi diuji pada viewport 390 px tanpa overflow. Kanvas desktop pada viewport 1440 px mengisi area konten 1208 px. Font lokal terdeteksi aktif.
- PDF HR: dokumen satu halaman berhasil dihasilkan melalui browser dengan font lokal; placeholder dalam nilai data dan teks HTML tidak dieksekusi sebagai template/kode.

Tarif pajak/BPJS, format bank dan kredensial vendor tetap menjadi konfigurasi perusahaan. Tidak ada tarif mock atau verifikasi client yang otomatis dianggap sah untuk produksi.
