
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        exit;
    }

    $user = authenticateUser();

    if (($user->role ?? null) !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Access denied. Admin only."]);
        exit;
    }

    // Get order ID from the URL query string
    $orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$orderId || $orderId < 1) {
        http_response_code(400);
        echo json_encode(["message" => "Valid order ID is required"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"));

    if (!is_object($data)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid JSON body"]);
        exit;
    }

    $status = $data->status ?? null;

    $allowedStatuses = [
        "pending",
        "processing",
        "shipped",
        "delivered",
        "cancelled"
    ];

    if (
        !is_string($status) ||
        !in_array($status, $allowedStatuses, true)
    ) {
        http_response_code(400);
        echo json_encode([
            "message" => "Invalid status",
            "allowed_statuses" => $allowedStatuses
        ]);
        exit;
    }

    $checkStmt = $pdo->prepare(
        "SELECT id FROM orders WHERE id = :order_id"
    );

    $checkStmt->execute([':order_id' => $orderId]);

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(["message" => "Order not found"]);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE orders SET status = :status WHERE id = :order_id"
    );

    $stmt->execute([
        ':status' => $status,
        ':order_id' => $orderId
    ]);

    echo json_encode([
        "message" => "Order status updated successfully",
        "order_id" => $orderId,
        "status" => $status
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Database error"]);
}