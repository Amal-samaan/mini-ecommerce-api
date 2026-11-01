
<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../vendor/autoload.php';

function authenticateUser()
{
    // قراءة Authorization Header
  
$authHeader = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';

if (empty($authHeader) && function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization']
        ?? $headers['authorization']
        ?? '';
}

// التأكد من وجود Token
if (!preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);

    echo json_encode([
        'message' => 'Authentication token is required'
    ]);

    exit;
}


    // استخراج Token
    $token = $matches[1];

    try {
        // التحقق من صحة Token
        $decoded = JWT::decode(
            $token,
            new Key(JWT_SECRET, 'HS256')
        );

        // التأكد من وجود بيانات المستخدم
        if (
            !isset($decoded->iss) ||
            $decoded->iss !== JWT_ISSUER ||
            !isset($decoded->user_id) ||
            !isset($decoded->role)
        ) {
            throw new UnexpectedValueException('Invalid token data');
        }

        // إرجاع بيانات المستخدم
        return $decoded;

    } catch (Throwable $e) {
        http_response_code(401);

        echo json_encode([
            'message' => 'Invalid or expired token'
        ]);

        exit;
    }
}
