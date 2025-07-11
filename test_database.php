<?php
require_once 'config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Kết nối database thành công!\n";

    // Kiểm tra các bảng quan trọng
    $tables = [
        'orders' => 'order_status, payment_status',
        'items' => 'id, name, price',
        'categories' => 'TT, name',
        'users' => 'id, email, role',
        'vouchers' => 'code, active',
        'posts' => 'title, author_id'
    ];

    foreach ($tables as $table => $columns) {
        try {
            $stmt = $db->query("SELECT $columns FROM $table LIMIT 1");
            echo "✅ Bảng $table: OK\n";
        } catch (Exception $e) {
            echo "❌ Bảng $table: " . $e->getMessage() . "\n";
        }
    }

    // Kiểm tra orders structure
    echo "\n📋 Cấu trúc bảng orders:\n";
    $stmt = $db->query("DESCRIBE orders");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']}: {$row['Type']}\n";
    }

    // Kiểm tra dữ liệu orders
    echo "\n📊 Dữ liệu orders mẫu:\n";
    $stmt = $db->query("SELECT id, order_status, payment_status FROM orders LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Order #{$row['id']}: {$row['order_status']} / {$row['payment_status']}\n";
    }

} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
?>
