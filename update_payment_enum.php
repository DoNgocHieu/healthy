<?php
require_once 'config/config.php';
$pdo = getDb();

echo "=== UPDATING ORDER 25 PAYMENT INFO ===\n";
$stmt = $pdo->prepare('UPDATE orders SET payment_status = "paid", payment_method = "vnpay" WHERE id = 25');
$stmt->execute();
echo "Updated order 25 payment info\n";

echo "\n=== CHECKING ORDER 25 ===\n";
$stmt = $pdo->query('SELECT id, payment_status, payment_method, payment_transaction_no FROM orders WHERE id = 25');
$order = $stmt->fetch();
if ($order) {
    echo "Order 25 - Status: " . ($order['payment_status'] ?: 'NULL') . " - Method: " . ($order['payment_method'] ?: 'NULL') . " - Transaction: " . ($order['payment_transaction_no'] ?: 'NULL') . "\n";
}
?>
