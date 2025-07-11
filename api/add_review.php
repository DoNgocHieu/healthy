<?php
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Lấy dữ liệu từ POST
$id_food = isset($_POST['id_food']) ? intval($_POST['id_food']) : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$star = isset($_POST['star']) ? intval($_POST['star']) : 0;
$detail = isset($_POST['detail']) ? trim($_POST['detail']) : '';

// Validation
if (!$id_food || !$username || !$star || !$detail) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    exit;
}

if ($star < 1 || $star > 5) {
    echo json_encode(['success' => false, 'message' => 'Số sao phải từ 1 đến 5']);
    exit;
}

if (strlen($username) > 100) {
    echo json_encode(['success' => false, 'message' => 'Tên quá dài']);
    exit;
}

if (strlen($detail) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Nội dung đánh giá quá dài']);
    exit;
}

try {
    $mysqli = getDbConnection();

    // Kiểm tra món ăn có tồn tại không
    $checkStmt = $mysqli->prepare("SELECT id FROM items WHERE id = ?");
    $checkStmt->bind_param('i', $id_food);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
        exit;
    }

    $checkStmt->close();

    // Thêm đánh giá mới
    $stmt = $mysqli->prepare("INSERT INTO comments (id_food, id_account, username, star, date, detail, photos) VALUES (?, 0, ?, ?, NOW(), ?, '')");
    $stmt->bind_param('isis', $id_food, $username, $star, $detail);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đánh giá đã được thêm thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm đánh giá: ' . $stmt->error]);
    }

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
