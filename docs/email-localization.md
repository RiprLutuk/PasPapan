# Fase Lokalisasi Email

Prioritas bahasa repository ini adalah Bahasa Indonesia terlebih dahulu, lalu Inggris sebagai pendamping.

## Perubahan

- Menstandarkan copy email dan notifikasi agar memakai translation key, bukan string campuran Indonesia dan Inggris yang di-hardcode.
- Memperbarui template email dan notification class agar format tanggal dan label mengikuti helper yang aware terhadap locale.
- Menambahkan translation entry yang belum ada di `lang/id.json` dan `lang/en.json` untuk subject email, greeting, label CTA, helper text, dan pesan notifikasi.

## Alasan

- Beberapa alur email sebelumnya masih bilingual parsial atau mencampur dua bahasa dalam satu pesan.
- Subject, label detail, dan notifikasi database belum konsisten memakai locale aktif.
- Fase ini membuat pengalaman email dan notifikasi lebih konsisten untuk pengguna Indonesia, dengan Inggris sebagai fallback kedua.

## Risiko

- Translation key yang ada mungkin masih belum lengkap di modul lain di luar alur mail/notifikasi yang diperbarui di fase ini.
- Copy notifikasi baru yang ditulis langsung di PHP atau Blade tanpa translation helper bisa memunculkan lagi output campuran bahasa.

## Pengembangan Lanjutan

- Tambahkan test terfokus untuk rendering email per locale.
- Perkenalkan helper yang bisa dipakai ulang untuk format tanggal, nominal, dan status terlokalisasi di semua notification class.
