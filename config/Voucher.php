<?php
require_once 'Database.php';

class Voucher {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function apply($code, $cart) {
        $code = trim(strtoupper($code));

        // Check if voucher exists and is valid
        $stmt = $this->db->prepare('
            SELECT * FROM vouchers
            WHERE code = ?
            AND start_date <= NOW()
            AND (end_date IS NULL OR end_date >= NOW())
            AND (max_uses IS NULL OR uses < max_uses)
        ');
        $stmt->execute([$code]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            return [
                'success' => false,
                'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'
            ];
        }

        $cartTotal = $cart->getCartTotal();

        // Check minimum order value
        if ($voucher['min_order_value'] && $cartTotal < $voucher['min_order_value']) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Đơn hàng tối thiểu %sđ để sử dụng mã giảm giá này',
                    number_format($voucher['min_order_value'], 0, ',', '.')
                )
            ];
        }

        // Calculate discount
        $discount = 0;
        if ($voucher['discount_type'] === 'percentage') {
            $discount = round($cartTotal * $voucher['discount_value'] / 100);
            if ($voucher['max_discount'] && $discount > $voucher['max_discount']) {
                $discount = $voucher['max_discount'];
            }
        } else { // fixed amount
            $discount = $voucher['discount_value'];
        }

        // Update usage count
        $stmt = $this->db->prepare('UPDATE vouchers SET uses = uses + 1 WHERE id = ?');
        $stmt->execute([$voucher['id']]);

        return [
            'success' => true,
            'message' => 'Áp dụng mã giảm giá thành công',
            'discount' => $discount,
            'voucher' => $voucher
        ];
    }

    public function create($data) {
        $stmt = $this->db->prepare('
            INSERT INTO vouchers (
                code, discount_type, discount_value, min_order_value,
                max_discount, start_date, end_date, max_uses, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        return $stmt->execute([
            strtoupper($data['code']),
            $data['discount_type'],
            $data['discount_value'],
            $data['min_order_value'] ?? null,
            $data['max_discount'] ?? null,
            $data['start_date'] ?? date('Y-m-d H:i:s'),
            $data['end_date'] ?? null,
            $data['max_uses'] ?? null,
            $data['description'] ?? null
        ]);
    }
}
