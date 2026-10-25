# Kebijakan Keamanan (Security Policy)

## Arsitektur Keamanan PasPapan

PasPapan menerapkan kontrol keamanan berlapis pada service **Rust API (`apps/api`)** dan **SvelteKit Web (`apps/web`)**:

1. **Autentikasi, Sesi Pepper (`SESSION_SECRET`) & Service Key (`API_INTERNAL_KEY`)**:
   - Kata sandi di-hash menggunakan **Argon2id** dengan salt acak per pengguna.
   - Token sesi 256-bit dihasilkan secara kriptografis, dikirim sebagai cookie `HttpOnly; SameSite=Lax`, dan di-hash menggunakan **HMAC-SHA256** (`SESSION_SECRET`) sebelum disimpan di tabel `sessions`.
   - Browser tidak pernah memanggil port API secara langsung (`PUBLIC_API_BASE_URL` dihapus); seluruh permintaan `/v1/*` melewati proxy server SvelteKit yang menyertakan header rahasia `x-paspapan-service-key` (`API_INTERNAL_KEY`) yang diverifikasi secara *constant-time* oleh Rust API.
   - Perubahan kata sandi otomatis merotasi sesi aktif dan mencabut seluruh sesi perangkat lain.
2. **Isolasi Multi-Tenant (`company_id`)**:
   - Seluruh tabel operasional memiliki foreign key `company_id` dan setiap query SQL mewajibkan filter `company_id` dari sesi terautentikasi.
3. **Proteksi CSRF & CORS**:
   - Seluruh endpoint mutasi (`POST`) memverifikasi header `Origin` terhadap daftar `APP_ORIGIN` tepercaya (`require_trusted_origin`).
   - Seluruh respons API menyertakan `Cache-Control: no-store`.
4. **Keamanan Object Storage (`local` / `r2` / `s3` / `minio`)**:
   - Nama berkas disanitasi dan kunci objek dibatasi pada namespace `{company_id}/{file_id}-{filename}`.
   - Percobaan path traversal (`..` atau path absolut) ditolak pada level adapter (`crates/adapters/src/storage.rs`).
   - Unduhan pada driver S3/R2/MinIO menggunakan **AWS Signature Version 4 (`AWS4-HMAC-SHA256`)** presigned URL berbatas waktu.
5. **Service Worker PWA**:
   - Service worker (`apps/web/static/sw.js`) tidak pernah menyimpan cache untuk endpoint `/v1/*`, `/health`, atau halaman HTML terautentikasi.

## Melaporkan Kerentanan

Jangan membuka issue publik untuk kerentanan keamanan. Laporkan temuan secara privat kepada pengelola repository beserta langkah reproduksi dan dampaknya.
