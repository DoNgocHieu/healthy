<?php
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');
$id_food = isset($_GET['id_food']) ? intval($_GET['id_food']) : 0;
if (!$id_food) {
    echo json_encode(['reviews'=>[]]);
    exit;
}
$mysqli = getDbConnection();
$stmt = $mysqli->prepare("SELECT username, star, date, detail, photos FROM comments WHERE id_food = ? ORDER BY id DESC");
$stmt->bind_param('i', $id_food);
$stmt->execute();
$stmt->bind_result($username, $star, $date, $detail, $photos);
$reviews = [];
while ($stmt->fetch()) {
    $reviews[] = [
        'username' => $username,
        'star'     => (int)$star,
        'date'     => $date,
        'detail'   => $detail,
        'photos'   => $photos
    ];
}

$stmt->close();
echo json_encode(['reviews'=>$reviews]);
