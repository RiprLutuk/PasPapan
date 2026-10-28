# Dashboard operasional

`GET /v1/admin/overview` memerlukan `admin.dashboard.view`. Agregat anggota, kehadiran, lokasi dan antrean selalu dibatasi perusahaan sesi. Tren 14 hari memakai tanggal lokal perusahaan dan membandingkan catatan check-in dengan penetapan shift; ini bukan perhitungan persentase keterlambatan.

Panel identitas presensi memerlukan `admin.attendances.view`. Jejak audit memerlukan `admin.audit.view`. Kesehatan database, traffic dan log error API memerlukan `admin.system.view`. Backend menyaring field tersebut; menyembunyikan panel di FE bukan kontrol otorisasi satu-satunya.

Traffic adalah request HTTP API dengan sesi perusahaan aktif, termasuk polling dan SSE. Middleware mencatat jumlah request, error 5xx dan total waktu hingga response dibentuk. Grafik memakai bucket per jam dalam jendela 24 jam; waktu sebelum proses API mulai ditandai belum terukur. Latensi adalah rata-rata waktu respons API, bukan waktu transfer berkas atau waktu loading browser.

Pengukuran berada di memori proses, dibatasi 1.000 perusahaan dan 24 bucket per perusahaan. Log menyimpan maksimum 20 error 5xx per perusahaan, dengan waktu, metode, template route, status dan durasi. Query string, ID pada URL, header, cookie, kredensial, payload dan isi exception tidak disimpan. Pengukuran direset saat restart dan tidak digabung antar replica. Ini bukan statistik pengunjung/CDN Cloudflare atau pengganti sistem log terpusat.

Dashboard menyegarkan data setiap 60 detik saat tab terlihat dan menyediakan tombol segarkan. Kegagalan mengambil telemetry ditampilkan sebagai tidak tersedia; tidak diubah menjadi status layanan sehat. Data chart dapat dibuka sebagai tabel.

Validasi lokal mencakup isolasi tenant, pembatasan field pada role khusus dashboard, tanggal perusahaan, ringkasan direktori, bucket/error telemetry, serta tata letak desktop/mobile dan tema terang/gelap. Fixture pengujian lokal tidak dikirim ke production.
