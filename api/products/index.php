<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

// إذا وصل id في الرابط نعرض منتج واحد
if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    $sql = "SELECT id, name, description, price, stock FROM products WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'message' => 'Product not found'
        ]);
        exit;
    }

    echo json_encode([
        'product' => $product
    ]);

} else {

    // اذا ما وصل id، نعرض كل المنتجات
    $sql = "SELECT id, name, description, price, stock FROM products";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'products' => $products
    ]);
}