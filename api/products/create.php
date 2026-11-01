<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$user = authenticateUser();

// السماح للـ Admin فقط
if ($user->role !== 'admin') {
    http_response_code(403);

    echo json_encode([
        'message' => 'Access denied. Admin only.'
    ]);

    exit;
}

// استقبال بيانات المنتج
$data = json_decode(file_get_contents('php://input'), true);

if (
    !is_array($data) ||
    empty($data['name']) ||
    !isset($data['price']) ||
    !isset($data['stock'])
) {
    http_response_code(400);

    echo json_encode([
        'message' => 'Name, price, and stock are required'
    ]);

    exit;
}

// التحقق من صحة السعر والمخزون
if (
    !is_numeric($data['price']) ||
    $data['price'] < 0 ||
    filter_var($data['stock'], FILTER_VALIDATE_INT) === false ||
    $data['stock'] < 0
) {
    http_response_code(400);

    echo json_encode([
        'message' => 'Invalid price or stock'
    ]);

    exit;
}

$name = trim($data['name']);
$description = $data['description'] ?? '';
$price = $data['price'];
$stock = (int) $data['stock'];

if ($name === '') {
    http_response_code(400);

    echo json_encode([
        'message' => 'Product name cannot be empty'
    ]);

    exit;
}

// إضافة المنتج إلى قاعدة البيانات
$sql = "INSERT INTO products (name, description, price, stock)
        VALUES (:name, :description, :price, :stock)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'name' => $name,
    'description' => $description,
    'price' => $price,
    'stock' => $stock
]);

http_response_code(201);

echo json_encode([
    'message' => 'Product created successfully',
    'product_id' => (int) $pdo->lastInsertId()
]);