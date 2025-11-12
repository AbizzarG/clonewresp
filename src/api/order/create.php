<?php
/**
 * API Endpoint: Create Order from Cart
 * Method: POST
 * Body: JSON { shipping_address: string }
 * Response: JSON { success: bool, message: string, order_ids: array }
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

// Only buyers can create orders
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
if (!isset($input['shipping_address']) || empty(trim($input['shipping_address']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Alamat pengiriman harus diisi'
    ]);
    exit();
}

$shipping_address = trim($input['shipping_address']);

// Validate address length
if (strlen($shipping_address) < 20) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Alamat pengiriman terlalu pendek. Minimal 20 karakter.'
    ]);
    exit();
}

if (strlen($shipping_address) > 500) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Alamat pengiriman terlalu panjang. Maksimal 500 karakter.'
    ]);
    exit();
}

try {
    $db = new Database();
    $pdo = $db->connect();

    // Start transaction
    $pdo->beginTransaction();

    // 1. Get buyer balance
    $balanceQuery = "SELECT balance FROM users WHERE user_id = ? FOR UPDATE";
    $balanceStmt = $pdo->prepare($balanceQuery);
    $balanceStmt->execute([$buyer_id]);
    $balanceResult = $balanceStmt->fetch(PDO::FETCH_ASSOC);

    if (!$balanceResult) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ]);
        exit();
    }

    $buyer_balance = $balanceResult['balance'];

    // 2. Get all cart items grouped by store
    $cartQuery = "SELECT
                    ci.cart_item_id,
                    ci.quantity,
                    p.product_id,
                    p.product_name,
                    p.price,
                    p.stock,
                    s.store_id,
                    s.store_name,
                    (ci.quantity * p.price) as subtotal
                  FROM cart_item ci
                  INNER JOIN product p ON ci.product_id = p.product_id
                  INNER JOIN store s ON p.store_id = s.store_id
                  WHERE ci.buyer_id = ? AND p.deleted_at IS NULL
                  ORDER BY s.store_id";

    $cartStmt = $pdo->prepare($cartQuery);
    $cartStmt->execute([$buyer_id]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if cart is empty
    if (empty($cartItems)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Keranjang belanja Anda kosong'
        ]);
        exit();
    }

    // 3. Group items by store and validate stock
    $itemsByStore = [];
    $totalPrice = 0;
    $stockErrors = [];

    foreach ($cartItems as $item) {
        $store_id = $item['store_id'];

        // Validate stock
        if ($item['quantity'] > $item['stock']) {
            $stockErrors[] = "{$item['product_name']} - Stok tersedia: {$item['stock']}, diminta: {$item['quantity']}";
        }

        // Group by store
        if (!isset($itemsByStore[$store_id])) {
            $itemsByStore[$store_id] = [
                'store_name' => $item['store_name'],
                'items' => [],
                'total' => 0
            ];
        }

        $itemsByStore[$store_id]['items'][] = $item;
        $itemsByStore[$store_id]['total'] += $item['subtotal'];
        $totalPrice += $item['subtotal'];
    }

    // Check stock errors
    if (!empty($stockErrors)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Stok tidak mencukupi untuk: ' . implode(', ', $stockErrors)
        ]);
        exit();
    }

    // 4. Check balance
    if ($buyer_balance < $totalPrice) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Saldo tidak mencukupi. Saldo Anda: Rp ' . number_format($buyer_balance, 0, ',', '.') . ', Total: Rp ' . number_format($totalPrice, 0, ',', '.')
        ]);
        exit();
    }

    // 5. Create orders for each store
    $createdOrderIds = [];

    foreach ($itemsByStore as $store_id => $storeData) {
        // Create order
        $orderQuery = "INSERT INTO orders (buyer_id, store_id, total_price, shipping_address, status, created_at)
                       VALUES (?, ?, ?, ?, 'waiting_approval', CURRENT_TIMESTAMP)
                       RETURNING order_id";

        $orderStmt = $pdo->prepare($orderQuery);
        $orderStmt->execute([
            $buyer_id,
            $store_id,
            $storeData['total'],
            $shipping_address
        ]);

        $orderResult = $orderStmt->fetch(PDO::FETCH_ASSOC);
        $order_id = $orderResult['order_id'];
        $createdOrderIds[] = $order_id;

        // Create order items
        $orderItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price_at_order, subtotal)
                           VALUES (?, ?, ?, ?, ?)";
        $orderItemStmt = $pdo->prepare($orderItemQuery);

        foreach ($storeData['items'] as $item) {
            $orderItemStmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price'],
                $item['subtotal']
            ]);

            // Deduct stock
            $updateStockQuery = "UPDATE product SET stock = stock - ? WHERE product_id = ?";
            $updateStockStmt = $pdo->prepare($updateStockQuery);
            $updateStockStmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Add balance to store
        $updateStoreBalanceQuery = "UPDATE store SET balance = balance + ? WHERE store_id = ?";
        $updateStoreBalanceStmt = $pdo->prepare($updateStoreBalanceQuery);
        $updateStoreBalanceStmt->execute([$storeData['total'], $store_id]);
    }

    // 6. Deduct buyer balance
    $updateBuyerBalanceQuery = "UPDATE users SET balance = balance - ? WHERE user_id = ?";
    $updateBuyerBalanceStmt = $pdo->prepare($updateBuyerBalanceQuery);
    $updateBuyerBalanceStmt->execute([$totalPrice, $buyer_id]);

    // 7. Clear cart
    $clearCartQuery = "DELETE FROM cart_item WHERE buyer_id = ?";
    $clearCartStmt = $pdo->prepare($clearCartQuery);
    $clearCartStmt->execute([$buyer_id]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat! Total ' . count($createdOrderIds) . ' pesanan dari ' . count($itemsByStore) . ' toko.',
        'order_ids' => $createdOrderIds,
        'total_paid' => $totalPrice
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Create order error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server. Silakan coba lagi.'
    ]);
}
