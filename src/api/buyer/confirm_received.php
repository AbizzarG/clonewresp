<?php
/**
 * API Endpoint: Confirm Order Received
 * Method: POST
 * Body: JSON { order_id: int }
 * Response: JSON { success: bool, message: string }
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

// Only buyers can confirm receipt
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login sebagai buyer'
    ]);
    exit();
}

// Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit();
}

$buyer_id = $_SESSION['user_id'];

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['order_id']) || empty($input['order_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Order ID is required'
    ]);
    exit();
}

$order_id = filter_var($input['order_id'], FILTER_VALIDATE_INT);

if ($order_id === false || $order_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid Order ID'
    ]);
    exit();
}

try {
    $db = new Database();
    $pdo = $db->connect();

    // Start transaction
    $pdo->beginTransaction();

    // Get order details and verify ownership + status
    $orderQuery = "SELECT order_id, buyer_id, status, store_id
                   FROM orders
                   WHERE order_id = ? AND buyer_id = ?
                   FOR UPDATE";
    $orderStmt = $pdo->prepare($orderQuery);
    $orderStmt->execute([$order_id, $buyer_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan atau bukan milik Anda'
        ]);
        exit();
    }

    // Verify order is in on_delivery status
    if ($order['status'] !== 'on_delivery') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Hanya pesanan dengan status "Sedang Dikirim" yang dapat dikonfirmasi'
        ]);
        exit();
    }

    // Update order status to received and set received_at timestamp
    $updateQuery = "UPDATE orders
                    SET status = 'received',
                        received_at = CURRENT_TIMESTAMP
                    WHERE order_id = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$order_id]);

    // Commit transaction
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dikonfirmasi sebagai diterima!'
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Confirm received error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server. Silakan coba lagi.'
    ]);
}
