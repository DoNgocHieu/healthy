<?php
require_once 'config/Database.php';

$db = Database::getInstance()->getConnection();

echo "=== Checking vouchers table ===\n";
$stmt = $db->prepare("SHOW TABLES LIKE 'vouchers'");
$stmt->execute();
if ($stmt->fetch()) {
    echo "Table exists. Structure:\n";
    $stmt = $db->query('DESCRIBE vouchers');
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . "\n";
    }
} else {
    echo "Table vouchers does not exist\n";
}
?>
