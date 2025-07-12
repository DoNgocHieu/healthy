<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
$mysqli = getDbConnection();

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['status' => 'error', 'items' => [], 'message' => 'No query']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, name, price, image_url FROM items WHERE name LIKE CONCAT('%', ?, '%') LIMIT 12");
$stmt->bind_param('s', $q);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => (int)$row['price'],
        'image_url' => $row['image_url'] ?: 'default.png'
    ];
}
$stmt->close();

if (count($items) > 0) {
    echo json_encode(['status' => 'success', 'items' => $items]);
} else {
    echo json_encode(['status' => 'success', 'items' => []]);
}
