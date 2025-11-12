<?php
ob_start(); // Prevent "headers already sent" errors
session_start();
require_once '../../config/Database.php';
require_once '../components/Toast.php';

// Auth Guard - Hanya buyer yang bisa checkout
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../index.php");
    exit();
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

$buyer_id = $_SESSION['user_id'];
$cartItemsByStore = [];
$totalPrice = 0;
$buyerBalance = 0;
$buyerAddress = '';
$error = null;

// Fetch cart items grouped by store
try {
    $db = new Database();
    $pdo = $db->connect();

    // Get buyer balance and address
    $balanceQuery = "SELECT balance, address FROM users WHERE user_id = ?";
    $balanceStmt = $pdo->prepare($balanceQuery);
    $balanceStmt->execute([$buyer_id]);
    $balanceResult = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    $buyerBalance = $balanceResult ? $balanceResult['balance'] : 0;
    $buyerAddress = $balanceResult && isset($balanceResult['address']) ? $balanceResult['address'] : '';

    // Query cart items dengan JOIN
    $query = "SELECT
                ci.cart_item_id,
                ci.quantity,
                p.product_id,
                p.product_name,
                p.price,
                p.stock,
                p.main_image_path,
                s.store_id,
                s.store_name,
                (ci.quantity * p.price) as subtotal
              FROM cart_item ci
              INNER JOIN product p ON ci.product_id = p.product_id
              INNER JOIN store s ON p.store_id = s.store_id
              WHERE ci.buyer_id = ? AND p.deleted_at IS NULL
              ORDER BY s.store_name, p.product_name";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$buyer_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by store_id
    foreach ($cartItems as $item) {
        $store_id = $item['store_id'];

        if (!isset($cartItemsByStore[$store_id])) {
            $cartItemsByStore[$store_id] = [
                'store_name' => $item['store_name'],
                'items' => [],
                'store_total' => 0
            ];
        }

        $cartItemsByStore[$store_id]['items'][] = $item;
        $cartItemsByStore[$store_id]['store_total'] += $item['subtotal'];
        $totalPrice += $item['subtotal'];
    }

    // Redirect jika cart kosong
    if (empty($cartItems)) {
        header("Location: cart.php");
        exit();
    }

} catch (PDOException $e) {
    $error = "Terjadi kesalahan saat memuat data.";
    error_log("Checkout Page Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Nimonspedia</title>
    <link rel="stylesheet" href="../../resources/public.css">
    <link rel="stylesheet" href="../../resources/buyer/checkout.css">
</head>
<body>

<?php
Toast::render();
include '../components/Navbar.php';
?>

<main class="checkout-container">
    <h1 class="checkout-title">Checkout</h1>

    <?php if ($error): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.showToast('error', <?php echo json_encode($error); ?>);
            });
        </script>
        <div class="checkout-error">
            <a href="cart.php" class="checkout-btn-back">← Kembali ke Keranjang</a>
        </div>

    <?php else: ?>

        <form id="checkout-form" class="checkout-content">

            <!-- Left Column: Order Summary -->
            <div class="checkout-order-section">
                <h2>Ringkasan Pesanan</h2>

                <?php foreach ($cartItemsByStore as $store_id => $storeData): ?>
                    <div class="checkout-store-group">
                        <h3 class="checkout-store-name">
                            <i data-lucide="store"></i>
                            <a href="store_detail.php?id=<?php echo $store_id; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($storeData['store_name']); ?>
                            </a>
                        </h3>

                        <div class="checkout-items">
                            <?php foreach ($storeData['items'] as $item):
                                // Handle both local path and URL
                                if (!empty($item['main_image_path'])) {
                                    if (strpos($item['main_image_path'], 'http://') === 0 || strpos($item['main_image_path'], 'https://') === 0) {
                                        $itemImagePath = htmlspecialchars($item['main_image_path']);
                                    } else {
                                        $itemImagePath = '../../' . htmlspecialchars($item['main_image_path']);
                                    }
                                } else {
                                    $placeholderText = urlencode(str_replace(' ', '+', $item['product_name']));
                                    $itemImagePath = 'https://placehold.co/600x400/f0f2f4/333333?text=' . $placeholderText;
                                }
                            ?>
                                <div class="checkout-item">
                                    <img src="<?php echo $itemImagePath; ?>"
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="checkout-item-image">

                                    <div class="checkout-item-info">
                                        <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                        <p class="checkout-item-price">
                                            Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                                            × <?php echo $item['quantity']; ?>
                                        </p>
                                    </div>

                                    <div class="checkout-item-subtotal">
                                        Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="checkout-store-total">
                            <span>Subtotal Toko:</span>
                            <span>Rp <?php echo number_format($storeData['store_total'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Right Column: Shipping & Payment -->
            <div class="checkout-form-section">

                <!-- Shipping Address -->
                <div class="checkout-card">
                    <h2>Alamat Pengiriman</h2>
                    <textarea
                        name="shipping_address"
                        id="shipping-address"
                        rows="4"
                        placeholder="Masukkan alamat lengkap pengiriman..."
                        required><?php echo htmlspecialchars($buyerAddress); ?></textarea>
                    <p class="checkout-field-note">* Harap masukkan alamat lengkap dengan detail (nama jalan, nomor rumah, kecamatan, kota, kode pos)</p>
                </div>

                <!-- Payment Summary -->
                <div class="checkout-card checkout-payment">
                    <h2>Pembayaran</h2>

                    <div class="checkout-summary-row">
                        <span>Total Pesanan:</span>
                        <span class="checkout-summary-value">Rp <?php echo number_format($totalPrice, 0, ',', '.'); ?></span>
                    </div>

                    <div class="checkout-summary-row">
                        <span>Saldo Anda:</span>
                        <span class="checkout-summary-value <?php echo $buyerBalance < $totalPrice ? 'insufficient' : ''; ?>">
                            Rp <?php echo number_format($buyerBalance, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <?php if ($buyerBalance < $totalPrice): ?>
                        <div class="checkout-insufficient-warning">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p>Saldo tidak mencukupi! Harap top-up terlebih dahulu.</p>
                        </div>
                    <?php endif; ?>

                    <div class="checkout-summary-row checkout-summary-total">
                        <span>Total Pembayaran:</span>
                        <span>Rp <?php echo number_format($totalPrice, 0, ',', '.'); ?></span>
                    </div>

                    <button
                        type="submit"
                        class="checkout-btn-submit"
                        <?php echo $buyerBalance < $totalPrice ? 'disabled' : ''; ?>>
                        <?php echo $buyerBalance < $totalPrice ? 'Saldo Tidak Cukup' : 'Bayar & Buat Pesanan'; ?>
                    </button>

                    <a href="cart.php" class="checkout-btn-cancel">Kembali ke Keranjang</a>
                </div>

            </div>

        </form>

    <?php endif; ?>

</main>
<script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
<script src="../../public/buyer/checkout.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
