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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$itemId = $input['id'] ?? '';

if (empty($itemId)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID món ăn']);
    exit;
}

try {
    $itemAdmin = new ItemAdmin();
    $result = $itemAdmin->deleteItem($itemId);
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
