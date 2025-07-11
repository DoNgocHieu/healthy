<?php
session_start();
require_once '../config/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /healthy/views/layout.php?page=login');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!isset($_POST['post_id'])) {
    http_response_code(400);
    exit('Missing post ID');
}

$db = Database::getInstance()->getConnection();

// Xử lý upload hình ảnh mới (nếu có)
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

        // Xóa hình ảnh cũ
        $stmt = $db->prepare("SELECT thumbnail FROM posts WHERE id = ?");
        $stmt->execute([$_POST['post_id']]);
        $oldThumbnail = $stmt->fetchColumn();
        if ($oldThumbnail && file_exists('../' . $oldThumbnail)) {
            unlink('../' . $oldThumbnail);
        }
    }
}

try {
    $query = "UPDATE posts SET title = ?, content = ?, updated_at = NOW()";
    $params = [$_POST['title'], $_POST['content']];

    if ($thumbnail) {
        $query .= ", thumbnail = ?";
        $params[] = $thumbnail;
    }

    $query .= " WHERE id = ?";
    $params[] = $_POST['post_id'];

    $stmt = $db->prepare($query);

    if ($stmt->execute($params)) {
        header('Location: ../views/layout.php?page=admin&section=posts');
    } else {
        throw new Exception('Failed to update post');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error updating post');
}
