<?php
require_once __DIR__ . '/../config/Auth.php';
require_once __DIR__ . '/../config/Cart.php';
require_once __DIR__ . '/../config/Order.php';
require_once __DIR__ . '/../config/UserProfile.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: /healthy/views/login.php');
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$cart = new Cart($userId);
$profile = new UserProfile();
$order = new Order($userId);

// Get cart items and validate stock
$items = $cart->getItems();
if (empty($items)) {
    header('Location: /healthy/views/cart/index.php');
    exit;
}

$invalidItems = $cart->validateStock();
if (!empty($invalidItems)) {
    header('Location: /healthy/views/cart/index.php');
    exit;
}

// Get user addresses
$addresses = $profile->getUserAddresses($userId);

// Get user points
$user = $profile->getUser($userId);
$availablePoints = $user['points'];
$maxPointsToUse = min($availablePoints, floor($cart->getCartTotal() / 1000)); // 1000đ per point
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/healthy/css/checkout.css" rel="stylesheet">
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Thanh toán</h1>

        <form id="checkoutForm" method="POST" action="/healthy/api/orders/create.php">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Shipping Address -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Địa chỉ giao hàng</h5>

                            <?php if (empty($addresses)): ?>
                                <div class="alert alert-warning">
                                    Bạn chưa có địa chỉ giao hàng.
                                    <a href="/healthy/views/address/add.php" class="alert-link">Thêm địa chỉ mới</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($addresses as $address): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="shipping_address"
                                               id="address<?php echo $address['id']; ?>"
                                               value="<?php echo htmlspecialchars($address['address']); ?>"
                                               <?php echo ($address['is_default'] ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="address<?php echo $address['id']; ?>">
                                            <?php echo htmlspecialchars($address['address']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>

                                <a href="/healthy/views/address/add.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Thêm địa chỉ mới
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Phương thức thanh toán</h5>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="codPayment" value="COD" checked>
                                <label class="form-check-label" for="codPayment">
                                    <i class="bi bi-cash"></i> Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="qrPayment" value="QR">
                                <label class="form-check-label" for="qrPayment">
                                    <i class="bi bi-qr-code"></i> Thanh toán QR Code
                                </label>
                            </div>

                            <div id="qrCodeSection" class="mt-3" style="display: none;">
                                <img src="/healthy/img/qr-payment.png" alt="QR Code" class="img-fluid">
                                <p class="text-muted small mt-2">
                                    Quét mã QR để thanh toán. Đơn hàng sẽ được xác nhận tự động sau khi thanh toán thành công.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Points Usage -->
                    <?php if ($maxPointsToUse > 0): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Sử dụng điểm tích lũy</h5>

                                <div class="points-info mb-3">
                                    <div>Điểm hiện có: <strong><?php echo $availablePoints; ?></strong></div>
                                    <div>Có thể sử dụng tối đa: <strong><?php echo $maxPointsToUse; ?></strong> điểm</div>
                                    <div class="text-muted small">1 điểm = 1.000đ</div>
                                </div>

                                <div class="input-group">
                                    <input type="number" class="form-control" id="pointsToUse" name="points_used"
                                           min="0" max="<?php echo $maxPointsToUse; ?>" value="0">
                                    <button class="btn btn-outline-secondary" type="button" onclick="useMaxPoints()">
                                        Dùng tối đa
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Order Items -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Đơn hàng của bạn</h5>

                            <?php foreach ($items as $item): ?>
                                <div class="order-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <img src="/healthy/img/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="order-item-image">
                                        </div>

                                        <div class="col">
                                            <h6 class="order-item-title mb-1">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </h6>

                                            <div class="text-muted">
                                                <?php echo number_format($item['price'], 0, ',', '.'); ?>đ x
                                                <?php echo $item['quantity']; ?>
                                            </div>
                                        </div>

                                        <div class="col-auto">
                                            <div class="order-item-total">
                                                <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card order-summary">
                        <div class="card-body">
                            <h5 class="card-title">Tổng cộng</h5>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Tạm tính:</span>
                                <span><?php echo number_format($cart->getCartTotal(), 0, ',', '.'); ?>đ</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2 points-discount" style="display: none;">
                                <span>Điểm tích lũy:</span>
                                <span class="text-success">-<span class="points-amount">0</span>đ</span>
                            </div>

                            <?php if (isset($voucher)): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Mã giảm giá (<?php echo $voucher['code']; ?>):</span>
                                    <span class="text-success">
                                        -<?php echo number_format($voucher['discount'], 0, ',', '.'); ?>đ
                                    </span>
                                </div>
                            <?php endif; ?>

                            <hr>

                            <div class="d-flex justify-content-between mb-3">
                                <strong>Tổng thanh toán:</strong>
                                <strong class="order-total">
                                    <?php echo number_format($cart->getCartTotal(), 0, ',', '.'); ?>đ
                                </strong>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" <?php echo empty($addresses) ? 'disabled' : ''; ?>>
                                    Đặt hàng
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <a href="/healthy/views/cart/index.php" class="text-decoration-none">
                                    <i class="bi bi-arrow-left"></i> Quay lại giỏ hàng
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function useMaxPoints() {
            $('#pointsToUse').val(<?php echo $maxPointsToUse; ?>);
            updateTotal();
        }

        function updateTotal() {
            const points = parseInt($('#pointsToUse').val()) || 0;
            const pointsDiscount = points * 1000;
            const subtotal = <?php echo $cart->getCartTotal(); ?>;
            const voucherDiscount = <?php echo isset($voucher) ? $voucher['discount'] : 0; ?>;

            if (points > 0) {
                $('.points-discount').show();
                $('.points-amount').text(pointsDiscount.toLocaleString('vi-VN'));
            } else {
                $('.points-discount').hide();
            }

            const total = subtotal - pointsDiscount - voucherDiscount;
            $('.order-total').text(total.toLocaleString('vi-VN') + 'đ');
        }

        $('#pointsToUse').on('input', function() {
            let points = parseInt($(this).val()) || 0;
            const max = parseInt($(this).attr('max'));

            if (points < 0) points = 0;
            if (points > max) points = max;

            $(this).val(points);
            updateTotal();
        });

        $('input[name="payment_method"]').on('change', function() {
            if ($(this).val() === 'QR') {
                $('#qrCodeSection').slideDown();
            } else {
                $('#qrCodeSection').slideUp();
            }
        });

        $('#checkoutForm').on('submit', function(e) {
            e.preventDefault();

            if (!$('input[name="shipping_address"]:checked').val()) {
                alert('Vui lòng chọn địa chỉ giao hàng');
                return;
            }

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        window.location.href = '/healthy/views/orders/success.php?id=' + response.order_id;
                    } else {
                        alert(response.message || 'Có lỗi xảy ra, vui lòng thử lại');
                    }
                },
                error: function() {
                    alert('Có lỗi xảy ra, vui lòng thử lại');
                }
            });
        });
    </script>
</body>
</html>
