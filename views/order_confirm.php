<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
$userId = $_SESSION['user_id'] ?? null;
$error = null;

if (!$userId) {
    header('Location: layout.php?page=login');
    exit;
}

// Lấy các món hàng được chọn từ giỏ hàng
$stmt = $pdo->prepare("
    SELECT
        ci.item_id,
        ci.quantity,
        i.name,
        i.price,
        i.image_url,
        i.quantity as stock_qty
    FROM cart_items ci
    JOIN items i ON ci.item_id = i.id
    WHERE ci.user_id = ?
    AND ci.is_deleted = 0
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng tiền
$subtotal = array_reduce(
    $cartItems,
    fn($sum, $item) => $sum + ($item['price'] * $item['quantity']),
    0
);

// Phí ship và giảm giá (có thể thay đổi logic tính toán)
$shipping = 0; // Miễn phí ship
$discount = 0; // Chưa có mã giảm giá

$total = $subtotal + $shipping - $discount;

// Lấy địa chỉ giao hàng đã chọn
$addrStmt = $pdo->prepare("
    SELECT id, fullname, phone, address, is_default
    FROM user_addresses
    WHERE user_id = ? AND id = ?
");
$selectedAddressId = $_SESSION['selected_address_id'] ?? null;
$addrStmt->execute([$userId, $selectedAddressId]);
$selectedAddress = $addrStmt->fetch(PDO::FETCH_ASSOC);

// Xử lý submit đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['payment_method'])) {
        $error = 'Vui lòng chọn phương thức thanh toán';
    } elseif (!$selectedAddress) {
        $error = 'Vui lòng chọn địa chỉ giao hàng';
    } elseif (empty($cartItems)) {
        $error = 'Giỏ hàng trống';
    } else {
        // Kiểm tra số lượng trong kho
        $outOfStock = [];
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock_qty']) {
                $outOfStock[] = $item['name'];
            }
        }

        if (!empty($outOfStock)) {
            $error = 'Xin lỗi, những món sau đã hết hàng hoặc không đủ số lượng: ' . implode(', ', $outOfStock);
        } else {
            try {
                $pdo->beginTransaction();

                // Tạo đơn hàng mới
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (
                        user_id,
                        shipping_address,
                        payment_method,
                        subtotal,
                        shipping_fee,
                        discount,
                        total_amount,
                        order_status,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $orderStmt->execute([
                    $userId,
                    $selectedAddress['address'], // hoặc ghép fullname, phone, address nếu muốn đầy đủ
                    $_POST['payment_method'],
                    $subtotal,
                    $shipping,
                    $discount,
                    $total
                ]);

                $orderId = $pdo->lastInsertId();

                // Thêm chi tiết đơn hàng và cập nhật số lượng trong kho
                foreach ($cartItems as $item) {
                    // Thêm vào order_items
                    $detailStmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, item_id, quantity, price)
                        VALUES (?, ?, ?, ?)
                    ");
                    $detailStmt->execute([
                        $orderId,
                        $item['item_id'],
                        $item['quantity'],
                        $item['price']
                    ]);

                    // Cập nhật số lượng trong kho
                    $updateStockStmt = $pdo->prepare("
                        UPDATE items
                        SET quantity = quantity - ?
                        WHERE id = ?
                    ");
                    $updateStockStmt->execute([
                        $item['quantity'],
                        $item['item_id']
                    ]);
                }

                // Xóa giỏ hàng
                $clearCartStmt = $pdo->prepare("
                    UPDATE cart_items
                    SET is_deleted = 1
                    WHERE user_id = ?
                ");
                $clearCartStmt->execute([$userId]);

                $pdo->commit();

                if ($_POST['payment_method'] === 'vnpay') {
                    // Chuyển hướng sang trang xử lý VNPAY, truyền orderId
                    header("Location: layout.php?page=vnpay_pay&id=$orderId");
                    exit;
                } else {
                    // Chuyển hướng bình thường
                    header("Location: layout.php?page=order_success&id=$orderId");
                    exit;
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Đã có lỗi xảy ra, vui lòng thử lại sau: ' . $e->getMessage();
            }
        }
    }
}
?>

<link rel="stylesheet" href="../css/checkout.css">

<div class="checkout-container">
    <?php if (isset($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="checkout-section">
        <h2>Đơn hàng của bạn</h2>
        <?php foreach ($cartItems as $item): ?>
            <div class="checkout-item">
                <img src="../img/<?= htmlspecialchars($item['image_url']) ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="item-info">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p>Số lượng: <?= $item['quantity'] ?></p>
                    <p>Đơn giá: <?= number_format($item['price'], 0, ',', '.') ?> đ</p>
                    <p class="item-total">
                        Thành tiền: <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> đ
                    </p>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="order-summary">
            <p>Tạm tính: <span><?= number_format($subtotal, 0, ',', '.') ?> đ</span></p>
            <p>Phí vận chuyển: <span><?= number_format($shipping, 0, ',', '.') ?> đ</span></p>
            <p>Giảm giá: <span><?= number_format($discount, 0, ',', '.') ?> đ</span></p>
            <p class="total">Tổng cộng: <span><?= number_format($total, 0, ',', '.') ?> đ</span></p>
        </div>
    </div>

    <div class="checkout-section">
        <h2>Địa chỉ giao hàng</h2>
        <?php if ($selectedAddress): ?>
            <div class="address-box">
                <p><strong>Người nhận:</strong> <?= htmlspecialchars($selectedAddress['fullname']) ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($selectedAddress['phone']) ?></p>
                <p><strong>Địa chỉ:</strong> <?= nl2br(htmlspecialchars($selectedAddress['address'])) ?></p>
            </div>
            <a href="layout.php?page=cart" class="change-address">Thay đổi địa chỉ</a>
        <?php else: ?>
            <p>Vui lòng <a href="layout.php?page=cart">chọn địa chỉ giao hàng</a></p>
        <?php endif; ?>
    </div>

    <form method="POST" class="checkout-form">
        <div class="checkout-section">
            <h2>Phương thức thanh toán</h2>
            <div class="payment-methods">
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="cod">
                    <span>Thanh toán khi nhận hàng</span>
                </label>

                <label class="payment-method">
                    <input type="radio" name="payment_method" value="bank_transfer">
                    <span>Chuyển khoản ngân hàng</span>
                </label>

                <label class="payment-method">
                    <input type="radio" name="payment_method" value="vnpay">
                    <span>Thanh toán online qua VNPAY</span>
                </label>
            </div>
        </div>

        <button type="submit" class="checkout-button">Đặt hàng</button>
    </form>
</div>
