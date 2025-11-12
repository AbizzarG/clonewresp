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
$productId = $input['product_id'] ?? null;

if (!$productId || !is_numeric($productId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid Product ID is required.']);
    exit();
}

try {
    $db = new Database();
    $pdo = $db->connect();

    $stmt = $pdo->prepare(
        "SELECT p.product_id FROM product p JOIN store s ON p.store_id = s.store_id
         WHERE p.product_id = ? AND s.user_id = ?"
    );
    $stmt->execute([$productId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found or you do not have permission to delete it.']);
        exit();
    }

    $updateStmt = $pdo->prepare("UPDATE product SET deleted_at = CURRENT_TIMESTAMP WHERE product_id = ?");
    $updateStmt->execute([$productId]);

    if ($updateStmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
    } else {
        throw new Exception("Failed to delete the product or it was already deleted.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
