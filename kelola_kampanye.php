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

function statusLabel($status)
{
    $labels = [
        'verified' => 'Verified',
        'pending' => 'Pending',
        'rejected' => 'Ditolak',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function saveUploadedCampaignImage(&$errors)
{
    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Gambar kampanye gagal diupload.";
        return null;
    }

    $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png'];
    $mimeType = '';

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['gambar']['tmp_name']);
        finfo_close($finfo);
    }

    $allowedMime = ['image/jpeg', 'image/png'];
    if (!in_array($ext, $allowedExt, true) || ($mimeType !== '' && !in_array($mimeType, $allowedMime, true))) {
        $errors[] = "Gambar kampanye harus berupa JPG atau PNG.";
        return null;
    }

    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    $fileName = "kampanye_" . time() . "_" . mt_rand(1000, 9999) . "." . $ext;
    $target = "uploads/" . $fileName;

    if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
        $errors[] = "Gambar kampanye gagal disimpan di server.";
        return null;
    }

    return $target;
}

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php?redirect=" . urlencode('kelola_kampanye.php'));
    exit;
}

if (($_SESSION['role'] ?? '') !== 'pengelola') {
    header("Location: index.php");
    exit;
}

$isLoggedIn = true;
$role = $_SESSION['role'] ?? '';
$idPengelola = (int) $_SESSION['id_user'];
$errors = [];
$statusMessage = '';

$statusMessages = [
    'saved' => 'Data kampanye berhasil disimpan.',
    'deleted' => 'Data kampanye berhasil dihapus.',
    'verified' => 'Donasi berhasil diverifikasi dan dana terkumpul diperbarui.',
    'rejected' => 'Donasi berhasil ditolak.',
];

if (isset($_GET['status'], $statusMessages[$_GET['status']])) {
    $statusMessage = $statusMessages[$_GET['status']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_campaign') {
        $idKampanye = (int) ($_POST['id_kampanye'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $targetDana = (float) ($_POST['target_dana'] ?? 0);
        $deadline = trim($_POST['deadline'] ?? '');
        $rekening = trim($_POST['rekening'] ?? '');

        if ($judul === '' || $kategori === '' || $lokasi === '' || $deskripsi === '') {
            $errors[] = "Judul, kategori, lokasi, dan deskripsi wajib diisi.";
        }

        if ($targetDana <= 0) {
            $errors[] = "Target dana harus lebih dari 0.";
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
            $errors[] = "Deadline wajib diisi dengan format tanggal yang valid.";
        }

        $gambar = saveUploadedCampaignImage($errors);

        if (!$errors) {
            if ($idKampanye > 0) {
                $stmtOwner = $conn->prepare("SELECT gambar FROM kampanye WHERE id_kampanye = ? AND id_pengelola = ?");
                $stmtOwner->bind_param("ii", $idKampanye, $idPengelola);
                $stmtOwner->execute();
                $existing = $stmtOwner->get_result()->fetch_assoc();

                if (!$existing) {
                    $errors[] = "Kampanye tidak ditemukan atau bukan milik akun ini.";
                } else {
                    $gambar = $gambar ?: $existing['gambar'];
                    $stmtUpdate = $conn->prepare("UPDATE kampanye
                                                 SET judul = ?, kategori = ?, lokasi = ?, deskripsi = ?, target_dana = ?, deadline = ?, gambar = ?, rekening = ?
                                                 WHERE id_kampanye = ? AND id_pengelola = ?");
                    $stmtUpdate->bind_param("ssssdsssii", $judul, $kategori, $lokasi, $deskripsi, $targetDana, $deadline, $gambar, $rekening, $idKampanye, $idPengelola);

                    if ($stmtUpdate->execute()) {
                        header("Location: kelola_kampanye.php?status=saved");
                        exit;
                    }

                    $errors[] = "Data kampanye gagal diperbarui.";
                }
            } else {
                $stmtInsert = $conn->prepare("INSERT INTO kampanye (id_pengelola, judul, kategori, lokasi, deskripsi, target_dana, deadline, gambar, rekening)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtInsert->bind_param("issssdsss", $idPengelola, $judul, $kategori, $lokasi, $deskripsi, $targetDana, $deadline, $gambar, $rekening);

                if ($stmtInsert->execute()) {
                    header("Location: kelola_kampanye.php?status=saved");
                    exit;
                }

                $errors[] = "Data kampanye gagal disimpan.";
            }
        }
    }

    if ($action === 'delete_campaign') {
        $idKampanye = (int) ($_POST['id_kampanye'] ?? 0);
        $stmtCampaign = $conn->prepare("SELECT dana_terkumpul FROM kampanye WHERE id_kampanye = ? AND id_pengelola = ?");
        $stmtCampaign->bind_param("ii", $idKampanye, $idPengelola);
        $stmtCampaign->execute();
        $campaign = $stmtCampaign->get_result()->fetch_assoc();

        if (!$campaign) {
            $errors[] = "Kampanye tidak ditemukan atau bukan milik akun ini.";
        } elseif ((float) $campaign['dana_terkumpul'] >= 10000) {
            $errors[] = "Kampanye dengan dana terkumpul minimal Rp 10.000 tidak dapat dihapus.";
        } else {
            $stmtDelete = $conn->prepare("DELETE FROM kampanye WHERE id_kampanye = ? AND id_pengelola = ?");
            $stmtDelete->bind_param("ii", $idKampanye, $idPengelola);

            if ($stmtDelete->execute()) {
                header("Location: kelola_kampanye.php?status=deleted");
                exit;
            }

            $errors[] = "Kampanye gagal dihapus. Pastikan kampanye tidak memiliki data donasi terkait.";
        }
    }

    if ($action === 'verify_donation') {
        $idDonasi = (int) ($_POST['id_donasi'] ?? 0);
        $decision = $_POST['decision'] ?? '';

        if (!in_array($decision, ['verified', 'rejected'], true)) {
            $errors[] = "Aksi verifikasi tidak valid.";
        } else {
            $conn->begin_transaction();

            try {
                $stmtDonation = $conn->prepare("SELECT donasi.id_donasi, donasi.id_kampanye, donasi.nominal, donasi.status
                                                FROM donasi
                                                JOIN kampanye ON donasi.id_kampanye = kampanye.id_kampanye
                                                WHERE donasi.id_donasi = ? AND kampanye.id_pengelola = ?
                                                FOR UPDATE");
                $stmtDonation->bind_param("ii", $idDonasi, $idPengelola);
                $stmtDonation->execute();
                $donation = $stmtDonation->get_result()->fetch_assoc();

                if (!$donation) {
                    throw new Exception("Donasi tidak ditemukan atau bukan bagian dari kampanye milik akun ini.");
                }

                if ($donation['status'] !== 'pending') {
                    throw new Exception("Donasi ini sudah pernah diverifikasi.");
                }

                $stmtUpdateDonation = $conn->prepare("UPDATE donasi SET status = ? WHERE id_donasi = ?");
                $stmtUpdateDonation->bind_param("si", $decision, $idDonasi);
                $stmtUpdateDonation->execute();

                if ($decision === 'verified') {
                    $stmtUpdateCampaign = $conn->prepare("UPDATE kampanye SET dana_terkumpul = dana_terkumpul + ? WHERE id_kampanye = ?");
                    $nominal = (float) $donation['nominal'];
                    $idKampanye = (int) $donation['id_kampanye'];
                    $stmtUpdateCampaign->bind_param("di", $nominal, $idKampanye);
                    $stmtUpdateCampaign->execute();
                }

                $conn->commit();
                header("Location: kelola_kampanye.php?status=" . ($decision === 'verified' ? 'verified' : 'rejected'));
                exit;
            } catch (Throwable $exception) {
                $conn->rollback();
                $errors[] = $exception->getMessage();
            }
        }
    }
}

$editCampaign = null;
$editId = (int) ($_GET['edit'] ?? 0);

if ($editId > 0) {
    $stmtEdit = $conn->prepare("SELECT * FROM kampanye WHERE id_kampanye = ? AND id_pengelola = ?");
    $stmtEdit->bind_param("ii", $editId, $idPengelola);
    $stmtEdit->execute();
    $editCampaign = $stmtEdit->get_result()->fetch_assoc();
}

$stmtCampaigns = $conn->prepare("SELECT kampanye.*,
                                        COALESCE(stats.pending_total, 0) AS pending_total,
                                        COALESCE(stats.pending_count, 0) AS pending_count,
                                        COALESCE(stats.verified_total, 0) AS verified_total,
                                        COALESCE(stats.verified_count, 0) AS verified_count,
                                        COALESCE(stats.rejected_total, 0) AS rejected_total,
                                        COALESCE(stats.rejected_count, 0) AS rejected_count
                                 FROM kampanye
                                 LEFT JOIN (
                                     SELECT id_kampanye,
                                            SUM(CASE WHEN status = 'pending' THEN nominal ELSE 0 END) AS pending_total,
                                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                                            SUM(CASE WHEN status = 'verified' THEN nominal ELSE 0 END) AS verified_total,
                                            SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) AS verified_count,
                                            SUM(CASE WHEN status = 'rejected' THEN nominal ELSE 0 END) AS rejected_total,
                                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
                                     FROM donasi
                                     GROUP BY id_kampanye
                                 ) stats ON kampanye.id_kampanye = stats.id_kampanye
                                 WHERE kampanye.id_pengelola = ?
                                 ORDER BY kampanye.deadline ASC, kampanye.dana_terkumpul ASC");
$stmtCampaigns->bind_param("i", $idPengelola);
$stmtCampaigns->execute();
$campaigns = $stmtCampaigns->get_result();

$stmtDonations = $conn->prepare("SELECT donasi.*, users.nama AS nama_donatur, users.email AS email_donatur, kampanye.judul AS judul_kampanye
                                 FROM donasi
                                 JOIN users ON donasi.id_user = users.id_user
                                 JOIN kampanye ON donasi.id_kampanye = kampanye.id_kampanye
                                 WHERE kampanye.id_pengelola = ?
                                 ORDER BY FIELD(donasi.status, 'pending', 'verified', 'rejected'), donasi.tanggal_donasi DESC");
$stmtDonations->bind_param("i", $idPengelola);
$stmtDonations->execute();
$donations = $stmtDonations->get_result();

$formCampaign = [
    'id_kampanye' => $editCampaign['id_kampanye'] ?? '',
    'judul' => $_POST['judul'] ?? ($editCampaign['judul'] ?? ''),
    'kategori' => $_POST['kategori'] ?? ($editCampaign['kategori'] ?? ''),
    'lokasi' => $_POST['lokasi'] ?? ($editCampaign['lokasi'] ?? ''),
    'deskripsi' => $_POST['deskripsi'] ?? ($editCampaign['deskripsi'] ?? ''),
    'target_dana' => $_POST['target_dana'] ?? ($editCampaign['target_dana'] ?? ''),
    'deadline' => $_POST['deadline'] ?? ($editCampaign['deadline'] ?? ''),
    'rekening' => $_POST['rekening'] ?? ($editCampaign['rekening'] ?? ''),
    'gambar' => $editCampaign['gambar'] ?? '',
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Kelola Kampanye - Donasi Kita</title>
</head>
<body>
    <header class="site-header compact-header">
        <div class="header-inner">
            <a href="index.php" class="brand">Donasi Kita</a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a href="index.php">Home</a>
                <a href="kelola_kampanye.php" class="active">Kelola Kampanye</a>
                <span class="nav-user">Halo, <?= e($_SESSION['nama'] ?? 'Pengelola'); ?></span>
                <a href="login.php?action=logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-page">
        <section class="page-heading">
            <p class="eyebrow">Pengelola</p>
            <h1>Kelola Kampanye dan Donasi</h1>
            <p class="muted">Kampanye, daftar donatur, dan verifikasi bukti transfer hanya ditampilkan untuk kampanye milik akun ini.</p>
        </section>

        <?php if ($statusMessage) { ?>
            <div class="alert success page-alert">
                <p><?= e($statusMessage); ?></p>
            </div>
        <?php } ?>

        <?php if ($errors) { ?>
            <div class="alert error page-alert">
                <?php foreach ($errors as $error) { ?>
                    <p><?= e($error); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

        <section class="form-panel management-form">
            <p class="eyebrow"><?= $editCampaign ? 'Edit kampanye' : 'Tambah kampanye'; ?></p>
            <h1><?= $editCampaign ? e($editCampaign['judul']) : 'Data Kampanye'; ?></h1>

            <form method="POST" enctype="multipart/form-data" class="donation-form">
                <input type="hidden" name="action" value="save_campaign">
                <input type="hidden" name="id_kampanye" value="<?= e($formCampaign['id_kampanye']); ?>">

                <label>
                    Judul kampanye
                    <input type="text" name="judul" value="<?= e($formCampaign['judul']); ?>" required>
                </label>

                <label>
                    Kategori
                    <input type="text" name="kategori" value="<?= e($formCampaign['kategori']); ?>" required>
                </label>

                <label>
                    Lokasi
                    <input type="text" name="lokasi" value="<?= e($formCampaign['lokasi']); ?>" required>
                </label>

                <label>
                    Deskripsi
                    <textarea name="deskripsi" rows="4" required><?= e($formCampaign['deskripsi']); ?></textarea>
                </label>

                <div class="form-grid">
                    <label>
                        Target dana
                        <input type="number" name="target_dana" min="1" value="<?= e($formCampaign['target_dana']); ?>" required>
                    </label>

                    <label>
                        Deadline
                        <input type="date" name="deadline" value="<?= e($formCampaign['deadline']); ?>" required>
                    </label>
                </div>

                <label>
                    Rekening
                    <input type="text" name="rekening" value="<?= e($formCampaign['rekening']); ?>">
                </label>

                <label>
                    Gambar kampanye (JPG/PNG)
                    <input type="file" name="gambar" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                </label>

                <?php if ($formCampaign['gambar']) { ?>
                    <p class="muted">Gambar saat ini: <?= e($formCampaign['gambar']); ?></p>
                <?php } ?>

                <div class="auth-actions">
                    <button type="submit" class="btn"><?= $editCampaign ? 'Simpan Perubahan' : 'Tambah Kampanye'; ?></button>
                    <?php if ($editCampaign) { ?>
                        <a href="kelola_kampanye.php" class="btn btn-secondary">Batal Edit</a>
                    <?php } ?>
                </div>
            </form>
        </section>

        <section class="section-title">
            <h2>Daftar Kampanye Saya</h2>
        </section>

        <?php if ($campaigns->num_rows === 0) { ?>
            <div class="empty-state">
                <h2>Belum ada kampanye</h2>
                <p class="muted">Tambahkan kampanye pertama melalui form di atas.</p>
            </div>
        <?php } else { ?>
            <section class="manager-grid" aria-label="Daftar kampanye pengelola">
                <?php while ($campaign = $campaigns->fetch_assoc()) { ?>
                    <article class="manager-card">
                        <img src="<?= e(campaignImage($campaign['gambar'])); ?>" alt="<?= e($campaign['judul']); ?>">
                        <div class="manager-card-body">
                            <span class="category"><?= e($campaign['kategori']); ?></span>
                            <h3><?= e($campaign['judul']); ?></h3>
                            <p class="muted"><?= e($campaign['lokasi']); ?> | Deadline <?= e(date('d M Y', strtotime($campaign['deadline']))); ?></p>

                            <div class="summary-grid compact-summary">
                                <div class="summary-card">
                                    <span>Terkumpul</span>
                                    <strong><?= e(rupiah($campaign['dana_terkumpul'])); ?></strong>
                                </div>
                                <div class="summary-card status-pending">
                                    <span>Pending</span>
                                    <strong><?= e(rupiah($campaign['pending_total'])); ?></strong>
                                    <small><?= e((int) $campaign['pending_count']); ?> donasi</small>
                                </div>
                            </div>

                            <div class="donation-breakdown">
                                <span class="status-badge status-verified">Verified: <?= e(rupiah($campaign['verified_total'])); ?> (<?= e((int) $campaign['verified_count']); ?>)</span>
                                <span class="status-badge status-pending">Pending: <?= e(rupiah($campaign['pending_total'])); ?> (<?= e((int) $campaign['pending_count']); ?>)</span>
                                <span class="status-badge status-rejected">Ditolak: <?= e(rupiah($campaign['rejected_total'])); ?> (<?= e((int) $campaign['rejected_count']); ?>)</span>
                            </div>

                            <div class="card-actions">
                                <a class="btn btn-small" href="kelola_kampanye.php?edit=<?= e($campaign['id_kampanye']); ?>">Edit</a>
                                <?php if ((float) $campaign['dana_terkumpul'] >= 10000) { ?>
                                    <button class="btn btn-small btn-disabled" type="button" disabled>Tidak Bisa Hapus</button>
                                <?php } else { ?>
                                    <form method="POST" onsubmit="return confirm('Hapus kampanye ini?');">
                                        <input type="hidden" name="action" value="delete_campaign">
                                        <input type="hidden" name="id_kampanye" value="<?= e($campaign['id_kampanye']); ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Hapus</button>
                                    </form>
                                <?php } ?>
                            </div>
                        </div>
                    </article>
                <?php } ?>
            </section>
        <?php } ?>

        <section class="table-card">
            <div class="section-title">
                <h2>Daftar Donatur dan Verifikasi Donasi</h2>
            </div>

            <?php if ($donations->num_rows === 0) { ?>
                <div class="empty-state">
                    <h2>Belum ada donasi</h2>
                    <p class="muted">Donasi untuk kampanye milik akun ini akan tampil di sini.</p>
                </div>
            <?php } else { ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Kampanye</th>
                                <th>Donatur</th>
                                <th>Nominal</th>
                                <th>Metode</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Bukti</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($donation = $donations->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <strong><?= e($donation['judul_kampanye']); ?></strong>
                                        <?php if ($donation['pesan']) { ?>
                                            <span class="table-note"><?= e($donation['pesan']); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <strong><?= e($donation['nama_donatur']); ?></strong>
                                        <span class="table-note"><?= e($donation['email_donatur']); ?></span>
                                    </td>
                                    <td><?= e(rupiah($donation['nominal'])); ?></td>
                                    <td><?= e($donation['metode_pembayaran']); ?></td>
                                    <td><?= e(date('d M Y H:i', strtotime($donation['tanggal_donasi']))); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= e($donation['status']); ?>">
                                            <?= e(statusLabel($donation['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($donation['bukti_transfer']) { ?>
                                            <a class="text-link" href="<?= e($donation['bukti_transfer']); ?>" target="_blank" rel="noopener">Lihat</a>
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($donation['status'] === 'pending') { ?>
                                            <div class="table-actions">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="verify_donation">
                                                    <input type="hidden" name="decision" value="verified">
                                                    <input type="hidden" name="id_donasi" value="<?= e($donation['id_donasi']); ?>">
                                                    <button type="submit" class="btn btn-small">Terima</button>
                                                </form>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="verify_donation">
                                                    <input type="hidden" name="decision" value="rejected">
                                                    <input type="hidden" name="id_donasi" value="<?= e($donation['id_donasi']); ?>">
                                                    <button type="submit" class="btn btn-small btn-danger">Tolak</button>
                                                </form>
                                            </div>
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
