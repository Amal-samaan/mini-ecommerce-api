
<?php

header("Content-Type: application/json");

require_once __DIR__ . '/../../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);

        echo json_encode([
            "message" => "Method not allowed"
        ]);

        exit;
    }

    $sql = "SELECT * FROM products ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);

    echo json_encode([
        "message" => "Products retrieved successfully",
        "products" => $products
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "message" => "Database error"
    ]);
}
