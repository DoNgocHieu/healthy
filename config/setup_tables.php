<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDb();

    // Đọc và thực thi file SQL
    $sql = file_get_contents(__DIR__ . '/../data/migrations/02_create_orders_tables.sql');

    $pdo->exec($sql);

    echo "Các bảng đã được tạo thành công!\n";

} catch(PDOException $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
