<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDb();

    // Đọc và thực thi file SQL cho users
    $sql = file_get_contents(__DIR__ . '/../data/migrations/01_create_users.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Bảng users đã được tạo và dữ liệu mẫu đã được thêm thành công!\n";

} catch(PDOException $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
