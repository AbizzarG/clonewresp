<?php
/**
 * API Endpoint: Get Cart Item Count
 * Method: GET
 * Response: JSON { count: int }
 */

session_start();
header('Content-Type: application/json');
require_once '../../config/Database.php';

// Only buyers have cart
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    echo json_encode(['count' => 0]);
    exit();
}

$buyer_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $pdo = $db->connect();

    // Count total unique products in cart (not total quantity)
    $query = "SELECT COUNT(*) as count
              FROM cart_item
              WHERE buyer_id = ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$buyer_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'count' => (int)$result['count']
    ]);

} catch (PDOException $e) {
    error_log("Cart count error: " . $e->getMessage());

    echo json_encode([
        'count' => 0
    ]);
}
