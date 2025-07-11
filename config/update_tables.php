<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDb();

    // Đọc và thực thi file SQL cho users
    $sql = file_get_contents(__DIR__ . '/../data/migrations/01_update_users.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    // Thực thi file cập nhật foreign keys
    $sql = file_get_contents(__DIR__ . '/../data/migrations/03_update_orders_foreign_keys.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Cập nhật bảng users và orders thành công!\n";

} catch(PDOException $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
