<?php
require_once __DIR__ . '/../../config/Database.php';

$db = Database::getInstance()->getConnection();

// Kiểm tra dữ liệu trong bảng categories
$stmt = $db->query("SELECT COUNT(*) as count FROM categories");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Số lượng categories: " . $result['count'];

// Hiển thị dữ liệu categories
$stmt = $db->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nDanh sách categories:\n";
print_r($categories);
