<?php
session_start();
require_once '../../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../../");
    exit();
}

$storeId = null;
$products = [];
$categories = [];
$error = '';

$searchTerm = $_GET['search'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$sortBy = $_GET['sort'] ?? 'product_name';
$sortOrder = $_GET['order'] ?? 'ASC';

try {
    $db = new Database();
    $pdo = $db->connect();

    $stmt = $pdo->prepare("SELECT store_id FROM store WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($store) {
        $storeId = $store['store_id'];

        $catStmt = $pdo->query("SELECT category_id, name FROM category ORDER BY name");
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT p.product_id, p.product_name, p.price, p.stock, p.main_image_path, c.name as category_name
                FROM product p
                LEFT JOIN category_item ci ON p.product_id = ci.product_id
                LEFT JOIN category c ON ci.category_id = c.category_id
                WHERE p.store_id = :store_id AND p.deleted_at IS NULL";

        $params = [':store_id' => $storeId];

        if (!empty($searchTerm)) {
            $sql .= " AND p.product_name ILIKE :search";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        if (!empty($categoryId)) {
            $sql .= " AND ci.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        $validSortColumns = ['product_name', 'price', 'stock'];
        $validSortOrders = ['ASC', 'DESC'];
        if (in_array($sortBy, $validSortColumns) && in_array($sortOrder, $validSortOrders)) {
            $sql .= " ORDER BY p." . $sortBy . " " . $sortOrder;
        }

        $prodStmt = $pdo->prepare($sql);
        $prodStmt->execute($params);
        $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $error = "No store found for this seller.";
    }
} catch (Exception $e) {
    $error = "Error fetching product data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="../../resources/seller/product_management.css">
</head>
<body>
    <?php include '../components/Navbar.php'; ?>

    <div class="container">
        <div class="pm-header-section">
            <h1 class="pm-title">Product Management</h1>
            <div class="pm-header-actions">
                <div class="pm-filter-dropdown">
                    <button class="pm-filter-icon-btn" id="filterDropdownBtn" aria-label="Filter" aria-haspopup="true" aria-expanded="false">
                        <i data-lucide="filter"></i>
                    </button>
                    <div class="pm-filter-dropdown-menu" id="filterDropdownMenu">
                        <form method="GET" action="" class="pm-filter-form">
                            <?php if (!empty($searchTerm)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                            <?php endif; ?>
                            <div class="pm-filter-dropdown-item">
                                <label for="categoryFilter">Kategori</label>
                                <div class="pm-select-wrapper">
                                    <select id="categoryFilter" name="category_id" class="pm-select">
                                        <option value="">Semua Kategori</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" <?php echo ($categoryId == $category['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="pm-select-icon" data-lucide="chevron-down"></i>
                                </div>
                            </div>
                            
                            <div class="pm-filter-dropdown-item">
                                <label for="sortBy">Urutkan Berdasarkan</label>
                                <div class="pm-select-wrapper">
                                    <select id="sortBy" name="sort" class="pm-select">
                                        <option value="product_name" <?php echo ($sortBy == 'product_name') ? 'selected' : ''; ?>>Nama</option>
                                        <option value="price" <?php echo ($sortBy == 'price') ? 'selected' : ''; ?>>Harga</option>
                                        <option value="stock" <?php echo ($sortBy == 'stock') ? 'selected' : ''; ?>>Stok</option>
                                    </select>
                                    <i class="pm-select-icon" data-lucide="chevron-down"></i>
                                </div>
                            </div>

                            <div class="pm-filter-dropdown-item">
                                <label for="sortOrder">Urutan</label>
                                <div class="pm-select-wrapper">
                                    <select id="sortOrder" name="order" class="pm-select">
                                        <option value="ASC" <?php echo ($sortOrder == 'ASC') ? 'selected' : ''; ?>>Naik</option>
                                        <option value="DESC" <?php echo ($sortOrder == 'DESC') ? 'selected' : ''; ?>>Turun</option>
                                    </select>
                                    <i class="pm-select-icon" data-lucide="chevron-down"></i>
                                </div>
                            </div>
                            
                            <div class="pm-filter-dropdown-actions">
                                <button type="submit" class="pm-btn-filter">Terapkan</button>
                                <a href="product_management.php" class="pm-btn-reset">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="export-buttons">
                    <button class="btn-export" onclick="exportProducts('csv')">Export CSV</button>
                    <button class="btn-export" onclick="exportProducts('excel')">Export Excel</button>
                </div>
                <a href="add_product.php" class="btn">Tambah Produk Baru</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif (empty($products)): ?>
            <div class="empty-state">
                <h2>No products found.</h2>
                <?php if (!empty($searchTerm)): ?>
                    <p>Tidak ada produk yang sesuai dengan pencarian "<?php echo htmlspecialchars($searchTerm); ?>".</p>
                    <a href="product_management.php" class="btn">Lihat Semua Produk</a>
                <?php else: ?>
                    <p>You haven't added any products yet. Let's add your first one!</p>
                    <a href="add_product.php" class="btn">Tambah Produk Pertama</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                        <tr>
                            <td>
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <?php
                                if ($product['stock'] == 0) {
                                    echo 'Out of Stock';
                                } elseif ($product['stock'] < 10) {
                                    echo 'Low Stock';
                                } else {
                                    echo 'In Stock';
                                }
                                ?>
                            </td>
                            <td class="actions">
                                <a href="edit_product.php?id=<?php echo $product['product_id']; ?>">Edit</a>
                                <button class="btn-delete" data-product-id="<?php echo $product['product_id']; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
    <script src="../../public/seller/delete_product.js"></script>
    <script src="../../public/seller/product_management.js"></script>
</body>
</html>
