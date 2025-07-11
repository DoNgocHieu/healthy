<?php
header('Content-Type: application/json');
require_once '../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // Nhận dữ liệu từ request
    $id = $_POST['id'] ?? null;
    $code = $_POST['code'] ?? '';
    $description = $_POST['description'] ?? '';
    $points_required = $_POST['points_required'] ?? 0;
    $discount_type = $_POST['discount_type'] ?? 'amount';
    $discount_value = $_POST['discount_value'] ?? 0;
    $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    // Validate dữ liệu
    if (empty($code)) {
        throw new Exception('Mã voucher không được để trống');
    }

    if ($discount_value <= 0) {
        throw new Exception('Giá trị giảm giá phải lớn hơn 0');
    }

    if ($discount_type === 'percent' && $discount_value > 100) {
        throw new Exception('Phần trăm giảm giá không thể lớn hơn 100%');
    }

    if ($points_required < 0) {
        throw new Exception('Điểm yêu cầu không thể âm');
    }

    // Kiểm tra mã voucher đã tồn tại chưa
    $stmt = $db->prepare('SELECT id FROM vouchers WHERE code = ? AND id != ?');
    $stmt->execute([$code, $id ?? '']);
    if ($stmt->fetch()) {
        throw new Exception('Mã voucher đã tồn tại');
    }

    if ($id) {
        // Cập nhật voucher
        $stmt = $db->prepare('
            UPDATE vouchers
            SET code = ?, description = ?, points_required = ?,
                discount_type = ?, discount_value = ?, active = ?,
                expires_at = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $code, $description, $points_required,
            $discount_type, $discount_value, $active,
            $expires_at, $id
        ]);
    } else {
        // Thêm voucher mới
        $stmt = $db->prepare('
            INSERT INTO vouchers (
                code, description, points_required,
                discount_type, discount_value, active,
                expires_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $code, $description, $points_required,
            $discount_type, $discount_value, $active,
            $expires_at
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
