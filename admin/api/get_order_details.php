<?php
session_start();
require_once '../../config/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Lấy ID đơn hàng
$orderId = $_GET['id'] ?? null;
if (!$orderId || !is_numeric($orderId)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid order ID']));
}

$db = Database::getInstance()->getConnection();

try {
    // Lấy thông tin đơn hàng chi tiết
    $query = "
        SELECT
            o.*,
            u.username,
            u.email,
            u.fullname as customer_name,
            u.phone as customer_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        exit(json_encode(['success' => false, 'message' => 'Order not found']));
    }

    // Lấy chi tiết sản phẩm trong đơn hàng
    $itemsQuery = "
        SELECT
            oi.*,
            i.name as item_name,
            i.image_url,
            i.TT as category_code,
            c.name as category_name
        FROM order_items oi
        LEFT JOIN items i ON oi.item_id = i.id
        LEFT JOIN categories c ON i.TT = c.TT
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ";

    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy thông tin voucher (nếu có)
    $voucher = null;
    if ($order['voucher_id']) {
        $voucherQuery = "SELECT * FROM vouchers WHERE id = ?";
        $voucherStmt = $db->prepare($voucherQuery);
        $voucherStmt->execute([$order['voucher_id']]);
        $voucher = $voucherStmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy lịch sử điểm (nếu có)
    $pointsHistory = [];
    if ($order['points_used'] > 0 || $order['points_earned'] > 0) {
        try {
            $pointsQuery = "
                SELECT * FROM points_history
                WHERE reference_id = ? AND reference_id IS NOT NULL
                ORDER BY created_at DESC
            ";
            $pointsStmt = $db->prepare($pointsQuery);
            $pointsStmt->execute([$orderId]);
            $pointsHistory = $pointsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Bảng points_history có thể chưa tồn tại
            error_log("Points history query failed: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $orderItems,
        'voucher' => $voucher,
        'points_history' => $pointsHistory
    ]);

} catch (Exception $e) {
    error_log("Get order details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy chi tiết đơn hàng'
    ]);
}
?>
