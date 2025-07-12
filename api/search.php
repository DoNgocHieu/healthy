<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
$mysqli = getDbConnection();

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['status' => 'error', 'items' => [], 'message' => 'No query']);
    exit;
}


$userId = $_SESSION['user_id'] ?? 0;
$sql = "SELECT i.id, i.name, i.price, i.image_url, i.quantity,
       ci.quantity AS cartQty
  FROM items i
  LEFT JOIN cart_items ci
    ON ci.item_id = i.id AND ci.user_id = ? AND ci.is_deleted = 0
 WHERE i.name LIKE CONCAT('%', ?, '%')
 LIMIT 12";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('is', $userId, $q);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => (int)$row['price'],
        'image_url' => $row['image_url'] ?: 'default.png',
        'stockQty' => isset($row['quantity']) ? (int)$row['quantity'] : 99,
        'cartQty' => isset($row['cartQty']) && $row['cartQty'] !== null ? (int)$row['cartQty'] : 1
    ];
}
$stmt->close();

if (count($items) > 0) {
    echo json_encode(['status' => 'success', 'items' => $items]);
} else {
    echo json_encode(['status' => 'success', 'items' => []]);
}
