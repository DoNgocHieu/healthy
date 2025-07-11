<?php
require_once __DIR__ . '/config/config.php';

try {
    $mysqli = getDbConnection();

    // Kiểm tra xem cột images đã tồn tại chưa
    $result = $mysqli->query("SHOW COLUMNS FROM comments LIKE 'images'");

    if ($result->num_rows == 0) {
        // Thêm cột images vào bảng comments
        $sql = "ALTER TABLE comments ADD COLUMN images TEXT NULL AFTER detail";

        if ($mysqli->query($sql)) {
            echo "✅ Đã thêm cột 'images' vào bảng comments thành công!\n";
        } else {
            echo "❌ Lỗi khi thêm cột: " . $mysqli->error . "\n";
        }
    } else {
        echo "ℹ️ Cột 'images' đã tồn tại trong bảng comments.\n";
    }

    // Kiểm tra cấu trúc bảng sau khi cập nhật
    echo "\n📋 Cấu trúc bảng comments hiện tại:\n";
    $result = $mysqli->query("DESCRIBE comments");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']} ({$row['Null']}, {$row['Key']}, {$row['Default']})\n";
    }

    $mysqli->close();

} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
?>
