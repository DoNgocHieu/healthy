<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getInstance()->getConnection();

if (empty($_POST['name'])) {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
    exit;
}

try {
    $db->beginTransaction();

    // Xử lý upload hình ảnh nếu có
    $imgPath = null;
    if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/categories/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['img']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['img']['tmp_name'], $targetPath)) {
            $imgPath = 'uploads/categories/' . $fileName;
        }
    }

    if (empty($_POST['id'])) {
        // Thêm mới
        // Tạo mã TT tự động dựa trên số lượng categories hiện tại
        $stmt = $db->query('SELECT COUNT(*) + 1 as next_tt FROM categories');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $tt = sprintf('CT%03d', $result['next_tt']);

        $stmt = $db->prepare('
            INSERT INTO categories (TT, name, img)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([
            $tt,
            $_POST['name'],
            $imgPath ?? ''
        ]);
    } else {
        // Cập nhật
        if ($imgPath) {
            // Nếu có upload hình ảnh mới
            $stmt = $db->prepare('
                UPDATE categories
                SET name = ?,
                    img = ?
                WHERE TT = ?
            ');
            $stmt->execute([
                $_POST['name'],
                $imgPath ?? '',
                $_POST['id']
            ]);
        } else {
            // Nếu không có upload hình ảnh mới
            $stmt = $db->prepare('
                UPDATE categories
                SET name = ?
                WHERE TT = ?
            ');
            $stmt->execute([
                $_POST['name'],
                $_POST['id']
            ]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
