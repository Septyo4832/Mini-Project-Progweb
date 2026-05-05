CREATE DATABASE crowdfunding_sosial;
USE crowdfunding_sosial;

-- =========================
-- TABEL USER
-- =========================
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_telp VARCHAR(20),
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