<?php
require_once __DIR__ . '/../config/Auth.php';
require_once __DIR__ . '/../config/Order.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: /healthy/views/login.php');
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$order = new Order($userId);

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$orders = $order->getUserOrders($page);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .order-card {
            transition: transform 0.2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
        }

        .order-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Đơn hàng của tôi</h1>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x display-1 text-muted"></i>
                <h3 class="mt-3">Bạn chưa có đơn hàng nào</h3>
                <p class="text-muted">Hãy đặt món ăn đầu tiên của bạn</p>
                <a href="/healthy/views/catalog/index.php" class="btn btn-primary">
                    Xem thực đơn
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($orders as $orderItem): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card order-card">
                            <div class="card-body">
                                <?php
                                $statusClass = [
                                    'pending' => 'bg-warning',
                                    'processing' => 'bg-primary',
                                    'shipping' => 'bg-info',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger'
                                ];
                                $statusText = [
                                    'pending' => 'Chờ xử lý',
                                    'processing' => 'Đang xử lý',
                                    'shipping' => 'Đang giao hàng',
                                    'completed' => 'Đã hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];
                                ?>
                                <span class="badge <?php echo $statusClass[$orderItem['order_status']]; ?> order-status">
                                    <?php echo $statusText[$orderItem['order_status']]; ?>
                                </span>

                                <h5 class="card-title">
                                    Đơn hàng #<?php echo $orderItem['id']; ?>
                                </h5>

                                <div class="text-muted mb-3">
                                    Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($orderItem['created_at'])); ?>
                                </div>

                                <div class="mb-3">
                                    <strong>Tổng số món:</strong> <?php echo $orderItem['total_items']; ?> món<br>
                                    <strong>Tổng tiền:</strong> <?php echo number_format($orderItem['total_amount'], 0, ',', '.'); ?>đ
                                </div>

                                <?php if ($orderItem['voucher_code']): ?>
                                    <div class="text-success small mb-3">
                                        Đã áp dụng mã giảm giá: <?php echo $orderItem['voucher_code']; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($orderItem['points_earned']): ?>
                                    <div class="text-success small mb-3">
                                        Điểm tích lũy: +<?php echo $orderItem['points_earned']; ?> điểm
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="/healthy/views/orders/detail.php?id=<?php echo $orderItem['id']; ?>"
                                       class="btn btn-primary btn-sm">
                                        Xem chi tiết
                                    </a>

                                    <?php if ($orderItem['order_status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="cancelOrder(<?php echo $orderItem['id']; ?>)">
                                            Hủy đơn hàng
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($page > 1 || count($orders) === 10): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item active">
                            <span class="page-link"><?php echo $page; ?></span>
                        </li>

                        <?php if (count($orders) === 10): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
                $.ajax({
                    url: '/healthy/api/orders/cancel.php',
                    method: 'POST',
                    data: { order_id: orderId },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Có lỗi xảy ra, vui lòng thử lại');
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra, vui lòng thử lại');
                    }
                });
            }
        }
    </script>
</body>
</html>
