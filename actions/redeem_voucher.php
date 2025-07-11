<?php
session_start();
require_once __DIR__ . '/../config/config.php';
$pdo = getDb();

$userId = $_SESSION['user_id'] ?? 0;
$voucherId = $_POST['voucher_id'] ?? 0;

// Lấy thông tin voucher
$stmt = $pdo->prepare('SELECT points_required FROM vouchers WHERE id = ? AND active = 1');
$stmt->execute([$voucherId]);
$voucher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voucher) {
    echo json_encode(['success' => false, 'message' => 'Voucher không tồn tại']);
    exit;
}

// Kiểm tra điểm hiện tại
$stmt = $pdo->prepare('SELECT SUM(change_amount) AS total_points FROM points_history WHERE user_id = ?');
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalPoints = (int)($row['total_points'] ?? 0);

if ($totalPoints < $voucher['points_required']) {
    echo json_encode(['success' => false, 'message' => 'Không đủ điểm']);
    exit;
}

// Trừ điểm
$stmt = $pdo->prepare('INSERT INTO points_history (user_id, change_amount, created_at) VALUES (?, ?, NOW())');
$stmt->execute([$userId, -$voucher['points_required']]);

// Lưu voucher đã đổi
$stmt = $pdo->prepare('INSERT INTO voucher_usage (voucher_id, user_id, used_at) VALUES (?, ?, NOW())');
$stmt->execute([$voucherId, $userId]);

echo json_encode(['success' => true, 'message' => 'Đổi voucher thành công']);