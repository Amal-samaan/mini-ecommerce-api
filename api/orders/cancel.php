
<?php

header("Content-Type: application/json");

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        http_response_code(405);
        echo json_encode([
            "message" => "Method not allowed"
        ]);
        exit;
    }

    // التحقق من الهوية
    $user = authenticateUser();
    $userId = $user->user_id;


    $data = json_decode(file_get_contents("php://input"));

    if (!is_object($data)) {
        http_response_code(400);
        echo json_encode([
            "message" => "Invalid JSON body"
        ]);
        exit;
    }

    $orderId = $data->order_id ?? null;

    if (!is_int($orderId) || $orderId < 1) {
        http_response_code(400);
        echo json_encode([
            "message" => "Valid order ID is required"
        ]);
        exit;
    }

    // البحث عن طلب العميل
    $sql = "SELECT id, status
            FROM orders
            WHERE id = :order_id
              AND user_id = :user_id";

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

    // الغاء الطلبات المعلقة فقط
    if ($order['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode([
            "message" => "Only pending orders can be cancelled"
        ]);
        exit;
    }

    // الغاء الطلب
    $updateSql = "UPDATE orders
                  SET status = 'cancelled'
                  WHERE id = :order_id
                    AND user_id = :user_id
                    AND status = 'pending'";

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':order_id' => $orderId,
        ':user_id' => $userId
    ]);

    if ($updateStmt->rowCount() !== 1) {
        http_response_code(409);
        echo json_encode([
            "message" => "Order could not be cancelled"
        ]);
        exit;
    }

    echo json_encode([
        "message" => "Order cancelled successfully",
        "order_id" => $orderId,
        "status" => "cancelled"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Database error"
    ]);
}