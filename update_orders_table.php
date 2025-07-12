<?php
require_once 'config/config.php';
$pdo = getDb();

echo "=== UPDATING ORDERS TABLE FOR VNPAY ===\n";

try {
    // 1. Update payment_method enum to include VNPay
    echo "1. Adding VNPay to payment_method enum...\n";
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('COD','transfer','momo','vnpay') DEFAULT NULL");

    // 2. Add VNPay transaction columns if they don't exist
    echo "2. Adding VNPay transaction columns...\n";

    // Check if columns exist first
    $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_transaction_no'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_transaction_no VARCHAR(100) NULL AFTER payment_status");
    }

    $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_bank_code'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_bank_code VARCHAR(20) NULL AFTER payment_transaction_no");
    }

    $columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_date'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_date DATETIME NULL AFTER payment_bank_code");
    }

    echo "3. Update payment_status enum to include 'completed'...\n";
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','paid','completed','failed') DEFAULT 'pending'");

    echo "✅ Database updated successfully!\n";

    // Show updated structure
    echo "\n=== UPDATED TABLE STRUCTURE ===\n";
    $result = $pdo->query('DESCRIBE orders');
    while ($row = $result->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
