<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: layout.php?page=login');
    exit;
}

// Lấy danh sách đơn hàng của người dùng
$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.created_at,
        o.order_status,
        o.payment_method,
        o.shipping_address,
        o.total_amount
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../css/orders.css">

<div class="orders-container">
    <h1>Đơn hàng của tôi</h1>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <!-- <img src="../img/empty-order.png" alt="Không có đơn hàng"> -->
            <p>Bạn chưa có đơn hàng nào</p>
            <a href="layout.php?page=home" class="btn-shop-now">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Đơn hàng #<?= $order['id'] ?></h3>
                            <p>Đặt ngày: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div class="order-status <?= strtolower($order['order_status']) ?>">
                            <?php
                            $statusText = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'shipping' => 'Đang giao hàng',
                                'completed' => 'Đã giao hàng',
                                'cancelled' => 'Đã hủy'
                            ];
                            echo $statusText[$order['order_status']] ?? $order['order_status'];
                            ?>
                        </div>
                    </div>

                    <div class="order-body">
                        <?php
                        // Lấy chi tiết đơn hàng
                        $detailStmt = $pdo->prepare("
                            SELECT
                                oi.quantity,
                                oi.price,
                                i.name,
                                i.image_url
                            FROM order_items oi
                            JOIN items i ON oi.item_id = i.id
                            WHERE oi.order_id = ?
                        ");
                        $detailStmt->execute([$order['id']]);
                        $orderItems = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <div class="order-items">
                            <?php foreach ($orderItems as $item): ?>
                                <div class="order-item">
                                    <img src="../img/<?= htmlspecialchars($item['image_url']) ?>"
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                    <div class="item-info">
                                        <h4><?= htmlspecialchars($item['name']) ?></h4>
                                        <p>Số lượng: <?= $item['quantity'] ?></p>
                                        <p>Đơn giá: <?= number_format($item['price'], 0, ',', '.') ?> đ</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-footer">
                            <div class="delivery-info">
                                <h4>Thông tin giao hàng</h4>
                                <?php
                                $shippingParts = explode(',', $order['shipping_address'] ?? '');
                                $fullname = trim($shippingParts[0] ?? '');
                                $phone = trim($shippingParts[1] ?? '');
                                $address = implode(',', array_slice($shippingParts, 2));
                                ?>
                                <p><strong>Người nhận:</strong> <?= htmlspecialchars($fullname) ?></p>
                                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($phone) ?></p>
                                <p><strong>Địa chỉ giao hàng:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'] ?? '')) ?></p>
                                <p><strong>Phương thức thanh toán:</strong>
                                    <?= $order['payment_method'] === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng' ?>
                                </p>
                            </div>
                            <div class="order-total">
                                <p>Tổng tiền: <span><?= number_format($order['total_amount'] ?? 0, 0, ',', '.') ?> đ</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
