# Tinjauan RBAC — 28 Oktober 2026

## Model yang berlaku

| Akun | Akses bawaan | Batas |
| --- | --- | --- |
| Owner | Semua capability perusahaan, termasuk pengaturan akses | Tetap dibatasi perusahaan sesi; bukan administrator global seluruh tenant |
| Admin | Operasional perusahaan termasuk payroll, audit, sistem | Tidak bisa mengelola akses, provider, aturan payroll atau format ekspor bank yang khusus owner |
| Manager | Inbox persetujuan dan keputusan tahap manager | Pengajuan bawahan langsung; tidak menyetujui pengajuan sendiri atau tahap final tanpa izin tambahan |
| Employee | Layanan dan data pribadi | Tidak mempunyai capability administratif bawaan |

Custom role menggantikan izin bawaan. Grant menambah izin; deny mengalahkan role dan grant. `admin.access.manage` tidak dapat didelegasikan. Owner dilindungi dari override serta perubahan status melalui pengelolaan karyawan. Hak efektif dibaca ulang pada permintaan API berikutnya. Permintaan yang sudah berjalan tidak dibatalkan secara retroaktif.

## Temuan dan perbaikan

1. Endpoint SSE `/v1/events/stream` sebelumnya mengirim agregat perusahaan, waktu audit terakhir dan jumlah koneksi database kepada semua sesi. Respons kini menyaring field sesuai capability; manager hanya mendapat hitungan persetujuan dalam cakupannya. Notifikasi tetap milik pengguna sendiri.
2. Halaman penjadwalan sebelumnya memerlukan izin kelola master shift dan lihat direktori selain izin penjadwalan. `/v1/admin/scheduling-options` kini menyediakan pilihan anggota aktif (ID, nama, kode karyawan) dan shift dalam perusahaan dengan izin penjadwalan saja. Endpoint direktori dan mutasi master shift tetap menolak akses tersebut.
3. Guard frontend kini memetakan URL alias ke izin tujuan dan menormalkan trailing slash. Label izin impor presensi ditambahkan agar tidak muncul sebagai kode permission mentah.

## Cakupan pengujian

Suite lokal memeriksa owner protection, penolakan delegasi hak akses, custom role, deny precedence, perubahan izin tanpa login ulang, CSRF, self-approval, cakupan manager, akses lintas tenant, audit perubahan akses, lookup penjadwalan dan field SSE. Suite operasi juga memeriksa pembatasan konfigurasi provider/payroll/bank pada owner.

Ini tinjauan kode rewrite dan pengujian lokal, bukan pembuktian konfigurasi production atau audit penetrasi menyeluruh. Admin bawaan mempunyai akses operasional luas termasuk gaji; gunakan custom role untuk HR, finance atau pembaca saja. `requests.approvals.company` memperluas cakupan perusahaan tetapi tidak memberikan hak keputusan tahap manager/admin dengan sendirinya. Akses provider/bank vendor tetap mengikuti konfigurasi integrasi.
