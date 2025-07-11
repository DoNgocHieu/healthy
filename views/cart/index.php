<?php
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Cart.php';
require_once __DIR__ . '/../../config/UserProfile.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: /healthy/views/auth/login.php');
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$cart = new Cart($userId);
$profile = new UserProfile();

$items = $cart->getItems();
$invalidItems = $cart->validateStock();
$addresses = $profile->getUserAddresses($userId);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/healthy/css/cart.css" rel="stylesheet">
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Giỏ hàng</h1>

        <?php if (!empty($invalidItems)): ?>
            <div class="alert alert-warning">
                <h5>Một số món ăn đã được cập nhật do thay đổi tồn kho:</h5>
                <ul class="mb-0">
                    <?php foreach ($invalidItems as $item): ?>
                        <li>
                            <?php echo htmlspecialchars($item['name']); ?>:
                            Chỉ còn <?php echo $item['available']; ?> phần
                            (bạn yêu cầu <?php echo $item['requested']; ?> phần)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted"></i>
                <h3 class="mt-3">Giỏ hàng trống</h3>
                <p class="text-muted">Hãy thêm món ăn vào giỏ hàng của bạn</p>
                <a href="/healthy/views/catalog/index.php" class="btn btn-primary">
                    Xem thực đơn
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart items -->
                <div class="col-lg-8">
                    <div class="cart-items card">
                        <div class="card-body">
                            <?php foreach ($items as $item): ?>
                                <div class="cart-item" data-id="<?php echo $item['item_id']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <img src="/healthy/img/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                 class="cart-item-image"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>

                                        <div class="col">
                                            <h5 class="cart-item-title">
                                                <a href="/healthy/views/catalog/item.php?id=<?php echo $item['item_id']; ?>">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h5>

                                            <div class="cart-item-price">
                                                <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                                            </div>
                                        </div>

                                        <div class="col-auto">
                                            <div class="quantity-input">
                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                        onclick="updateQuantity(<?php echo $item['item_id']; ?>, -1)">
                                                    -
                                                </button>
                                                <input type="number" class="form-control form-control-sm"
                                                       value="<?php echo $item['quantity']; ?>" min="1"
                                                       max="<?php echo $item['stock_quantity']; ?>"
                                                       onchange="updateQuantity(<?php echo $item['item_id']; ?>, this.value)">
                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                        onclick="updateQuantity(<?php echo $item['item_id']; ?>, 1)">
                                                    +
                                                </button>
                                            </div>

                                            <div class="text-muted small">
                                                Còn <?php echo $item['stock_quantity']; ?> phần
                                            </div>
                                        </div>

                                        <div class="col-auto text-end">
                                            <div class="cart-item-total">
                                                <?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?>đ
                                            </div>

                                            <button type="button" class="btn btn-link text-danger btn-sm"
                                                    onclick="removeItem(<?php echo $item['item_id']; ?>)">
                                                <i class="bi bi-trash"></i> Xóa
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Cart summary -->
                <div class="col-lg-4">
                    <div class="cart-summary card">
                        <div class="card-body">
                            <h5 class="card-title">Tổng cộng</h5>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Tạm tính:</span>
                                <span class="cart-subtotal">
                                    <?php echo number_format($cart->getCartTotal(), 0, ',', '.'); ?>đ
                                </span>
                            </div>

                            <div class="voucher-input mb-3">
                                <label for="voucher" class="form-label">Mã giảm giá:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="voucher"
                                           placeholder="Nhập mã giảm giá">
                                    <button class="btn btn-outline-secondary" type="button" onclick="applyVoucher()">
                                        Áp dụng
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="/healthy/views/checkout/index.php" class="btn btn-primary btn-lg">
                                    Đặt hàng (<?php echo number_format($cart->getCartTotal(), 0, ',', '.'); ?>đ)
                                </a>
                                <a href="/healthy/views/catalog/index.php" class="btn btn-outline-secondary">
                                    Tiếp tục mua hàng
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function updateQuantity(itemId, quantity) {
            if (typeof quantity !== 'number') {
                quantity = parseInt(quantity);
            }

            const currentQty = parseInt($(`[data-id="${itemId}"] input`).val());
            if (typeof quantity === 'number' && quantity !== 1 && quantity !== -1) {
                // Direct input
                newQty = quantity;
            } else {
                // Button click
                newQty = currentQty + quantity;
            }

            if (newQty < 1) newQty = 1;
            const maxQty = parseInt($(`[data-id="${itemId}"] input`).attr('max'));
            if (newQty > maxQty) newQty = maxQty;

            $.ajax({
                url: '/healthy/api/cart/update_quantity.php',
                method: 'POST',
                data: {
                    item_id: itemId,
                    quantity: newQty
                },
                success: function(response) {
                    if (response.success) {
                        $(`[data-id="${itemId}"] input`).val(response.quantity);
                        $(`[data-id="${itemId}"] .cart-item-total`).text(
                            formatPrice(response.item_total)
                        );
                        updateCartSummary(response.cart_total);
                        updateCartCount(response.cart_count);
                    } else {
                        showToast(response.message, 'danger');
                    }
                }
            });
        }

        function removeItem(itemId) {
            if (confirm('Bạn có chắc muốn xóa món này khỏi giỏ hàng?')) {
                $.ajax({
                    url: '/healthy/api/cart/remove_item.php',
                    method: 'POST',
                    data: { item_id: itemId },
                    success: function(response) {
                        if (response.success) {
                            $(`[data-id="${itemId}"]`).fadeOut(function() {
                                $(this).remove();
                                if ($('.cart-item').length === 0) {
                                    location.reload();
                                }
                            });
                            updateCartSummary(response.cart_total);
                            updateCartCount(response.cart_count);
                        }
                    }
                });
            }
        }

        function applyVoucher() {
            const code = $('#voucher').val();
            if (!code) return;

            $.ajax({
                url: '/healthy/api/cart/apply_voucher.php',
                method: 'POST',
                data: { code: code },
                success: function(response) {
                    if (response.success) {
                        showToast(response.message);
                        updateCartSummary(response.cart_total, response.discount);
                    } else {
                        showToast(response.message, 'danger');
                    }
                }
            });
        }

        function updateCartSummary(total, discount = 0) {
            $('.cart-subtotal').text(formatPrice(total));
            if (discount > 0) {
                $('.cart-discount').text(`-${formatPrice(discount)}`);
            }
            $('.btn-primary').text(`Đặt hàng (${formatPrice(total - discount)})`);
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(price).replace('₫', 'đ');
        }

        function showToast(message, type = 'success') {
            const toast = `
                <div class="toast-container position-fixed bottom-0 end-0 p-3">
                    <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">${message}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                    data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(toast);
            const toastEl = $('.toast').toast('show');
            toastEl.on('hidden.bs.toast', function() {
                $(this).parent().remove();
            });
        }
    </script>
</body>
</html>
