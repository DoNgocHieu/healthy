<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/ItemAdmin.php';

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$itemId = $_GET['id'] ?? '';
if (empty($itemId)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID món ăn']);
    exit;
}

try {
    $itemAdmin = new ItemAdmin();
    $item = $itemAdmin->getItem($itemId);

    if ($item) {
        echo json_encode([
            'success' => true,
            'item' => $item
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy món ăn'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
