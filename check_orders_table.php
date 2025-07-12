<?php
require_once 'config/config.php';
$pdo = getDb();

echo "=== ORDERS TABLE STRUCTURE ===\n";
$result = $pdo->query('SHOW COLUMNS FROM orders LIKE "payment_status"');
$column = $result->fetch();
if ($column) {
    echo "Payment Status Column: " . $column['Type'] . "\n";
}

$result = $pdo->query('SHOW COLUMNS FROM orders LIKE "payment_method"');
$column = $result->fetch();
if ($column) {
    echo "Payment Method Column: " . $column['Type'] . "\n";
}

echo "\n=== CURRENT ORDERS WITH PAYMENT INFO ===\n";
$stmt = $pdo->query('SELECT id, payment_status, payment_method, payment_transaction_no FROM orders');
while ($row = $stmt->fetch()) {
    echo "Order ID: " . $row['id'] . " - Status: " . ($row['payment_status'] ?: 'NULL') . " - Method: " . ($row['payment_method'] ?: 'NULL') . " - Transaction: " . ($row['payment_transaction_no'] ?: 'NULL') . "\n";
}
?>
