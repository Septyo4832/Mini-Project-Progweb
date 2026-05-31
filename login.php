<?php
session_start();
include "koneksi.php";

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function safeRedirect($value)
{
    $value = trim((string) $value);

    if ($value === '') {
        return 'index.php';
    }

    $parts = parse_url($value);
    if (isset($parts['scheme']) || isset($parts['host']) || strpos($value, '//') === 0) {
        return 'index.php';
    }

    return $value;
}

function passwordMatches($input, $storedHash)
{
    $storedHash = (string) $storedHash;
    $info = password_get_info($storedHash);

    if (($info['algo'] ?? 0) !== 0) {
        return password_verify($input, $storedHash);
    }

    return hash_equals($storedHash, (string) $input);
}

if (($_GET['action'] ?? '') === 'logout') {
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit();
}

$isLoggedIn = isset($_SESSION['id_user']);
$role = $_SESSION['role'] ?? '';
$mode = $_GET['mode'] ?? 'login';
$mode = $mode === 'register' ? 'register' : 'login';
$redirect = safeRedirect($_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php');
$loginNotice = ($_GET['required'] ?? '') === 'donasi'
    ? "Anda harus login terlebih dahulu untuk melakukan donasi."
    : '';
$requiredParam = $loginNotice ? '&required=donasi' : '';
$loginTabUrl = 'login.php?redirect=' . urlencode($redirect) . $requiredParam;
$registerTabUrl = 'login.php?mode=register&redirect=' . urlencode($redirect) . $requiredParam;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLoggedIn) {
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $passwordInput = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id_user, nama, email, no_telp, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $data = $result->fetch_assoc();

            if (passwordMatches($passwordInput, $data['password'])) {
                $_SESSION['id_user'] = $data['id_user'];
                $_SESSION['nama'] = $data['nama'];
                $_SESSION['email'] = $data['email'];
                $_SESSION['no_telp'] = $data['no_telp'];
                $_SESSION['role'] = $data['role'];

                header("Location: " . $redirect);
                exit();
            }
        }

        $errors[] = "Email atau password tidak sesuai.";
    }

    if (isset($_POST['register'])) {
        $mode = 'register';
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $noTelp = trim($_POST['no_telp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($nama === '' || $email === '' || $noTelp === '' || $password === '') {
            $errors[] = "Nama, email, nomor telepon, dan password wajib diisi.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid.";
        }

        if (strlen($password) < 6) {
            $errors[] = "Password minimal 6 karakter.";
        }

        if ($password !== $confirmPassword) {
            $errors[] = "Konfirmasi password tidak sama.";
        }

        if (!$errors) {
            $stmtCheck = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
            $stmtCheck->bind_param("s", $email);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows > 0) {
                $errors[] = "Email sudah terdaftar.";
            }
        }

        if (!$errors) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'donatur';

            $stmtInsert = $conn->prepare("INSERT INTO users (nama, email, no_telp, alamat, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("ssssss", $nama, $email, $noTelp, $alamat, $passwordHash, $role);

            if ($stmtInsert->execute()) {
                $_SESSION['id_user'] = $stmtInsert->insert_id;
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;
                $_SESSION['no_telp'] = $noTelp;
                $_SESSION['role'] = $role;

                header("Location: " . $redirect);
                exit();
            }

            $errors[] = "Akun gagal dibuat. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Donasi Kita</title>
    <link rel="stylesheet" href="style.css">
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
                    <a href="login.php" class="active">Login</a>
                <?php } ?>
            </nav>
        </div>
    </header>

    <main class="auth-page">
        <section class="auth-card">
            <?php if ($isLoggedIn) { ?>
                <p class="eyebrow">Sudah masuk</p>
                <h1>Halo, <?= e($_SESSION['nama'] ?? 'User'); ?></h1>
                <p class="muted">Kamu sudah login. Tombol login tidak ditampilkan saat session masih aktif.</p>
                <div class="auth-actions">
                    <a href="index.php" class="btn">Kembali ke Home</a>
                    <?php if ($role === 'pengelola') { ?>
                        <a href="kelola_kampanye.php" class="btn btn-secondary">Kelola Kampanye</a>
                    <?php } else { ?>
                        <a href="riwayat_donasi.php" class="btn btn-secondary">Riwayat Donasi</a>
                    <?php } ?>
                    <a href="login.php?action=logout" class="btn btn-secondary">Logout</a>
                </div>
            <?php } else { ?>
                <div class="auth-tabs">
                    <a href="<?= e($loginTabUrl); ?>" class="<?= $mode === 'login' ? 'active' : ''; ?>">Login</a>
                    <a href="<?= e($registerTabUrl); ?>" class="<?= $mode === 'register' ? 'active' : ''; ?>">Buat akun</a>
                </div>

                <?php if ($errors) { ?>
                    <div class="alert error">
                        <?php foreach ($errors as $error) { ?>
                            <p><?= e($error); ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if ($loginNotice && !$errors) { ?>
                    <div class="alert error">
                        <p><?= e($loginNotice); ?></p>
                    </div>
                <?php } ?>

                <?php if ($mode === 'register') { ?>
                    <p class="eyebrow">Akun donatur</p>
                    <h1>Buat Akun</h1>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="redirect" value="<?= e($redirect); ?>">
                        <label>
                            Nama lengkap
                            <input type="text" name="nama" value="<?= e($_POST['nama'] ?? ''); ?>" required>
                        </label>
                        <label>
                            Email
                            <input type="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required>
                        </label>
                        <label>
                            No. telepon
                            <input type="text" name="no_telp" value="<?= e($_POST['no_telp'] ?? ''); ?>" required>
                        </label>
                        <label>
                            Alamat
                            <textarea name="alamat" rows="3"><?= e($_POST['alamat'] ?? ''); ?></textarea>
                        </label>
                        <label>
                            Password
                            <input type="password" name="password" required>
                        </label>
                        <label>
                            Konfirmasi password
                            <input type="password" name="confirm_password" required>
                        </label>
                        <button type="submit" name="register" class="btn">Buat akun</button>
                    </form>
                <?php } else { ?>
                    <p class="eyebrow">Masuk akun</p>
                    <h1>Login</h1>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="redirect" value="<?= e($redirect); ?>">
                        <label>
                            Email
                            <input type="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required>
                        </label>
                        <label>
                            Password
                            <input type="password" name="password" required>
                        </label>
                        <button type="submit" name="login" class="btn">Login</button>
                    </form>
                <?php } ?>
            <?php } ?>
        </section>
    </main>
</body>
</html>
