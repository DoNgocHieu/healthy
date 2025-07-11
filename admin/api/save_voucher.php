<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Database.php';

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
    $db = Database::getInstance()->getConnection();

    // Prepare data
    $data = [
        'id' => $_POST['id'] ?? '',
        'code' => $_POST['code'] ?? '',
        'description' => $_POST['description'] ?? '',
        'points_required' => (int)($_POST['points_required'] ?? 0),
        'discount_type' => $_POST['discount_type'] ?? 'amount',
        'discount_value' => (float)($_POST['discount_value'] ?? 0),
        'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        'active' => isset($_POST['active']) ? 1 : 0 // Map is_active to active
    ];

    $db->beginTransaction();

    if (empty($data['id'])) {
        // Insert new voucher
        $stmt = $db->prepare('
            INSERT INTO vouchers (code, description, points_required, discount_type, discount_value, expires_at, active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['code'],
            $data['description'],
            $data['points_required'],
            $data['discount_type'],
            $data['discount_value'],
            $data['expires_at'],
            $data['active']
        ]);
    } else {
        // Update existing voucher
        $stmt = $db->prepare('
            UPDATE vouchers
            SET code = ?, description = ?, points_required = ?, discount_type = ?, discount_value = ?,
                expires_at = ?, active = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $data['code'],
            $data['description'],
            $data['points_required'],
            $data['discount_type'],
            $data['discount_value'],
            $data['expires_at'],
            $data['active'],
            $data['id']
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
