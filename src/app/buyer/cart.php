<?php
ob_start(); // Prevent "headers already sent" errors
session_start();
require_once '../../config/Database.php';
require_once '../components/Toast.php';

// Logout - HARUS SEBELUM OUTPUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../index.php");
    exit();
}

$buyer_id = $_SESSION['user_id'];
$cartItemsByStore = [];
$totalPrice = 0;
$error = null;

// Fetch cart items
try {
    $db = new Database();
    $pdo = $db->connect();

    // Query cart items dengan JOIN ke product dan store
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

} catch (PDOException $e) {
    $error = "Terjadi kesalahan saat memuat keranjang.";
    error_log("Cart Page Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Nimonspedia</title>
    <link rel="stylesheet" href="../../resources/public.css">
    <link rel="stylesheet" href="../../resources/buyer/cart.css">
</head>
<body>
<?php Toast::render(); ?>
<?php include '../components/Navbar.php'; ?>

<main class="cart-container">
    <h1 class="cart-title">Keranjang Belanja</h1>

    <?php if ($error): ?>
        <!-- Error State -->
        <div class="cart-error">
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="index.php" class="cart-btn-back">Kembali Belanja</a>
        </div>

    <?php elseif (empty($cartItemsByStore)): ?>
        <!-- Empty Cart State -->
        <div class="cart-empty">
            <i data-lucide="shopping-cart" class="cart-empty-icon"></i>
            <h2>Keranjang Anda Kosong</h2>
            <p>Belum ada produk di keranjang belanja Anda.</p>
            <a href="index.php" class="cart-btn-shop">Mulai Belanja</a>
        </div>

    <?php else: ?>
        <!-- Cart Items -->
        <div class="cart-content">

            <!-- Cart Items List -->
            <div class="cart-items-section">
                <?php foreach ($cartItemsByStore as $store_id => $storeData): ?>
                    <div class="cart-store-group">
                        <h3 class="cart-store-name">
                            <i class='cart-store-icon' data-lucide="store"></i>
                            <a href="store_detail.php?id=<?php echo $store_id; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($storeData['store_name']); ?>
                            </a>
                        </h3>

                        <div class="cart-store-items">
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
                            <div class="cart-item" data-cart-item-id="<?php echo $item['cart_item_id']; ?>">

                                <!-- Product Image -->
                                <div class="cart-item-image">
                                    <img src="<?php echo $itemImagePath; ?>"
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>

                                <!-- Product Info -->
                                <div class="cart-item-info">
                                    <h3 class="cart-item-name">
                                        <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </a>
                                    </h3>
                                    <p class="cart-item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>

                                    <!-- Stock Warning -->
                                    <?php if ($item['quantity'] > $item['stock']): ?>
                                        <p class="cart-item-stock-warning">
                                            Stok tersisa: <?php echo $item['stock']; ?> (Kurangi jumlah!)
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Quantity Control -->
                                <div class="cart-item-quantity">
                                    <button class="cart-qty-btn cart-qty-decrease"
                                            data-cart-item-id="<?php echo $item['cart_item_id']; ?>"
                                            data-current-qty="<?php echo $item['quantity']; ?>">
                                        -
                                    </button>
                                    <input type="number"
                                           class="cart-qty-input"
                                           value="<?php echo $item['quantity']; ?>"
                                           min="1"
                                           max="<?php echo $item['stock']; ?>"
                                           data-cart-item-id="<?php echo $item['cart_item_id']; ?>"
                                           data-max-stock="<?php echo $item['stock']; ?>">
                                    <button class="cart-qty-btn cart-qty-increase"
                                            data-cart-item-id="<?php echo $item['cart_item_id']; ?>"
                                            data-current-qty="<?php echo $item['quantity']; ?>"
                                            data-max-stock="<?php echo $item['stock']; ?>">
                                        +
                                    </button>
                                </div>

                                <!-- Subtotal -->
                                <div class="cart-item-subtotal">
                                    <p class="cart-subtotal-label">Subtotal:</p>
                                    <p class="cart-subtotal-price">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></p>
                                </div>

                                <!-- Remove Button -->
                                <button class="cart-item-remove"
                                        data-cart-item-id="<?php echo $item['cart_item_id']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>

                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-store-total">
                            <span>Subtotal Toko:</span>
                            <span>Rp <?php echo number_format($storeData['store_total'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <h2>Ringkasan Belanja</h2>

                <div class="cart-summary-row">
                    <span>Total Item:</span>
                    <span id="cart-total-items"><?php 
                        $totalItems = 0;
                        foreach ($cartItemsByStore as $storeData) {
                            $totalItems += count($storeData['items']);
                        }
                        echo $totalItems; 
                    ?> produk</span>
                </div>

                <div class="cart-summary-row cart-summary-total">
                    <span>Total Harga:</span>
                    <span id="cart-total-price">Rp <?php echo number_format($totalPrice, 0, ',', '.'); ?></span>
                </div>

                <a href="checkout.php" class="cart-btn-checkout">Lanjut ke Checkout</a>
                <a href="index.php" class="cart-btn-continue">Lanjut Belanja</a>
            </div>

        </div>
    <?php endif; ?>

</main>

<?php 
require_once '../components/ModalConfirmation.php';
ModalConfirmation::render([
    'id' => 'confirmRemoveCart',
    'title' => 'Konfirmasi Hapus',
    'message' => 'Hapus produk dari keranjang?',
    'confirmText' => 'Ya, Hapus',
    'cancelText' => 'Batal',
    'confirmClass' => 'primary'
]);
?>

<script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
<script src="../../public/buyer/cart.js" defer></script>

</body>
</html>
<?php ob_end_flush(); ?>
