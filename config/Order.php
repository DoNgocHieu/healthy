<?php
require_once __DIR__ . '/Database.php';

class Order
{
    private $db;
    private $userId;

    public function __construct($userId = null)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
    }

    public function create($cart, $data)
    {
        error_log('Order create data: ' . print_r($data, true));
        try {
            $this->db->beginTransaction();

            // Create order

            $stmt = $this->db->prepare('
                INSERT INTO orders (
                    user_id, order_status, payment_status, payment_method,
                    total_amount, subtotal, shipping_fee, discount, points_used, points_value, points_earned,
                    shipping_address, notes, voucher_id, created_at, updated_at, payment_transaction_no, payment_bank_code, payment_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?)
            ');

            $pointsEarned = floor($cart->getCartTotal() / 10000); // 1 point per 10,000 VND

            $orderStatus = 'pending';
            $paymentStatus = 'pending';

            $subtotal = $data['subtotal'] ?? $cart->getCartTotal();
            $shippingFee = $data['shipping_fee'] ?? 15000;
            $discount = $data['discount'] ?? 0;
            $totalAmount = $data['total_amount'] ?? ($subtotal + $shippingFee - $discount);
            $pointsValue = $data['points_value'] ?? 0;
            $payment_method = $data['payment_method'] ?? 'COD';

            $stmt->execute([
                $this->userId,
                $orderStatus,
                $paymentStatus,
                $payment_method,
                $totalAmount,
                $subtotal,
                $shippingFee,
                $discount,
                $data['points_used'] ?? 0,
                $pointsValue ?? 0,
                $pointsEarned ?? 0,
                $data['shipping_address'],
                $data['notes'] ?? null,
                $data['voucher_id'] ?? null,
                $data['payment_transaction_no'] ?? null,
                $data['payment_bank_code'] ?? null,
                $data['payment_date'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Add order items
            $stmt = $this->db->prepare('
                INSERT INTO order_items (order_id, item_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ');

            $cartItems = $cart->getItems();
            foreach ($cartItems as $item) {
                $stmt->execute([
                    $orderId,
                    $item['item_id'],
                    $item['quantity'],
                    $item['price']
                ]);

                // Update stock
                $this->updateStock($item['item_id'], $item['quantity']);
            }

            // Update user points
            if (isset($data['points_used']) && $data['points_used'] > 0) {
                $stmt = $this->db->prepare('
                    UPDATE users
                    SET points = points - ?
                    WHERE id = ?
                ');
                $stmt->execute([$data['points_used'], $this->userId]);
            }

            // Add earned points
            $stmt = $this->db->prepare('
                UPDATE users
                SET points = points + ?
                WHERE id = ?
            ');
            $stmt->execute([$pointsEarned, $this->userId]);

            // Clear cart
            $cart->clear();

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'points_earned' => $pointsEarned
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo đơn hàng: ' . $e->getMessage()
            ];
        }
    }

    private function updateStock($itemId, $quantity)
    {
        $stmt = $this->db->prepare('
            UPDATE items
            SET stock_quantity = stock_quantity - ?
            WHERE id = ?
        ');
        $stmt->execute([$quantity, $itemId]);
    }

    public function getOrder($orderId)
    {
        $stmt = $this->db->prepare('
            SELECT o.*,
                   u.fullname, u.email, u.phone,
                   v.code as voucher_code,
                   v.discount_type,
                   v.discount_value
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN vouchers v ON o.voucher_id = v.id
            WHERE o.id = ? AND o.user_id = ?
        ');
        $stmt->execute([$orderId, $this->userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $order['items'] = $this->getOrderItems($orderId);
        }

        return $order;
    }

    public function getOrderItems($orderId)
    {
        $stmt = $this->db->prepare('
            SELECT oi.*, i.name, i.image_url
            FROM order_items oi
            JOIN items i ON oi.item_id = i.id
            WHERE oi.order_id = ?
        ');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserOrders($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare('
            SELECT o.*,
                   COUNT(oi.id) as total_items,
                   v.code as voucher_code
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN vouchers v ON o.voucher_id = v.id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->execute([$this->userId, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelOrder($orderId)
    {
        $order = $this->getOrder($orderId);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Đơn hàng không tồn tại'
            ];
        }

        if ($order['order_status'] !== 'pending') {
            return [
                'success' => false,
                'message' => 'Chỉ có thể hủy đơn hàng chưa xử lý'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Update order status
            $stmt = $this->db->prepare('
                UPDATE orders
                SET order_status = \'cancelled\'
                WHERE id = ? AND user_id = ?
            ');
            $stmt->execute([$orderId, $this->userId]);

            // Restore stock
            foreach ($order['items'] as $item) {
                $stmt = $this->db->prepare('
                    UPDATE items
                    SET stock_quantity = stock_quantity + ?
                    WHERE id = ?
                ');
                $stmt->execute([$item['quantity'], $item['item_id']]);
            }

            // Restore points if used
            if ($order['points_used'] > 0) {
                $stmt = $this->db->prepare('
                    UPDATE users
                    SET points = points + ?
                    WHERE id = ?
                ');
                $stmt->execute([$order['points_used'], $this->userId]);
            }

            // Remove earned points
            if ($order['points_earned'] > 0) {
                $stmt = $this->db->prepare('
                    UPDATE users
                    SET points = points - ?
                    WHERE id = ?
                ');
                $stmt->execute([$order['points_earned'], $this->userId]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Đã hủy đơn hàng thành công'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hủy đơn hàng: ' . $e->getMessage()
            ];
        }
    }
}
