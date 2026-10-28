# Integrasi verifier dan aturan payroll

Provider belum dipilih. Integrasi di repository adalah kontrak bridge, bukan SDK vendor atau jaminan liveness. Tanpa konfigurasi, pendaftaran biometrik ditolak. Profil lama tidak diberi `server_verified` secara otomatis. Descriptor browser tidak digunakan sebagai otorisasi presensi.

## Pengaturan dinamis oleh super admin

Buka `/admin/integrations` memakai akun super admin/owner perusahaan. Panel mendukung beberapa konfigurasi vendor untuk biometrik dan tanda tangan, dengan satu provider aktif per jenis. URL, nama, kredensial, aktivasi, penonaktifan, dan penggantian kredensial dikelola di panel tanpa redeploy. Daftar driver berasal dari backend; saat ini adapter yang tersedia adalah `signed_bridge_v1`. Vendor yang memakai protokol berbeda tetap memerlukan adapter, bukan sekadar mengganti URL.

Konfigurasi terikat perusahaan pada sesi. Admin operasional tidak dapat membaca/mengubah konfigurasi provider, mengubah kebijakan payroll atau menyimpan format bank; operasi payroll dan rekening tetap mengikuti izin payroll. Panel menampilkan metadata dan indikator keberadaan kredensial, tidak pernah nilai kredensial lama. Field kredensial kosong berarti pertahankan; penghapusan memerlukan opsi eksplisit dan provider harus nonaktif.

Backend menyimpan kredensial dengan AES-256-GCM dan nonce acak baru setiap enkripsi. Associated data mengikat ciphertext ke perusahaan, provider dan purpose. Implementasi memakai [RustCrypto AES-GCM](https://docs.rs/aes-gcm/0.10.3/aes_gcm/). Master key berada di server di luar database:

```dotenv
INTEGRATION_MASTER_KEY=
```

Nilainya adalah base64 dari 32 byte acak. Lokal sudah disiapkan tanpa menampilkan key; deployment lain memerlukan bootstrap key satu kali. Simpan backup key bersama backup database secara terpisah. Mengganti master key sembarangan membuat kredensial lama tidak dapat dibuka; rotasi kredensial provider sehari-hari dilakukan lewat panel.

Setiap perubahan provider menaikkan versi, membuat audit tanpa kredensial dan membatalkan tantangan belum selesai. Challenge terikat provider/versi; receipt lama tidak dapat digunakan setelah konfigurasi diganti. Penonaktifan lewat panel tidak mengaktifkan kembali konfigurasi environment secara diam-diam.

## Kompatibilitas konfigurasi API lama

Konfigurasi lama berikut tetap terbaca hanya jika perusahaan belum mempunyai konfigurasi panel untuk purpose tersebut. Utamakan panel untuk pengelolaan berikutnya:

```dotenv
BIOMETRIC_VERIFIER_URL=https://verifier.example/enroll
BIOMETRIC_VERIFIER_SECRET=
DOCUMENT_SIGNER_URL=https://signer.example/verify
DOCUMENT_SIGNER_SECRET=
```

Gunakan secret acak berbeda, minimal 32 byte. URL harus HTTPS tanpa query. Jangan taruh secret di frontend. Bridge harus memverifikasi identitas, liveness dan kecocokan foto melalui provider sebenarnya; daftar gerakan yang dikirim browser bukan bukti. Untuk dokumen, bridge memvalidasi tanda tangan digital pada PDF yang diunggah, termasuk rantai sertifikat dan kebijakan validitas. PDF biasa boleh diterbitkan sebagai dokumen tanpa tanda tangan; sistem hanya menampilkan status verifikasi tanda tangan bila menerima receipt provider yang sah. Membuat tanda tangan pada PDF memerlukan alur vendor terlebih dahulu, lalu unggah PDF hasilnya.

## Protokol bridge

1. Frontend mengunggah foto/PDF privat. `POST /v1/providers/challenges` menerima `purpose` (`biometric_enrollment` atau `document_signature`) dan `resource_id`. API memeriksa tenant, pemilik berkas, jenis berkas dan izin dokumen.
2. API membuat challenge 10 menit, mengembalikan `verification_url`. Query `request` berisi `base64url(JSON).base64url(HMAC-SHA256(payload))`, tanpa padding. HMAC dihitung atas string payload base64url, memakai secret sesuai purpose.
3. Bridge memverifikasi HMAC dan expiry sebelum melakukan pekerjaan. JSON memuat `type=request`, `challenge_id`, `company_id`, `user_id`, `purpose`, `resource_id`, `resource_sha256`, `expires_at` (epoch detik).
4. Popup mengirim `window.opener.postMessage({type:'paspapan.provider.ready',challenge_id}, APP_ORIGIN)`. Frontend membalas `{type:'paspapan.provider.resource',challenge_id,data_base64}` hanya ke origin dan window provider. Bridge wajib memeriksa origin aplikasi, hash byte berkas terhadap `resource_sha256`, dan melakukan verifikasi nyata. Jangan mempercayai descriptor atau hasil liveness client.
5. Sesudah hasil provider sah, bridge menandatangani receipt dengan format HMAC sama: salin binding challenge, ubah `type` menjadi `receipt`, tambahkan `verified:true` dan `reference` (ID hasil provider, maksimal 200 karakter). Jangan mengembalikan secret.
6. Popup mengirim `{challenge_id,receipt}` ke opener dengan target origin aplikasi yang tepat. Frontend meneruskan receipt saat menyimpan biometrik atau menerbitkan dokumen. API memverifikasi signature, tenant, pengguna, purpose, berkas, hash, expiry dan konsumsi sekali pakai dalam transaksi yang sama dengan penyimpanan.

Receipt palsu, kedaluwarsa, dipakai ulang atau dipindahkan ke akun/tenant/berkas lain ditolak. Bila penyimpanan gagal, konsumsi di-rollback. Jangan mencatat signed URL/receipt atau foto dalam log. Bridge sebaiknya mengirim `Referrer-Policy: no-referrer` dan tidak memuat sumber pihak ketiga sebelum query ditangani. Endpoint ini tidak menerima callback browser tanpa autentikasi maupun boolean `verified` dari browser sebagai bukti.

## Payroll

`/admin/payrolls` menyediakan editor aturan. Tidak ada tarif pajak atau BPJS bawaan. Aturan versi perusahaan memuat nama, jenis (`earning`, `deduction`, `employer`), tarif dalam basis poin (100 bp = 1%), nominal tetap, batas dasar, kategori opsional dan metode (`flat`, `tier`, `progressive`). Dasar dapat berupa gaji pokok prorata, bruto, atau penghasilan kena pajak. Tunjangan berbasis tarif memakai gaji pokok agar urutan aturan tidak menyebabkan perhitungan berulang. Biaya perusahaan dicatat terpisah dan tidak dipotong dari karyawan.

Kalkulasi menerima gaji pokok, menit/tarif lembur, hari kerja/dibayar, masa kerja THR, kategori dan penyesuaian per karyawan. THR adalah gaji pokok dikali masa kerja 0–12 bulan dibagi 12; prorata memakai hari dibayar/hari kerja. Penghasilan tambahan dapat ditandai tidak kena pajak. Snapshot seluruh input, aturan dan versi disimpan bersama rincian gaji. Mengubah aturan tidak mengubah slip lama. Payroll terpublikasi terkunci. Nominal input berupa rupiah penuh, bukan sen, dengan pembulatan setengah ke atas.

Metode `tier` memilih tarif seluruh dasar berdasarkan batas atas inklusif (untuk tabel tarif efektif yang ditetapkan perusahaan). `progressive` menjumlahkan tarif marginal tiap lapisan. Lapisan harus meningkat, baris terakhir tanpa batas. Kategori wajib cocok bila aturan berkategori digunakan; sistem menolak kategori yang belum dikonfigurasi. Ini mesin formula, bukan tabel tarif resmi atau jaminan kepatuhan perpajakan: perusahaan harus memvalidasi kategori, plafon, tarif dan periode berlakunya. Rekonsiliasi pajak tahunan dan perhitungan lembur berbasis kalender tidak dijalankan otomatis.

## Rekening dan ekspor bank

Hanya izin `admin.payroll.manage` yang dapat mengelola rekening dan format ekspor. Isi rekening sebelum publikasi: saat publikasi sistem menyimpan snapshot rekening pada setiap rincian gaji. Perubahan rekening sesudah publikasi tidak mengganti tujuan pada ekspor periode tersebut. Periode lama tanpa snapshot ditolak untuk ekspor bank, sehingga rekening terkini tidak ditempelkan secara retroaktif ke slip lama.

Konfigurasikan urutan, judul kolom, dan pemisah CSV sesuai format bank, lalu konfirmasikan pemetaannya. Nomor rekening dan gaji bersih wajib dipetakan. `POST /v1/admin/payrolls/{id}/bank-export` hanya mengekspor periode terpublikasi/ditutup dengan snapshot rekening lengkap, mengirim `Cache-Control: no-store`, dan membuat audit tanpa menyimpan nomor rekening di metadata. CSV mengutip sel serta menetralkan awalan formula spreadsheet. Unduhan ini bukan perintah transfer ke bank; operator tetap memeriksa dan mengunggah berkas ke layanan bank yang digunakan.

## Template dan PDF HR

Template berupa teks dengan variabel terbatas (`employee_name`, `employee_code`, `company_name`, `date`, `division`, `job_title`). API mengisi data milik perusahaan/pengguna yang mengajukan dan menyimpan snapshot versi template. Perubahan template tidak mengubah permintaan lama. Variabel tidak diproses ulang di dalam nilai data; HTML selalu diperlakukan sebagai teks.

Pratinjau dapat dicetak/disimpan ke PDF oleh browser dengan font Jakarta Sans lokal dan ukuran A4, lalu PDF diunggah untuk diterbitkan oleh HR. Proses ini sengaja memakai dialog cetak; tidak ada janji pembuatan PDF otomatis oleh background worker. Verifikasi tanda tangan mengikuti kontrak provider di atas.
