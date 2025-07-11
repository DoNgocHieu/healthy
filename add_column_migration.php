<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDb();
$sql = file_get_contents(__DIR__ . '/data/migrations/20231228_add_is_deleted_to_cart_items.sql');

try {
    $pdo->exec($sql);
    echo "Migration added is_deleted column successfully.\n";
} catch (Exception $e) {
    echo "Error executing migration: " . $e->getMessage() . "\n";
}
