<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/ItemAdmin.php';

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $itemAdmin = new ItemAdmin();

    // Prepare data
    $data = [
        'id' => $_POST['id'] ?? '',
        'name' => $_POST['name'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'description' => $_POST['description'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'stock_quantity' => $_POST['stock_quantity'] ?? 0
    ];

    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../img/';
        $uploadedFile = $_FILES['image'];
        $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
            $data['image_url'] = $filename;
        }
    }

    $result = $itemAdmin->saveItem($data);
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
