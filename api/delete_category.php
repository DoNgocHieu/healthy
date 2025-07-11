<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getInstance()->getConnection();

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

try {
    $db->beginTransaction();

    // Kiểm tra xem có món ăn nào thuộc danh mục này không
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM items WHERE TT = ?');
    $stmt->execute([$id]); // Sử dụng TT thay vì category_id
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count > 0) {
        throw new Exception('Không thể xóa danh mục này vì đã có ' . $count . ' món ăn thuộc danh mục');
    }

    // Xóa danh mục
    $stmt = $db->prepare('DELETE FROM categories WHERE TT = ?');
    $stmt->execute([$id]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
