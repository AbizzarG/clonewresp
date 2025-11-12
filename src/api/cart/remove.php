<?php
/**
 * API Endpoint: Remove Item from Cart
 * Method: POST
 * Body: JSON { cart_item_id: int }
 * Response: JSON { success: bool, message: string }
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

// Only buyers can remove from cart
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login sebagai buyer'
    ]);
    exit();
}

$buyer_id = $_SESSION['user_id'];

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['cart_item_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Cart item ID harus diisi'
    ]);
    exit();
}

$cart_item_id = (int)$input['cart_item_id'];

try {
    $db = new Database();
    $pdo = $db->connect();

    // Start transaction
    $pdo->beginTransaction();

    // 1. Verify cart item belongs to this buyer
    $verifyQuery = "SELECT cart_item_id
                    FROM cart_item
                    WHERE cart_item_id = ? AND buyer_id = ?";

    $verifyStmt = $pdo->prepare($verifyQuery);
    $verifyStmt->execute([$cart_item_id, $buyer_id]);
    $cartItem = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartItem) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Item tidak ditemukan di keranjang Anda'
        ]);
        exit();
    }

    // 2. Delete cart item
    $deleteQuery = "DELETE FROM cart_item WHERE cart_item_id = ?";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->execute([$cart_item_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Item berhasil dihapus dari keranjang'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Remove cart item error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server. Silakan coba lagi.'
    ]);
}
