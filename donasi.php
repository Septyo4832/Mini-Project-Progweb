<?php
// 1. KONEKSI (Pastikan file koneksi.php menggunakan mysqli)
require_once 'koneksi.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proses_donasi'])) {
    
    // Ambil data dari form (menggunakan atribut name yang kita tambahkan di bawah)
    $nama        = $_POST['nama'] ?? '';
    $email       = $_POST['email'] ?? '';
    $nominal     = $_POST['nominal_input'] ?? 0;
    $metode      = $_POST['metode_pembayaran'] ?? ''; // Ini akan terisi jika kamu menggunakan radio/select
    $id_kampanye = $_POST['id_kampanye'] ?? 1; 

    // Konfigurasi User Baru (Default)
    $password_default = password_hash('donatur123', PASSWORD_DEFAULT);
    
    // A. Cek/Simpan User
    $stmt_cek = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
    $stmt_cek->bind_param("s", $email);
    $stmt_cek->execute();
    $result = $stmt_cek->get_result();

    if ($result->num_rows > 0) {
        $id_user = $result->fetch_assoc()['id_user'];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'donatur')");
        $stmt_insert->bind_param("sss", $nama, $email, $password_default);
        $stmt_insert->execute();
        $id_user = $stmt_insert->insert_id;
    }

    // B. Proses File Upload
    $nama_file = null;
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION);
        $nama_file = "bukti_" . time() . "_" . $id_user . "." . $ext;
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], "uploads/" . $nama_file);
    }

    // C. Simpan Donasi
    $stmt_donasi = $conn->prepare("INSERT INTO donasi (id_user, id_kampanye, nominal, metode_pembayaran, bukti_transfer, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt_donasi->bind_param("iidss", $id_user, $id_kampanye, $nominal, $metode, $nama_file);
    
    if ($stmt_donasi->execute()) {
        echo "<script>alert('Donasi Berhasil!'); window.location.href='index.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Donasi</title>
</head>
<body>
    <header>
        <h1>DonasiKita</h1>
        <nav>
            <a href="index.php">🏠︎ Home</a>
            <a href="login.php">➜] Login</a>
        </nav>
    </header>

    <section class="form">
        <h2>Form Donasi</h2>
        <!-- WAJIB: Tambah method, action, dan enctype -->
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="id_kampanye" value="1">
            
            <!-- WAJIB: Tambah atribut name -->
            <input type="text" name="nama" placeholder="Nama Lengkap" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            
            <div class="nominal">
                <p>Nominal:</p>
                <!-- Catatan: Tombol <button> di dalam <form> tanpa type="button" akan dianggap submit. 
                     Tanpa JS, tombol-tombol ini tidak bisa mengisi input di bawahnya secara otomatis. 
                     User harus mengetik manual di input nominal di bawah ini. -->
                <button type="button">10.000</button>
                <button type="button">50.000</button>
                <button type="button">100.000</button>
                <button type="button">1.000.000</button>
                <input type="number" name="nominal_input" placeholder="Masukkan Nominal" required>
            </div>

            <p>Metode Pembayaran:</p>
            <div class="payment">
                <!-- Karena tanpa JS, kita gunakan cara HTML murni: Pilih lewat Select atau ketik -->
                <select name="metode_pembayaran" required style="width: 100%; padding: 10px; margin-bottom: 10px;">
                    <option value="">-- Pilih Metode --</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="QRIS">QRIS</option>
                </select>
            </div><br>

            <p>Bukti Pembayaran:</p>
            <input type="file" name="bukti_transfer" required><br>
            
            <!-- WAJIB: Tambah name untuk validasi di PHP -->
            <button type="submit" name="proses_donasi" class="btn">Donasi</button>
        </form>
    </section>
</body>
</html>