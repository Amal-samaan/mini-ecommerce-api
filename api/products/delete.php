
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$user = authenticateUser();

if ($user->role !== 'admin') {
    http_response_code(403);
    echo json_encode([
        "message" => "Access denied. Admin only"
    ]);
    exit;
}

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit;
}

// قراءة رقم المنتج من الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["message" => "Valid product id is required"]);
    exit;
}

$id = (int) $_GET['id'];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid product id"]);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM products WHERE id = :id");
    $check->execute([':id' => $id]);

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(["message" => "Product not found"]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode([
        "message" => "Product deleted successfully",
        "id" => $id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error"]);
}
