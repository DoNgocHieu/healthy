<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Cart.php';
require_once __DIR__ . '/../../config/Order.php';
require_once __DIR__ . '/../../config/config.php'; // Thêm dòng này

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$cart = new Cart($userId);
$order = new Order($userId);
$pdo = getDb(); 

// Validate cart
$items = $cart->getItems();
if (empty($items)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Giỏ hàng trống'
    ]);
    exit;
}

// Validate request data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

if (!isset($data['shipping_address']) || empty($data['shipping_address'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng chọn địa chỉ giao hàng'
    ]);
    exit;
}

if (!isset($data['payment_method']) || !in_array($data['payment_method'], ['COD', 'QR'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức thanh toán không hợp lệ'
    ]);
    exit;
}

// Create order
$result = $order->create($cart, $data);

if ($result['success']) {
 
    echo json_encode([
        'success' => true,
        'order_id' => $result['order_id'],
        'points_earned' => $result['points_earned']
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
