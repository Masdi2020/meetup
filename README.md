# Meeting Room

Meeting Room adalah aplikasi web untuk mengelola peminjaman ruang rapat. Aplikasi ini membantu pengguna melihat ketersediaan ruangan, mengajukan peminjaman, memantau persetujuan, serta menyimpan dokumentasi dan notulen setelah rapat selesai. Admin mengelola peminjaman dan data pendukung, sedangkan akun display menampilkan informasi rapat pada layar ruangan.

## Daftar isi

- [Fitur utama](#fitur-utama)
- [Hak akses](#hak-akses)
- [Tech stack](#tech-stack)
- [Instalasi lokal](#instalasi-lokal)
- [Menjalankan aplikasi](#menjalankan-aplikasi)
- [Tata cara penggunaan](#tata-cara-penggunaan)
- [Alur status peminjaman](#alur-status-peminjaman)
- [Struktur proyek](#struktur-proyek)
- [Perintah pengembangan](#perintah-pengembangan)
- [Pemecahan masalah](#pemecahan-masalah)

## Fitur utama

- Kalender ketersediaan dan detail ruangan, termasuk fasilitasnya.
- Pengajuan peminjaman dengan tanggal, waktu, jumlah peserta, kebutuhan tambahan, dan banner opsional.
- Pemilihan waktu dengan interval 15 menit serta pemeriksaan benturan dengan peminjaman aktif.
- Persetujuan, penolakan, pembatalan, perubahan, dan penyelesaian peminjaman sesuai hak akses.
- Riwayat peminjaman beserta unggahan dokumentasi dan notulen rapat.
- Dashboard admin serta pengelolaan ruangan, fasilitas, pengguna, dan pengaturan aplikasi.
- Ekspor data peminjaman ke PDF, Excel (XLSX), dan CSV, dengan pilihan kolom, filter status, dan pratinjau.
- Audit aktivitas untuk menelusuri perubahan data dan aktivitas pengguna.
- Tampilan banner rapat untuk layar ruangan.
- Pembaruan data secara langsung melalui WebSocket dan penyelesaian peminjaman otomatis melalui scheduler.

## Hak akses

| Peran     | Kegunaan                                                                                  | Halaman awal    |
| --------- | ----------------------------------------------------------------------------------------- | --------------- |
| `user`    | Melihat ketersediaan, mengajukan dan mengelola peminjaman sendiri, mengunggah hasil rapat | `/availability` |
| `admin`   | Mengelola peminjaman, data master, pengguna, ekspor, audit, dan pengaturan                | `/admin`        |
| `display` | Memilih ruangan dan menampilkan banner rapat                                              | `/display`      |

Login menggunakan **username dan password** melalui `/login`. Akun pengguna dikelola oleh admin; tidak tersedia halaman registrasi publik.

## Tech stack

Versi berikut mengikuti deklarasi dependensi proyek. Versi terpasang dikunci melalui `composer.lock` dan `package-lock.json`.

| Bagian                          | Teknologi                                                       |
| ------------------------------- | --------------------------------------------------------------- |
| Backend                         | PHP, Laravel 13                                                 |
| Penghubung backend dan frontend | Inertia.js 3                                                    |
| Frontend                        | Vue 3, TypeScript 5                                             |
| Tampilan                        | Tailwind CSS 4, Lucide Icons                                    |
| Build                           | Vite 8, Laravel Vite Plugin                                     |
| Kalender dan waktu              | VCalendar 3, Day.js                                             |
| Pembaruan langsung              | Laravel Reverb, Laravel Echo, Pusher JS                         |
| Routing frontend                | Laravel Wayfinder, Ziggy                                        |
| Database lokal bawaan           | SQLite                                                          |
| Ekspor                          | jsPDF, jsPDF AutoTable, write-excel-file                        |
| Pemeriksaan kualitas            | Pest, Laravel Pint, Larastan/PHPStan, ESLint, Prettier, vue-tsc |

Konfigurasi awal menggunakan database untuk session, cache, dan queue. Redis/Predis tersedia bila diperlukan, tetapi tidak wajib untuk konfigurasi lokal ini.

## Instalasi lokal

### 1. Persyaratan

- PHP **8.4.1 atau lebih baru** yang kompatibel dengan dependensi terkunci. Walaupun `composer.json` mendeklarasikan `^8.3`, dependensi yang terpasang mensyaratkan minimal 8.4.1.
- Composer 2.
- Node.js **22.13 atau versi lebih baru dalam seri 22**, atau Node.js 24, beserta npm, sesuai persyaratan tooling proyek.
- Ekstensi PHP yang dibutuhkan dependensi, termasuk driver SQLite (`pdo_sqlite` dan `sqlite3`) bila menggunakan database bawaan.

Pastikan `php`, `composer`, dan `npm` tersedia di `PATH`. Proses build juga memanggil PHP melalui Wayfinder.

### 2. Siapkan dependensi dan environment

Buka terminal pada direktori proyek, lalu jalankan:

```sh
composer install
npm ci
```

Jika `.env` belum tersedia, salin `.env.example` menjadi `.env`. Pada PowerShell:

```powershell
Copy-Item .env.example .env
```

Atur nilai berikut di `.env`:

```dotenv
APP_NAME="Meeting Room"
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DEFAULT_PASSWORD=ganti-dengan-password-awal-lokal
```

`DEFAULT_PASSWORD` digunakan untuk akun yang tidak memiliki password khusus pada seeder, serta pengelolaan password pengguna. Isi sebelum menjalankan seeder. Nama tampilan aplikasi juga memiliki pengaturan tersendiri di menu admin.

### 3. Konfigurasi pembaruan langsung

Ubah `BROADCAST_CONNECTION` dan tambahkan konfigurasi Reverb berikut ke `.env`. Contoh ini untuk penggunaan pada komputer lokal:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=meetup-local
REVERB_APP_KEY=meetup-local-key
REVERB_APP_SECRET=ganti-dengan-secret-lokal
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Ganti nilai contoh key dan secret untuk lingkungan selain pengembangan lokal. Jika diakses dari perangkat lain, gunakan hostname atau alamat server yang dapat dijangkau perangkat tersebut untuk host aplikasi dan WebSocket.

### 4. Buat database dan data awal

Buat file SQLite jika belum tersedia. Pada PowerShell:

```powershell
if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File -Path database/database.sqlite
}
```

Kemudian jalankan:

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Seeder mengisi status peminjaman, fasilitas, ruangan, pengaturan, dan akun awal. Jalankan pada database baru; beberapa seeder membuat data langsung sehingga pengulangan dapat menyebabkan duplikasi atau kegagalan karena data sudah ada.

Akun awal admin menggunakan username `admin`, dan akun layar menggunakan username `display`. Password awal keduanya tercantum di [UserSeeder](database/seeders/UserSeeder.php). Ganti password akun bawaan sebelum digunakan di lingkungan operasional. Akun umum dari seeder memakai `DEFAULT_PASSWORD`, kecuali yang memiliki password khusus.

## Menjalankan aplikasi

Untuk menjalankan setiap layanan secara eksplisit, buka terminal terpisah untuk masing-masing perintah:

| Terminal | Perintah                    | Fungsi                                                     |
| -------- | --------------------------- | ---------------------------------------------------------- |
| 1        | `php artisan serve`         | Server aplikasi pada `http://localhost:8000`               |
| 2        | `npm run dev`               | Server aset frontend dan pembaruan saat pengembangan       |
| 3        | `php artisan reverb:start`  | Server WebSocket                                           |
| 4        | `php artisan schedule:work` | Menjalankan tugas terjadwal, termasuk penyelesaian booking |
| 5        | `php artisan queue:work`    | Memproses pekerjaan antrean                                |

Buka **http://localhost:8000** di browser, lalu login. Aplikasi juga menyediakan `composer run dev` sebagai pemanggil `php artisan dev`; pastikan Reverb dan scheduler tetap berjalan bila tidak dijalankan oleh proses tersebut.

Zona waktu aplikasi saat ini adalah **Asia/Makassar (WITA, UTC+8)**, sesuai `config/app.php`.

Untuk menyiapkan aset tanpa server Vite:

```sh
npm run build
```

Pada deployment, arahkan document root web server ke `public/`, gunakan `APP_DEBUG=false`, pastikan direktori `storage/` dan `bootstrap/cache/` dapat ditulis, serta kelola Reverb dan queue worker sebagai layanan. Jalankan `php artisan schedule:run` setiap menit melalui penjadwal sistem untuk mengaktifkan tugas otomatis.

## Tata cara penggunaan

### Pengguna

1. Login menggunakan akun yang diberikan admin. Jika diminta, tentukan password baru.
2. Buka halaman ketersediaan, pilih ruangan yang ingin dilihat, lalu periksa jadwal dan detail fasilitas.
3. Buka formulir peminjaman dan isi ruangan, judul rapat, tanggal, waktu mulai dan selesai, serta jumlah peserta. Tambahkan kebutuhan khusus atau banner jika diperlukan.
4. Kirim pengajuan. Peminjaman pengguna dibuat dengan status `PENDING` untuk diproses admin.
5. Pantau pengajuan di `/riwayat`. Pengguna dapat mengubah atau membatalkan peminjaman sendiri yang masih `PENDING` atau `APPROVED`, serta menyelesaikan peminjaman yang `APPROVED`.
6. Setelah berstatus `FINISHED`, unggah dokumentasi dan notulen pada bagian hasil rapat.

Waktu peminjaman menggunakan menit `00`, `15`, `30`, atau `45`. Waktu selesai harus setelah waktu mulai. Peminjaman berstatus `PENDING` dan `APPROVED` ikut diperhitungkan saat memeriksa benturan jadwal.

### Admin

1. Login sebagai admin untuk membuka dashboard.
2. Siapkan fasilitas, ruangan, dan akun pengguna melalui menu pengelolaan masing-masing. Aktifkan atau nonaktifkan ketersediaan ruangan sesuai kebutuhan.
3. Buka pengelolaan peminjaman untuk menyetujui atau menolak pengajuan `PENDING`.
4. Buat peminjaman atas nama pengguna bila diperlukan, dengan memilih peminjam dan status awal pada formulir admin.
5. Kelola peminjaman yang disetujui, termasuk perubahan jadwal, pembatalan, atau penyelesaian. Admin juga dapat menghapus peminjaman sesuai kontrol yang tersedia.
6. Untuk laporan, buka ekspor, pilih filter, kolom, dan format, tinjau pratinjau, lalu unduh.
7. Gunakan menu audit untuk menelusuri aktivitas dan menu pengaturan untuk menyesuaikan aplikasi.

### Display ruangan

1. Login menggunakan akun dengan peran `display`.
2. Pilih ruangan pada `/display`.
3. Tampilkan halaman banner ruangan pada layar yang disediakan. Informasi diperbarui melalui Reverb saat terjadi perubahan terkait peminjaman.

### Batas unggahan

| Jenis             | Format         | Batas                                                 |
| ----------------- | -------------- | ----------------------------------------------------- |
| Banner peminjaman | JPG, JPEG, PNG | 5 MB per file                                         |
| Dokumentasi rapat | JPG, JPEG, PNG | 5 MB per file, maksimal 10 file per permintaan unggah |
| Notulen rapat     | PDF, DOC, DOCX | 10 MB per file                                        |

Hasil rapat hanya dapat dikelola setelah peminjaman `FINISHED`. Unggahan notulen baru menggantikan notulen sebelumnya.

## Alur status peminjaman

| Status      | Arti                     | Perubahan berikutnya                     |
| ----------- | ------------------------ | ---------------------------------------- |
| `PENDING`   | Menunggu keputusan admin | `APPROVED`, `REJECTED`, atau `CANCELLED` |
| `APPROVED`  | Peminjaman disetujui     | `FINISHED` atau `CANCELLED`              |
| `REJECTED`  | Pengajuan ditolak        | Tidak ada transisi lanjutan              |
| `CANCELLED` | Peminjaman dibatalkan    | Tidak ada transisi lanjutan              |
| `FINISHED`  | Peminjaman selesai       | Dokumentasi dan notulen dapat dikelola   |

Scheduler memeriksa peminjaman setiap menit. Peminjaman `APPROVED` yang waktu akhirnya telah lewat diubah menjadi `FINISHED`. Aksi manual tetap mengikuti peran, kepemilikan, dan status peminjaman.

## Struktur proyek

```text
app/
  Console/Commands/    Perintah otomatisasi peminjaman
  Events/              Event pembaruan booking dan banner
  Http/Controllers/    Endpoint aplikasi
  Http/Middleware/     Pembatasan peran dan otorisasi aksi
  Http/Requests/       Validasi input per endpoint
  Models/              Model data
  Services/            Logika bisnis, query, dan audit
database/
  migrations/          Struktur database
  seeders/             Data awal aplikasi
resources/js/
  components/          Komponen antarmuka bersama
  composables/         Logika frontend yang digunakan ulang
  layouts/             Tata letak halaman
  lib/                 Utilitas, termasuk ekspor
  pages/               Halaman Admin, User, Display, Auth, dan Shared
  types/               Definisi tipe TypeScript
routes/                Rute aplikasi dan jadwal perintah
tests/                 Pengujian aplikasi
```

## Perintah pengembangan

| Perintah                              | Keterangan                                                                                |
| ------------------------------------- | ----------------------------------------------------------------------------------------- |
| `npm run build`                       | Build aset frontend                                                                       |
| `npm run types:check`                 | Pemeriksaan tipe Vue dan TypeScript                                                       |
| `npm run lint:check`                  | Pemeriksaan ESLint                                                                        |
| `npm run format:check`                | Pemeriksaan format pada `resources/`                                                      |
| `composer run lint:check`             | Pemeriksaan format PHP dengan Pint                                                        |
| `composer run types:check`            | Analisis statis PHP dengan PHPStan                                                        |
| `php artisan test`                    | Menjalankan pengujian aplikasi                                                            |
| `composer run test`                   | Membersihkan cache konfigurasi, memeriksa format dan tipe PHP, lalu menjalankan pengujian |
| `composer run ci:check`               | Menjalankan rangkaian pemeriksaan frontend dan backend                                    |
| `php artisan bookings:finish-expired` | Memproses peminjaman yang sudah berakhir secara manual                                    |

## Pemecahan masalah

- **Versi PHP ditolak Composer:** periksa `php -v` dan gunakan PHP yang memenuhi dependensi terkunci. Jalankan `composer check-platform-reqs` untuk memeriksa versi dan ekstensi.
- **PowerShell menolak skrip npm:** gunakan `npm.cmd` atau `npx.cmd`, misalnya `npm.cmd ci` dan `npm.cmd run dev`.
- **Data awal gagal dibuat:** pastikan migrasi telah berjalan dan `DEFAULT_PASSWORD` sudah diisi sebelum seeding. Periksa juga apakah data awal sudah pernah dimasukkan.
- **Gambar atau lampiran tidak muncul:** pastikan `php artisan storage:link` telah berhasil dan `APP_URL` sesuai alamat aplikasi.
- **Pembaruan langsung tidak bekerja:** pastikan Reverb aktif, port dapat diakses, serta konfigurasi key, host, port, dan scheme backend/frontend cocok. Setelah perubahan `.env`, jalankan `php artisan config:clear`, mulai ulang Reverb, dan mulai ulang Vite atau build ulang aset.
- **Booking tidak selesai otomatis:** pastikan scheduler berjalan dan waktu server sesuai zona waktu aplikasi.
- **Unggahan ditolak server sebelum validasi aplikasi:** sesuaikan `upload_max_filesize`, `post_max_size`, dan batas ukuran request web server dengan ukuran serta jumlah file yang diizinkan.
