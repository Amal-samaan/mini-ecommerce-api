
<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';


$data = json_decode(file_get_contents('php://input'), true);

// التأكد من أن البيانات موجودة
if (
    !is_array($data) ||
    empty($data['name']) ||
    empty($data['email']) ||
    empty($data['password'])
) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Name, email and password are required'
    ]);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

// التحقق من البريد الالكتروني
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Invalid email address'
    ]);
    exit;
}

// تشفير  الباسوورد 
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO users (name, email, password)
            VALUES (:name, :email, :password)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword
    ]);

    http_response_code(201);

    echo json_encode([
        'message' => 'User registered successfully',
        'user_id' => (int) $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode([
            'message' => 'Email already exists'
        ]);
    } else {
        error_log($e->getMessage());

        http_response_code(500);
        echo json_encode([
            'message' => 'Internal server error'
        ]);
    }
}
