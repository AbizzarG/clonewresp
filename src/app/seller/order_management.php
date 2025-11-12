<?php
session_start();
require_once '../../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../../");
    exit();
}

$error = '';
$orders = [];
$storeId = null;
$statusFilter = $_GET['status'] ?? 'waiting_approval';
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 4;
$totalItems = 0;
$totalPages = 0;

if ($page < 1) $page = 1;

try {
    $db = new Database();
    $pdo = $db->connect();
    $stmt = $pdo->prepare("SELECT store_id FROM store WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$store) {
        throw new Exception("No store found for this seller.");
    }
    $storeId = $store['store_id'];
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($storeId) {
    $offset = ($page - 1) * $itemsPerPage;

    try {
        $baseSql = "FROM orders o JOIN users u ON o.buyer_id = u.user_id WHERE o.store_id = :store_id";
        $params = [':store_id' => $storeId];

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $baseSql .= " AND o.status = :status";
            $params[':status'] = $statusFilter;
        }
        if (!empty($searchTerm)) {
            $baseSql .= " AND (CAST(o.order_id AS TEXT) ILIKE :search OR u.name ILIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        // Count total for pagination
        $countStmt = $pdo->prepare("SELECT COUNT(o.order_id) " . $baseSql);
        $countStmt->execute($params);
        $totalItems = $countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Fetch orders for the current page
        $orderStmt = $pdo->prepare(
            "SELECT o.order_id, o.created_at, o.total_price, o.status, u.name as buyer_name " .
            $baseSql . " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset"
        );
        // Bind parameters dynamically
        foreach ($params as $key => &$val) {
            $orderStmt->bindParam($key, $val);
        }
        $orderStmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
        $orderStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $orderStmt->execute();
        $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = "Error fetching orders: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="../../resources/seller/order_management.css">
</head>
<body>
    <?php include '../components/Navbar.php'; ?>

    <div class="container">
        <h1>Order Management</h1>

        <!-- Filters -->
        <div class="filters">
            <div class="status-tabs">
                <?php $statuses = ['waiting_approval', 'approved', 'on_delivery', 'received', 'rejected', 'all']; ?>
                <?php foreach ($statuses as $s): ?>
                    <a href="?status=<?php echo $s; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="<?php echo ($statusFilter == $s) ? 'active' : ''; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $s)); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="export-buttons">
                <button class="btn-export" onclick="exportOrders('csv')">Export CSV</button>
                <button class="btn-export" onclick="exportOrders('excel')">Export Excel</button>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif (empty($orders)): ?>
            <div class="empty-state">
                <h2>No orders found for this status.</h2>
            </div>
        <?php else: ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Buyer</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr id="order-row-<?php echo $order['order_id']; ?>">
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                            <td>Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($order['status']); ?></span></td>
                            <td>
                                <?php if ($order['status'] == 'waiting_approval'): ?>
                                    <button class="action-btn btn-approve" data-order-id="<?php echo $order['order_id']; ?>">Approve</button>
                                    <button class="action-btn btn-reject" data-order-id="<?php echo $order['order_id']; ?>">Reject</button>
                                <?php elseif ($order['status'] == 'approved'): ?>
                                    <button class="action-btn btn-delivery" data-order-id="<?php echo $order['order_id']; ?>">Set Delivery</button>
                                <?php endif; ?>
                                <button class="action-btn btn-detail" data-order-id="<?php echo $order['order_id']; ?>">Details</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Menampilkan <?php echo $offset + 1; ?> sampai <?php echo min($offset + $itemsPerPage, $totalItems); ?> dari <?php echo $totalItems; ?> pesanan
                    </div>
                    <div class="pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">← Previous</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">← Previous</span>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1) {
                            echo '<a href="?status=' . urlencode($statusFilter) . '&search=' . urlencode($searchTerm) . '&page=1" class="pagination-btn">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="pagination-btn disabled">...</span>';
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++) {
                            if ($i == $page) {
                                echo '<span class="pagination-btn active">' . $i . '</span>';
                            } else {
                                echo '<a href="?status=' . urlencode($statusFilter) . '&search=' . urlencode($searchTerm) . '&page=' . $i . '" class="pagination-btn">' . $i . '</a>';
                            }
                        }

                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="pagination-btn disabled">...</span>';
                            }
                            echo '<a href="?status=' . urlencode($statusFilter) . '&search=' . urlencode($searchTerm) . '&page=' . $totalPages . '" class="pagination-btn">' . $totalPages . '</a>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">Next →</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">Next →</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reject Order</h2>
                <span class="close">&times;</span>
            </div>
            <form id="rejectForm">
                <input type="hidden" id="rejectOrderId" name="order_id">
                <label for="reject_reason">Reason for Rejection (Required):</label>
                <textarea id="reject_reason" name="reason" required style="width: 100%; height: 80px;"></textarea>
                <button type="submit">Confirm Reject</button>
            </form>
        </div>
    </div>
    <div id="deliveryModal" class="modal">
        <div class="modal-content">
             <div class="modal-header">
                <h2>Set Delivery Time</h2>
                <span class="close">&times;</span>
            </div>
            <form id="deliveryForm">
                <input type="hidden" id="deliveryOrderId" name="order_id">
                <label for="delivery_time">Estimated Delivery Date:</label>
                <input type="datetime-local" id="delivery_time" name="delivery_time" required>
                <button type="submit">Set Delivery</button>
            </form>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Order Details</h2>
                <span class="close" id="detailModalClose">&times;</span>
            </div>
            <div id="detailModalBody" style="max-height: 60vh; overflow-y: auto;">
                <p>Loading order details...</p>
            </div>
        </div>
    </div>

    <script src="../../public/seller/order_management.js"></script>
</body>
</html>
