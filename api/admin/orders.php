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

    if (($user->role ?? null) !== 'admin') {
        http_response_code(403);
        echo json_encode([
            "message" => "Access denied. Admin only."
        ]);
        exit;
    }

    $sql = "SELECT id, user_id, total_price, status, created_at
            FROM orders
            ORDER BY created_at DESC";

    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "message" => "Orders retrieved successfully",
        "count" => count($orders),
        "orders" => $orders
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Database error"
    ]);
}