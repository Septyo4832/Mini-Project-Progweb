<?php
session_start();
require_once 'koneksi.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function statusLabel($status)
{
    $labels = [
        'verified' => 'Verified',
        'pending' => 'Pending',
        'rejected' => 'Ditolak',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php?redirect=" . urlencode('riwayat_donasi.php'));
    exit;
}

$isLoggedIn = true;
$role = $_SESSION['role'] ?? '';
$idUser = (int) $_SESSION['id_user'];

$summary = [
    'verified' => ['count' => 0, 'total' => 0],
    'pending' => ['count' => 0, 'total' => 0],
    'rejected' => ['count' => 0, 'total' => 0],
];

$stmtSummary = $conn->prepare("SELECT status, COUNT(*) AS jumlah, COALESCE(SUM(nominal), 0) AS total FROM donasi WHERE id_user = ? GROUP BY status");
$stmtSummary->bind_param("i", $idUser);
$stmtSummary->execute();
$summaryResult = $stmtSummary->get_result();

while ($row = $summaryResult->fetch_assoc()) {
    $summary[$row['status']] = [
        'count' => (int) $row['jumlah'],
        'total' => (float) $row['total'],
    ];
}

$stmtHistory = $conn->prepare("SELECT donasi.*, kampanye.judul
                               FROM donasi
                               JOIN kampanye ON donasi.id_kampanye = kampanye.id_kampanye
                               WHERE donasi.id_user = ?
                               ORDER BY donasi.tanggal_donasi DESC");
$stmtHistory->bind_param("i", $idUser);
$stmtHistory->execute();
$history = $stmtHistory->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Riwayat Donasi - Donasi Kita</title>
</head>
<body>
    <header class="site-header compact-header">
        <div class="header-inner">
            <a href="index.php" class="brand">Donasi Kita</a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a href="index.php">Home</a>
                <?php if ($role === 'pengelola') { ?>
                    <a href="kelola_kampanye.php">Kelola Kampanye</a>
                <?php } else { ?>
                    <a href="riwayat_donasi.php" class="active">Riwayat Donasi</a>
                <?php } ?>
                <span class="nav-user">Halo, <?= e($_SESSION['nama'] ?? 'User'); ?></span>
                <a href="login.php?action=logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-page">
        <section class="page-heading">
            <p class="eyebrow">Riwayat donasi</p>
            <h1>Donasi Saya</h1>
            <p class="muted">Semua donasi yang pernah dikirim beserta status verifikasinya.</p>
        </section>

        <section class="summary-grid" aria-label="Ringkasan donasi">
            <?php foreach (['verified', 'pending', 'rejected'] as $status) { ?>
                <article class="summary-card status-<?= e($status); ?>">
                    <span><?= e(statusLabel($status)); ?></span>
                    <strong><?= e(rupiah($summary[$status]['total'])); ?></strong>
                    <small><?= e($summary[$status]['count']); ?> donasi</small>
                </article>
            <?php } ?>
        </section>

        <section class="table-card">
            <div class="section-title">
                <h2>Riwayat Donasi</h2>
            </div>

            <?php if ($history->num_rows === 0) { ?>
                <div class="empty-state">
                    <h2>Belum ada donasi</h2>
                    <p class="muted">Donasi yang kamu kirim akan tampil di sini.</p>
                </div>
            <?php } else { ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Kampanye</th>
                                <th>Nominal</th>
                                <th>Metode</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $history->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <strong><?= e($row['judul']); ?></strong>
                                        <?php if ($row['pesan']) { ?>
                                            <span class="table-note"><?= e($row['pesan']); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td><?= e(rupiah($row['nominal'])); ?></td>
                                    <td><?= e($row['metode_pembayaran']); ?></td>
                                    <td><?= e(date('d M Y H:i', strtotime($row['tanggal_donasi']))); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= e($row['status']); ?>">
                                            <?= e(statusLabel($row['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['bukti_transfer']) { ?>
                                            <a class="text-link" href="<?= e($row['bukti_transfer']); ?>" target="_blank" rel="noopener">Lihat</a>
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </section>
    </main>
</body>
</html>
