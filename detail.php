<?php
// 1. Sertakan file koneksi
require_once 'koneksi.php';

// 2. Ambil ID dari URL (misal: detail.php?id=1)
// Jika tidak ada ID di URL, dialihkan ke halaman utama
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_kampanye = $_GET['id'];

// 3. Query ambil data kampanye berdasarkan ID
$query = "SELECT * FROM kampanye WHERE id_kampanye = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_kampanye);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Jika data tidak ditemukan
if (!$data) {
    echo "Kampanye tidak ditemukan.";
    exit;
}

// 4. Logika perhitungan progress bar
$target = $data['target_dana'];
$terkumpul = $data['dana_terkumpul'];
$persentase = ($terkumpul / $target) * 100;

// Batasi persentase maksimal 100% untuk tampilan bar
$width_bar = ($persentase > 100) ? 100 : $persentase;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Detail Kampanye - <?php echo $data['judul']; ?></title>
</head>
<body>
    <header>
        <h1>DonasiKita</h1>
        <nav>
            <a href="index.php">🏠︎ Home</a>
            <a href="login.php">➜] Login</a>
        </nav>
    </header>
    <section class="detail">
        <div class="container">
            <!-- Gambar dinamis dari database -->
            <img src="aset/<?php echo $data['gambar']; ?>" alt="<?php echo $data['judul']; ?>">
            
            <div class="text">
                <!-- Judul dinamis -->
                <h2><?php echo $data['judul']; ?></h2>
                
                <!-- Deskripsi dinamis -->
                <p><?php echo nl2br($data['deskripsi']); ?></p>
                
                <div class="progress-info">
                    <!-- Format angka ke Rupiah -->
                    <span>Terkumpul: <strong>Rp <?php echo number_format($terkumpul, 0, ',', '.'); ?></strong></span>
                    <span>Target: <strong>Rp <?php echo number_format($target, 0, ',', '.'); ?></strong></span>
                </div>
                
                <div class="progress-container">
                    <!-- Persentase dinamis pada width inline style -->
                    <div class="progress-bar" style="width: <?php echo $width_bar; ?>%;"></div> 
                </div>

            <!-- Mengarahkan ke form donasi dengan membawa ID kampanye -->
            <a href="donasi.php?id=<?php echo $data['id_kampanye']; ?>" class="btn-donasi">Donasi Sekarang</a>
        </div>
    </section>
</body>
</html>