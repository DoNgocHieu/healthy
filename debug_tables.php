<?php
require_once 'config/Database.php';

$db = Database::getInstance()->getConnection();

echo "=== Items table structure ===\n";
$stmt = $db->query('DESCRIBE items');
while ($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Key'] . "\n";
}

echo "\n=== Categories table structure ===\n";
$stmt = $db->query('DESCRIBE categories');
while ($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Key'] . "\n";
}

echo "\n=== Foreign Keys in items ===\n";
$stmt = $db->query("
    SELECT
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'items'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
while ($row = $stmt->fetch()) {
    echo "Column: " . $row['COLUMN_NAME'] . " -> " .
         $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "\n";
}
?>
