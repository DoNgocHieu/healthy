<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Cart.php';
require_once __DIR__ . '/../../config/Voucher.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$cart = new Cart($userId);
$voucher = new Voucher();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

if (!isset($data['code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing voucher code']);
    exit;
}

$code = trim($data['code']);
$result = $voucher->apply($code, $cart);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'discount' => $result['discount'],
        'cart_total' => $cart->getCartTotal()
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
