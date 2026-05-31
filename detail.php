<?php
session_start();
require_once 'koneksi.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function campaignImage($image)
{
    if (!$image) {
        return 'aset/bencana.jpeg';
    }

    $image = trim((string) $image);

    if (strpos($image, 'aset/') === 0 || strpos($image, 'uploads/') === 0) {
        return $image;
    }

    return 'aset/' . $image;
}

$isLoggedIn = isset($_SESSION['id_user']);
$role = $_SESSION['role'] ?? '';
$idKampanye = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idKampanye <= 0) {
    header("Location: index.php");
    exit;
}

$query = "SELECT kampanye.*, users.nama AS nama_pengelola
          FROM kampanye
          JOIN users ON kampanye.id_pengelola = users.id_user
          WHERE kampanye.id_kampanye = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idKampanye);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: index.php");
    exit;
}

$target = (float) $data['target_dana'];
$terkumpul = (float) $data['dana_terkumpul'];
$persentase = $target > 0 ? min(100, ($terkumpul / $target) * 100) : 0;
$isExpired = strtotime($data['deadline']) < strtotime(date('Y-m-d'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title><?= e($data['judul']); ?> - Donasi Kita</title>
</head>
<body>
    <header class="site-header compact-header">
        <div class="header-inner">
            <a href="index.php" class="brand">Donasi Kita</a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a href="index.php">Home</a>
                <?php if ($isLoggedIn) { ?>
                    <?php if ($role === 'pengelola') { ?>
                        <a href="kelola_kampanye.php">Kelola Kampanye</a>
                    <?php } else { ?>
                        <a href="riwayat_donasi.php">Riwayat Donasi</a>
                    <?php } ?>
                    <span class="nav-user">Halo, <?= e($_SESSION['nama'] ?? 'User'); ?></span>
                    <a href="login.php?action=logout">Logout</a>
                <?php } else { ?>
                    <a href="login.php">Login</a>
                <?php } ?>
            </nav>
        </div>
    </header>

    <main class="detail-page">
        <?php if (($_GET['status'] ?? '') === 'donasi-berhasil') { ?>
            <div class="alert success page-alert">
                <p>Donasi berhasil dikirim dan menunggu verifikasi.</p>
            </div>
        <?php } ?>

        <section class="detail-layout">
            <img src="<?= e(campaignImage($data['gambar'])); ?>" alt="<?= e($data['judul']); ?>">

            <div class="detail-content">
                <span class="category"><?= e($data['kategori']); ?></span>
                <h1><?= e($data['judul']); ?></h1>
                <p class="muted"><?= e($data['lokasi']); ?> oleh <?= e($data['nama_pengelola']); ?></p>
                <p><?= nl2br(e($data['deskripsi'])); ?></p>

                <div class="progress-info">
                    <span>Terkumpul <strong>Rp <?= number_format($terkumpul, 0, ',', '.'); ?></strong></span>
                    <span>Target <strong>Rp <?= number_format($target, 0, ',', '.'); ?></strong></span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= $persentase; ?>%;"></div>
                </div>

                <div class="detail-meta">
                    <p><strong>Deadline:</strong> <?= e(date('d M Y', strtotime($data['deadline']))); ?></p>
                    <p><strong>Rekening:</strong> <?= e($data['rekening']); ?></p>
                </div>

                <?php if ($isExpired) { ?>
                    <span class="btn btn-disabled">Kampanye Berakhir</span>
                <?php } elseif ($isLoggedIn) { ?>
                    <a href="donasi.php?id=<?= e($data['id_kampanye']); ?>" class="btn">Donasi Sekarang</a>
                <?php } else { ?>
                    <a
                        href="login.php?required=donasi&redirect=<?= e(urlencode('donasi.php?id=' . $data['id_kampanye'])); ?>"
                        class="btn"
                        onclick="alert('Anda harus login terlebih dahulu untuk melakukan donasi.');"
                    >Donasi Sekarang</a>
                <?php } ?>
            </div>
        </section>
    </main>
</body>
</html>
