<?php
require_once '../config/Auth.php';
require_once '../config/OrderAdmin.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$orderAdmin = new OrderAdmin();

// Get filters from query params
$filters = [
    'id' => $_GET['id'] ?? null,
    'status' => $_GET['status'] ?? null,
    'payment_status' => $_GET['payment_status'] ?? null,
    'payment_method' => $_GET['payment_method'] ?? null,
    'user' => $_GET['user'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

// Get orders with pagination
$result = $orderAdmin->getOrders($filters, $page, $perPage);
$orders = $result['orders'];
$totalPages = $result['totalPages'];

// Get order stats
$stats = $orderAdmin->getOrderStats($filters['date_from'], $filters['date_to']);

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    if ($_POST['action'] === 'update_status') {
        $response = $orderAdmin->updateOrderStatus($_POST['order_id'], $_POST['status']);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($_POST['action'] === 'update_payment') {
        $response = $orderAdmin->updatePaymentStatus($_POST['order_id'], $_POST['status']);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($_POST['action'] === 'get_order') {
        $order = $orderAdmin->getOrder($_POST['order_id']);
        header('Content-Type: application/json');
        echo json_encode($order);
        exit;
    }
}

$title = "Quản lý đơn hàng";
require_once 'layout.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Tổng đơn hàng</p>
                                <h5 class="font-weight-bolder"><?= number_format($stats['total_orders']) ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                <i class="ni ni-cart text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Doanh thu</p>
                                <h5 class="font-weight-bolder"><?= number_format($stats['total_revenue']) ?>đ</h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                <i class="ni ni-money-coins text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Đã thanh toán</p>
                                <h5 class="font-weight-bolder"><?= number_format($stats['paid_amount']) ?>đ</h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle">
                                <i class="ni ni-credit-card text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Khách hàng</p>
                                <h5 class="font-weight-bolder"><?= number_format($stats['unique_customers']) ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                <i class="ni ni-single-02 text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header pb-0">
            <h6>Bộ lọc</h6>
        </div>
        <div class="card-body">
            <form id="filter-form" method="GET">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Mã đơn hàng</label>
                            <input type="text" name="id" class="form-control" value="<?= htmlspecialchars($filters['id'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Trạng thái đơn hàng</label>
                            <select name="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="pending" <?= ($filters['status'] === 'pending') ? 'selected' : '' ?>>Chờ xử lý</option>
                                <option value="processing" <?= ($filters['status'] === 'processing') ? 'selected' : '' ?>>Đang xử lý</option>
                                <option value="shipping" <?= ($filters['status'] === 'shipping') ? 'selected' : '' ?>>Đang giao</option>
                                <option value="completed" <?= ($filters['status'] === 'completed') ? 'selected' : '' ?>>Hoàn thành</option>
                                <option value="cancelled" <?= ($filters['status'] === 'cancelled') ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Trạng thái thanh toán</label>
                            <select name="payment_status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="pending" <?= ($filters['payment_status'] === 'pending') ? 'selected' : '' ?>>Chưa thanh toán</option>
                                <option value="paid" <?= ($filters['payment_status'] === 'paid') ? 'selected' : '' ?>>Đã thanh toán</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Phương thức thanh toán</label>
                            <select name="payment_method" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="COD" <?= ($filters['payment_method'] === 'COD') ? 'selected' : '' ?>>COD</option>
                                <option value="bank" <?= ($filters['payment_method'] === 'bank') ? 'selected' : '' ?>>Chuyển khoản</option>
                                <option value="momo" <?= ($filters['payment_method'] === 'momo') ? 'selected' : '' ?>>Momo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Từ ngày</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Đến ngày</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-control-label">Tìm kiếm khách hàng</label>
                            <input type="text" name="user" class="form-control" placeholder="Tên, email hoặc số điện thoại" value="<?= htmlspecialchars($filters['user'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-3">Lọc</button>
                        <a href="orders.php" class="btn btn-outline-secondary mb-3 ms-2">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Mã đơn hàng</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Khách hàng</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Số món</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tổng tiền</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Trạng thái</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thanh toán</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Ngày tạo</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <div class="d-flex px-2">
                                <div class="my-auto">
                                    <h6 class="mb-0 text-xs">#<?= $order['id'] ?></h6>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($order['fullname']) ?></p>
                            <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($order['phone']) ?></p>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= $order['total_items'] ?></span>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= number_format($order['total_amount']) ?>đ</span>
                        </td>
                        <td>
                            <select class="form-control form-control-sm order-status" data-order-id="<?= $order['id'] ?>">
                                <option value="pending" <?= ($order['order_status'] === 'pending') ? 'selected' : '' ?>>Chờ xử lý</option>
                                <option value="processing" <?= ($order['order_status'] === 'processing') ? 'selected' : '' ?>>Đang xử lý</option>
                                <option value="shipping" <?= ($order['order_status'] === 'shipping') ? 'selected' : '' ?>>Đang giao</option>
                                <option value="completed" <?= ($order['order_status'] === 'completed') ? 'selected' : '' ?>>Hoàn thành</option>
                                <option value="cancelled" <?= ($order['order_status'] === 'cancelled') ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control form-control-sm payment-status" data-order-id="<?= $order['id'] ?>">
                                <option value="pending" <?= ($order['payment_status'] === 'pending') ? 'selected' : '' ?>>Chưa thanh toán</option>
                                <option value="paid" <?= ($order['payment_status'] === 'paid') ? 'selected' : '' ?>>Đã thanh toán</option>
                            </select>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold">
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-link text-secondary mb-0 view-order" data-order-id="<?= $order['id'] ?>">
                                <i class="fa fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer d-flex justify-content-center">
            <ul class="pagination pagination-primary m-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Chi tiết đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="order-details"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update order status
    $('.order-status').change(function() {
        const orderId = $(this).data('order-id');
        const status = $(this).val();

        if (!confirm('Bạn có chắc muốn cập nhật trạng thái đơn hàng?')) {
            return;
        }

        $.ajax({
            url: 'orders.php',
            type: 'POST',
            data: {
                action: 'update_status',
                order_id: orderId,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Update payment status
    $('.payment-status').change(function() {
        const orderId = $(this).data('order-id');
        const status = $(this).val();

        if (!confirm('Bạn có chắc muốn cập nhật trạng thái thanh toán?')) {
            return;
        }

        $.ajax({
            url: 'orders.php',
            type: 'POST',
            data: {
                action: 'update_payment',
                order_id: orderId,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // View order details
    $('.view-order').click(function() {
        const orderId = $(this).data('order-id');

        $.ajax({
            url: 'orders.php',
            type: 'POST',
            data: {
                action: 'get_order',
                order_id: orderId
            },
            success: function(order) {
                let html = `
                    <div class="mb-4">
                        <h6 class="text-sm">Thông tin khách hàng</h6>
                        <p class="text-xs mb-1">Họ tên: ${order.fullname}</p>
                        <p class="text-xs mb-1">Email: ${order.email}</p>
                        <p class="text-xs mb-1">Số điện thoại: ${order.phone}</p>
                        <p class="text-xs mb-1">Địa chỉ: ${order.address}</p>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-sm">Thông tin đơn hàng</h6>
                        <p class="text-xs mb-1">Mã đơn hàng: #${order.id}</p>
                        <p class="text-xs mb-1">Ngày tạo: ${new Date(order.created_at).toLocaleString()}</p>
                        <p class="text-xs mb-1">Phương thức thanh toán: ${order.payment_method}</p>
                        ${order.voucher_code ? `<p class="text-xs mb-1">Mã giảm giá: ${order.voucher_code}</p>` : ''}
                        <p class="text-xs mb-1">Điểm sử dụng: ${order.points_used || 0}</p>
                        <p class="text-xs mb-1">Điểm nhận được: ${order.points_earned || 0}</p>
                    </div>

                    <div>
                        <h6 class="text-sm">Chi tiết món ăn</h6>
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Món ăn</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Số lượng</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Đơn giá</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                order.items.forEach(item => {
                    html += `
                        <tr>
                            <td>
                                <div class="d-flex px-2 py-1">
                                    <div>
                                        <img src="${item.image_url}" class="avatar avatar-sm me-3" alt="${item.name}">
                                    </div>
                                    <div class="d-flex flex-column justify-content-center">
                                        <h6 class="mb-0 text-xs">${item.name}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="text-xs font-weight-bold mb-0">${item.quantity}</p>
                            </td>
                            <td>
                                <p class="text-xs font-weight-bold mb-0">${Number(item.price).toLocaleString()}đ</p>
                            </td>
                            <td>
                                <p class="text-xs font-weight-bold mb-0">${(item.quantity * item.price).toLocaleString()}đ</p>
                            </td>
                        </tr>
                    `;
                });

                html += `
                                </tbody>
                            </table>
                        </div>

                        <div class="row justify-content-end mt-4">
                            <div class="col-md-4">
                                <p class="text-xs mb-1">Tổng tiền hàng: ${Number(order.subtotal).toLocaleString()}đ</p>
                                ${order.voucher_code ? `<p class="text-xs mb-1">Giảm giá: ${Number(order.discount).toLocaleString()}đ</p>` : ''}
                                ${order.points_used ? `<p class="text-xs mb-1">Điểm sử dụng: ${Number(order.points_amount).toLocaleString()}đ</p>` : ''}
                                <p class="text-xs font-weight-bold">Tổng thanh toán: ${Number(order.total_amount).toLocaleString()}đ</p>
                            </div>
                        </div>
                    </div>
                `;

                $('.order-details').html(html);
                $('#orderModal').modal('show');
            }
        });
    });
});
</script>
