# SIPADUKA

Aplikasi administrasi keluarga dan wilayah RW berbasis Laravel. Proyek ini disiapkan sebagai demonstrasi portofolio dengan struktur database lokal dan data sintetis.

## Fitur

- Autentikasi admin dan pengguna RW.
- Pengelolaan data keluarga dan anggota keluarga.
- Dashboard ringkasan untuk admin dan RW.
- Integrasi Google Apps Script yang opsional melalui konfigurasi lokal.

## Teknologi

- PHP 8.2+ dan Laravel 12.
- MySQL atau MariaDB.
- Node.js dan npm untuk aset frontend.

## Menjalankan Secara Lokal

1. Siapkan PHP 8.2+, Composer, Node.js, npm, dan MySQL/MariaDB.
2. Pasang dependensi dan buat file environment lokal:

   ```powershell
   composer install
   Copy-Item .env.example .env
   ```

3. Buat database kosong, lalu atur `DB_*` di `.env`. Isi `DEMO_ADMIN_PASSWORD` dan `DEMO_RW_PASSWORD` dengan password lokal minimal 12 karakter. Seeder akan berhenti jika password belum diatur.
4. Buat application key dan jalankan migrasi serta data demo:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   ```

5. Bangun aset dan jalankan aplikasi:

   ```powershell
   npm install
   npm run build
   php artisan serve
   ```

   Buka alamat lokal yang ditampilkan Artisan. Akun demo menggunakan username `demo_admin`, `demo_rw_01`, dan `demo_rw_02`; password-nya adalah nilai lokal yang Anda atur di `.env`.

## Integrasi Eksternal

`APPSCRIPT_URL` bersifat opsional dan kosong secara default. Biarkan kosong untuk demonstrasi lokal tanpa layanan eksternal. Jangan commit `.env`, URL endpoint aktif, kredensial, dump database, atau data pribadi.

Semua data demo harus sintetis. Jangan gunakan data warga nyata dalam database, screenshot, log, maupun dokumentasi.
