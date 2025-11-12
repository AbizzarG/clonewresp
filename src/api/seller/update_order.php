<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
$orderId = $input['order_id'] ?? null;

if (!$action || !$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing action or order ID.']);
    exit();
}

$db = new Database();
$pdo = $db->connect();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT s.store_id FROM store s
         JOIN orders o ON s.store_id = o.store_id
         WHERE s.user_id = ? AND o.order_id = ?"
    );
    $stmt->execute([$_SESSION['user_id'], $orderId]);
    if (!$stmt->fetch()) {
        throw new Exception("Order not found or access denied.");
    }

    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE orders SET status = 'approved', confirmed_at = CURRENT_TIMESTAMP WHERE order_id = ? AND status = 'waiting_approval'");
            $stmt->execute([$orderId]);
            if ($stmt->rowCount() === 0) throw new Exception("Order could not be approved. It might not be in 'waiting_approval' status.");
            $message = 'Order approved successfully.';
            break;

        case 'reject':
            $reason = trim($input['reason'] ?? '');
            if (empty($reason)) throw new Exception("Rejection reason is required.");

            $orderStmt = $pdo->prepare("SELECT o.buyer_id, o.total_price, o.status, o.store_id FROM orders o WHERE o.order_id = ?");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order || $order['status'] !== 'waiting_approval') {
                throw new Exception("Order cannot be rejected. It might have been processed already.");
            }

            $refundStmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE user_id = ?");
            $refundStmt->execute([$order['total_price'], $order['buyer_id']]);

            $deductStoreStmt = $pdo->prepare("UPDATE store SET balance = balance - ? WHERE store_id = ?");
            $deductStoreStmt->execute([$order['total_price'], $order['store_id']]);

            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $item) {
                $stockStmt = $pdo->prepare("UPDATE product SET stock = stock + ? WHERE product_id = ?");
                $stockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            $updateStmt = $pdo->prepare("UPDATE orders SET status = 'rejected', reject_reason = ?, confirmed_at = CURRENT_TIMESTAMP WHERE order_id = ?");
            $updateStmt->execute([$reason, $orderId]);
            $message = 'Order rejected and buyer has been refunded.';
            break;

        case 'set_delivery':
            $deliveryTime = $input['delivery_time'] ?? null;
            if (empty($deliveryTime)) throw new Exception("Delivery time is required.");

            $stmt = $pdo->prepare("UPDATE orders SET status = 'on_delivery', delivery_time = ? WHERE order_id = ? AND status = 'approved'");
            $stmt->execute([$deliveryTime, $orderId]);
            if ($stmt->rowCount() === 0) throw new Exception("Order could not be set to delivery. It might not be in 'approved' status.");
            $message = 'Order status updated to on delivery.';
            break;

        default:
            throw new Exception("Invalid action specified.");
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
