CREATE DATABASE IF NOT EXISTS crowdfunding_sosial;
USE crowdfunding_sosial;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS donasi;
DROP TABLE IF EXISTS kampanye;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- =========================
-- TABEL USER
-- =========================
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_telp VARCHAR(20) NOT NULL,
    alamat TEXT,
    password VARCHAR(255) NOT NULL,
    role ENUM('donatur', 'pengelola') NOT NULL
);

-- =========================
-- TABEL KAMPANYE
-- =========================
CREATE TABLE kampanye (
    id_kampanye INT AUTO_INCREMENT PRIMARY KEY,
    id_pengelola INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    lokasi VARCHAR(100) NOT NULL,
    deskripsi TEXT NOT NULL,
    target_dana DECIMAL(15,2) NOT NULL,
    dana_terkumpul DECIMAL(15,2) DEFAULT 0,
    deadline DATE NOT NULL,
    gambar VARCHAR(255),
    rekening VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_pengelola) REFERENCES users(id_user)
);

-- =========================
-- TABEL DONASI
-- =========================
CREATE TABLE donasi (
    id_donasi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_kampanye INT NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    metode_pembayaran VARCHAR(50) NOT NULL,
    pesan TEXT,
    bukti_transfer VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    tanggal_donasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_kampanye) REFERENCES kampanye(id_kampanye)
);

-- =========================
-- DATA AWAL
-- Password akun contoh: password123
-- =========================
INSERT INTO users
(id_user, nama, email, no_telp, alamat, password, role)
VALUES
(1, 'Budi Santoso', 'budi@example.com', '081234567890', 'Yogyakarta', '$2y$10$1pseVAjImTr3ouG5KRKJtODEMBdxg1rb73SUFjqMq5/biL0bmrqBS', 'donatur'),
(2, 'Siti Aminah', 'siti@example.com', '081298765432', 'Bantul', '$2y$10$1pseVAjImTr3ouG5KRKJtODEMBdxg1rb73SUFjqMq5/biL0bmrqBS', 'donatur'),
(3, 'Yayasan Peduli Sesama', 'peduli@example.com', '0274123456', 'Sleman', '$2y$10$1pseVAjImTr3ouG5KRKJtODEMBdxg1rb73SUFjqMq5/biL0bmrqBS', 'pengelola'),
(4, 'Komunitas Hijau', 'hijau@example.com', '0274987654', 'Bantul', '$2y$10$1pseVAjImTr3ouG5KRKJtODEMBdxg1rb73SUFjqMq5/biL0bmrqBS', 'pengelola');

INSERT INTO kampanye
(id_kampanye, id_pengelola, judul, kategori, lokasi, deskripsi, target_dana, dana_terkumpul, deadline, gambar, rekening)
VALUES
(1, 3, 'Bantu Korban Banjir', 'Bencana', 'Yogyakarta',
'Banjir besar menyebabkan banyak warga kehilangan tempat tinggal dan membutuhkan bantuan makanan, pakaian, obat-obatan, serta kebutuhan darurat.',
10000000, 2500000, '2026-06-30', 'banjir2.jpg', 'BRI 123456789 a.n Yayasan Peduli Sesama'),

(2, 3, 'Bantu Pendidikan Anak Desa', 'Pendidikan', 'Gunungkidul',
'Kampanye ini bertujuan membantu anak-anak desa mendapatkan perlengkapan sekolah seperti buku, tas, dan seragam.',
15000000, 4000000, '2026-07-15', 'pendidikan1.png', 'BCA 987654321 a.n Yayasan Peduli Sesama'),

(3, 4, 'Gerakan Bersih Sungai', 'Lingkungan', 'Bantul',
'Kegiatan sosial untuk membersihkan sungai dan menyediakan tempat sampah bagi masyarakat sekitar.',
8000000, 1000000, '2026-05-25', 'bersih_sungai.jpg', 'Mandiri 1122334455 a.n Komunitas Hijau');

INSERT INTO donasi
(id_user, id_kampanye, nominal, metode_pembayaran, pesan, bukti_transfer, status)
VALUES
(1, 1, 50000, 'Transfer Bank', 'Semoga membantu para korban banjir.', 'uploads/bukti1.jpg', 'pending'),
(2, 1, 100000, 'QRIS', 'Semoga cepat pulih.', 'uploads/bukti2.jpg', 'verified'),
(1, 2, 75000, 'E-Wallet', 'Semangat untuk adik-adik sekolah.', 'uploads/bukti3.jpg', 'pending');
