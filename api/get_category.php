<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit;
}

try {
    $stmt = $db->prepare('SELECT * FROM categories WHERE TT = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        echo json_encode(['success' => true, 'category' => $category]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy danh mục']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
