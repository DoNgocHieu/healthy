<?php
header('Content-Type: application/json');
require_once '../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Nhận dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        throw new Exception('ID voucher không được cung cấp');
    }

    // Kiểm tra xem voucher đã được sử dụng chưa
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM voucher_usage WHERE voucher_id = ?');
    $stmt->execute([$data['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        throw new Exception('Không thể xóa voucher đã được sử dụng');
    }

    // Xóa voucher
    $stmt = $db->prepare('DELETE FROM vouchers WHERE id = ?');
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Không tìm thấy voucher để xóa');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
