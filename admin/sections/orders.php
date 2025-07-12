<?php
require_once __DIR__ . '/../../config/OrderAdmin.php';

$orderAdmin = new OrderAdmin();

// Xử lý các filter từ GET parameters
$filters = [];
if (!empty($_GET['order_id'])) {
    $filters['id'] = $_GET['order_id'];
}
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['payment_status'])) {
    $filters['payment_status'] = $_GET['payment_status'];
}
if (!empty($_GET['payment_method'])) {
    $filters['payment_method'] = $_GET['payment_method'];
}
if (!empty($_GET['user_search'])) {
    $filters['user'] = $_GET['user_search'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Lấy trang hiện tại
$page = isset($_GET['page_number']) ? (int)$_GET['page_number'] : 1;
$perPage = 10;

// Lấy danh sách đơn hàng
$result = $orderAdmin->getOrders($filters, $page, $perPage);
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Quản lý đơn hàng</h2>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="section" value="orders">

                <div class="col-md-2">
                    <label class="form-label">Mã đơn hàng</label>
                    <input type="text" class="form-control" name="order_id" value="<?php echo $_GET['order_id'] ?? ''; ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Trạng thái đơn hàng</label>
                    <select class="form-select" name="status">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="processing" <?php echo (isset($_GET['status']) && $_GET['status'] === 'processing') ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="shipping" <?php echo (isset($_GET['status']) && $_GET['status'] === 'shipping') ? 'selected' : ''; ?>>Đang giao</option>
                        <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Trạng thái thanh toán</label>
                    <select class="form-select" name="payment_status">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'pending') ? 'selected' : ''; ?>>Chưa thanh toán</option>
                        <option value="paid" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'paid') ? 'selected' : ''; ?>>Đã thanh toán</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tìm khách hàng</label>
                    <input type="text" class="form-control" name="user_search" value="<?php echo $_GET['user_search'] ?? ''; ?>" placeholder="Tên/Email/SĐT">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <a href="?page=admin&section=orders" class="btn btn-secondary">Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>PT thanh toán</th>
                            <th>Trạng thái ĐH</th>
                            <th>Trạng thái TT</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['orders'] as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['fullname'] ?? 'N/A'); ?><br>
                                <small><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo number_format($order['total_amount']); ?>đ</td>
                            <td><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
                            <td>
                                <select class="form-select form-select-sm order-status"
                                        data-order-id="<?php echo $order['id']; ?>"
                                        <?php echo $order['order_status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                    <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                    <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                    <option value="shipping" <?php echo $order['order_status'] === 'shipping' ? 'selected' : ''; ?>>Đang giao</option>
                                    <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                    <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm payment-status"
                                        data-order-id="<?php echo $order['id']; ?>"
                                        <?php echo $order['order_status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                    <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                                    <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                </select>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-order" data-order-id="<?php echo $order['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($result['totalPages'] > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=admin&section=orders&page_number=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý cập nhật trạng thái đơn hàng
    document.querySelectorAll('.order-status').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const status = this.value;

            if (confirm('Bạn có chắc muốn cập nhật trạng thái đơn hàng?')) {
                fetch('/healthy/admin/api/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        order_status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Cập nhật trạng thái thành công');
                        if (status === 'cancelled') {
                            this.disabled = true;
                            const paymentSelect = document.querySelector(`.payment-status[data-order-id="${orderId}"]`);
                            if (paymentSelect) {
                                paymentSelect.disabled = true;
                            }
                        }
                    } else {
                        alert('Có lỗi xảy ra: ' + data.message);
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật trạng thái');
                });
            } else {
                // Restore previous value if cancelled
                this.value = this.getAttribute('data-previous-value');
            }
        });

        // Store the initial value
        select.setAttribute('data-previous-value', select.value);
    });

    // Xử lý cập nhật trạng thái thanh toán
    document.querySelectorAll('.payment-status').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const status = this.value;

            if (confirm('Bạn có chắc muốn cập nhật trạng thái thanh toán?')) {
                fetch('/healthy/admin/api/update_payment_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Cập nhật trạng thái thanh toán thành công');
                    } else {
                        alert('Có lỗi xảy ra: ' + data.message);
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật trạng thái thanh toán');
                });
            } else {
                // Restore previous value if cancelled
                this.value = this.getAttribute('data-previous-value');
            }
        });

        // Store the initial value
        select.setAttribute('data-previous-value', select.value);
    });

    // Xử lý xem chi tiết đơn hàng
    document.querySelectorAll('.view-order').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const modal = document.getElementById('orderDetailModal');

            // Load order details
            fetch(`/healthy/admin/api/get_order_details.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('API Response:', data); // Debug log
                    if (data.success) {
                        const modalBody = modal.querySelector('.modal-body');
                        try {
                            modalBody.innerHTML = generateOrderDetailHTML(data.order);
                            new bootstrap.Modal(modal).show();
                        } catch (error) {
                            console.error('Error generating HTML:', error);
                            alert('Có lỗi xảy ra khi hiển thị thông tin đơn hàng: ' + error.message);
                        }
                    } else {
                        alert('Có lỗi xảy ra khi tải thông tin đơn hàng: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi tải thông tin đơn hàng: ' + error.message);
                });
        });
    });

    function generateOrderDetailHTML(order) {
        // Đảm bảo các thuộc tính có giá trị mặc định
        const items = order.items || [];
        const fullname = order.fullname || order.customer_name || 'N/A';
        const email = order.email || 'N/A';
        const phone = order.phone || order.customer_phone || 'N/A';

        return `
            <div class="row">
                <div class="col-md-6">
                    <h6>Thông tin khách hàng</h6>
                    <p>Họ tên: ${fullname}<br>
                       Email: ${email}<br>
                       SĐT: ${phone}</p>
                </div>
                <div class="col-md-6">
                    <h6>Thông tin đơn hàng</h6>
                    <p>Mã đơn: #${order.id}<br>
                       Ngày đặt: ${new Date(order.created_at).toLocaleString('vi-VN')}<br>
                       Trạng thái: ${getOrderStatusText(order.order_status)}</p>
                </div>
            </div>

            <h6>Chi tiết đơn hàng</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.length > 0 ? items.map(item => `
                            <tr>
                                <td>${item.name || item.item_name || 'N/A'}</td>
                                <td>${item.quantity || 0}</td>
                                <td>${formatPrice(item.price || 0)}đ</td>
                                <td>${formatPrice((item.quantity || 0) * (item.price || 0))}đ</td>
                            </tr>
                        `).join('') : `
                            <tr>
                                <td colspan="4" class="text-center">Không có sản phẩm nào</td>
                            </tr>
                        `}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end">Tạm tính:</td>
                            <td>${formatPrice(order.subtotal || 0)}đ</td>
                        </tr>
                        ${order.voucher_code ? `
                            <tr>
                                <td colspan="3" class="text-end">Mã giảm giá (${order.voucher_code}):</td>
                                <td>-${formatPrice(order.discount || 0)}đ</td>
                            </tr>
                        ` : ''}
                        ${(order.points_used || 0) > 0 ? `
                            <tr>
                                <td colspan="3" class="text-end">Điểm sử dụng (${order.points_used} điểm):</td>
                                <td>-${formatPrice(order.points_value || 0)}đ</td>
                            </tr>
                        ` : ''}
                        <tr>
                            <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                            <td><strong>${formatPrice(order.total_amount || 0)}đ</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>Địa chỉ giao hàng</h6>
                    <p>${order.shipping_address || 'Chưa có địa chỉ'}</p>
                </div>
                <div class="col-md-6">
                    <h6>Ghi chú</h6>
                    <p>${order.notes || 'Không có ghi chú'}</p>
                </div>
            </div>
        `;
    }

    function getOrderStatusText(status) {
        const statusMap = {
            'pending': 'Chờ xử lý',
            'processing': 'Đang xử lý',
            'shipping': 'Đang giao',
            'completed': 'Hoàn thành',
            'cancelled': 'Đã hủy'
        };
        return statusMap[status] || status;
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price);
    }
});
</script>
