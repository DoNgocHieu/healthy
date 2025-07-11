<?php
session_start();
require_once '../config/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /healthy/views/layout.php?page=login');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing post ID');
}

$db = Database::getInstance()->getConnection();

try {
    $query = "SELECT * FROM posts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        http_response_code(404);
        exit('Post not found');
    }

    header('Content-Type: application/json');
    echo json_encode($post);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error retrieving post');
}
