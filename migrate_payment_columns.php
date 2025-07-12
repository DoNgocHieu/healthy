<?php
require_once 'config/config.php';
$pdo = getDb();

echo "Starting database migration...\n";

try {
    $pdo->exec('ALTER TABLE orders ADD COLUMN payment_transaction_no VARCHAR(50) NULL');
    echo "✅ Added payment_transaction_no column\n";
} catch (Exception $e) {
    echo "ℹ️ payment_transaction_no: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec('ALTER TABLE orders ADD COLUMN payment_bank_code VARCHAR(10) NULL');
    echo "✅ Added payment_bank_code column\n";
} catch (Exception $e) {
    echo "ℹ️ payment_bank_code: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec('ALTER TABLE orders ADD COLUMN payment_date DATETIME NULL');
    echo "✅ Added payment_date column\n";
} catch (Exception $e) {
    echo "ℹ️ payment_date: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec('ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    echo "✅ Added updated_at column\n";
} catch (Exception $e) {
    echo "ℹ️ updated_at: " . $e->getMessage() . "\n";
}

echo "\n🎉 Database migration completed!\n";
?>
