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

// Auth Guard - Memastikan hanya 'buyer' atau 'guest' yang bisa mengakses
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['buyer', 'guest'])) {
    header("Location: ../../");
    exit();
}


// Get product_id from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$product = null;
$categories = [];
$error = null;

// Fetch product details
try {
    $db = new Database();
    $pdo = $db->connect();

    // Query product with store information
    $query = "SELECT
                p.product_id,
                p.product_name,
                p.description,
                p.price,
                p.stock,
                p.main_image_path,
                p.created_at,
                s.store_id,
                s.store_name,
                s.store_description
              FROM product p
              INNER JOIN store s ON p.store_id = s.store_id
              WHERE p.product_id = ? AND p.deleted_at IS NULL";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // If product not found, set error
    if (!$product) {
        $error = "Product not found or has been deleted.";
    } else {
        // Fetch categories for this product
        $catQuery = "SELECT c.category_id, c.name
                     FROM category c
                     INNER JOIN category_item ci ON c.category_id = ci.category_id
                     WHERE ci.product_id = ?
                     ORDER BY c.name";
        $catStmt = $pdo->prepare($catQuery);
        $catStmt->execute([$product_id]);
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Product Detail Error: " . $e->getMessage());
}

// Handle both local path and URL for product image
if ($product && !empty($product['main_image_path'])) {
    if (strpos($product['main_image_path'], 'http://') === 0 || strpos($product['main_image_path'], 'https://') === 0) {
        $productImagePath = htmlspecialchars($product['main_image_path']);
    } else {
        $productImagePath = '../../' . htmlspecialchars($product['main_image_path']);
    }
} else if ($product) {
    $placeholderText = urlencode(str_replace(' ', '+', $product['product_name']));
    $productImagePath = 'https://placehold.co/600x400/f0f2f4/333333?text=' . $placeholderText;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['product_name']) . ' - Nimonspedia' : 'Product Not Found'; ?></title>
    <link rel="stylesheet" href="../../resources/public.css">
    <link rel="stylesheet" href="../../resources/buyer/product_detail.css">
</head>
<body>
<?php Toast::render(); ?>
<?php include '../components/Navbar.php'; ?>

<main class="detail-container">
    <?php if ($error): ?>
        <!-- Error State -->
        <div class="detail-error">
            <h2>Oops!</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="index.php" class="detail-btn-back">← Back to Product Discovery</a>
        </div>
    <?php else: ?>
        <!-- Product Detail Content -->
        <div class="detail-content">

            <!-- Left Column: Product Image -->
            <div class="detail-image-section">
                <img src="<?php echo $productImagePath; ?>"
                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                     class="detail-main-image">
            </div>

            <!-- Right Column: Product Info -->
            <div class="detail-info-section">

                <!-- Product Name -->
                <h1 class="detail-product-name"><?php echo htmlspecialchars($product['product_name']); ?></h1>

                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                    <div class="detail-categories">
                        <?php foreach ($categories as $cat): ?>
                            <span class="detail-category-badge"><?php echo htmlspecialchars($cat['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="detail-price-section">
                    <span class="detail-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                </div>

                <!-- Stock Info -->
                <div class="detail-stock-info">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="detail-stock-available">Stok: <?php echo $product['stock']; ?> tersedia</span>
                    <?php else: ?>
                        <span class="detail-stock-empty">Stok Habis</span>
                    <?php endif; ?>
                </div>

                <!-- Store Info -->
                <div class="detail-store-info">
                    <h3>Toko</h3>
                    <div class="detail-store-card">
                        <a href="store_detail.php?id=<?php echo $product['store_id']; ?>" style="text-decoration: none; color: inherit;">
                            <h4><?php echo htmlspecialchars($product['store_name']); ?></h4>
                        </a>
                        <?php if ($product['store_description']): ?>
                            <p><?php echo $product['store_description']; ?></p>
                        <?php endif; ?>
                        <a href="store_detail.php?id=<?php echo $product['store_id']; ?>" class="detail-view-store-btn" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                            Lihat Toko
                        </a>
                    </div>
                </div>

                <!-- Add to Cart Button / Guest Message -->
                <div class="detail-actions">
                    <?php if ($_SESSION['role'] === 'buyer'): ?>
                        <?php if ($product['stock'] > 0): ?>
                            <div class="detail-quantity-selector">
                                <label for="quantity">Jumlah:</label>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            </div>
                            <button class="detail-btn-add-cart"
                                    data-product-id="<?php echo $product['product_id']; ?>"
                                    data-max-stock="<?php echo $product['stock']; ?>">
                                <i class='btn-icon' data-lucide="shopping-cart"></i> Tambah ke Keranjang
                            </button>
                        <?php else: ?>
                            <button class="detail-btn-disabled" disabled>Stok Habis</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="detail-guest-message">
                            <p>Silakan <a href="../../index.php">login</a> untuk membeli produk ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Back to Discovery -->
                <a href="index.php" class="detail-link-back">
                    <i class='btn-icon' data-lucide="arrow-left"></i> 
                    Kembali ke Halaman Utama
                </a>
            </div>

        </div>

        <!-- Product Description Section (Full Width) -->
        <div class="detail-description-section">
            <h2>Deskripsi Produk</h2>
            <div class="detail-description-content">
                <?php echo $product['description']; // Rich HTML content from database ?>
            </div>
        </div>

    <?php endif; ?>
</main>
<script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
<script src="../../public/buyer/product_detail.js" defer></script>

</body>
</html>
<?php ob_end_flush(); ?>
