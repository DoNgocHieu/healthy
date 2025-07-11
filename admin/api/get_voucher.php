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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$voucherId = $_GET['id'] ?? '';
if (empty($voucherId)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID voucher']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare('SELECT * FROM vouchers WHERE id = ?');
    $stmt->execute([$voucherId]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($voucher) {
        // Map database fields to frontend expected names
        $voucher['discount_amount'] = $voucher['discount_value'];
        $voucher['expiry_date'] = $voucher['expires_at'];
        $voucher['is_active'] = $voucher['active'];

        echo json_encode([
            'success' => true,
            'voucher' => $voucher
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy voucher'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
