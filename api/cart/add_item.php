<?php
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Cart.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$itemId = $_POST['item_id'] ?? 0;
$quantity = max(1, intval($_POST['quantity'] ?? 1));

if (!$itemId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item ID'
    ]);
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$cart = new Cart($userId);
$result = $cart->addItem($itemId, $quantity);

echo json_encode($result);
