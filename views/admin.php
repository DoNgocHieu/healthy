<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: layout.php?page=login');
    exit;
}

// Lấy section admin cần hiển thị
$section = $_GET['section'] ?? 'dashboard';

// Danh sách các section admin được phép
$allowedSections = [
    'dashboard',
    'orders',
    'items',
    'categories',
    'users',
    'vouchers',
    'reviews',
    'posts',
    'reports',
    'settings'
];

if (!in_array($section, $allowedSections)) {
    $section = 'dashboard';
}

// Capture the output of admin section
ob_start();
$filePath = __DIR__ . "/../admin/sections/{$section}.php";
if (file_exists($filePath)) {
    require_once $filePath;
    $content = ob_get_clean();
    require_once __DIR__ . "/../admin/layout.php";
} else {
    ob_end_clean();
    echo "Section không tồn tại: {$section}";
}
