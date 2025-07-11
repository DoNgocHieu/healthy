<?php
require_once __DIR__ . '/../config/Auth.php';
require_once __DIR__ . '/../config/Order.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: /healthy/views/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: /healthy/views/orders/index.php');
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$orderId = intval($_GET['id']);

$order = new Order($userId);
$orderDetails = $order->getOrder($orderId);

if (!$orderDetails) {
    header('Location: /healthy/views/orders/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .success-icon {
            color: #28a745;
            font-size: 5rem;
        }

        .order-summary {
            background-color: #f8f9fa;
            border-radius: 1rem;
            padding: 2rem;
        }

        .qr-instructions {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <div class="text-center mb-4">
            <i class="bi bi-check-circle success-icon"></i>
            <h1 class="mt-3">Đặt hàng thành công!</h1>
            <p class="lead">
                Cảm ơn bạn đã đặt hàng. Mã đơn hàng của bạn là <strong>#<?php echo $orderId; ?></strong>
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="order-summary">
                    <h4 class="mb-4">Thông tin đơn hàng</h4>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Địa chỉ giao hàng:</strong>
                        </div>
                        <div class="col-sm-8">
                            <?php echo nl2br(htmlspecialchars($orderDetails['shipping_address'])); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Phương thức thanh toán:</strong>
                        </div>
                        <div class="col-sm-8">
                            <?php if ($orderDetails['payment_method'] === 'COD'): ?>
                                Thanh toán khi nhận hàng (COD)
                            <?php else: ?>
                                Thanh toán QR Code
                                <?php if ($orderDetails['payment_status'] === 'pending'): ?>
                                    <div class="qr-instructions">
                                        <p class="mb-2">Vui lòng quét mã QR để hoàn tất thanh toán:</p>
                                        <img src="/healthy/img/qr-payment.png" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                        <p class="text-muted small mt-2">
                                            Đơn hàng của bạn sẽ được xử lý sau khi thanh toán thành công.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Trạng thái đơn hàng:</strong>
                        </div>
                        <div class="col-sm-8">
                            <?php
                            $statusClass = [
                                'pending' => 'text-warning',
                                'processing' => 'text-primary',
                                'shipping' => 'text-info',
                                'completed' => 'text-success',
                                'cancelled' => 'text-danger'
                            ];
                            $statusText = [
                                'pending' => 'Chờ xử lý',
                                'processing' => 'Đang xử lý',
                                'shipping' => 'Đang giao hàng',
                                'completed' => 'Đã hoàn thành',
                                'cancelled' => 'Đã hủy'
                            ];
                            ?>
                            <span class="<?php echo $statusClass[$orderDetails['order_status']]; ?>">
                                <?php echo $statusText[$orderDetails['order_status']]; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($orderDetails['points_earned']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Điểm tích lũy:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span class="text-success">+<?php echo $orderDetails['points_earned']; ?> điểm</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h5 class="mt-4 mb-3">Chi tiết đơn hàng</h5>

                    <?php foreach ($orderDetails['items'] as $item): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="/healthy/img/<?php echo htmlspecialchars($item['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">

                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <div class="text-muted">
                                    <?php echo number_format($item['price'], 0, ',', '.'); ?>đ x
                                    <?php echo $item['quantity']; ?>
                                </div>
                            </div>

                            <div class="text-end">
                                <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <hr>

                    <div class="row text-end">
                        <div class="col">
                            <div class="mb-2">
                                Tạm tính: <?php echo number_format($orderDetails['total_amount'], 0, ',', '.'); ?>đ
                            </div>

                            <?php if ($orderDetails['points_used']): ?>
                                <div class="mb-2 text-success">
                                    Điểm sử dụng: -<?php echo number_format($orderDetails['points_used'] * 1000, 0, ',', '.'); ?>đ
                                </div>
                            <?php endif; ?>

                            <?php if ($orderDetails['voucher_code']): ?>
                                <div class="mb-2 text-success">
                                    Mã giảm giá (<?php echo $orderDetails['voucher_code']; ?>):
                                    <?php if ($orderDetails['discount_type'] === 'percentage'): ?>
                                        -<?php echo $orderDetails['discount_value']; ?>%
                                    <?php else: ?>
                                        -<?php echo number_format($orderDetails['discount_value'], 0, ',', '.'); ?>đ
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="h5">
                                Tổng cộng: <?php echo number_format($orderDetails['total_amount'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="/healthy/views/orders/index.php" class="btn btn-primary">
                        Xem đơn hàng của tôi
                    </a>
                    <a href="/healthy/views/catalog/index.php" class="btn btn-outline-primary ms-2">
                        Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
