# Checklist Keamanan PasPapan

Daftar kontrol keamanan yang wajib dijaga pada setiap perubahan di `apps/api`, `apps/web`, dan `crates/*`:

- [ ] **Isolasi Multi-Tenant**: Setiap query SQL pada tabel bisnis wajib memfilter `company_id` dari `AuthContext`.
- [ ] **Autentikasi Antar-Service (`API_INTERNAL_KEY`)**: Pastikan `API_INTERNAL_KEY` diset sama pada `apps/api/.env` dan `apps/web/.env`, serta port API di produksi tidak diekspos langsung ke internet publik.
- [ ] **Verifikasi Origin (CSRF)**: Setiap handler mutasi (`POST`) wajib memanggil `require_trusted_origin(&headers, &state.app_origin)`.
- [ ] **Otorisasi Capability**: Setiap endpoint admin/manager wajib memverifikasi capability efektif melalui `require_capability` atau predikat bawahan langsung (`manager_user_id`).
- [ ] **Keamanan Sesi (`SESSION_SECRET`)**: Jangan pernah menyimpan token sesi mentah di database atau log; simpan hanya hash HMAC-SHA256 (`hash_session_token`).
- [ ] **Keamanan Object Storage**: Gunakan `ObjectStorage::build_object_key` untuk men-sanitasi nama berkas dan menolak karakter path traversal (`..` / `/`).
- [ ] **Batas Cache**: Pastikan header `Cache-Control: no-store` terpasang pada seluruh respons API dan service worker (`apps/web/static/sw.js`) tidak menyimpan cache untuk `/v1/*`.
