<?php
session_start();
require_once '../../config/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Lấy dữ liệu gửi lên
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$db = Database::getInstance()->getConnection();

try {
    $query = "UPDATE orders SET payment_status = ? WHERE id = ?";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$data['status'], $data['order_id']])) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật trạng thái thanh toán thành công'
        ]);
    } else {
        throw new Exception('Failed to update payment status');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi cập nhật trạng thái thanh toán'
    ]);
}
