<?php
header('Content-Type: application/json');
require_once '../config/Database.php';

try {
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        throw new Exception('ID đánh giá không được cung cấp');
    }

    $db = Database::getInstance()->getConnection();

    // Xóa đánh giá
    $stmt = $db->prepare('DELETE FROM comments WHERE id = ?');
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Không tìm thấy đánh giá để xóa');
    }

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
