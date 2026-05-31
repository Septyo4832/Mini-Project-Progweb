<?php
session_start();
require_once "koneksi.php";

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function campaignImage($image)
{
    if (!$image) {
        return 'aset/bencana.jpeg';
    }

    return strpos($image, 'aset/') === 0 ? $image : 'aset/' . $image;
}

$isLoggedIn = isset($_SESSION['id_user']);
$keyword = trim($_GET['q'] ?? '');

$query = "SELECT kampanye.*, users.nama AS nama_pengelola
          FROM kampanye
          JOIN users ON kampanye.id_pengelola = users.id_user
          WHERE deadline >= CURDATE()";

if ($keyword !== '') {
    $query .= " AND (kampanye.judul LIKE ? OR kampanye.kategori LIKE ? OR kampanye.lokasi LIKE ?)";
}

$query .= " ORDER BY deadline ASC, dana_terkumpul ASC";

$stmt = $conn->prepare($query);

if ($keyword !== '') {
    $likeKeyword = '%' . $keyword . '%';
    $stmt->bind_param("sss", $likeKeyword, $likeKeyword, $likeKeyword);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Donasi Kita</title>
</head>
<body>
    <header class="site-header home-header">
        <div class="header-inner">
            <a href="index.php" class="brand">Donasi Kita</a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a href="index.php" class="active">Home</a>
                <?php if ($isLoggedIn) { ?>
                    <span class="nav-user">Halo, <?= e($_SESSION['nama'] ?? 'User'); ?></span>
                    <a href="login.php?action=logout">Logout</a>
                <?php } else { ?>
                    <a href="login.php">Login</a>
                <?php } ?>
            </nav>
        </div>

        <section class="hero-content">
            <p class="eyebrow">Crowdfunding sosial</p>
            <h1>Bantu Sesama, Mulai Dari Sini</h1>
            <p>Temukan kampanye aktif dan salurkan donasi untuk kebutuhan pendidikan, bencana, dan lingkungan.</p>
        </section>
    </header>

    <main>
        <section class="filter">
            <form method="GET" class="search-form">
                <input type="text" name="q" value="<?= e($keyword); ?>" placeholder="Cari kampanye, kategori, atau lokasi">
                <button type="submit" class="btn">Cari</button>
            </form>
        </section>

        <section class="campaigns" aria-label="Daftar kampanye">
            <?php if ($result->num_rows === 0) { ?>
                <div class="empty-state">
                    <h2>Kampanye tidak ditemukan</h2>
                    <p class="muted">Coba gunakan kata kunci lain.</p>
                </div>
            <?php } ?>

            <?php while ($row = $result->fetch_assoc()) { ?>
                <?php
                    $target = (float) $row['target_dana'];
                    $terkumpul = (float) $row['dana_terkumpul'];
                    $progress = $target > 0 ? min(100, ($terkumpul / $target) * 100) : 0;
                ?>
                <article class="campaign-card">
                    <img src="<?= e(campaignImage($row['gambar'])); ?>" alt="<?= e($row['judul']); ?>">
                    <div class="campaign-body">
                        <span class="category"><?= e($row['kategori']); ?></span>
                        <h2><?= e($row['judul']); ?></h2>
                        <p class="muted"><?= e($row['lokasi']); ?> oleh <?= e($row['nama_pengelola']); ?></p>

                        <div class="amount-row">
                            <span>Rp <?= number_format($terkumpul, 0, ',', '.'); ?></span>
                            <span>Rp <?= number_format($target, 0, ',', '.'); ?></span>
                        </div>
                        <div class="progress-container" aria-label="Progress donasi">
                            <div class="progress-bar" style="width: <?= $progress; ?>%;"></div>
                        </div>

                        <div class="card-footer">
                            <span>Deadline <?= e(date('d M Y', strtotime($row['deadline']))); ?></span>
                            <a href="detail.php?id=<?= e($row['id_kampanye']); ?>" class="btn btn-small">Lihat Detail</a>
                        </div>
                    </div>
                </article>
            <?php } ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Donasi Kita</p>
    </footer>
</body>
</html>
