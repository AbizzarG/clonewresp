<?php
/**
 * API Endpoint: Get Order Detail
 * Method: GET
 * Parameter: order_id
 * Response: JSON { success: bool, data: object, message: string }
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

// Only buyers and sellers can view orders
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['buyer', 'seller'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login untuk melihat detail order'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Validate order_id
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Order ID tidak valid'
    ]);
    exit();
}

$order_id = (int)$_GET['order_id'];

try {
    $db = new Database();
    $pdo = $db->connect();

    // Build query based on role
    if ($user_role === 'buyer') {
        // Buyer can only view their own orders
        $orderQuery = "SELECT
                        o.order_id,
                        o.store_id,
                        o.buyer_id,
                        o.total_price,
                        o.shipping_address,
                        o.status,
                        o.reject_reason,
                        o.confirmed_at,
                        o.delivery_time,
                        o.received_at,
                        o.created_at,
                        s.store_name,
                        u.name as buyer_name
                      FROM orders o
                      INNER JOIN store s ON o.store_id = s.store_id
                      LEFT JOIN users u ON o.buyer_id = u.user_id
                      WHERE o.order_id = ? AND o.buyer_id = ?";

        $orderStmt = $pdo->prepare($orderQuery);
        $orderStmt->execute([$order_id, $user_id]);
    } else {
        // Seller can only view orders from their store
        // First, get seller's store_id
        $storeStmt = $pdo->prepare("SELECT store_id FROM store WHERE user_id = ?");
        $storeStmt->execute([$user_id]);
        $store = $storeStmt->fetch(PDO::FETCH_ASSOC);

        if (!$store) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Toko tidak ditemukan'
            ]);
            exit();
        }

        $orderQuery = "SELECT
                        o.order_id,
                        o.store_id,
                        o.buyer_id,
                        o.total_price,
                        o.shipping_address,
                        o.status,
                        o.reject_reason,
                        o.confirmed_at,
                        o.delivery_time,
                        o.received_at,
                        o.created_at,
                        s.store_name,
                        u.name as buyer_name
                      FROM orders o
                      INNER JOIN store s ON o.store_id = s.store_id
                      LEFT JOIN users u ON o.buyer_id = u.user_id
                      WHERE o.order_id = ? AND o.store_id = ?";

        $orderStmt = $pdo->prepare($orderQuery);
        $orderStmt->execute([$order_id, $store['store_id']]);
    }

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan'
        ]);
        exit();
    }

    // Get order items
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
    $itemsStmt->execute([$order_id]);
    $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $order
    ]);

} catch (PDOException $e) {
    error_log("Order Detail API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat detail pesanan'
    ]);
}
?>