<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Database.php';

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$voucherId = $input['id'] ?? '';

if (empty($voucherId)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID voucher']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $db->beginTransaction();

    // Kiểm tra xem voucher có được sử dụng không
    $stmt = $db->prepare("SHOW TABLES LIKE 'order_vouchers'");
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM order_vouchers WHERE voucher_id = ?');
        $stmt->execute([$voucherId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($count > 0) {
            throw new Exception('Không thể xóa voucher này vì đã được sử dụng trong đơn hàng');
        }
    }

    // Xóa voucher
    $stmt = $db->prepare('DELETE FROM vouchers WHERE id = ?');
    $stmt->execute([$voucherId]);

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
