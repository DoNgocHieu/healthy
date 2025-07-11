<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'not_logged_in', 'message' => 'Vui lòng đăng nhập để sử dụng tính năng này']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$itemId = intval($_POST['item_id'] ?? $_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID món ăn không hợp lệ']);
    exit;
}

try {
    if ($method === 'POST') {
        // Thêm/xóa yêu thích
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            // Thêm vào yêu thích
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, item_id) VALUES (?, ?)");
            $result = $stmt->execute([$userId, $itemId]);

            if ($result) {
                echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Đã thêm vào yêu thích']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Không thể thêm vào yêu thích']);
            }
        } elseif ($action === 'remove') {
            // Xóa khỏi yêu thích
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND item_id = ?");
            $result = $stmt->execute([$userId, $itemId]);

            if ($result) {
                echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Đã xóa khỏi yêu thích']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Không thể xóa khỏi yêu thích']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ']);
        }
    } elseif ($method === 'GET') {
        // Kiểm tra trạng thái yêu thích
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemId]);
        $isFavorite = $stmt->fetch() ? true : false;

        echo json_encode(['status' => 'success', 'is_favorite' => $isFavorite]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
