
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

    $sql = "SELECT id, total_price, status, created_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId
    ]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);

    echo json_encode([
        "message" => "Orders retrieved successfully",
        "orders" => $orders
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "message" => "Database error"
    ]);
}
