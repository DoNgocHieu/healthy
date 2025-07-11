<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getInstance()->getConnection();

$id   = $_POST['id'] ?? '';
$name = $_POST['name'] ?? '';
$img  = '';

function randomFileName($original) {
    $ext = pathinfo($original, PATHINFO_EXTENSION);
    return uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
}

// Nếu có file ảnh upload
if (!empty($_FILES['img']['name'])) {
    $targetDir = '../uploads/categories/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $filename = randomFileName($_FILES['img']['name']);
    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($_FILES['img']['tmp_name'], $targetFile)) {
        $img = 'uploads/categories/' . $filename; // Lưu đường dẫn tương đối vào DB

        // Nếu là edit, xóa ảnh cũ
        if (!empty($id)) {
            $stmt = $db->prepare('SELECT img FROM categories WHERE TT = ?');
            $stmt->execute([$id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($old && !empty($old['img'])) {
                $oldPath = '../' . $old['img'];
                if (is_file($oldPath)) @unlink($oldPath);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload ảnh thất bại']);
        exit;
    }
}

// Thêm hoặc cập nhật
try {
    if (empty($id)) {
        // Tạo mã TT tự động
        $stmt = $db->query('SELECT COUNT(*) + 1 as next_tt FROM categories');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $tt = sprintf('CT%03d', $result['next_tt']);

        $stmt = $db->prepare('INSERT INTO categories (TT, name, img) VALUES (?, ?, ?)');
        $stmt->execute([$tt, $name, $img]);
    } else {
        if ($img) {
            $stmt = $db->prepare('UPDATE categories SET name = ?, img = ? WHERE TT = ?');
            $stmt->execute([$name, $img, $id]);
        } else {
            $stmt = $db->prepare('UPDATE categories SET name = ? WHERE TT = ?');
            $stmt->execute([$name, $id]);
        }
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
