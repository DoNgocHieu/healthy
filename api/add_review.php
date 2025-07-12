<?php
session_start();
require_once dirname(__DIR__) . '/config/config.php';

// Debug - Log all received data
$debug_file = dirname(__DIR__) . '/debug_upload.log';
file_put_contents($debug_file, "\n" . date('Y-m-d H:i:s') . " - New request\n", FILE_APPEND);
file_put_contents($debug_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($debug_file, "FILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);
file_put_contents($debug_file, "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n", FILE_APPEND);

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

// Debug validation
file_put_contents($debug_file, "Validation check:\n", FILE_APPEND);
file_put_contents($debug_file, "id_food: '$id_food' (empty: " . (!$id_food ? 'YES' : 'NO') . ")\n", FILE_APPEND);
file_put_contents($debug_file, "username: '$username' (empty: " . (!$username ? 'YES' : 'NO') . ")\n", FILE_APPEND);
file_put_contents($debug_file, "star: '$star' (empty: " . (!$star ? 'YES' : 'NO') . ")\n", FILE_APPEND);
file_put_contents($debug_file, "detail: '$detail' (empty: " . (!$detail ? 'YES' : 'NO') . ")\n", FILE_APPEND);

// Validation
if (!$id_food || !$username || !$star || !$detail) {
    file_put_contents($debug_file, "VALIDATION FAILED\n", FILE_APPEND);
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

// Xử lý upload ảnh
$uploadedImages = [];

if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $uploadDir = dirname(__DIR__) . '/uploads/reviews/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $maxImages = 3;

    $imageCount = count($_FILES['images']['name']);
    if ($imageCount > $maxImages) {
        echo json_encode(['success' => false, 'message' => "Tối đa $maxImages ảnh"]);
        exit;
    }

    for ($i = 0; $i < $imageCount; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['images']['tmp_name'][$i];
            $fileType = $_FILES['images']['type'][$i];
            $fileSize = $_FILES['images']['size'][$i];

            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WebP)']);
                exit;
            }

            // Validate file size
            if ($fileSize > $maxFileSize) {
                echo json_encode(['success' => false, 'message' => 'Kích thước ảnh không được vượt quá 5MB']);
                exit;
            }

            // Generate unique filename
            $extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
            $filename = uniqid('review_' . $id_food . '_') . '.' . $extension;
            $targetPath = $uploadDir . $filename;

            // Move uploaded file
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedImages[] = $filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi upload ảnh']);
                exit;
            }
        }
    }
}

// Convert uploaded images array to JSON string
$imagesJson = !empty($uploadedImages) ? json_encode($uploadedImages) : null;

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

    $user_id = $_SESSION['user_id'] ?? 0;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để bình luận!']);
        exit;
    }

    // Kiểm tra đã mua hàng chưa
    $sql = "SELECT COUNT(*) FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.item_id = ? AND o.user_id = ? AND o.status IN ('completed', 'shipping')";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $id_food, $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count < 1) {
        echo json_encode(['success' => false, 'message' => 'Bạn cần mua món này trước khi đánh giá!']);
        exit;
    }

    // Thêm đánh giá mới với ảnh
    $stmt = $mysqli->prepare("INSERT INTO comments (id_food, id_account, username, star, date, detail, images, photos) VALUES (?, 0, ?, ?, NOW(), ?, ?, '')");
    $stmt->bind_param('isiss', $id_food, $username, $star, $detail, $imagesJson);

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
