<?php
require_once 'config/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Tạo order_items mẫu
    $sql = "INSERT INTO order_items (order_id, item_id, item_name, quantity, price) VALUES
            (1, 1, 'Món ăn mẫu 1', 2, 75000),
            (1, 2, 'Món ăn mẫu 2', 1, 50000),
            (2, 3, 'Món ăn mẫu 3', 3, 80000),
            (3, 1, 'Món ăn mẫu 1', 1, 100000)";

    $db->exec($sql);
    echo "Đã tạo order_items mẫu thành công!";

    // Kiểm tra dữ liệu
    echo "<h3>Order Items:</h3>";
    $stmt = $db->query("SELECT * FROM order_items");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($items);
    echo "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
