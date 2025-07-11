<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDb();
$sql = file_get_contents(__DIR__ . '/data/migrations/20231228_create_orders_tables.sql');

try {
    $pdo->exec($sql);
    echo "Migration executed successfully.\n";
} catch (Exception $e) {
    echo "Error executing migration: " . $e->getMessage() . "\n";
}
