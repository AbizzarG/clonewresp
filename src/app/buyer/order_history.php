<?php
session_start();
require_once '../../config/Database.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../../");
    exit();
}

$buyer_id = $_SESSION['user_id'];
$orders = [];
$error = null;

// Filter dan Sort
$statusFilter = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'desc'; // desc atau asc

// Query parameters
$whereClauses = ["o.buyer_id = ?"];
$params = [$buyer_id];

if ($statusFilter !== 'all') {
    $whereClauses[] = "o.status = ?";
    $params[] = $statusFilter;
}

$whereSql = "WHERE " . implode(" AND ", $whereClauses);
$orderSql = $sortBy === 'asc' ? "ORDER BY o.created_at ASC" : "ORDER BY o.created_at DESC";

try {
    $db = new Database();
    $pdo = $db->connect();

    // Query untuk mendapatkan orders dengan store name
    $query = "SELECT 
                o.order_id,
                o.store_id,
                o.total_price,
                o.shipping_address,
                o.status,
                o.reject_reason,
                o.confirmed_at,
                o.delivery_time,
                o.received_at,
                o.created_at,
                s.store_name
              FROM orders o
              INNER JOIN store s ON o.store_id = s.store_id
              $whereSql
              $orderSql";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Untuk setiap order, ambil order items dengan product info
    foreach ($orders as &$order) {
        $itemsQuery = "SELECT 
                        oi.order_item_id,
                        oi.product_id,
                        oi.quantity,
                        oi.price_at_order,
                        oi.subtotal,
                        p.product_name,
                        p.main_image_path
                      FROM order_items oi
                      INNER JOIN product p ON oi.product_id = p.product_id
                      WHERE oi.order_id = ?
                      ORDER BY oi.order_item_id";
        
        $itemsStmt = $pdo->prepare($itemsQuery);
        $itemsStmt->execute([$order['order_id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "Terjadi kesalahan saat memuat riwayat pesanan.";
    error_log("Order History Error: " . $e->getMessage());
}

// Helper function untuk status badge
function getStatusBadge($status) {
    $statuses = [
        'waiting_approval' => ['text' => 'Menunggu Persetujuan', 'class' => 'oh-status-waiting'],
        'approved' => ['text' => 'Disetujui', 'class' => 'oh-status-approved'],
        'rejected' => ['text' => 'Ditolak', 'class' => 'oh-status-rejected'],
        'on_delivery' => ['text' => 'Dalam Pengiriman', 'class' => 'oh-status-delivery'],
        'received' => ['text' => 'Diterima', 'class' => 'oh-status-received']
    ];
    return $statuses[$status] ?? ['text' => $status, 'class' => 'oh-status-default'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Nimonspedia</title>
    <link rel="stylesheet" href="../../resources/public.css">
    <link rel="stylesheet" href="../../resources/buyer/order_history.css">
</head>
<body>
    <?php include '../components/Navbar.php'; ?>
    
    <main class="oh-container">
        <div class="oh-header-section">
            <h1 class="oh-title">Riwayat Pesanan</h1>
            <div class="oh-filter-dropdown">
                <button class="oh-filter-icon-btn" id="filterDropdownBtn" aria-label="Filter" aria-haspopup="true" aria-expanded="false">
                    <i data-lucide="filter"></i>
                </button>
                <div class="oh-filter-dropdown-menu" id="filterDropdownMenu">
                    <form method="GET" action="" class="oh-filter-form">
                        <div class="oh-filter-dropdown-item">
                            <label for="statusFilter">Filter Status</label>
                            <div class="oh-select-wrapper">
                                <select id="statusFilter" name="status" class="oh-select">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                    <option value="waiting_approval" <?php echo $statusFilter === 'waiting_approval' ? 'selected' : ''; ?>>Menunggu Persetujuan</option>
                                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                                    <option value="on_delivery" <?php echo $statusFilter === 'on_delivery' ? 'selected' : ''; ?>>Dalam Pengiriman</option>
                                    <option value="received" <?php echo $statusFilter === 'received' ? 'selected' : ''; ?>>Diterima</option>
                                </select>
                                <i class="oh-select-icon" data-lucide="chevron-down"></i>
                            </div>
                        </div>
                        
                        <div class="oh-filter-dropdown-item">
                            <label for="sortBy">Urutkan</label>
                            <div class="oh-select-wrapper">
                                <select id="sortBy" name="sort" class="oh-select">
                                    <option value="desc" <?php echo $sortBy === 'desc' ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="asc" <?php echo $sortBy === 'asc' ? 'selected' : ''; ?>>Terlama</option>
                                </select>
                                <i class="oh-select-icon" data-lucide="chevron-down"></i>
                            </div>
                        </div>
                        
                        <div class="oh-filter-dropdown-actions">
                            <button type="submit" class="oh-btn-filter">Terapkan</button>
                            <a href="order_history.php" class="oh-btn-reset">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="oh-error">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php else: ?>

            <!-- Orders List -->
            <?php if (empty($orders)): ?>
                <div class="oh-empty-state">
                    <i data-lucide="package" class="oh-empty-icon"></i>
                    <h2>Belum Ada Pesanan</h2>
                    <p>Anda belum memiliki riwayat pesanan. Mulai berbelanja sekarang!</p>
                    <a href="index.php" class="oh-btn-primary">Mulai Belanja</a>
                </div>
            <?php else: ?>
                <div class="oh-orders-list">
                    <?php foreach ($orders as $order): 
                        $statusInfo = getStatusBadge($order['status']);
                        $orderDate = date('d M Y, H:i', strtotime($order['created_at']));
                    ?>
                        <div class="oh-order-card" data-order-id="<?php echo $order['order_id']; ?>">
                            <div class="oh-order-header">
                                <div class="oh-order-info">
                                    <div class="oh-order-id">Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <div class="oh-order-date"><?php echo htmlspecialchars($orderDate); ?></div>
                                    <div class="oh-store-name"><?php echo htmlspecialchars($order['store_name']); ?></div>
                                </div>
                                <span class="oh-status-badge <?php echo $statusInfo['class']; ?>">
                                    <?php echo htmlspecialchars($statusInfo['text']); ?>
                                </span>
                            </div>

                            <div class="oh-order-products">
                                <?php foreach ($order['items'] as $item):
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
                                    <div class="oh-product-item">
                                        <img src="<?php echo $itemImagePath; ?>"
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             class="oh-product-thumb">
                                        <div class="oh-product-info">
                                            <span class="oh-product-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                            <span class="oh-product-qty">Qty: <?php echo $item['quantity']; ?></span>
                                        </div>
                                        <div class="oh-product-price">
                                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="oh-order-footer">
                                <div class="oh-order-total">
                                    <span class="oh-total-label">Total:</span>
                                    <span class="oh-total-price">Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></span>
                                </div>

                                <?php if ($order['status'] === 'rejected'): ?>
                                    <div class="oh-refund-info">
                                        <i data-lucide="rotate-ccw"></i>
                                        <span>Dana telah dikembalikan ke saldo Anda</span>
                                    </div>
                                <?php endif; ?>

                                <div class="oh-order-actions">
                                    <?php if ($order['status'] === 'on_delivery'): ?>
                                        <button class="oh-btn-confirm-received" data-order-id="<?php echo $order['order_id']; ?>">
                                            <i data-lucide="package-check"></i>
                                            Terima Pesanan
                                        </button>
                                    <?php endif; ?>

                                    <button class="oh-btn-detail" data-order-id="<?php echo $order['order_id']; ?>">
                                        Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <!-- Modal Order Detail -->
    <?php include '../components/ModalOrderDetail.php'; ?>

    <script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
    <script src="../../public/buyer/orderHistory.js"></script>
</body>
</html>