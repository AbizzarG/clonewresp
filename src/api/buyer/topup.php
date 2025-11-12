<?php
session_start();
header('Content-Type: application/json');

// Auth guard - hanya buyer yang bisa top-up
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Buyer login required.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['amount'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Amount is required.'
        ]);
        exit;
    }

    $amount = filter_var($input['amount'], FILTER_VALIDATE_INT);

    // Validasi amount
    if ($amount === false || $amount < 10000) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid amount. Minimum top-up is Rp 10.000.'
        ]);
        exit;
    }

    if ($amount > 100000000) { // Max 100 juta
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Amount too large. Maximum top-up is Rp 100.000.000.'
        ]);
        exit;
    }

    // Connect to database
    require_once __DIR__ . '/../../config/Database.php';
    $db = new Database();
    $pdo = $db->connect();

    $userId = $_SESSION['user_id'];

    // Begin transaction
    $pdo->beginTransaction();

    // Get current balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
        exit;
    }

    $currentBalance = (int)$user['balance'];
    $newBalance = $currentBalance + $amount;

    // Update balance
    $updateStmt = $pdo->prepare("UPDATE users SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
    $updateStmt->execute([$newBalance, $userId]);

    // Commit transaction
    $pdo->commit();

    // Update session balance (optional, untuk konsistensi)
    $_SESSION['balance'] = $newBalance;

    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Top-up berhasil!',
        'data' => [
            'previous_balance' => $currentBalance,
            'topup_amount' => $amount,
            'new_balance' => $newBalance
        ]
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Top-up error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again later.'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Top-up error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
