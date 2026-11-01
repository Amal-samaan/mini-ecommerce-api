<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header("Content-Type: application/json; charset=utf-8");

$user = authenticateUser();

if ($user->role !== 'admin') {
    http_response_code(403);
    echo json_encode([
        "message" => "Access denied. Admin only"
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'PUT' && $method !== 'PATCH') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit;
}

// قراءة رقم المنتج من الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int) $_GET['id'] <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Valid product id is required"]);
    exit;
}

$id = (int) $_GET['id'];

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON data"]);
    exit;
}

$allowedFields = ['name', 'description', 'price', 'stock'];
$updates = [];
$params = [':id' => $id];

if ($method === 'PUT') {
    foreach ($allowedFields as $field) {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            http_response_code(400);
            echo json_encode(["message" => "All product fields are required for PUT"]);
            exit;
        }
    }
}

foreach ($allowedFields as $field) {
    if (array_key_exists($field, $data)) {

        if ($data[$field] === null) {
            http_response_code(400);
            echo json_encode(["message" => "$field cannot be null"]);
            exit;
        }

        if ($field === 'name') {
            if (!is_string($data[$field]) || trim($data[$field]) === '') {
                http_response_code(400);
                echo json_encode(["message" => "Product name cannot be empty"]);
                exit;
            }

            $params[':name'] = trim($data[$field]);

        } elseif ($field === 'description') {
            if (!is_string($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Description must be text"]);
                exit;
            }

            $params[':description'] = trim($data[$field]);

        } elseif ($field === 'price') {
            if (!is_numeric($data[$field]) || $data[$field] < 0) {
                http_response_code(400);
                echo json_encode(["message" => "Price must be a non-negative number"]);
                exit;
            }

            $params[':price'] = (float) $data[$field];

        } elseif ($field === 'stock') {
            if (
                filter_var($data[$field], FILTER_VALIDATE_INT) === false ||
                $data[$field] < 0
            ) {
                http_response_code(400);
                echo json_encode(["message" => "Stock must be a non-negative integer"]);
                exit;
            }

            $params[':stock'] = (int) $data[$field];
        }

        $updates[] = "$field = :$field";
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(["message" => "At least one field must be provided"]);
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

    $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        "message" => "Product updated successfully",
        "method" => $method
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error"]);
}