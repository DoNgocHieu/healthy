<?php
session_start();
require_once '../../config/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Lấy dữ liệu gửi lên
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['order_id']) || !isset($data['order_status'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$db = Database::getInstance()->getConnection();

try {
    error_log("Updating order status - Order ID: " . $data['order_id'] . ", Status: " . $data['order_status']);

    $query = "UPDATE orders SET order_status = ? WHERE id = ?";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$data['order_status'], $data['order_id']])) {
        error_log("Successfully updated order status");

        // Nếu đơn hàng được đánh dấu là đã giao, cộng điểm cho khách hàng
        if ($data['order_status'] === 'completed') {
            error_log("Order marked as completed, processing points");
            $queryPoints = "
                UPDATE users u
                INNER JOIN orders o ON u.id = o.user_id
                SET u.points = u.points + o.points_earned,
                    u.points_total = u.points_total + o.points_earned
                WHERE o.id = ? AND o.order_status != 'completed'";
            $stmtPoints = $db->prepare($queryPoints);
            $stmtPoints->execute([$data['order_id']]);

            // Ghi lại lịch sử điểm (nếu có bảng points_history)
            try {
                $queryHistory = "
                    INSERT INTO points_history (user_id, change_amount, type, reference_id, description)
                    SELECT o.user_id, o.points_earned, 'earn', o.id, CONCAT('Điểm thưởng từ đơn hàng #', o.id)
                    FROM orders o
                    WHERE o.id = ? AND o.points_earned > 0";
                $stmtHistory = $db->prepare($queryHistory);
                $stmtHistory->execute([$data['order_id']]);
            } catch (Exception $e) {
                error_log("Points history error: " . $e->getMessage());
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật trạng thái đơn hàng thành công'
        ]);
    } else {
        throw new Exception('Failed to update order status');
    }
} catch (Exception $e) {
    error_log("Order status update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi cập nhật trạng thái đơn hàng'
    ]);
}
?>
