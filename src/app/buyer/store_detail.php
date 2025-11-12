<?php
ob_start();
session_start();
require_once '../../config/Database.php';
require_once '../components/Toast.php';

$store_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$store_id) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$pdo = $db->connect();

$storeQuery = "SELECT store_id, store_name, store_description, store_logo_path
               FROM store
               WHERE store_id = ?";
$storeStmt = $pdo->prepare($storeQuery);
$storeStmt->execute([$store_id]);
$store = $storeStmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header("Location: index.php?error=store_not_found");
    exit();
}

$itemsPerPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 12;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : PHP_INT_MAX;

$conditions = ["p.store_id = :store_id", "p.deleted_at IS NULL"];
$params = ['store_id' => $store_id];

if ($searchQuery) {
    $conditions[] = "p.product_name ILIKE :search";
    $params['search'] = "%{$searchQuery}%";
}

if ($categoryFilter) {
    $conditions[] = "ci.category_id = :category";
    $params['category'] = $categoryFilter;
}

if ($minPrice > 0) {
    $conditions[] = "p.price >= :min_price";
    $params['min_price'] = $minPrice;
}

if ($maxPrice < PHP_INT_MAX) {
    $conditions[] = "p.price <= :max_price";
    $params['max_price'] = $maxPrice;
}

$whereClause = implode(' AND ', $conditions);

$countSql = "SELECT COUNT(DISTINCT p.product_id)
             FROM product p
             LEFT JOIN category_item ci ON p.product_id = ci.product_id
             WHERE {$whereClause}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $itemsPerPage);

$dataSql = "SELECT DISTINCT p.product_id, p.product_name, p.price, p.main_image_path, p.stock, p.created_at
            FROM product p
            LEFT JOIN category_item ci ON p.product_id = ci.product_id
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset";

$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $value) {
    $dataStmt->bindValue(":{$key}", $value);
}
$dataStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$products = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$catStmt = $pdo->query("SELECT category_id, name FROM category ORDER BY name");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($store['store_name']); ?> - Nimonspedia</title>
    <link rel="stylesheet" href="../../resources/public.css">
    <link rel="stylesheet" href="../../resources/buyer/product_discovery.css">
    <link rel="stylesheet" href="../../resources/buyer/store_detail.css">
</head>
<body>
<?php Toast::render(); ?>
<?php include '../components/Navbar.php'; ?>

<main class="pd-page-container">
    <div class="store-header">
        <div class="store-header-content">
            <?php if ($store['store_logo_path']): ?>
                <img src="../../<?php echo htmlspecialchars($store['store_logo_path']); ?>"
                     alt="<?php echo htmlspecialchars($store['store_name']); ?>"
                     class="store-logo">
            <?php else: ?>
                <div class="store-logo" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="store" style="width: 48px; height: 48px; color: #999;"></i>
                </div>
            <?php endif; ?>

            <div class="store-info">
                <h1><?php echo htmlspecialchars($store['store_name']); ?></h1>
                <?php if ($store['store_description']): ?>
                    <p><?php echo $store['store_description']; ?></p>
                <?php endif; ?>
                <div class="store-stats">
                    <span><?php echo $totalProducts; ?> produk tersedia</span>
                </div>
            </div>
        </div>
    </div>

    <div class="pd-header-section">
        <h2 class="pd-title">Semua Produk</h2>
        <div class="pd-filter-dropdown">
            <button type="button" class="pd-filter-icon-btn" id="filterToggleBtn" aria-label="Filter">
                <i data-lucide="filter"></i>
            </button>
            <div class="pd-filter-dropdown-menu" id="filterDropdown">
                <form method="GET" action="store_detail.php" class="pd-filter-form">
                    <input type="hidden" name="id" value="<?php echo $store_id; ?>">
                    <?php if ($searchQuery): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <?php endif; ?>
                    
                    <div class="pd-filter-dropdown-item">
                        <label for="category">Kategori</label>
                        <div class="pd-select-wrapper">
                            <select id="category" name="category">
                                <option value="0">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"
                                            <?php echo ($categoryFilter == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="pd-select-icon" data-lucide="chevron-down"></i>
                        </div>
                    </div>

                    <div class="pd-filter-dropdown-item">
                        <label for="min_price">Harga Minimum</label>
                        <input type="number" id="min_price" name="min_price" 
                               placeholder="0"
                               value="<?php echo ($minPrice > 0) ? $minPrice : ''; ?>">
                    </div>

                    <div class="pd-filter-dropdown-item">
                        <label for="max_price">Harga Maksimum</label>
                        <input type="number" id="max_price" name="max_price" 
                               placeholder="0"
                               value="<?php echo ($maxPrice < PHP_INT_MAX) ? $maxPrice : ''; ?>">
                    </div>

                    <div class="pd-filter-dropdown-actions">
                        <button type="submit" class="pd-btn-filter">Terapkan</button>
                        <a href="store_detail.php?id=<?php echo $store_id; ?>" class="pd-btn-reset">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="pd-product-grid">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product):
                if (!empty($product['main_image_path'])) {
                    if (strpos($product['main_image_path'], 'http://') === 0 || strpos($product['main_image_path'], 'https://') === 0) {
                        $productImagePath = htmlspecialchars($product['main_image_path']);
                    } else {
                        $productImagePath = '../../' . htmlspecialchars($product['main_image_path']);
                    }
                } else {
                    $placeholderText = urlencode(str_replace(' ', '+', $product['product_name']));
                    $productImagePath = 'https://placehold.co/600x400/f0f2f4/333333?text=' . $placeholderText;
                }
            ?>
                <div class="pd-product-card">
                    <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="pd-card-link">
                        <div class="pd-card-image">
                            <img src="<?php echo $productImagePath; ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <?php if ($product['stock'] == 0): ?>
                                <div class="pd-stock-out-badge">Stok Habis</div>
                            <?php endif; ?>
                        </div>

                        <div class="pd-card-info">
                            <h3 class="pd-product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="pd-product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                            <p class="pd-product-stock">Stok: <?php echo $product['stock']; ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="pd-empty-state">
                <i data-lucide="package" class="pd-empty-icon"></i>
                <h3>Tidak ada produk ditemukan</h3>
                <p>Coba ubah filter atau kata kunci pencarian Anda.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pd-pagination">
            <span class="pd-pagination-info">
                Menampilkan <?php echo $offset + 1; ?> sampai <?php echo min($offset + $itemsPerPage, $totalProducts); ?> dari <?php echo $totalProducts; ?> produk
            </span>

            <?php
            $queryParams = ['id' => $store_id];
            if ($searchQuery) $queryParams['search'] = $searchQuery;
            if ($categoryFilter) $queryParams['category'] = $categoryFilter;
            if ($minPrice > 0) $queryParams['min_price'] = $minPrice;
            if ($maxPrice < PHP_INT_MAX) $queryParams['max_price'] = $maxPrice;
            $queryString = http_build_query($queryParams);
            ?>
            <div class="pd-pagination-controls">
                <?php if ($currentPage > 1): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $currentPage - 1; ?>&perPage=<?php echo $itemsPerPage; ?>"
                       class="pd-pagination-btn">« Prev</a>
                <?php endif; ?>

                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>&perPage=<?php echo $itemsPerPage; ?>"
                       class="pd-pagination-btn <?php echo ($i == $currentPage) ? 'pd-pagination-active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $currentPage + 1; ?>&perPage=<?php echo $itemsPerPage; ?>"
                       class="pd-pagination-btn">Next »</a>
                <?php endif; ?>
            </div>

            <select onchange="window.location.href='?<?php echo $queryString; ?>&page=1&perPage='+this.value" class="pd-per-page-select">
                <option value="4" <?php echo ($itemsPerPage == 4) ? 'selected' : ''; ?>>4 item</option>
                <option value="8" <?php echo ($itemsPerPage == 8) ? 'selected' : ''; ?>>8 item</option>
                <option value="12" <?php echo ($itemsPerPage == 12) ? 'selected' : ''; ?>>12 item</option>
                <option value="20" <?php echo ($itemsPerPage == 20) ? 'selected' : ''; ?>>20 item</option>
            </select>
        </div>
    <?php endif; ?>

</main>

<script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
<script src="../../public/buyer/storeDetail.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
