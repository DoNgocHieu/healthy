<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'not_logged_in', 'message' => 'Vui lòng đăng nhập để xem danh sách yêu thích']);
    exit;
}

try {
    // Lấy danh sách món ăn yêu thích của user
    $stmt = $pdo->prepare("
        SELECT i.id, i.name, i.price, i.description, i.image_url, i.quantity, f.created_at as favorited_at
        FROM favorites f
        JOIN items i ON f.item_id = i.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$userId]);

    $favorites = [];
    while ($row = $stmt->fetch()) {
        $favorites[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'description' => $row['description'],
            'image_url' => $row['image_url'],
            'quantity' => (int)$row['quantity'],
            'favorited_at' => $row['favorited_at']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'favorites' => $favorites,
        'count' => count($favorites)
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
