
<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';


$user = authenticateUser();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'message' => 'Method not allowed'
    ]);
    exit;
}


$data = json_decode(file_get_contents('php://input'), true);


if (
    !is_array($data) ||
    !isset($data['items']) ||
    !is_array($data['items']) ||
    empty($data['items'])
) {
    http_response_code(400);
    echo json_encode([
        'message' => 'At least one product is required'
    ]);
    exit;
}


$items = [];

foreach ($data['items'] as $item) {
    if (
        !is_array($item) ||
        !isset($item['product_id'], $item['quantity']) ||
        filter_var($item['product_id'], FILTER_VALIDATE_INT) === false ||
        filter_var($item['quantity'], FILTER_VALIDATE_INT) === false ||
        (int) $item['product_id'] <= 0 ||
        (int) $item['quantity'] <= 0
    ) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Each item must have a valid product_id and quantity'
        ]);
        exit;
    }

    $productId = (int) $item['product_id'];
    $quantity = (int) $item['quantity'];

    if (!isset($items[$productId])) {
        $items[$productId] = 0;
    }

    
    if ($quantity > PHP_INT_MAX - $items[$productId]) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Total quantity is too large',
            'product_id' => $productId
        ]);
        exit;
    }

    $items[$productId] += $quantity;
}

try {
    $pdo->beginTransaction();

    $totalPrice = 0;
    $orderItems = [];

    
    foreach ($items as $productId => $quantity) {
        $sql = "SELECT id, name, price, stock
                FROM products
                WHERE id = :id
                FOR UPDATE";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $productId
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $pdo->rollBack();
            http_response_code(404);

            echo json_encode([
                'message' => 'Product not found',
                'product_id' => $productId
            ]);
            exit;
        }

        if ($quantity > (int) $product['stock']) {
            $pdo->rollBack();
            http_response_code(400);

            echo json_encode([
                'message' => 'Insufficient stock',
                'product_id' => $productId,
                'requested_quantity' => $quantity,
                'available_stock' => (int) $product['stock']
            ]);
            exit;
        }

        $itemPrice = (float) $product['price'];

        $totalPrice += $itemPrice * $quantity;

        $orderItems[] = [
            'product_id' => (int) $productId,
            'quantity' => $quantity,
            'price' => $itemPrice
        ];


        $sql = "UPDATE products
                SET stock = stock - :quantity
                WHERE id = :id
                  AND stock >= :quantity";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':quantity' => $quantity,
            ':id' => $productId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Stock update failed');
        }
    }

    
    $sql = "INSERT INTO orders (user_id, total_price, status)
            VALUES (:user_id, :total_price, :status)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => (int) $user->user_id,
        ':total_price' => $totalPrice,
        ':status' => 'pending'
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $sql = "INSERT INTO order_items
            (order_id, product_id, quantity, price)
            VALUES (:order_id, :product_id, :quantity, :price)";

    $stmt = $pdo->prepare($sql);

    foreach ($orderItems as $item) {
        $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price']
        ]);
    }

    $pdo->commit();

    http_response_code(201);

    echo json_encode([
        'message' => 'Order created successfully',
        'order_id' => $orderId,
        'total_price' => round($totalPrice, 2),
        'status' => 'pending',
        'items' => $orderItems
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Order creation failed: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'message' => 'An error occurred while creating the order'
    ]);
}



