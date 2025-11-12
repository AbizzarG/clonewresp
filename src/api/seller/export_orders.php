<?php
/**
 * API Endpoint: Export Orders
 * Method: GET
 * Parameters: format (csv|excel), status (optional), search (optional)
 * Response: File download (CSV or Excel)
 */

session_start();
require_once '../../config/Database.php';

// Only sellers can export
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    http_response_code(403);
    die('Unauthorized access');
}

$user_id = $_SESSION['user_id'];
$format = $_GET['format'] ?? 'csv'; // csv or excel
$statusFilter = $_GET['status'] ?? '';
$searchTerm = $_GET['search'] ?? '';

try {
    $db = new Database();
    $pdo = $db->connect();

    // Get seller's store_id
    $storeStmt = $pdo->prepare("SELECT store_id FROM store WHERE user_id = ?");
    $storeStmt->execute([$user_id]);
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$store) {
        http_response_code(403);
        die('Store not found');
    }

    $storeId = $store['store_id'];

    // Build query
    $sql = "SELECT o.order_id, o.created_at, o.total_price, o.status,
                   o.shipping_address, o.confirmed_at, o.delivery_time,
                   o.received_at, o.reject_reason, u.name as buyer_name, u.email as buyer_email
            FROM orders o
            JOIN users u ON o.buyer_id = u.user_id
            WHERE o.store_id = :store_id";

    $params = [':store_id' => $storeId];

    if (!empty($statusFilter) && $statusFilter !== 'all') {
        $sql .= " AND o.status = :status";
        $params[':status'] = $statusFilter;
    }

    if (!empty($searchTerm)) {
        $sql .= " AND (CAST(o.order_id AS TEXT) ILIKE :search OR u.name ILIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }

    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orders)) {
        die('No data to export');
    }

    // Generate export based on format
    if ($format === 'csv') {
        exportCSV($orders);
    } else {
        exportExcel($orders);
    }

} catch (PDOException $e) {
    error_log("Export Orders Error: " . $e->getMessage());
    http_response_code(500);
    die('Error exporting orders');
}

/**
 * Export data as CSV
 */
function exportCSV($orders) {
    $filename = 'orders_export_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header row
    fputcsv($output, [
        'Order ID',
        'Order Date',
        'Buyer Name',
        'Buyer Email',
        'Total Price',
        'Status',
        'Shipping Address',
        'Confirmed At',
        'Delivery Time',
        'Received At',
        'Reject Reason'
    ]);

    // Data rows
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['order_id'],
            $order['created_at'],
            $order['buyer_name'],
            $order['buyer_email'],
            'Rp ' . number_format($order['total_price'], 0, ',', '.'),
            $order['status'],
            $order['shipping_address'],
            $order['confirmed_at'] ?? '-',
            $order['delivery_time'] ?? '-',
            $order['received_at'] ?? '-',
            $order['reject_reason'] ?? '-'
        ]);
    }

    fclose($output);
    exit();
}

/**
 * Export data as Excel (HTML table format)
 */
function exportExcel($orders) {
    $filename = 'orders_export_' . date('Y-m-d_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Orders</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml></head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<thead><tr>';
    echo '<th>Order ID</th>';
    echo '<th>Order Date</th>';
    echo '<th>Buyer Name</th>';
    echo '<th>Buyer Email</th>';
    echo '<th>Total Price</th>';
    echo '<th>Status</th>';
    echo '<th>Shipping Address</th>';
    echo '<th>Confirmed At</th>';
    echo '<th>Delivery Time</th>';
    echo '<th>Received At</th>';
    echo '<th>Reject Reason</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($orders as $order) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($order['order_id']) . '</td>';
        echo '<td>' . htmlspecialchars($order['created_at']) . '</td>';
        echo '<td>' . htmlspecialchars($order['buyer_name']) . '</td>';
        echo '<td>' . htmlspecialchars($order['buyer_email']) . '</td>';
        echo '<td>Rp ' . number_format($order['total_price'], 0, ',', '.') . '</td>';
        echo '<td>' . htmlspecialchars($order['status']) . '</td>';
        echo '<td>' . htmlspecialchars($order['shipping_address']) . '</td>';
        echo '<td>' . htmlspecialchars($order['confirmed_at'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($order['delivery_time'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($order['received_at'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($order['reject_reason'] ?? '-') . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';

    exit();
}
?>
