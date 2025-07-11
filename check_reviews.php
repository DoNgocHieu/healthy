<?php
require_once 'config/Database.php';

$db = Database::getInstance()->getConnection();

echo "=== Checking comments table ===\n";
$stmt = $db->prepare("SHOW TABLES LIKE 'comments'");
$stmt->execute();
if ($stmt->fetch()) {
    echo "Table exists. Structure:\n";
    $stmt = $db->query('DESCRIBE comments');
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . "\n";
    }
} else {
    echo "Table comments does not exist\n";
}
?>
