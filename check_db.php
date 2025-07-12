<?php
require_once 'config/config.php';
$mysqli = getDbConnection();
echo "=== COMMENTS TABLE STRUCTURE ===\n";
$result = $mysqli->query('DESCRIBE comments');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . "\n";
}

echo "\n=== SAMPLE COMMENTS ===\n";
$result = $mysqli->query('SELECT id, id_food, username, star, detail, images FROM comments LIMIT 3');
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Food: " . $row['id_food'] . " - User: " . $row['username'] . " - Star: " . $row['star'] . " - Images: " . $row['images'] . "\n";
}

echo "\n=== UPLOADS DIRECTORY ===\n";
$uploadDir = __DIR__ . '/uploads/reviews/';
echo "Looking for: " . $uploadDir . "\n";
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    echo "Found " . (count($files) - 2) . " files in uploads/reviews/\n";
    foreach (array_slice($files, 2, 5) as $file) {
        echo "- " . $file . "\n";
    }
} else {
    echo "uploads/reviews/ directory not found!\n";
}
?>
