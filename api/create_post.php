<?php
header('Content-Type: application/json');
session_start();
require_once '../config/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$db = Database::getInstance()->getConnection();

// Lấy dữ liệu từ $_POST khi dùng FormData
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// Kiểm tra dữ liệu
if (empty($title) || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tiêu đề và nội dung không được để trống']);
    exit();
}

// Xử lý upload hình ảnh
$thumbnail = null;
if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/posts/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadFile)) {
        $thumbnail = 'uploads/posts/' . $fileName;
    }
}

try {
    $query = "INSERT INTO posts (title, content, thumbnail, author_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$title, $content, $thumbnail, $_SESSION['user_id']])) {
        echo json_encode(['success' => true, 'message' => 'Bài viết đã được tạo thành công']);
    } else {
        throw new Exception('Failed to create post');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo bài viết: ' . $e->getMessage()]);
}
