<?php
require_once 'config/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Tạo bảng site_settings
    $sql = "CREATE TABLE IF NOT EXISTS site_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text', 'textarea', 'url', 'email', 'number', 'image') DEFAULT 'text',
        setting_group VARCHAR(50) DEFAULT 'general',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $db->exec($sql);
    echo "✅ Tạo bảng site_settings thành công!<br>";

    // Thêm dữ liệu mặc định
    $defaultSettings = [
        ['site_name', 'BROCCOLI', 'text', 'header', 'Tên website hiển thị trên header'],
        ['site_logo', '../img/logo.png', 'image', 'header', 'Logo website'],
        ['site_slogan', 'Healthy Food For Life', 'text', 'header', 'Slogan website'],
        ['phone_number', '1900-1234', 'text', 'contact', 'Số điện thoại liên hệ'],
        ['email', 'info@broccoli.com', 'email', 'contact', 'Email liên hệ'],
        ['facebook_url', 'https://facebook.com/broccoli', 'url', 'social', 'Link Facebook'],
        ['instagram_url', 'https://instagram.com/broccoli', 'url', 'social', 'Link Instagram'],
        ['address', '123 Healthy Street, Ho Chi Minh City', 'textarea', 'contact', 'Địa chỉ công ty'],
        ['working_hours', 'Thứ 2 - Chủ nhật: 8:00 - 22:00', 'text', 'contact', 'Giờ hoạt động'],
        ['delivery_info', 'Miễn phí giao hàng cho đơn từ 200.000đ', 'text', 'general', 'Thông tin giao hàng']
    ];

    $stmt = $db->prepare("
        INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type, setting_group, description)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }

    echo "✅ Thêm dữ liệu mặc định thành công!<br>";

    // Hiển thị dữ liệu hiện tại
    echo "<h3>Dữ liệu site_settings:</h3>";
    $result = $db->query("SELECT * FROM site_settings ORDER BY setting_group, setting_key");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Key</th><th>Value</th><th>Type</th><th>Group</th><th>Description</th></tr>";

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['setting_key']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['setting_value'], 0, 50)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['setting_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['setting_group']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
