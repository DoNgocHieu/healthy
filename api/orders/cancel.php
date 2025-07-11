<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Order.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$order = new Order($userId);

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

if (!isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order_id parameter']);
    exit;
}

$result = $order->cancelOrder($data['order_id']);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => $result['message']
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
