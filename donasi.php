<?php
session_start();
require_once 'koneksi.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$isLoggedIn = isset($_SESSION['id_user']);
$role = $_SESSION['role'] ?? '';
$idKampanye = isset($_POST['id_kampanye'])
    ? (int) $_POST['id_kampanye']
    : (int) ($_GET['id'] ?? 0);

if ($idKampanye <= 0) {
    header("Location: index.php");
    exit;
}

if (!$isLoggedIn) {
    $redirect = 'donasi.php?id=' . $idKampanye;
    $loginUrl = 'login.php?required=donasi&redirect=' . urlencode($redirect);
    header("Location: " . $loginUrl);
    exit;
}

$stmtCampaign = $conn->prepare("SELECT id_kampanye, judul, target_dana, dana_terkumpul, rekening FROM kampanye WHERE id_kampanye = ? AND deadline >= CURDATE()");
$stmtCampaign->bind_param("i", $idKampanye);
$stmtCampaign->execute();
$campaign = $stmtCampaign->get_result()->fetch_assoc();

if (!$campaign) {
    header("Location: index.php");
    exit;
}

$sessionUserId = (int) $_SESSION['id_user'];
$stmtUser = $conn->prepare("SELECT nama, email, no_telp FROM users WHERE id_user = ?");
$stmtUser->bind_param("i", $sessionUserId);
$stmtUser->execute();
$currentUser = $stmtUser->get_result()->fetch_assoc();

if (!$currentUser) {
    session_unset();
    session_destroy();
    header("Location: login.php?required=donasi&redirect=" . urlencode('donasi.php?id=' . $idKampanye));
    exit;
}

$_SESSION['nama'] = $currentUser['nama'];
$_SESSION['email'] = $currentUser['email'];
$_SESSION['no_telp'] = $currentUser['no_telp'];

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['proses_donasi'])) {
    $noTelp = trim($_POST['no_telp'] ?? '');
    $nominal = (float) ($_POST['nominal_input'] ?? 0);
    $metode = trim($_POST['metode_pembayaran'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    $idUser = (int) $_SESSION['id_user'];

    if ($noTelp === '') {
        $errors[] = "Nomor telepon wajib tersimpan pada akun donatur.";
    }

    if ($nominal < 10000) {
        $errors[] = "Nominal donasi minimal Rp 10.000.";
    }

    if ($metode === '') {
        $errors[] = "Metode pembayaran wajib dipilih.";
    }

    $namaFile = null;
    if (!$errors) {
        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg'];
            $mimeType = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['bukti_transfer']['tmp_name']);
                finfo_close($finfo);
            }

            if (!in_array($ext, $allowedExt, true) || ($mimeType !== '' && $mimeType !== 'image/jpeg')) {
                $errors[] = "Bukti pembayaran harus berupa file JPG.";
            } else {
                $namaFile = "bukti_" . time() . "_" . mt_rand(1000, 9999) . "." . $ext;

                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], "uploads/" . $namaFile)) {
                    $namaFile = "uploads/" . $namaFile;
                } else {
                    $errors[] = "Bukti pembayaran gagal disimpan di server.";
                }
            }
        } else {
            $errors[] = "Bukti pembayaran wajib diunggah.";
        }
    }

    if (!$errors && $noTelp !== $currentUser['no_telp']) {
        $stmtUpdatePhone = $conn->prepare("UPDATE users SET no_telp = ? WHERE id_user = ?");
        $stmtUpdatePhone->bind_param("si", $noTelp, $idUser);
        $stmtUpdatePhone->execute();
        $_SESSION['no_telp'] = $noTelp;
    }

    if (!$errors && $idUser) {
        $stmtDonasi = $conn->prepare("INSERT INTO donasi (id_user, id_kampanye, nominal, metode_pembayaran, pesan, bukti_transfer, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmtDonasi->bind_param("iidsss", $idUser, $idKampanye, $nominal, $metode, $pesan, $namaFile);

        if ($stmtDonasi->execute()) {
            header("Location: detail.php?id=" . $idKampanye . "&status=donasi-berhasil");
            exit;
        }

        $errors[] = "Donasi gagal disimpan. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Donasi - Donasi Kita</title>
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

    <main class="form-page">
        <section class="form-panel">
            <p class="eyebrow">Form donasi</p>
            <h1><?= e($campaign['judul']); ?></h1>
            <div class="summary-grid donation-summary">
                <div class="summary-card">
                    <span>Target</span>
                    <strong>Rp <?= number_format((float) $campaign['target_dana'], 0, ',', '.'); ?></strong>
                </div>
                <div class="summary-card">
                    <span>Dana terkumpul</span>
                    <strong>Rp <?= number_format((float) $campaign['dana_terkumpul'], 0, ',', '.'); ?></strong>
                </div>
            </div>
            <p class="muted">Rekening tujuan: <?= e($campaign['rekening']); ?></p>

            <?php if ($errors) { ?>
                <div class="alert error">
                    <?php foreach ($errors as $error) { ?>
                        <p><?= e($error); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <form method="POST" action="" enctype="multipart/form-data" class="donation-form">
                <input type="hidden" name="id_kampanye" value="<?= e($idKampanye); ?>">

                <label>
                    Nama lengkap
                    <input type="text" value="<?= e($currentUser['nama']); ?>" readonly>
                </label>

                <label>
                    Email
                    <input type="email" value="<?= e($currentUser['email']); ?>" readonly>
                </label>

                <label>
                    No. telepon
                    <input type="text" name="no_telp" value="<?= e($_POST['no_telp'] ?? $currentUser['no_telp']); ?>" required>
                </label>

                <div class="nominal">
                    <p>Nominal</p>
                    <div class="quick-amounts">
                        <button type="button" data-amount="10000">10.000</button>
                        <button type="button" data-amount="50000">50.000</button>
                        <button type="button" data-amount="100000">100.000</button>
                        <button type="button" data-amount="1000000">1.000.000</button>
                    </div>
                    <input type="number" name="nominal_input" id="nominal_input" value="<?= e($_POST['nominal_input'] ?? ''); ?>" placeholder="Masukkan nominal" min="10000" required>
                </div>

                <label>
                    Metode pembayaran
                    <select name="metode_pembayaran" required>
                        <option value="">Pilih metode</option>
                        <option value="Transfer Bank" <?= ($_POST['metode_pembayaran'] ?? '') === 'Transfer Bank' ? 'selected' : ''; ?>>Transfer Bank</option>
                        <option value="E-Wallet" <?= ($_POST['metode_pembayaran'] ?? '') === 'E-Wallet' ? 'selected' : ''; ?>>E-Wallet</option>
                        <option value="QRIS" <?= ($_POST['metode_pembayaran'] ?? '') === 'QRIS' ? 'selected' : ''; ?>>QRIS</option>
                    </select>
                </label>

                <label>
                    Pesan
                    <textarea name="pesan" rows="3" placeholder="Tulis pesan singkat"><?= e($_POST['pesan'] ?? ''); ?></textarea>
                </label>

                <label>
                    Bukti pembayaran (JPG)
                    <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,image/jpeg" required>
                </label>

                <button type="submit" name="proses_donasi" class="btn">Kirim Donasi</button>
            </form>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-amount]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('nominal_input').value = button.dataset.amount;
            });
        });
    </script>
</body>
</html>
