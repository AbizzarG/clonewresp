<?php
/**
 * API Endpoint: Export Products
 * Method: GET
 * Parameters: format (csv|excel), category_id (optional), search (optional)
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
$categoryId = $_GET['category_id'] ?? '';
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
    $sql = "SELECT p.product_id, p.product_name, p.price, p.stock,
                   p.description, p.main_image_path, p.created_at,
                   c.name as category_name
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

    $sql .= " ORDER BY p.product_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        die('No data to export');
    }

    // Generate export based on format
    if ($format === 'csv') {
        exportCSV($products);
    } else {
        exportExcel($products);
    }

} catch (PDOException $e) {
    error_log("Export Products Error: " . $e->getMessage());
    http_response_code(500);
    die('Error exporting products');
}

/**
 * Export data as CSV
 */
function exportCSV($products) {
    $filename = 'products_export_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header row
    fputcsv($output, [
        'Product ID',
        'Product Name',
        'Category',
        'Price',
        'Stock',
        'Description',
        'Image Path',
        'Created At'
    ]);

    // Data rows
    foreach ($products as $product) {
        // Strip HTML tags from description for CSV
        $description = strip_tags($product['description']);
        $description = preg_replace('/\s+/', ' ', $description); // Normalize whitespace
        $description = trim($description);

        fputcsv($output, [
            $product['product_id'],
            $product['product_name'],
            $product['category_name'] ?? 'Uncategorized',
            'Rp ' . number_format($product['price'], 0, ',', '.'),
            $product['stock'],
            $description,
            $product['main_image_path'],
            $product['created_at']
        ]);
    }

    fclose($output);
    exit();
}

/**
 * Export data as Excel (HTML table format)
 */
function exportExcel($products) {
    $filename = 'products_export_' . date('Y-m-d_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Products</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml></head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<thead><tr>';
    echo '<th>Product ID</th>';
    echo '<th>Product Name</th>';
    echo '<th>Category</th>';
    echo '<th>Price</th>';
    echo '<th>Stock</th>';
    echo '<th>Description</th>';
    echo '<th>Image Path</th>';
    echo '<th>Created At</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($products as $product) {
        // Strip HTML tags from description for Excel
        $description = strip_tags($product['description']);
        $description = preg_replace('/\s+/', ' ', $description); // Normalize whitespace
        $description = trim($description);

        echo '<tr>';
        echo '<td>' . htmlspecialchars($product['product_id']) . '</td>';
        echo '<td>' . htmlspecialchars($product['product_name']) . '</td>';
        echo '<td>' . htmlspecialchars($product['category_name'] ?? 'Uncategorized') . '</td>';
        echo '<td>Rp ' . number_format($product['price'], 0, ',', '.') . '</td>';
        echo '<td>' . htmlspecialchars($product['stock']) . '</td>';
        echo '<td>' . htmlspecialchars($description) . '</td>';
        echo '<td>' . htmlspecialchars($product['main_image_path']) . '</td>';
        echo '<td>' . htmlspecialchars($product['created_at']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';

    exit();
}
?>
