# Penjelasan File PHP

Penjelasan untuk tiap file PHP menurut nomor baris/rentang baris, hubungan antarfile, hubungan ke tabel database, dan apa kegunaannya. Kenapa tidak didalam filenya langsung? Supaya rapi :)

## Alur Hubungan Utama

| File | Peran | Terhubung ke |
| --- | --- | --- |
| `koneksi.php` | Membuat koneksi database `$conn`. | Semua file PHP yang memakai query database. |
| `index.php` | Halaman utama, search, urutan data, pagination. | `koneksi.php`, tabel `kampanye`, `users`, `detail.php`, `login.php`, `kelola_kampanye.php`, `riwayat_donasi.php`. |
| `detail.php` | Menampilkan detail kampanye yang dipilih. | `index.php`, `koneksi.php`, tabel `kampanye`, `users`, `donasi.php`, `login.php`. |
| `donasi.php` | Form dan proses input donasi. | `detail.php`, `login.php`, `koneksi.php`, tabel `kampanye`, `users`, `donasi`, folder `uploads`. |
| `login.php` | Login, logout, register, session user. | `koneksi.php`, tabel `users`, semua halaman protected. |
| `kelola_kampanye.php` | Dashboard pengelola kampanye dan verifikasi donasi. | `login.php`, `koneksi.php`, tabel `kampanye`, `donasi`, `users`, folder `uploads`. |
| `riwayat_donasi.php` | Riwayat donasi donatur yang login. | `login.php`, `koneksi.php`, tabel `donasi`, `kampanye`. |

## `koneksi.php`

| Baris | Kegunaan | Hubungan | Yang Dilakukan |
| --- | --- | --- | --- |
| 1 | Membuka kode PHP. | Diproses oleh server PHP. | Memberi tahu server bahwa isi file adalah PHP. |
| 2 | Membuat koneksi database. | Menghasilkan variabel `$conn` untuk file lain. | Menghubungkan PHP ke MySQL `crowdfunding_sosial`; jika gagal, tampilkan pesan error. |
| 3 | Menutup kode PHP. | Tidak wajib untuk file PHP murni. | Mengakhiri blok PHP. |

## `index.php`

| Baris | Kegunaan | Hubungan | Yang Dilakukan |
| --- | --- | --- | --- |
| 1-3 | Inisialisasi halaman. | `session`, `koneksi.php`. | Mengaktifkan session dan mengambil `$conn` dari file koneksi. |
| 5-8 | Helper `e()`. | Semua output HTML dari database/form. | Mengamankan teks sebelum ditampilkan agar tidak mudah terkena XSS. |
| 10-23 | Helper `campaignImage()`. | Folder `aset`, folder `uploads`. | Menentukan path gambar kampanye; jika kosong memakai gambar default. |
| 25-37 | Helper `bindStatement()`. | Query prepared statement MySQLi. | Mengikat parameter dinamis ke query search/pagination. |
| 39-52 | Helper `pageUrl()`. | Pagination halaman utama. | Membuat URL pagination sambil mempertahankan keyword dan tanggal search. |
| 54-60 | Membaca status login dan input GET. | `$_SESSION`, `$_GET`. | Mengecek user login, role, keyword search, tanggal search, halaman aktif, dan limit data per halaman. |
| 62-64 | Kondisi awal query. | Tabel `kampanye`. | Menyaring hanya kampanye yang belum melewati deadline. |
| 66-73 | Kondisi search keyword. | Kolom `judul`, `lokasi`, `kategori`. | Jika ada keyword, query mencari kampanye berdasarkan nama kegiatan/lokasi/kategori. |
| 75-79 | Kondisi search tanggal. | Kolom `deadline`. | Jika tanggal valid, query mencari deadline yang sama dengan tanggal input. |
| 81-92 | Query jumlah data. | Tabel `kampanye`, `users`. | Menghitung total kampanye aktif untuk menentukan jumlah pagination. |
| 94-107 | Query daftar kampanye. | Tabel `kampanye`, `users`. | Mengambil data kampanye aktif, urut deadline paling dekat lalu dana terkumpul paling kecil, sesuai limit halaman. |
| 110-117 | Struktur awal HTML. | Browser dan `style.css`. | Menentukan dokumen HTML, viewport responsive, stylesheet, dan judul halaman. |
| 119-143 | Header dan navigasi. | `login.php`, `kelola_kampanye.php`, `riwayat_donasi.php`. | Menampilkan menu sesuai status login dan role user. |
| 145-155 | Form search. | Query GET di baris 54-79. | Mengirim keyword dan tanggal untuk memfilter kampanye. |
| 157-163 | Kondisi data kosong. | Hasil query `$result`. | Menampilkan pesan jika kampanye tidak ditemukan. |
| 165-192 | Loop kartu kampanye. | `detail.php`, tabel `kampanye`. | Menampilkan gambar, kategori, judul, lokasi, pengelola, dana, progress, deadline, dan tombol detail. |
| 195-211 | Pagination. | Helper `pageUrl()`. | Menampilkan tombol halaman sebelumnya, nomor halaman, dan berikutnya. |
| 214-218 | Footer dan akhir HTML. | Browser. | Menutup tampilan halaman utama. |

## `detail.php`

| Baris | Kegunaan | Hubungan | Yang Dilakukan |
| --- | --- | --- | --- |
| 1-3 | Inisialisasi halaman. | `session`, `koneksi.php`. | Mengaktifkan session dan mengambil `$conn`. |
| 5-8 | Helper `e()`. | Output dari DB. | Mengamankan teks sebelum ditampilkan. |
| 10-23 | Helper gambar kampanye. | Folder `aset`, `uploads`. | Menentukan path gambar detail kampanye. |
| 25-27 | Membaca session dan ID kampanye. | `$_SESSION`, `$_GET['id']`. | Mengambil role login dan ID kampanye yang dipilih dari halaman utama. |
| 29-32 | Validasi ID. | `index.php`. | Jika ID tidak valid, user dikembalikan ke halaman utama. |
| 34-42 | Query detail kampanye. | Tabel `kampanye`, `users`. | Mengambil detail kampanye dan nama pengelola berdasarkan ID. |
| 44-47 | Validasi data kampanye. | `index.php`. | Jika kampanye tidak ada, redirect ke halaman utama. |
| 49-52 | Hitung progress dan status deadline. | Kolom `target_dana`, `dana_terkumpul`, `deadline`. | Menghitung persentase donasi dan mengecek kampanye sudah berakhir atau belum. |
| 55-62 | Head HTML. | `style.css`. | Menyiapkan halaman detail dan judul sesuai nama kampanye. |
| 64-82 | Header navigasi. | `index.php`, `login.php`, `kelola_kampanye.php`, `riwayat_donasi.php`. | Menampilkan menu sesuai status login dan role. |
| 84-89 | Notifikasi sukses donasi. | `donasi.php`. | Menampilkan pesan jika donasi berhasil dikirim dan menunggu verifikasi. |
| 91-111 | Konten detail kampanye. | Data dari query baris 34-42. | Menampilkan gambar, kategori, judul, lokasi, deskripsi, dana, progress, deadline, dan rekening. |
| 113-123 | Tombol donasi. | `donasi.php`, `login.php`. | Jika kampanye berakhir tombol disabled; jika login masuk ke donasi; jika belum login diarahkan ke login. |
| 124-128 | Penutup HTML. | Browser. | Menutup struktur halaman. |

## `donasi.php`

| Baris | Kegunaan | Hubungan | Yang Dilakukan |
| --- | --- | --- | --- |
| 1-3 | Inisialisasi halaman. | `session`, `koneksi.php`. | Mengaktifkan session dan koneksi database. |
| 5-8 | Helper `e()`. | Output form dan DB. | Mengamankan teks sebelum tampil di HTML. |
| 10-14 | Ambil status login, role, dan ID kampanye. | `$_SESSION`, `$_POST`, `$_GET`. | ID kampanye bisa datang dari URL atau hidden input form. |
| 16-19 | Validasi ID kampanye. | `index.php`. | Redirect jika ID tidak valid. |
| 21-26 | Proteksi login. | `login.php`. | Jika belum login, user dibawa ke login lalu kembali ke halaman donasi. |
| 28-31 | Query ringkasan kampanye. | Tabel `kampanye`. | Mengambil judul, target, dana terkumpul, rekening, dan memastikan deadline masih aktif. |
| 33-36 | Validasi kampanye aktif. | `index.php`. | Redirect jika kampanye tidak ditemukan atau sudah lewat deadline. |
| 38-42 | Query data user login. | Tabel `users`. | Mengambil nama, email, dan nomor telepon user dari DB. |
| 44-49 | Validasi user session. | `login.php`. | Jika session tidak cocok dengan DB, session dihapus dan user diminta login ulang. |
| 51-55 | Sinkronisasi session dan error list. | `$_SESSION`. | Memperbarui data user di session dan menyiapkan array error. |
| 57-62 | Membaca submit form donasi. | Form HTML baris 182-231. | Mengambil nomor telepon, nominal, metode, pesan, dan ID user. |
| 64-74 | Validasi input utama. | Requirement donasi. | Mengecek nomor telepon, minimal nominal Rp10.000, dan metode pembayaran. |
| 76-106 | Validasi dan upload bukti transfer. | `$_FILES`, folder `uploads`. | Wajib upload file JPG/JPEG, cek ekstensi/MIME, simpan file ke server, simpan path ke `$namaFile`. |
| 108-113 | Update nomor telepon. | Tabel `users`. | Jika nomor telepon berubah, update data user dan session. |
| 115-125 | Insert donasi. | Tabel `donasi`, `detail.php`. | Menyimpan donasi berstatus `pending`; jika sukses kembali ke detail kampanye. |
| 129-136 | Head HTML. | `style.css`. | Menyiapkan dokumen halaman donasi. |
| 138-156 | Header navigasi. | `index.php`, `login.php`, `kelola_kampanye.php`, `riwayat_donasi.php`. | Menampilkan menu sesuai role. |
| 158-172 | Ringkasan kampanye. | Data query baris 28-31. | Menampilkan judul, target, dana terkumpul, dan rekening. |
| 174-180 | Tampil error validasi. | Array `$errors`. | Menampilkan semua pesan error form. |
| 182-193 | Data pengguna. | Tabel `users`. | Menampilkan nama dan email user login sebagai readonly. |
| 195-209 | Input nominal. | Proses validasi baris 68-70. | Menampilkan nomor telepon, tombol nominal cepat, dan input nominal minimal 10.000. |
| 211-219 | Input metode pembayaran. | Kolom `metode_pembayaran`. | User memilih Transfer Bank, E-Wallet, atau QRIS. |
| 221-229 | Input pesan dan bukti transfer. | Kolom `pesan`, `bukti_transfer`. | Pesan opsional dan file JPG wajib untuk bukti transfer. |
| 231-232 | Tombol submit. | Proses POST baris 57-125. | Mengirim form donasi. |
| 236-242 | JavaScript nominal cepat. | Tombol `data-amount`. | Saat tombol nominal diklik, nilainya masuk ke input nominal. |
| 243-244 | Penutup HTML. | Browser. | Menutup halaman. |

## `login.php`

| Baris | Kegunaan | Hubungan | Yang Dilakukan |
| --- | --- | --- | --- |
| 1-3 | Inisialisasi login. | `session`, `koneksi.php`. | Mengaktifkan session dan koneksi database. |
| 5-8 | Helper `e()`. | Output form/login. | Mencegah output HTML tidak aman. |
| 10-24 | Helper `safeRedirect()`. | Parameter `redirect`. | Mencegah redirect ke domain luar; hanya mengizinkan path lokal. |
| 26-36 | Helper `passwordMatches()`. | Tabel `users.password`. | Mendukung password hash modern dan password plain lama dari DB lokal. |
| 38-44 | Proses logout. | `index.php`. | Menghapus session dan kembali ke halaman utama. |
| 46-57 | Variabel mode dan URL tab. | `$_SESSION`, `$_GET`, `$_POST`. | Menentukan user login atau belum, mode login/register, redirect, dan notifikasi login wajib. |
| 59-85 | Proses login. | Tabel `users`, session. | Mencari user berdasarkan email, validasi password, menyimpan data user ke session, lalu redirect. |
| 87-121 | Validasi register. | Form register. | Mengecek field wajib, format email, panjang password, konfirmasi password, dan email duplikat. |
| 123-142 | Insert user baru. | Tabel `users`, session. | Membuat hash password, menyimpan user role `donatur`, mengisi session, lalu redirect. |
| 147-154 | Head HTML. | `style.css`. | Menyiapkan dokumen halaman login. |
| 156-174 | Header navigasi. | `index.php`, `kelola_kampanye.php`, `riwayat_donasi.php`. | Menampilkan menu sesuai status login. |
| 176-190 | Tampilan jika sudah login. | Session user. | Menampilkan nama user, tombol kembali, tombol role, dan logout. |
| 191-209 | Tab login/register dan alert. | `$errors`, `$loginNotice`. | Menampilkan pilihan form dan pesan error/notifikasi. |
| 211-241 | Form register. | Proses register baris 87-142. | Mengirim nama, email, no telepon, alamat, password, dan konfirmasi password. |
| 242-256 | Form login. | Proses login baris 59-85. | Mengirim email, password, dan redirect tujuan. |
| 257-262 | Penutup HTML. | Browser. | Menutup halaman login. |

## `kelola_kampanye.php`

| Baris | Kegunaan | Hubungan | Yang Dilakukan |
| --- | --- | --- | --- |
| 1-3 | Inisialisasi dashboard. | `session`, `koneksi.php`. | Mengaktifkan session dan koneksi database. |
| 5-8 | Helper `e()`. | Semua output DB/form. | Mengamankan output HTML. |
| 10-13 | Helper `rupiah()`. | Semua tampilan nominal. | Mengubah angka menjadi format Rupiah. |
| 15-28 | Helper gambar kampanye. | Folder `aset`, `uploads`. | Menghasilkan path gambar kampanye yang benar. |
| 30-39 | Helper label status. | Kolom `donasi.status`. | Mengubah `verified/pending/rejected` menjadi label tampilan. |
| 41-81 | Helper upload gambar kampanye. | Form gambar, folder `uploads`. | Validasi file JPG/PNG, cek MIME, simpan file ke server, lalu return path file. |
| 83-91 | Proteksi akses. | `login.php`, `index.php`. | Jika belum login redirect ke login; jika bukan pengelola redirect ke home. |
| 93-108 | Variabel awal dan pesan status. | Session, parameter `status`. | Menyimpan ID pengelola, error, dan pesan sukses setelah aksi. |
| 110-173 | Aksi tambah/edit kampanye. | Form kampanye, tabel `kampanye`, folder `uploads`. | Validasi input, upload gambar, update kampanye jika ada ID, atau insert kampanye baru jika ID kosong. |
| 175-197 | Aksi hapus kampanye. | Tabel `kampanye`. | Mengecek kepemilikan dan melarang hapus jika dana terkumpul >= Rp10.000. |
| 199-247 | Aksi verifikasi donasi. | Tabel `donasi`, `kampanye`. | Dengan transaksi DB, ubah status donasi; jika `verified`, dana terkumpul kampanye bertambah. |
| 249-257 | Ambil kampanye yang diedit. | Parameter `edit`, tabel `kampanye`. | Mengambil data kampanye milik pengelola untuk mengisi form edit. |
| 259-282 | Query daftar kampanye pengelola. | Tabel `kampanye`, `donasi`. | Mengambil semua kampanye milik pengelola beserta total dan jumlah donasi per status. |
| 284-292 | Query daftar donasi. | Tabel `donasi`, `users`, `kampanye`. | Mengambil semua donasi pada kampanye milik pengelola. |
| 294-304 | Data default form. | `$_POST`, data edit. | Mengisi ulang form dari POST saat error atau dari data kampanye saat edit. |
| 307-314 | Head HTML. | `style.css`. | Menyiapkan dokumen dashboard pengelola. |
| 316-326 | Header navigasi. | `index.php`, `login.php`. | Menampilkan menu home, kelola, nama pengelola, dan logout. |
| 328-347 | Judul dan alert. | `$statusMessage`, `$errors`. | Menampilkan keterangan halaman, pesan sukses, dan pesan error. |
| 349-410 | Form tambah/edit kampanye. | Proses POST `save_campaign`. | Menampilkan input judul, kategori, lokasi, deskripsi, target, deadline, rekening, gambar, dan tombol submit. |
| 412-465 | Daftar kampanye saya. | Query `$campaigns`. | Menampilkan kartu kampanye, ringkasan dana, badge status, tombol edit, dan tombol hapus/disabled. |
| 467-546 | Tabel donatur dan verifikasi. | Query `$donations`, proses `verify_donation`. | Menampilkan nama donatur, nominal, metode, tanggal, status, bukti transfer, tombol Terima/Tolak untuk pending. |
| 547-549 | Penutup HTML. | Browser. | Menutup halaman dashboard. |

## `riwayat_donasi.php`

| Baris | Kegunaan | Hubungan | Yang Dilakukan |
| --- | --- | --- | --- |
| 1-3 | Inisialisasi halaman. | `session`, `koneksi.php`. | Mengaktifkan session dan koneksi database. |
| 5-8 | Helper `e()`. | Output DB. | Mengamankan output. |
| 10-13 | Helper `rupiah()`. | Tampilan nominal. | Format angka ke Rupiah. |
| 15-24 | Helper label status. | Kolom `donasi.status`. | Mengubah status DB menjadi label user-friendly. |
| 26-29 | Proteksi login. | `login.php`. | Jika belum login, user diarahkan ke login. |
| 31-39 | Variabel user dan default summary. | Session user. | Menentukan ID user login dan menyiapkan ringkasan status default nol. |
| 41-51 | Query ringkasan donasi. | Tabel `donasi`. | Menghitung total nominal dan jumlah donasi user per status. |
| 53-60 | Query riwayat donasi. | Tabel `donasi`, `kampanye`. | Mengambil semua donasi milik user login, terbaru dulu. |
| 63-70 | Head HTML. | `style.css`. | Menyiapkan dokumen halaman riwayat. |
| 72-86 | Header navigasi. | `index.php`, `kelola_kampanye.php`, `login.php`. | Menampilkan menu sesuai role dan tombol logout. |
| 88-93 | Judul halaman. | Browser. | Menampilkan konteks halaman riwayat donasi. |
| 95-103 | Ringkasan status. | Data `$summary`. | Menampilkan total Verified, Pending, dan Ditolak beserta jumlah donasi. |
| 105-115 | Kondisi data kosong. | Hasil query `$history`. | Menampilkan pesan jika user belum pernah donasi. |
| 116-157 | Tabel riwayat. | Data `$history`. | Menampilkan kampanye, nominal, metode, tanggal, status berwarna, pesan, dan link bukti. |
| 159-161 | Penutup HTML. | Browser. | Menutup halaman riwayat. |

## Catatan Penting tentang `$conn`

`$conn` berasal dari `koneksi.php`. Setiap file yang menjalankan query database harus memanggil `koneksi.php` terlebih dahulu. Contohnya:

```php
require_once 'koneksi.php';
```

Setelah itu `$conn` bisa dipakai untuk:

```php
$stmt = $conn->prepare("SELECT ...");
```

Jadi kalau IDE menandai `$conn` sebagai error, biasanya itu karena IDE tidak membaca hubungan antarfile. Selama `koneksi.php` berhasil di-include dan database aktif, kode tetap berjalan.
