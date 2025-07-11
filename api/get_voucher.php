<?php
header('Content-Type: application/json');
require_once '../config/Database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID voucher không được cung cấp');
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT * FROM vouchers WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        throw new Exception('Không tìm thấy voucher');
    }

    echo json_encode([
        'success' => true,
        'voucher' => $voucher
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
