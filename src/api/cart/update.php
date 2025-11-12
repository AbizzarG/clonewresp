<?php
/**
 * API Endpoint: Update Cart Item Quantity
 * Method: POST
 * Body: JSON { cart_item_id: int, quantity: int }
 * Response: JSON { success: bool, message: string, newQuantity: int, newSubtotal: float }
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

// Only buyers can update cart
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
if (!isset($input['cart_item_id']) || !isset($input['quantity'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Cart item ID dan quantity harus diisi'
    ]);
    exit();
}

$cart_item_id = (int)$input['cart_item_id'];
$new_quantity = (int)$input['quantity'];

// Validate quantity
if ($new_quantity < 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Quantity harus minimal 1'
    ]);
    exit();
}

try {
    $db = new Database();
    $pdo = $db->connect();

    // Start transaction
    $pdo->beginTransaction();

    // 1. Verify cart item belongs to this buyer and get product info
    $cartQuery = "SELECT ci.cart_item_id, ci.product_id, ci.quantity,
                         p.product_name, p.stock, p.price
                  FROM cart_item ci
                  INNER JOIN product p ON ci.product_id = p.product_id
                  WHERE ci.cart_item_id = ? AND ci.buyer_id = ? AND p.deleted_at IS NULL";

    $cartStmt = $pdo->prepare($cartQuery);
    $cartStmt->execute([$cart_item_id, $buyer_id]);
    $cartItem = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartItem) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Item tidak ditemukan di keranjang Anda'
        ]);
        exit();
    }

    // 2. Check stock availability
    if ($new_quantity > $cartItem['stock']) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Stok tidak mencukupi. Tersedia: {$cartItem['stock']}"
        ]);
        exit();
    }

    // 3. Update quantity
    $updateQuery = "UPDATE cart_item
                    SET quantity = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE cart_item_id = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$new_quantity, $cart_item_id]);

    $pdo->commit();

    // Calculate new subtotal
    $new_subtotal = $new_quantity * $cartItem['price'];

    echo json_encode([
        'success' => true,
        'message' => 'Quantity berhasil diupdate',
        'newQuantity' => $new_quantity,
        'newSubtotal' => $new_subtotal
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Update cart error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server. Silakan coba lagi.'
    ]);
}
