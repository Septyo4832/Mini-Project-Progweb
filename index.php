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

    $image = trim((string) $image);

    if (strpos($image, 'aset/') === 0 || strpos($image, 'uploads/') === 0) {
        return $image;
    }

    return 'aset/' . $image;
}

function bindStatement($stmt, $types, array &$params)
{
    if ($types === '') {
        return;
    }

    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }

    $stmt->bind_param($types, ...$refs);
}

function pageUrl($page, $keyword, $tanggal)
{
    $params = ['page' => $page];

    if ($keyword !== '') {
        $params['q'] = $keyword;
    }

    if ($tanggal !== '') {
        $params['tanggal'] = $tanggal;
    }

    return 'index.php?' . http_build_query($params);
}

$isLoggedIn = isset($_SESSION['id_user']);
$role = $_SESSION['role'] ?? '';
$keyword = trim($_GET['q'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');
$tanggal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) ? $tanggal : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$conditions = ["kampanye.deadline >= CURDATE()"];
$types = '';
$params = [];

if ($keyword !== '') {
    $conditions[] = "(kampanye.judul LIKE ? OR kampanye.lokasi LIKE ? OR kampanye.kategori LIKE ?)";
    $likeKeyword = '%' . $keyword . '%';
    $types .= 'sss';
    $params[] = $likeKeyword;
    $params[] = $likeKeyword;
    $params[] = $likeKeyword;
}

if ($tanggal !== '') {
    $conditions[] = "kampanye.deadline = ?";
    $types .= 's';
    $params[] = $tanggal;
}

$whereSql = implode(' AND ', $conditions);
$countSql = "SELECT COUNT(*) AS total
             FROM kampanye
             JOIN users ON kampanye.id_pengelola = users.id_user
             WHERE $whereSql";
$countStmt = $conn->prepare($countSql);
bindStatement($countStmt, $types, $params);
$countStmt->execute();
$totalRows = (int) $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$query = "SELECT kampanye.*, users.nama AS nama_pengelola
          FROM kampanye
          JOIN users ON kampanye.id_pengelola = users.id_user
          WHERE $whereSql
          ORDER BY kampanye.deadline ASC, kampanye.dana_terkumpul ASC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);
bindStatement($stmt, $queryTypes, $queryParams);

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

        <section class="hero-content">
            <p class="eyebrow">Crowdfunding sosial</p>
            <h1>Bantu Sesama, Mulai Dari Sini</h1>
            <p>Temukan kampanye aktif dan salurkan donasi untuk kebutuhan pendidikan, bencana, dan lingkungan.</p>
        </section>
    </header>

    <main>
        <section class="filter">
            <form method="GET" class="search-form">
                <input type="text" name="q" value="<?= e($keyword); ?>" placeholder="Cari nama kegiatan, lokasi, atau kategori">
                <input type="date" name="tanggal" value="<?= e($tanggal); ?>" aria-label="Cari berdasarkan tanggal deadline">
                <button type="submit" class="btn">Cari</button>
            </form>
            <p class="filter-note">
                <?= e($totalRows); ?> kampanye aktif ditemukan, diurutkan dari deadline terdekat dan dana terkecil.
            </p>
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

        <?php if ($totalPages > 1) { ?>
            <nav class="pagination" aria-label="Pagination kampanye">
                <?php if ($page > 1) { ?>
                    <a href="<?= e(pageUrl($page - 1, $keyword, $tanggal)); ?>">Sebelumnya</a>
                <?php } ?>

                <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                    <a href="<?= e(pageUrl($i, $keyword, $tanggal)); ?>" class="<?= $i === $page ? 'active' : ''; ?>">
                        <?= e($i); ?>
                    </a>
                <?php } ?>

                <?php if ($page < $totalPages) { ?>
                    <a href="<?= e(pageUrl($page + 1, $keyword, $tanggal)); ?>">Berikutnya</a>
                <?php } ?>
            </nav>
        <?php } ?>
    </main>

    <footer>
        <p>&copy; 2026 Donasi Kita</p>
    </footer>
</body>
</html>
