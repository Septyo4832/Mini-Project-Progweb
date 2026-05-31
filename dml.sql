INSERT INTO kampanye
(id_pengelola, judul, kategori, lokasi, deskripsi, target_dana, dana_terkumpul, deadline, gambar, rekening)
VALUES
(3, 'Bantu Korban Banjir', 'Bencana', 'Yogyakarta',
'Banjir besar menyebabkan banyak warga kehilangan tempat tinggal dan membutuhkan bantuan makanan, pakaian, obat-obatan, serta kebutuhan darurat.',
10000000, 2500000, '2026-06-30', 'aset/banjir2.jpg', 'BRI 123456789 a.n Yayasan Peduli Sesama'),

(3, 'Bantu Pendidikan Anak Desa', 'Pendidikan', 'Gunungkidul',
'Kampanye ini bertujuan membantu anak-anak desa mendapatkan perlengkapan sekolah seperti buku, tas, dan seragam.',
15000000, 4000000, '2026-07-15', 'aset/pendidikan1.png', 'BCA 987654321 a.n Yayasan Peduli Sesama'),

(4, 'Gerakan Bersih Sungai', 'Lingkungan', 'Bantul',
'Kegiatan sosial untuk membersihkan sungai dan menyediakan tempat sampah bagi masyarakat sekitar.',
8000000, 1000000, '2026-05-25', 'aset/bersih_sungai.jpg', 'Mandiri 1122334455 a.n Komunitas Hijau');

INSERT INTO donasi
(id_user, id_kampanye, nominal, metode_pembayaran, pesan, bukti_transfer, status)
VALUES
(1, 1, 50000, 'Transfer Bank', 'Semoga membantu para korban banjir.', 'upload/bukti1.jpg', 'pending'),
(2, 1, 100000, 'QRIS', 'Semoga cepat pulih.', 'upload/bukti2.jpg', 'verified'),
(1, 2, 75000, 'E-Wallet', 'Semangat untuk adik-adik sekolah.', 'upload/bukti3.jpg', 'pending');