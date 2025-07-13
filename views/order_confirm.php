<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
$userId = $_SESSION['user_id'] ?? null;
$error = null;

// Check login (redirect logic moved to layout.php)
if (!$userId) {
    echo '<div style="text-align:center;padding:50px;">
            <h3>Vui lòng đăng nhập để đặt hàng</h3>
            <a href="layout.php?page=login" class="btn">Đăng nhập</a>
          </div>';
    return;
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
$shipping = ($subtotal < 200000) ? 15000 : 0; // Miễn phí ship với đơn hàng trên 200.000đ
$discount = 0; // Chưa có mã giảm giá
if (!empty($_SESSION['discount_value'])) {
    $discount = (int)$_SESSION['discount_value'];
}
// hoặc nếu dùng $_POST
if (!empty($_POST['discount_value'])) {
    $discount = (int)$_POST['discount_value'];
}

$total = $subtotal + $shipping - $discount;

// Lấy địa chỉ giao hàng đã chọn
$addrStmt = $pdo->prepare("
    SELECT id, fullname, phone, address, is_default
    FROM user_addresses
    WHERE user_id = ? AND id = ?
");
$selectedAddressId = $_SESSION['selected_address_id'] ?? null;
$selectedAddress = null;
if ($selectedAddressId) {
    $addrStmt->execute([$userId, $selectedAddressId]);
    $selectedAddress = $addrStmt->fetch(PDO::FETCH_ASSOC);
}

// Display form only (POST processing moved to layout.php)
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

    <form method="POST" action="layout.php?page=order_confirm" class="checkout-form">
        <div class="checkout-section">
            <h2>Phương thức thanh toán</h2>
            <div class="payment-methods">
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="cod">
                    <div class="payment-info">
                        <i class="fas fa-truck payment-icon"></i>
                        <div>
                            <span class="payment-title">Thanh toán khi nhận hàng</span>
                            <small class="payment-desc">Thanh toán bằng tiền mặt khi nhận hàng</small>
                        </div>
                    </div>
                </label>

                <label class="payment-method">
                    <input type="radio" name="payment_method" value="bank_transfer">
                    <div class="payment-info">
                        <i class="fas fa-university payment-icon"></i>
                        <div>
                            <span class="payment-title">Chuyển khoản ngân hàng</span>
                            <small class="payment-desc">Chuyển khoản trực tiếp qua ngân hàng</small>
                        </div>
                    </div>
                </label>

                <label class="payment-method">
                    <input type="radio" name="payment_method" value="vnpay">
                    <div class="payment-info">
                        <i class="fas fa-credit-card payment-icon text-primary"></i>
                        <div>
                            <span class="payment-title">Thanh toán online qua VNPay</span>
                            <small class="payment-desc">Thanh toán an toàn qua thẻ ATM, Visa, MasterCard</small>
                        </div>
                    </div>
                    <div class="vnpay-logos">
                        <small>Hỗ trợ: <strong>Vietcombank, BIDV, VietinBank, Agribank...</strong></small>
                    </div>
                </label>
            </div>
        </div>

        <input type="hidden" name="subtotal" id="input-subtotal" value="<?= $subtotal ?>">
        <input type="hidden" name="discount" id="input-discount" value="<?= $discount ?>">
        <input type="hidden" name="shipping" id="input-shipping" value="<?= $shipping ?>">
        <input type="hidden" name="total" id="input-total" value="<?= $total ?>">
        <input type="hidden" name="voucher_code" id="input-voucher" value="">
        <input type="hidden" name="voucher_id" id="input-voucher-id" value="">
        <button type="submit" class="checkout-button">Đặt hàng</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const subtotal = sessionStorage.getItem('cart_subtotal') || '0';
  const discount = sessionStorage.getItem('cart_discount') || '0';
  const shipping = 15000; 
  const total = Number(subtotal) + Number(shipping) - Number(discount);
  const voucher = sessionStorage.getItem('cart_voucher') || '';

  // Cập nhật giao diện
  const spans = document.querySelectorAll('.order-summary span');
  spans[0].textContent = Number(subtotal).toLocaleString('vi-VN') + ' đ';
  spans[1].textContent = Number(shipping).toLocaleString('vi-VN') + ' đ';
  spans[2].textContent = Number(discount).toLocaleString('vi-VN') + ' đ';
  spans[3].textContent = Number(total).toLocaleString('vi-VN') + ' đ';

  // Cập nhật input hidden
  document.getElementById('input-subtotal').value = subtotal;
  document.getElementById('input-discount').value = discount;
  document.getElementById('input-shipping').value = shipping;
  document.getElementById('input-total').value = total;
  document.getElementById('input-voucher').value = voucher;
  document.getElementById('input-voucher-id').value = sessionStorage.getItem('cart_voucher_id') || '';
});


</script>