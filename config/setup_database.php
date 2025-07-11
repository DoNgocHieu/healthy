<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Tạo kết nối PDO tới MySQL server (không chọn database)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Đọc file SQL
    $sql = file_get_contents(__DIR__ . '/../data/database.sql');

    // Thực thi các câu lệnh SQL
    $pdo->exec($sql);

    echo "Database đã được tạo và khởi tạo thành công!\n";

} catch(PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
