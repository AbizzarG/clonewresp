<?php
session_start();

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

// Auth Guard - Hanya buyer yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Nimonspedia</title>
    <link rel="stylesheet" href="../../resources/public.css">
    <link rel="stylesheet" href="../../resources/buyer/order_success.css">
</head>
<body>

<?php include '../components/Navbar.php'; ?>

<main class="success-container">

    <div class="success-card">

        <!-- Success Icon -->
        <div class="success-icon">
            <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>

        <!-- Success Message -->
        <h1>Pesanan Berhasil Dibuat!</h1>
        <p class="success-description">
            Terima kasih telah berbelanja di Nimonspedia. Pesanan Anda sedang menunggu konfirmasi dari penjual.
        </p>

        <!-- Order Info -->
        <div class="success-info">
            <div class="success-info-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Status: <strong>Menunggu Persetujuan</strong></span>
            </div>

            <div class="success-info-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                </svg>
                <span>Penjual akan segera memproses pesanan Anda</span>
            </div>

            <div class="success-info-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span>Anda akan menerima notifikasi untuk update pesanan</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="success-actions">
            <a href="index.php" class="success-btn-primary">Lanjut Belanja</a>
            <a href="order_history.php" class="success-btn-secondary">Lihat Pesanan Saya</a>
        </div>
    </div>
</main>
</body>
</html>
