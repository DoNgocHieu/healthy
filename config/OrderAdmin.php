<?php
require_once __DIR__ . '/Database.php';

class OrderAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getOrders($filters = [], $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['id'])) {
            $where[] = 'o.id = ?';
            $params[] = $filters['id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'o.order_status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $where[] = 'o.payment_status = ?';
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['payment_method'])) {
            $where[] = 'o.payment_method = ?';
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['user'])) {
            $where[] = '(u.fullname LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $searchTerm = "%{$filters['user']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(o.created_at) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(o.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total records for pagination
        $stmt = $this->db->prepare('
            SELECT COUNT(DISTINCT o.id) as total
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ' . $whereClause
        );
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get orders
        $stmt = $this->db->prepare('
            SELECT o.*, u.fullname, u.email, u.phone,
                   COUNT(oi.id) as total_items,
                   v.code as voucher_code
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN vouchers v ON o.voucher_id = v.id
            ' . $whereClause . '
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ');

        $params = array_merge($params, [$perPage, $offset]);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    public function getOrder($orderId) {
        $stmt = $this->db->prepare('
            SELECT o.*, u.fullname, u.email, u.phone,
                   v.code as voucher_code, v.discount_type, v.discount_value
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN vouchers v ON o.voucher_id = v.id
            WHERE o.id = ?
        ');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $order['items'] = $this->getOrderItems($orderId);
        }

        return $order;
    }

    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare('
            SELECT oi.*, i.name, i.image_url
            FROM order_items oi
            JOIN items i ON oi.item_id = i.id
            WHERE oi.order_id = ?
        ');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($orderId, $status) {
        try {
            $this->db->beginTransaction();

            // Get current order status
            $stmt = $this->db->prepare('SELECT order_status FROM orders WHERE id = ?');
            $stmt->execute([$orderId]);
            $currentStatus = $stmt->fetch(PDO::FETCH_ASSOC)['order_status'];

            // Update status
            $stmt = $this->db->prepare('
                UPDATE orders
                SET order_status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([$status, $orderId]);

            // If cancelling order
            if ($status === 'cancelled' && $currentStatus !== 'cancelled') {
                $this->handleOrderCancellation($orderId);
            }

            // If completing order
            if ($status === 'completed' && $currentStatus !== 'completed') {
                $this->handleOrderCompletion($orderId);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Cập nhật trạng thái đơn hàng thành công'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    private function handleOrderCancellation($orderId) {
        // Get order details
        $order = $this->getOrder($orderId);

        // Restore stock
        $stmt = $this->db->prepare('
            UPDATE items i
            JOIN order_items oi ON i.id = oi.item_id
            SET i.stock_quantity = i.stock_quantity + oi.quantity
            WHERE oi.order_id = ?
        ');
        $stmt->execute([$orderId]);

        // Restore points if used
        if ($order['points_used'] > 0) {
            $stmt = $this->db->prepare('
                UPDATE users
                SET points = points + ?
                WHERE id = ?
            ');
            $stmt->execute([$order['points_used'], $order['user_id']]);
        }

        // Remove earned points
        if ($order['points_earned'] > 0) {
            $stmt = $this->db->prepare('
                UPDATE users
                SET points = points - ?
                WHERE id = ?
            ');
            $stmt->execute([$order['points_earned'], $order['user_id']]);
        }
    }

    private function handleOrderCompletion($orderId) {
        // Update payment status for COD orders
        $stmt = $this->db->prepare('
            UPDATE orders
            SET payment_status = "paid"
            WHERE id = ? AND payment_method = "COD"
        ');
        $stmt->execute([$orderId]);
    }

    public function updatePaymentStatus($orderId, $status) {
        $stmt = $this->db->prepare('
            UPDATE orders
            SET payment_status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');

        try {
            $stmt->execute([$status, $orderId]);
            return [
                'success' => true,
                'message' => 'Cập nhật trạng thái thanh toán thành công'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    public function getOrderStats($dateFrom = null, $dateTo = null) {
        $where = [];
        $params = [];

        if ($dateFrom) {
            $where[] = 'DATE(created_at) >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = 'DATE(created_at) <= ?';
            $params[] = $dateTo;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare('
            SELECT
                COUNT(*) as total_orders,
                COUNT(CASE WHEN order_status = "pending" THEN 1 END) as pending_orders,
                COUNT(CASE WHEN order_status = "processing" THEN 1 END) as processing_orders,
                COUNT(CASE WHEN order_status = "shipping" THEN 1 END) as shipping_orders,
                COUNT(CASE WHEN order_status = "completed" THEN 1 END) as completed_orders,
                COUNT(CASE WHEN order_status = "cancelled" THEN 1 END) as cancelled_orders,
                SUM(CASE WHEN order_status != "cancelled" THEN total_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN payment_status = "paid" THEN total_amount ELSE 0 END) as paid_amount,
                COUNT(DISTINCT user_id) as unique_customers
            FROM orders
            ' . $whereClause
        );

        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
