<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDb();

    // Đọc file SQL
    $sql = file_get_contents(__DIR__ . '/../data/migrations/create_tables.sql');

    // Thực thi các câu lệnh SQL
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Đã tạo và cập nhật cấu trúc database thành công!\n";

} catch(PDOException $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
