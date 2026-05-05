<?php
include "koneksi.php";

$query = "SELECT kampanye.*, users.nama AS nama_pengelola
          FROM kampanye
          JOIN users ON kampanye.id_pengelola = users.id_user
          WHERE deadline >= CURDATE()
          ORDER BY deadline ASC, dana_terkumpul ASC";

$result = mysqli_query($conn, $query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Crowdfunding Sosial</title>
</head>
<body>
    <header>
        <h1>DonasiKita</h1>
        <nav>
            <a href="index.php">🏠︎ Home</a>
            <a href="login.php">➜] Login</a>
        </nav>
    </header>

    <section class="filter">
        <h2>Bantu Sesama, Mulai Dari Sini</h2>
        <input type="text" placeholder="Cari kampanye...">
    </section>

    <section class="campaigns">
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <div class="card">
            <img src="<?= $row['gambar']; ?>" alt="poster kampanye">

            <h3><?= $row['judul']; ?></h3>
            <p>Kategori: <?= $row['kategori']; ?></p>
            <p>Penyelenggara: <?= $row['nama_pengelola']; ?></p>
            <p>Target: Rp <?= number_format($row['target_dana'], 0, ',', '.'); ?></p>
            <p>Terkumpul: Rp <?= number_format($row['dana_terkumpul'], 0, ',', '.'); ?></p>
            <p>Deadline: <?= $row['deadline']; ?></p>
            <a href="detail.php?id=<?= $row['id_kampanye']; ?>" class="btn"> Lihat Detail </a>
            </div>  
        <?php } ?> 
    </section>

    <footer>
        <p>© 2026 DonasiKita</p>
    </footer>
</body>
</html>