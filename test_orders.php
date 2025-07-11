<?php
require_once 'config/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "<h2>Test Orders Data</h2>";

    // Kiểm tra dữ liệu orders
    $stmt = $db->query("SELECT * FROM orders LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Sample Orders:</h3>";
    if (empty($orders)) {
        echo "<p>No orders found in database</p>";

        // Tạo dữ liệu mẫu
        echo "<h3>Creating sample order...</h3>";

        // Tạo user mẫu nếu chưa có
        $userStmt = $db->prepare("INSERT IGNORE INTO users (username, email, password, fullname, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $userStmt->execute(['testuser', 'test@example.com', password_hash('123456', PASSWORD_DEFAULT), 'Test User', '0123456789', 'user']);

        $userId = $db->lastInsertId() ?: 1;

        // Tạo order mẫu
        $orderStmt = $db->prepare("
            INSERT INTO orders (user_id, fullname, email, phone, address, city, district, total_amount, order_status, payment_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $orderStmt->execute([
            $userId,
            'Test Customer',
            'customer@example.com',
            '0987654321',
            '123 Test Street',
            'Ho Chi Minh',
            'District 1',
            250000,
            'pending',
            'pending'
        ]);

        $orderId = $db->lastInsertId();
        echo "<p>Created sample order with ID: $orderId</p>";

        // Tạo order items mẫu
        $itemStmt = $db->prepare("
            INSERT INTO order_items (order_id, item_id, item_name, quantity, price)
            VALUES (?, ?, ?, ?, ?)
        ");
        $itemStmt->execute([$orderId, 1, 'Sample Item', 2, 125000]);

        echo "<p>Created sample order item</p>";

    } else {
        echo "<pre>";
        print_r($orders);
        echo "</pre>";
    }

    // Kiểm tra order_items
    echo "<h3>Sample Order Items:</h3>";
    $itemsStmt = $db->query("SELECT * FROM order_items LIMIT 5");
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        echo "<p>No order items found</p>";
    } else {
        echo "<pre>";
        print_r($items);
        echo "</pre>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
