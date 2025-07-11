<?php
require_once __DIR__ . '/../config/config.php';

class Cart {
    private $pdo;
    private $userId;

    public function __construct($userId = null) {
        $this->pdo = getDb();
        $this->userId = $userId;
    }

    public function getItems() {
        if (!$this->userId) return [];

        $stmt = $this->pdo->prepare("
            SELECT cart_items.*,
                   items.name, items.price, items.image_url,
                   items.quantity as stock_quantity
            FROM cart_items
            JOIN items ON cart_items.item_id = items.id
            WHERE cart_items.user_id = ?
            ORDER BY cart_items.added_at DESC
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll();
    }

    public function addItem($itemId, $quantity = 1) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            // Kiểm tra số lượng tồn kho
            $stmt = $this->pdo->prepare("SELECT quantity FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();

            if (!$item) {
                return ['success' => false, 'message' => 'Món ăn không tồn tại'];
            }

            if ($item['quantity'] < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Số lượng trong kho không đủ. Chỉ còn ' . $item['quantity'] . ' phần.'
                ];
            }

            // Kiểm tra món ăn đã có trong giỏ hàng chưa
            $stmt = $this->pdo->prepare("
                SELECT quantity FROM cart_items
                WHERE user_id = ? AND item_id = ?
            ");
            $stmt->execute([$this->userId, $itemId]);
            $cartItem = $stmt->fetch();

            if ($cartItem) {
                // Cập nhật số lượng
                $newQuantity = $cartItem['quantity'] + $quantity;
                if ($newQuantity > $item['quantity']) {
                    return [
                        'success' => false,
                        'message' => 'Số lượng vượt quá tồn kho'
                    ];
                }

                $stmt = $this->pdo->prepare("
                    UPDATE cart_items
                    SET quantity = quantity + ?
                    WHERE user_id = ? AND item_id = ?
                ");
                $stmt->execute([$quantity, $this->userId, $itemId]);
            } else {
                // Thêm món mới vào giỏ
                $stmt = $this->pdo->prepare("
                    INSERT INTO cart_items (user_id, item_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$this->userId, $itemId, $quantity]);
            }

            $cartCount = $this->getItemCount();

            return [
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'cart_count' => $cartCount
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function updateQuantity($itemId, $quantity) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            // Kiểm tra số lượng tồn kho
            $stmt = $this->pdo->prepare("SELECT quantity FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();

            if ($quantity > $item['quantity']) {
                return [
                    'success' => false,
                    'message' => 'Số lượng vượt quá tồn kho'
                ];
            }

            if ($quantity <= 0) {
                // Xóa món khỏi giỏ hàng
                $stmt = $this->pdo->prepare("
                    DELETE FROM cart_items
                    WHERE user_id = ? AND item_id = ?
                ");
                $stmt->execute([$this->userId, $itemId]);
            } else {
                // Cập nhật số lượng
                $stmt = $this->pdo->prepare("
                    UPDATE cart_items
                    SET quantity = ?
                    WHERE user_id = ? AND item_id = ?
                ");
                $stmt->execute([$quantity, $this->userId, $itemId]);
            }

            // Lấy thông tin cập nhật
            $stmt = $this->pdo->prepare("
                SELECT quantity, (quantity * items.price) as total
                FROM cart_items
                JOIN items ON cart_items.item_id = items.id
                WHERE cart_items.user_id = ? AND cart_items.item_id = ?
            ");
            $stmt->execute([$this->userId, $itemId]);
            $item = $stmt->fetch();

            $cartTotal = $this->getCartTotal();
            $cartCount = $this->getItemCount();

            return [
                'success' => true,
                'message' => 'Đã cập nhật giỏ hàng',
                'quantity' => $item ? $item['quantity'] : 0,
                'item_total' => $item ? $item['total'] : 0,
                'cart_total' => $cartTotal,
                'cart_count' => $cartCount
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function removeItem($itemId) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM cart_items
                WHERE user_id = ? AND item_id = ?
            ");
            $stmt->execute([$this->userId, $itemId]);

            $cartTotal = $this->getCartTotal();
            $cartCount = $this->getItemCount();

            return [
                'success' => true,
                'message' => 'Đã xóa món khỏi giỏ hàng',
                'cart_total' => $cartTotal,
                'cart_count' => $cartCount
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function clearCart() {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$this->userId]);

            return [
                'success' => true,
                'message' => 'Đã xóa giỏ hàng',
                'cart_total' => 0,
                'cart_count' => 0
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function getItemCount() {
        if (!$this->userId) return 0;

        $stmt = $this->pdo->prepare("
            SELECT SUM(quantity) FROM cart_items WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getCartTotal() {
        if (!$this->userId) return 0;

        $stmt = $this->pdo->prepare("
            SELECT SUM(cart_items.quantity * items.price)
            FROM cart_items
            JOIN items ON cart_items.item_id = items.id
            WHERE cart_items.user_id = ?
        ");
        $stmt->execute([$this->userId]);
        return (float)$stmt->fetchColumn();
    }

    public function validateStock() {
        if (!$this->userId) return [];

        $items = $this->getItems();
        $invalid = [];

        foreach ($items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $invalid[] = [
                    'id' => $item['item_id'],
                    'name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $item['stock_quantity']
                ];

                // Cập nhật số lượng về tối đa có thể
                if ($item['stock_quantity'] > 0) {
                    $this->updateQuantity($item['item_id'], $item['stock_quantity']);
                } else {
                    $this->removeItem($item['item_id']);
                }
            }
        }

        return $invalid;
    }
}
