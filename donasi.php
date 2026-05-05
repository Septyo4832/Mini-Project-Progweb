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
            <a href="index.html">🏠︎ Home</a>
            <a href="login.html">➜] Login</a>
        </nav>
    </header>

    <section class="form">
        <h2>Form Donasi</h2>
        <form>
            <input type="text" placeholder="Nama Lengkap"><br>
            <input type="email" placeholder="Email"><br>
            <div class="nominal">
                <p>Nomimal:</p>
                <button>10.000</button>
                <button>50.000</button>
                <button>100.000</button>
                <button>1.000.000</button>
                <input type="number" placeholder="Masukkan Nomimal">
            </div>
            <p>Metode Pembayaran:</p>
            <div class="payment">
                <button>🏦 Transfer Bank</button>
                <button>📱 E-Wallet</button>
                <button>🔳 QRIS</button>
            </div><br>
            <p>Bukti Pembayaran:</p>
            <input type="file"><br>
            <button type="submit" class="btn">Donasi</button>
        </form>
    </section>
</body>
</html>