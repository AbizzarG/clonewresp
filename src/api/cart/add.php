<?php
/**
 * API Endpoint: Add Product to Cart
 * Method: POST
 * Body: JSON { product_id: int, quantity: int }
 * Response: JSON { success: bool, message: string }
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

// Only buyers can add to cart
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login sebagai buyer untuk menambahkan produk ke keranjang'
    ]);
    exit();
}

// Get buyer_id from session
$buyer_id = $_SESSION['user_id'];

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['product_id']) || !isset($input['quantity'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Product ID dan quantity harus diisi'
    ]);
    exit();
}

$product_id = (int)$input['product_id'];
$quantity = (int)$input['quantity'];

// Validate quantity
if ($quantity < 1) {
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

    // 1. Check if product exists and has enough stock
    $productQuery = "SELECT product_id, product_name, stock, price
                     FROM product
                     WHERE product_id = ? AND deleted_at IS NULL";
    $productStmt = $pdo->prepare($productQuery);
    $productStmt->execute([$product_id]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Produk tidak ditemukan'
        ]);
        exit();
    }

    // 2. Check if product already in cart
    $cartQuery = "SELECT cart_item_id, quantity
                  FROM cart_item
                  WHERE product_id = ? AND buyer_id = ?";
    $cartStmt = $pdo->prepare($cartQuery);
    $cartStmt->execute([$product_id, $buyer_id]);
    $existingCart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCart) {
        // Product already in cart - UPDATE quantity
        $newQuantity = $existingCart['quantity'] + $quantity;

        // Check if new quantity exceeds stock
        if ($newQuantity > $product['stock']) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Stok tidak mencukupi. Di keranjang: {$existingCart['quantity']}, Tersedia: {$product['stock']}"
            ]);
            exit();
        }

        // Update cart
        $updateQuery = "UPDATE cart_item
                        SET quantity = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE cart_item_id = ?";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([$newQuantity, $existingCart['cart_item_id']]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Berhasil menambahkan {$quantity} {$product['product_name']} ke keranjang (Total: {$newQuantity})"
        ]);

    } else {
        // Product not in cart - INSERT new
        // Check if quantity exceeds stock
        if ($quantity > $product['stock']) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Stok tidak mencukupi. Tersedia: {$product['stock']}"
            ]);
            exit();
        }

        // Insert to cart
        $insertQuery = "INSERT INTO cart_item (product_id, buyer_id, quantity)
                        VALUES (?, ?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$product_id, $buyer_id, $quantity]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Berhasil menambahkan {$quantity} {$product['product_name']} ke keranjang"
        ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Add to cart error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server. Silakan coba lagi.'
    ]);
}
