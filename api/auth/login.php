<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;


$data = json_decode(file_get_contents('php://input'), true);

// التحقق من البيانات 
if (
    !is_array($data) ||
    empty($data['email']) ||
    empty($data['password'])
) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Email and password are required'
    ]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];


$sql = "SELECT id, name, email, password, role
        FROM users
        WHERE email = :email";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'email' => $email
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// التحقق من المستخدم وكلمة المرور
if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode([
        'message' => 'Invalid email or password'
    ]);
    exit;
}

// انشاء  بيانات الرمز
$issuedAt = time();
$expirationTime = $issuedAt + JWT_EXPIRATION;

$payload = [
    'iss' => JWT_ISSUER,
    'iat' => $issuedAt,
    'exp' => $expirationTime,
    'user_id' => (int) $user['id'],
    'role' => $user['role']
];

//  JWT
$token = JWT::encode($payload, JWT_SECRET, 'HS256');

// حذف كلمة المرور من بيانات المستخدم
unset($user['password']);


http_response_code(200);

echo json_encode([
    'message' => 'Login successful',
    'token' => $token,
    'user' => $user
]);
 