<?php
ob_start(); // Prevent "headers already sent" errors
session_start();
require_once '../../config/Database.php';
require_once '../components/Toast.php';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../");
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['buyer', 'guest'])) {
    header("Location: ../../");
    exit();
}

$products = [];
$categories = [];
$totalProducts = 0;
$totalPages = 0;
$pageError = null;
$items_per_page = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $items_per_page;

$filters = [
    'search'     => $_GET['search'] ?? '',
    'min_price'  => $_GET['min_price'] ?? null,
    'max_price'  => $_GET['max_price'] ?? null,
    'categories' => $_GET['category'] ?? [],
];

$queryParams = $_GET;
unset($queryParams['page']);
$baseQueryString = http_build_query($queryParams);

try {
    $db = new Database();
    $pdo = $db->connect();

    $catStmt = $pdo->query("SELECT category_id, name FROM category ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $baseSql = "FROM product p JOIN store s ON p.store_id = s.store_id";
    $whereClauses = ["p.deleted_at IS NULL"];
    $params = [];

    if (!empty($filters['categories']) && is_array($filters['categories'])) {
        $baseSql .= " JOIN category_item ci ON p.product_id = ci.product_id";
        $inQuery = implode(',', array_fill(0, count($filters['categories']), '?'));
        $whereClauses[] = "ci.category_id IN ($inQuery)";
        foreach ($filters['categories'] as $cat_id) {
            $params[] = (int)$cat_id;
        }
    }

    if (!empty($filters['search'])) {
        $whereClauses[] = "p.product_name ILIKE ?";
        $params[] = "%" . $filters['search'] . "%";
    }

    if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
        $whereClauses[] = "p.price >= ?";
        $params[] = $filters['min_price'];
    }
    if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
        $whereClauses[] = "p.price <= ?";
        $params[] = $filters['max_price'];
    }

    $sqlWhere = " WHERE " . implode(" AND ", $whereClauses);

    $countSql = "SELECT COUNT(DISTINCT p.product_id) as total " . $baseSql . $sqlWhere;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalProducts = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = $totalProducts > 0 ? ceil($totalProducts / $items_per_page) : 0;

    $dataParams = $params;
    $dataParams[] = $items_per_page;
    $dataParams[] = $offset;

    $dataSql = "SELECT DISTINCT p.product_id, p.product_name, p.price, p.main_image_path, p.stock, s.store_name, p.created_at "
             . $baseSql
             . $sqlWhere
             . " ORDER BY p.created_at DESC"
             . " LIMIT ? OFFSET ?";

    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($dataParams);
    $products = $dataStmt->fetchAll(PDO::FETCH_ASSOC);


} catch (Exception $e) {
    error_log("Product Discovery Error: " . $e->getMessage());
    $pageError = "Terjadi kesalahan saat memuat data. Silakan coba lagi nanti.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Discovery - Nimonspedia</title>
    <link rel="stylesheet" href="../../resources/buyer/product_discovery.css">
</head>
<body>
    <?php Toast::render(); ?>
    <?php include '../components/Navbar.php';?>
    <main class="pd-page-container">

        <div class="pd-header-section">
            <h3 class="pd-title">For you</h3>
            <div class="pd-filter-dropdown">
                <button class="pd-filter-icon-btn" id="filterDropdownBtn" aria-label="Filter">
                    <i data-lucide="filter"></i>
                </button>
                <div class="pd-filter-dropdown-menu" id="filterDropdownMenu">
                    <form method="GET" action="" class="pd-filter-form">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                        
                        <div class="pd-filter-dropdown-item">
                            <label for="min_price">Harga Min</label>
                            <input type="number" name="min_price" id="min_price" placeholder="Rp Min"
                                   value="<?php echo htmlspecialchars($filters['min_price'] ?? ''); ?>">
                        </div>
                        
                        <div class="pd-filter-dropdown-item">
                            <label for="max_price">Harga Max</label>
                            <input type="number" name="max_price" id="max_price" placeholder="Rp Max"
                                   value="<?php echo htmlspecialchars($filters['max_price'] ?? ''); ?>">
                        </div>
                        
                        <div class="pd-filter-dropdown-item">
                            <label for="category-select">Kategori</label>
                            <select class="category-select" name="category[]" id="category-select" multiple size="3">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                            <?php echo in_array($category['category_id'], $filters['categories']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="pd-filter-dropdown-actions">
                            <button type="submit" class="pd-btn-filter">Terapkan</button>
                            <a href="/app/buyer/index.php" class="pd-btn-reset">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="pd-product-grid" id="productGrid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product):
                    if (!empty($product['main_image_path'])) {
                        if (strpos($product['main_image_path'], 'http://') === 0 || strpos($product['main_image_path'], 'https://') === 0) {
                            $imagePath = htmlspecialchars($product['main_image_path']);
                        } else {
                            $imagePath = '../../' . htmlspecialchars($product['main_image_path']);
                        }
                    } else {
                        $placeholderText = urlencode(str_replace(' ', '+', $product['product_name']));
                        $imagePath = 'https://placehold.co/600x400/f0f2f4/333333?text=' . $placeholderText;
                    }
                ?>
                    <div class="pd-product-card">
                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="pd-card-link">
                            <div class="pd-card-image">
                                <img src="<?php echo $imagePath; ?>"
                                    alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            </div>

                            <div class="pd-card-info">
                                <h3 class="pd-product-name">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </h3>
                                <div class='pd-product-border'>
                                    <i data-lucide='banknote' id='banknote'></i>
                                    <p class="pd-product-price">
                                        Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                                    </p>
                                </div>
                                <p class="pd-store-name">
                                    <?php echo htmlspecialchars($product['store_name']); ?>
                                </p>
                            </div>
                        </a>

                        <div class="pd-card-actions">
                            <?php if ($_SESSION['role'] === 'buyer'): ?>
                                <?php if ($product['stock'] > 0): ?>
                                    <button class="pd-btn-add-to-cart" data-product-id="<?php echo $product['product_id']; ?>">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <span class="pd-stock-out-badge">Stok Habis</span>
                                <?php endif; ?>
                            <?php else:?>
                                <span class="pd-stock-out-badge">Login untuk Beli</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Card Skeleton -->
            <div class="pd-skeleton-container" id="skeletonContainer" style="<?php echo empty($products) ? 'display: contents;' : 'display: none;'; ?>">
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <div class="pd-product-card pd-skeleton-card">
                        <div class="pd-card-skeleton">
                            <div class="pd-skeleton-image"></div>
                            <div class="pd-card-info">
                                <div class="pd-skeleton-line pd-skeleton-title"></div>
                                <div class="pd-skeleton-line pd-skeleton-price"></div>
                                <div class="pd-skeleton-line pd-skeleton-store"></div>
                            </div>
                            <div class="pd-card-actions">
                                <div class="pd-skeleton-button"></div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <?php if (empty($products) && !isset($pageError)): ?>
            <?php if (!empty($filters['search']) || !empty($filters['min_price']) || !empty($filters['max_price']) || !empty($filters['categories'])): ?>
                <p class="pd-empty-state">Produk tidak ditemukan. Coba reset filter Anda.</p>
            <?php endif; ?>
        <?php elseif (isset($pageError)): ?>
            <p class="pd-empty-state"><?php echo htmlspecialchars($pageError); ?></p>
        <?php endif; ?>


        <nav class="pd-pagination-container" aria-label="Navigasi Halaman">
            <?php if ($totalPages > 1): ?>

                <?php if ($page > 1): ?>
                    <a href="?<?php echo $baseQueryString; ?>&page=<?php echo $page - 1; ?>" class="pd-page-link">&laquo; Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?php echo $baseQueryString; ?>&page=<?php echo $i; ?>"
                        class="pd-page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo $baseQueryString; ?>&page=<?php echo $page + 1; ?>" class="pd-page-link">Next &raquo;</a>
                <?php endif; ?>

            <?php endif; ?>
        </nav>

    </main>
    <script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
    <script src="../../public/buyer/discovery.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>