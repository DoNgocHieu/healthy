<?php
session_start();
require_once '../config/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /healthy/views/layout.php?page=login');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Missing post ID']));
}

$db = Database::getInstance()->getConnection();

try {
    // Xóa hình ảnh cũ
    $stmt = $db->prepare("SELECT thumbnail FROM posts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $thumbnail = $stmt->fetchColumn();
    if ($thumbnail && file_exists('../' . $thumbnail)) {
        unlink('../' . $thumbnail);
    }

    // Xóa bài viết
    $query = "DELETE FROM posts WHERE id = ?";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$_GET['id']])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete post');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Error deleting post']));
}
