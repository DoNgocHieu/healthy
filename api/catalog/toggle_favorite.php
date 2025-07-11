<?php
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Catalog.php';

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
if (!$itemId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item ID'
    ]);
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$catalog = new Catalog();
$result = $catalog->toggleFavorite($userId, $itemId);

echo json_encode($result);
