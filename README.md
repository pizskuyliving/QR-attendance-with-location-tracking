# FLEX — Flexible Learning Experience

Website absensi mahasiswa berbasis scan QR. Laravel 13 + SQLite, tanpa build step, dan bisa di-install di layar utama ponsel (PWA, jalan di iPhone dan Android).

Tampilan memakai tema gelap *deep space* (diadaptasi dari design system StarChart): latar Deep Space `#1E1B4B`, kartu `#2E2A6E`, Nebula `#A78BFA` untuk elemen interaktif, dan Star `#FDE047` untuk tombol aksi utama serta progres kehadiran. Status memakai hijau `#4ADE80`, kuning `#FBBF24`, biru `#60A5FA`, dan merah `#F87171`. Semua warna ada sebagai variabel di awal `public/css/app.css`. Font Fredoka (judul), DM Sans (teks), dan Space Mono (NPM/kode), berlisensi SIL OFL, disimpan di `public/fonts` sehingga tetap tampil tanpa internet.

Nama sistem, kepanjangan, dan versi diatur di `config/absen.php` (`nama`, `nama_panjang`, `versi`). Nama kampus di footer diatur lewat `NAMA_INSTITUSI` di `.env`.

## Cara kerja

**Mahasiswa**: buka website di ponsel, **daftar akun** (NPM, kelas A–G, nama sesuai KTM, email, kata sandi), masukkan kode verifikasi 6 angka dari email, **pilih sendiri mata kuliah** yang diikuti (boleh lebih dari satu, termasuk dari kelas lain), lalu tekan **Scan QR**, arahkan kamera ke QR di layar kelas. Kehadiran langsung tercatat dan muncul di **Riwayat**.

**Asisten dosen**: masuk ke dashboard, pilih kelas, pilih pertemuan, tekan **Nyalakan QR**. Layar QR menampilkan daftar mahasiswa yang sudah hadir secara langsung. Tekan **Akhiri sesi** bila selesai (opsional: yang belum scan otomatis ditandai Alpa).

QR berganti setiap 15 detik dan ditandatangani (HMAC), jadi foto QR yang dikirim ke grup chat cepat kedaluwarsa. Mahasiswa hanya bisa absen di kelas tempat ia terdaftar, dan hanya selama sesi masih aktif.

## Fitur per fase (sesuai flow)

| Fase | Fitur | Status |
|---|---|---|
| 1 | Absen pakai QR: scan dengan kamera, konfirmasi kehadiran, riwayat absen saya | Selesai |
| 1 | Nyalakan QR: pilih kelas & matkul, nyalakan QR hari ini, akhiri sesi | Selesai |
| 2 | Kelola data mahasiswa: smart import Excel, tambah manual, edit, hapus, reset sandi | Selesai |
| 2 | Kelola kelas & matkul: daftar kelas diampu, tambah/ubah/hapus kelas, jumlah pertemuan (default 8) | Selesai |
| 2 | Rekap otomatis: per pertemuan, per mahasiswa, persentase kehadiran; edit/hapus absen per sel | Selesai |
| 3 | Export rekap absen + nilai ke satu file Excel, siap cetak (A4 landscape, 1 halaman lebar, kolom tanda tangan) | Selesai |
| 3 | Login: mahasiswa (email), asisten (email), lupa kata sandi lewat kode email | Selesai |
| 4 | Profil: ubah profil, ganti kata sandi, kelola akun asisten (khusus admin) | Selesai |
| + | Daftar akun mahasiswa (kelas A–G) + verifikasi kode via email; mahasiswa memilih mata kuliah sendiri di akunnya; captcha anti-bot Cloudflare Turnstile di login & daftar | Selesai |
| + | Lokasi GPS saat scan (wajib), tampil di riwayat mahasiswa, layar QR, tab Riwayat & Lokasi, dan sheet Excel "Riwayat Scan" | Selesai |
| + | Anti titip absen: radius kampus per kelas, satu akun = satu HP, tanda scan mencurigakan | Selesai |
| + | Log perubahan: semua edit/hapus oleh asisten dan scan yang ditolak, di tab Log | Selesai |
| + | Verifikasi dua langkah (2FA) untuk asisten & admin: kode 6 angka dari aplikasi authenticator + kode cadangan | Selesai |
| + | Asisten daftar sendiri, akun aktif setelah disetujui admin | Selesai |
| + | Materi per pertemuan (file PPT/PDF/dll. atau tautan), bisa dibuka mahasiswa tanpa syarat absen | Selesai |
| + | Tugas & laporan: tenggat, lampiran soal dari asisten (banyak file + tautan), pengumpulan file dari HP, tanda terlambat, penilaian + komentar, unduh semua (ZIP) | Selesai |
| + | Kuis refleksi akhir pertemuan: 5 soal A/B/C, hanya untuk yang hadir, batas waktu + hitung mundur, analisis per soal, salin ke kelas lain, nilai Kuis berbobot sendiri | Selesai |
| + | Rekap nilai akhir: bobot kehadiran & tugas per kelas, huruf A–E, export Excel | Selesai |

## Menjalankan di komputer

Butuh PHP 8.3+ (ekstensi `pdo_sqlite`, `zip`, `gd`, `xml`, `mbstring`) dan Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Buka http://localhost:8000 (mahasiswa) dan http://localhost:8000/asisten (asisten).

Akun awal dari seeder (ganti kata sandinya setelah login):

| Peran | Login | Kata sandi |
|---|---|---|
| Laboran | admin@kampus.ac.id | admin123 |
| Asisten | asisten@kampus.ac.id | asisten123 |
| Dosen | dosen@kampus.ac.id | dosen123 |

Seeder tidak membuat akun mahasiswa. Mahasiswa mendaftar sendiri lewat halaman **Daftar** (email kampus + kode verifikasi).

## Captcha anti-bot (Cloudflare Turnstile)

Login mahasiswa, login asisten, dan form daftar dilindungi **Cloudflare Turnstile**: Cloudflare memeriksa bahwa pengunjung bukan bot (biasanya lolos otomatis dengan tanda centang hijau) dan server memverifikasi hasilnya ke Cloudflare. Selama key belum diisi, `APP_ENV=local` memakai key uji Cloudflare (selalu lolos, ada tulisan "Mode uji" di bawah widget); di `APP_ENV=production` login ditolak sampai key diisi.

Cara mengaktifkan (gratis):
1. Masuk ke https://dash.cloudflare.com, buka menu **Turnstile**, lalu **Add widget**.
2. Isi nama widget, misalnya "FLEX". Di **Hostnames** tambahkan domain website kamu, misalnya `absen.kampus.ac.id`, serta `localhost` untuk uji coba. Jika memakai tunnel trycloudflare, tambahkan alamat `xxxx.trycloudflare.com` yang sedang dipakai (alamat ini berganti setiap tunnel dijalankan ulang).
3. Pilih Widget mode **Managed**, lalu **Create**.
4. Salin **Site Key** dan **Secret Key** ke `.env`:
```
TURNSTILE_SITE_KEY=0x4AAAAAAA...
TURNSTILE_SECRET_KEY=0x4AAAAAAA...
```
5. Jalankan `php artisan config:clear`.

Turnstile butuh koneksi internet, baik di browser pengguna maupun di server.

## Anti titip absen

Tiga lapis pemeriksaan saat mahasiswa scan QR (selain QR yang berganti tiap 15 detik):

1. **Radius kampus.** Di tab **Pengaturan** kelas, buka dari HP di dalam ruang kelas, tekan **Pakai lokasi saya**, isi radius (saran 100 m), lalu Simpan. Scan dari luar radius ditolak. GPS di dalam gedung sering meleset, jadi diberi kelonggaran sebesar akurasi GPS, paling banyak 50 m. Kosongkan untuk mematikan.
2. **Satu akun = satu HP.** Scan pertama mengikat akun ke HP itu (penanda acak di cookie browser). Scan dari HP lain ditolak, dan satu HP tidak bisa dipakai absen untuk dua akun. Bila mahasiswa ganti HP, ganti browser, atau menghapus data browser, asisten menekan **Reset HP** di tab Mahasiswa.
3. **Tanda mencurigakan.** Scan tetap dicatat hadir tetapi diberi tanda ⚠ bila akurasi GPS di atas 100 m atau koordinatnya sama persis dengan mahasiswa lain (ciri fake GPS). Tanda muncul di layar QR, tab Riwayat & Lokasi (ada filter "Hanya yang mencurigakan"), garis oranye di tabel absensi, dan sheet Excel "Riwayat Scan".

Fake GPS tetap tidak bisa dicegah 100% dari browser; lapisan ini membuatnya sulit dan meninggalkan jejak.

## Pendaftaran asisten dengan persetujuan admin

Asisten bisa mendaftar sendiri lewat tautan **Daftar akun asisten** di halaman masuk asisten (`/asisten/daftar`): nama, email, mata kuliah yang diampu, dan kata sandi. Akun berstatus *menunggu* dan belum bisa masuk. Admin melihat pengingat di dashboard, lalu menekan **Setujui** atau **Tolak** di **Profil → Pendaftaran asisten menunggu persetujuan**. Akun yang ditolak tidak bisa masuk; admin bisa menghapusnya agar email itu bisa mendaftar lagi. Akun yang dibuat langsung oleh admin tetap aktif tanpa persetujuan. Semua persetujuan tercatat di log.

## Verifikasi dua langkah (2FA) asisten & admin

Di **Profil → Verifikasi dua langkah**, tekan **Aktifkan 2FA**, pindai QR dengan Google Authenticator / Microsoft Authenticator, lalu masukkan kode 6 angka. Simpan 8 **kode cadangan** yang muncul (hanya ditampilkan sekali). Sejak itu, setelah kata sandi benar, login asisten meminta kode dari aplikasi. Kode berganti tiap 30 detik, kode yang sudah dipakai tidak bisa dipakai lagi, dan setelah 5 kode salah harus login ulang dari awal.

Bila HP hilang: masuk dengan salah satu kode cadangan (sekali pakai). Bila kode cadangan juga hilang, **admin** menekan **Reset 2FA** pada akun tersebut di halaman Profil. Pastikan jam HP diatur otomatis, karena kode bergantung pada waktu. Akun mahasiswa tidak memakai 2FA (sudah dilindungi kunci satu HP dan radius kelas).

## Materi, tugas & nilai

- **Materi** (tab Materi): unggah file (PPT, PDF, Word, Excel, ZIP, gambar) atau tempel tautan (YouTube, Google Drive) per pertemuan. Semua mahasiswa yang mengikuti mata kuliah bisa membuka dan mengunduh dari HP, **tanpa harus absen dulu**. Mahasiswa membukanya dengan mengetuk kartu mata kuliah di Beranda.
- **Tugas/laporan** (tab Tugas): tentukan pertemuan, judul, petunjuk, tenggat, dan apakah kiriman terlambat masih diterima. Asisten bisa melampirkan beberapa file (soal, template laporan, data; maks. 10 per simpan, jenis sama dengan materi) dan beberapa tautan (satu per baris), total maks. 20 lampiran per tugas; tambah atau hapus lampiran di halaman tugas. Mahasiswa peserta kelas mengunduh lampiran dari halaman tugasnya. Mahasiswa mengunggah satu file (bisa diganti sampai dinilai); kiriman setelah tenggat ditandai *terlambat*. Asisten memberi nilai 0–100 dan komentar langsung di tabel, dan bisa mengunduh semua kiriman sekaligus sebagai ZIP (nama file diawali NPM).
- **Kuis refleksi** (tab Kuis): asisten menyiapkan 5 soal pilihan A/B/C per pertemuan (draf, bisa diubah), lalu menekan *Buka kuis* di akhir pertemuan dengan durasi tertentu. Hanya mahasiswa yang berstatus Hadir di pertemuan itu yang bisa menjawab, sekali saja; urutan soal dan pilihan diacak per mahasiswa, pilihan tersimpan otomatis, dan jawaban terkirim sendiri saat waktu habis. Setelah ditutup, mahasiswa melihat nilai dan kunci; asisten melihat persentase benar per soal, nilai per mahasiswa, dan bisa mengunduh Excel atau menyalin kuis ke kelas lain dengan mata kuliah yang sama. Kuis yang tidak dijawab (termasuk tidak hadir) bernilai 0.
- **Rekap & Nilai** (satu tab): kehadiran H/I/S/A, nilai tiap tugas, UTS/UAS, nilai akhir, dan huruf dalam satu tabel per mahasiswa, plus rekap per pertemuan. Nilai akhir = rata-rata berbobot kehadiran, tugas, kuis, UTS, dan UAS (bobot per kelas, jumlah 100; bobot kuis awalnya 0). Tugas yang lewat tenggat tanpa kiriman dihitung 0; kiriman yang belum dinilai dan tugas yang belum lewat tenggat tidak dihitung. Huruf diatur laboran di menu Sistem. Export: satu tombol *Unduh rekap Excel* berisi sheet Rekap (absen + nilai + tanda tangan), Nilai Tugas, Per Pertemuan, dan Riwayat Scan.
- File disimpan di `storage/app/private/` (tidak bisa diakses langsung dari URL). **Ikut backup folder ini** bersama `database/database.sqlite`.
- Batas ukuran unggahan diatur `MAKS_UNGGAH_KB` di `.env` (default 20480 = 20 MB). **Penting di Laragon/XAMPP:** batas bawaan PHP hanya 2 MB, jadi naikkan di `php.ini` (Laragon: menu → PHP → php.ini) lalu restart:

  ```ini
  upload_max_filesize = 20M
  post_max_size = 25M
  ```

## Log perubahan

Tab **Log** di setiap kelas mencatat siapa, kapan, dan dari IP mana: ubah/hapus absen per sel (status lama → baru), isi massal, tambah/ubah/hapus mahasiswa, import Excel, reset sandi, reset HP, ubah kelas, nyalakan/akhiri QR, serta setiap scan yang ditolak beserta alasannya. Log hanya bisa ditambah, tidak bisa diedit atau dihapus dari aplikasi.

## Email kampus wajib saat daftar

Mahasiswa hanya bisa mendaftar dengan email mahasiswa UIR, format `nama@student.uir.ac.id`. Gmail, email pribadi lain, maupun email staf `@uir.ac.id` ditolak. Atur di `.env`:
```
DOMAIN_EMAIL_MAHASISWA=student.uir.ac.id
```
Lebih dari satu domain dipisahkan koma; kosongkan untuk menerima semua email. Jalankan `php artisan config:clear` setelah mengubahnya.

## Lupa kata sandi

Di halaman masuk, tekan **Lupa kata sandi?**, pilih jenis akun (mahasiswa / asisten / dosen / laboran), lalu isi email. FLEX mengirim kode 6 angka yang berlaku 15 menit. Masukkan kode itu beserta kata sandi baru. Setelah berhasil, "ingat saya" di perangkat lain ikut tidak berlaku, dan kejadiannya tercatat di log.

- Pesan yang muncul selalu sama, terdaftar atau tidak, agar halaman ini tidak bisa dipakai menebak email yang terdaftar.
- Maksimal 5 kode salah. Kirim ulang paling cepat 60 detik sekali.
- Hanya mahasiswa yang sudah verifikasi email dan staf berstatus aktif yang bisa memakai jalur ini.
- Verifikasi dua langkah (2FA) tetap diminta saat masuk sesudahnya.
- Mahasiswa yang tidak bisa membuka emailnya tetap bisa minta asisten membuat kata sandi sementara (tab Mahasiswa → Reset sandi).
- Pengiriman memakai pengaturan email yang sama dengan kode verifikasi pendaftaran (bagian di bawah).

## Mengirim email kode verifikasi

Secara bawaan (`MAIL_MAILER=log`) email **tidak benar-benar terkirim**; isinya ditulis ke `storage/logs/laravel.log` (cari "Kode verifikasi"). Ini cukup untuk uji coba.

Agar terkirim sungguhan lewat Gmail:
1. Aktifkan Verifikasi 2 Langkah di akun Google, lalu buat **App Password** di https://myaccount.google.com/apppasswords (16 karakter).
2. Isi `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=null
MAIL_USERNAME=emailkamu@gmail.com
MAIL_PASSWORD="xxxx xxxx xxxx xxxx"
MAIL_FROM_ADDRESS=emailkamu@gmail.com
MAIL_FROM_NAME="FLEX"
```
3. `php artisan config:clear`.

Gmail gratis dibatasi ±500 email/hari. Untuk skala besar bisa pakai Brevo/Mailgun/SMTP kampus dengan isian yang sama.

## Penting: kamera butuh HTTPS

Browser ponsel hanya mengizinkan kamera di situs **HTTPS** (atau `localhost`). Untuk mencoba dari ponsel sebelum di-hosting, jalankan tunnel, misalnya:

```bash
php artisan serve
cloudflared tunnel --url http://localhost:8000   # atau: ngrok http 8000
```

lalu buka alamat `https://...` yang diberikan di ponsel. Untuk produksi, pasang di hosting yang memakai SSL (Let's Encrypt). Set `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false` di `.env`.

Ingin pakai MySQL? Ubah `DB_CONNECTION=mysql` dan isi `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` di `.env`, lalu `php artisan migrate --seed`.

## Format Excel untuk import

Cukup ada kolom **NPM** dan **Nama** di mana saja (baris judul tidak harus di baris 1; judul seperti "N P M", "NIM", "Nama Mahasiswa" juga dikenali). Kolom pertemuan opsional (`P1..P8`, `Pertemuan 1`, atau `1..8`) berisi `H/I/S/A` (juga dikenali: hadir, izin, sakit, alpa, ✓, v, 1). Template bisa diunduh dari tab **Mahasiswa** di setiap kelas. NPM yang sudah ada akan diperbarui namanya, bukan diduplikasi.

## Struktur kode

- `app/Models/SesiAbsen.php` — pembuatan & verifikasi isi QR yang berganti tiap 15 detik
- `app/Http/Controllers/Mahasiswa/AbsenController.php` — scan, konfirmasi, riwayat
- `app/Http/Controllers/Mahasiswa/KelasController.php` — mahasiswa memilih / keluar mata kuliah
- `config/absen.php` — daftar kelas A–G (ubah di sini bila perlu)
- `app/Http/Controllers/Asisten/*` — kelas, mahasiswa, sesi QR, absensi, profil
- `app/Services/ImportExcel.php` — smart import
- `app/Services/Rekap.php` dan `ExportExcel.php` — rekap & export
- `resources/views/` — tampilan Blade; `public/css/app.css` — gaya
- `public/manifest.webmanifest`, `public/sw.js` — PWA
- `public/vendor/` — html5-qrcode (pemindai) & qrcode-generator (pembuat QR)
