<?php
session_start();
require_once '../../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../../");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../");
    exit();
}

$userName = $_SESSION['name'];
$userEmail = $_SESSION['email'];

$storeId = null;
$storeName = 'No Store Found';
$storeLogoPath = null;
$storeDescription = '';
$storeBalance = 0;
$totalProducts = 0;
$pendingOrders = 0;
$lowStockProducts = 0;
$error = '';
$success = '';

try {
    $db = new Database();
    $pdo = $db->connect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_store_info'])) {
        $stmt = $pdo->prepare("SELECT store_id, store_logo_path FROM store WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentStore = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($currentStore) {
            $storeId = $currentStore['store_id'];
            $newStoreName = trim($_POST['store_name'] ?? '');
            $newStoreDescription = $_POST['store_description'] ?? '';
            $newLogoPath = $currentStore['store_logo_path'];

            if (empty($newStoreName) || strlen($newStoreName) > 100) {
                $error = 'Store name is required and must be less than 100 characters.';
            }

            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['store_logo'];
                if ($file['size'] > 2 * 1024 * 1024) { // 2MB
                    $error = 'Logo file size must be less than 2MB.';
                } else {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($file['type'], $allowedTypes)) {
                        $error = 'Invalid logo file type. Only JPG, PNG, and WEBP are allowed.';
                    } else {
                        $uploadDir = '../../upload/logos/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = 'logo_' . $storeId . '_' . time() . '.' . $fileExtension;
                        $filePath = $uploadDir . $fileName;

                        if (move_uploaded_file($file['tmp_name'], $filePath)) {
                            if ($newLogoPath && file_exists('../../' . $newLogoPath)) {
                                unlink('../../' . $newLogoPath);
                            }
                            $newLogoPath = 'upload/logos/' . $fileName;
                        } else {
                            $error = 'Failed to upload new logo.';
                        }
                    }
                }
            }

            if (empty($error)) {
                $updateStmt = $pdo->prepare("UPDATE store SET store_name = ?, store_description = ?, store_logo_path = ? WHERE store_id = ?");
                $updateStmt->execute([$newStoreName, $newStoreDescription, $newLogoPath, $storeId]);
                $success = "Store information updated successfully!";
            }
        } else {
            $error = "Could not find your store to update.";
        }
    }


    $stmt = $pdo->prepare("SELECT store_id, store_name, store_description, balance, store_logo_path FROM store WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($store) {
        $storeId = $store['store_id'];
        $storeName = $store['store_name'];
        $storeDescription = $store['store_description'] ?? 'No description provided.';
        $storeLogoPath = $store['store_logo_path'];
        $storeBalance = $store['balance'];

        $stmt = $pdo->prepare("SELECT COUNT(product_id) FROM product WHERE store_id = ? AND deleted_at IS NULL");
        $stmt->execute([$storeId]);
        $totalProducts = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(order_id) FROM orders WHERE store_id = ? AND status = 'waiting_approval'");
        $stmt->execute([$storeId]);
        $pendingOrders = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(product_id) FROM product WHERE store_id = ? AND stock < 10 AND deleted_at IS NULL");
        $stmt->execute([$storeId]);
        $lowStockProducts = $stmt->fetchColumn();

    } else {
        $error = "No store associated with this seller account.";
    }
} catch (Exception $e) {
    $error = 'Error loading dashboard data. Please try again later.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <link rel="stylesheet" href="../../resources/seller/dashboard.css">
</head>
<body>
    <?php include '../components/Navbar.php';?>

    <div class="dashboard-container">
        <h1>Seller Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($userName); ?>!</p>

        <?php if (!empty($error)): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p class="success-msg"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if ($storeId): ?>
            <!-- Header Info Display -->
            <div id="displayStoreInfo" class="header-info">
                <?php if ($storeLogoPath): ?>
                    <img src="../../<?php echo htmlspecialchars($storeLogoPath); ?>" alt="Store Logo" class="store-logo">
                <?php endif; ?>
                <h2><?php echo htmlspecialchars($storeName); ?></h2>
                <p><strong>Total Revenue:</strong> Rp <?php echo number_format($storeBalance, 0, ',', '.'); ?></p>
                <div>
                    <strong>Description:</strong>
                    <div><?php echo $storeDescription; ?></div>
                </div>
                <button id="editStoreBtn" class="action-button" style="margin-top: 15px;">Edit Store Info</button>
            </div>

            <!-- Header Info Edit Form -->
            <form id="editStoreForm" method="POST" enctype="multipart/form-data">
                <h3>Edit Store Information</h3>
                <input type="hidden" name="update_store_info" value="1">
                <div class="form-group">
                    <label for="store_name">Store Name</label>
                    <input type="text" id="store_name" name="store_name" value="<?php echo htmlspecialchars($storeName); ?>" required>
                </div>
                <div class="form-group">
                    <label for="store_description">Store Description</label>
                    <div id="editor"><?php echo $storeDescription; ?></div>
                    <input type="hidden" name="store_description" id="store_description_hidden">
                </div>
                <div class="form-group">
                    <label for="store_logo">Change Logo (Optional, max 2MB)</label>
                    <input type="file" id="store_logo" name="store_logo" accept="image/jpeg, image/png, image/webp">
                    <img id="logo-preview" src="#" alt="New Logo Preview"/>
                </div>
                <button type="submit" class="action-button">Save Changes</button>
                <button type="button" id="cancelEditBtn" class="action-button" style="background-color: #6c757d;">Cancel</button>
            </form>

            <!-- Quick Stats Cards -->
            <div class="quick-stats">
                <h3>Quick Stats</h3>
                <div class="stats-cards">
                    <div class="card">
                        <h4>Total Unique Products</h4>
                        <p><?php echo $totalProducts; ?></p>
                    </div>
                    <div class="card">
                        <h4>Pending Orders</h4>
                        <p><?php echo $pendingOrders; ?></p>
                    </div>
                    <div class="card">
                        <h4>Products with Low Stock (&lt;10)</h4>
                        <p><?php echo $lowStockProducts; ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Buttons -->
            <div class="quick-actions">
                <a href="product_management.php" class="action-button">Kelola Produk</a>
                <a href="order_management.php" class="action-button">Lihat Orders</a>
                <a href="add_product.php" class="action-button">Tambah Produk Baru</a>
            </div>
        <?php else: ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script src="../../public/seller/dashboard.js"></script>
</body>
</html>
