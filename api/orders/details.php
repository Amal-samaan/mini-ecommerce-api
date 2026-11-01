
<?php

header("Content-Type: application/json");

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            "message" => "Method not allowed"
        ]);
        exit;
    }

    $user = authenticateUser();
    $userId = $user->user_id;

    $orderId = $_GET['id'] ?? null;

    if (!$orderId || !filter_var($orderId, FILTER_VALIDATE_INT) || $orderId < 1) {
        http_response_code(400);
        echo json_encode([
            "message" => "Valid order ID is required"
        ]);
        exit;
    }

    $sql = "SELECT id, user_id, total_price, status, created_at
            FROM orders
            WHERE id = :order_id AND user_id = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':order_id' => $orderId,
        ':user_id' => $userId
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            "message" => "Order not found"
        ]);
        exit;
    }

    echo json_encode([
        "message" => "Order retrieved successfully",
        "order" => $order
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Database error"
    ]);
}
 