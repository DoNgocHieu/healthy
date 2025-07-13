<?php
require_once __DIR__ . '/../../config/OrderAdmin.php';


$orderAdmin = new OrderAdmin();

// Xử lý chọn tháng/năm
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Tính ngày đầu/tháng và cuối/tháng
$dateFrom = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$dateTo = date('Y-m-t', strtotime($dateFrom));
$stats = $orderAdmin->getOrderStats($dateFrom, $dateTo);

// Helper function để format số an toàn
function formatNumber($value) {
    return number_format($value ?? 0);
}
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Tổng quan</h2>

    <!-- Form chọn tháng/năm -->
    <form id="filterForm" class="mb-4 d-flex align-items-center gap-2" style="gap:1rem;" onsubmit="return false;">
        <label for="month" class="form-label mb-0">Tháng:</label>
        <select name="month" id="month" class="form-select" style="width:100px;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>><?= $m ?></option>
            <?php endfor; ?>
        </select>
        <label for="year" class="form-label mb-0">Năm:</label>
        <select name="year" id="year" class="form-select" style="width:100px;">
            <?php $currentYear = intval(date('Y')); for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <script>
    function loadStats() {
        const month = document.getElementById('month').value;
        const year = document.getElementById('year').value;
        fetch(`/healthy/api/get_order_stats.php?month=${month}&year=${year}`)
            .then(res => res.json())
            .then(stats => {
                document.querySelector('.h5.mb-0.font-weight-bold.text-gray-800').textContent = stats.total_orders;
                document.querySelectorAll('.h5.mb-0.font-weight-bold.text-gray-800')[1].textContent = Number(stats.total_revenue).toLocaleString() + 'đ';
                document.querySelectorAll('.h5.mb-0.font-weight-bold.text-gray-800')[2].textContent = Number(stats.paid_amount).toLocaleString() + 'đ';
                document.querySelectorAll('.h5.mb-0.font-weight-bold.text-gray-800')[3].textContent = stats.unique_customers;

                // Trạng thái đơn hàng
                document.querySelector('p.card-text.display-4').textContent = stats.pending_orders;
                document.querySelectorAll('p.card-text.display-4')[1].textContent = stats.processing_orders;
                document.querySelectorAll('p.card-text.display-4')[2].textContent = stats.shipping_orders;
                document.querySelectorAll('p.card-text.display-4')[3].textContent = stats.completed_orders;
                document.querySelectorAll('p.card-text.display-4')[4].textContent = stats.cancelled_orders;
            });
    }
    document.getElementById('month').addEventListener('change', loadStats);
    document.getElementById('year').addEventListener('change', loadStats);
    </script>

    <script>
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const month = document.getElementById('month').value;
        const year = document.getElementById('year').value;
        fetch(`/healthy/api/get_order_stats.php?month=${month}&year=${year}`)
            .then(res => res.json())
            .then(stats => {
                document.querySelector('.h5.mb-0.font-weight-bold.text-gray-800').textContent = stats.total_orders;
                document.querySelectorAll('.h5.mb-0.font-weight-bold.text-gray-800')[1].textContent = Number(stats.total_revenue).toLocaleString() + 'đ';
                document.querySelectorAll('.h5.mb-0.font-weight-bold.text-gray-800')[2].textContent = Number(stats.paid_amount).toLocaleString() + 'đ';
                document.querySelectorAll('.h5.mb-0.font-weight-bold.text-gray-800')[3].textContent = stats.unique_customers;

                // Trạng thái đơn hàng
                document.querySelector('p.card-text.display-4').textContent = stats.pending_orders;
                document.querySelectorAll('p.card-text.display-4')[1].textContent = stats.processing_orders;
                document.querySelectorAll('p.card-text.display-4')[2].textContent = stats.shipping_orders;
                document.querySelectorAll('p.card-text.display-4')[3].textContent = stats.completed_orders;
                document.querySelectorAll('p.card-text.display-4')[4].textContent = stats.cancelled_orders;
            });
    });
    </script>

    <div class="row">
        <!-- Tổng đơn hàng -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng đơn hàng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatNumber($stats['total_orders']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cart3 fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doanh thu -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Doanh thu</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatNumber($stats['total_revenue']); ?>đ</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đã thanh toán -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Đã thanh toán</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatNumber($stats['paid_amount']); ?>đ</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cash fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Khách hàng -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Khách hàng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatNumber($stats['unique_customers']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Trạng thái đơn hàng -->
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Trạng thái đơn hàng</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Chờ xử lý -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Chờ xử lý</h5>
                                    <p class="card-text display-4"><?php echo $stats['pending_orders']; ?></p>
                                    <a href="?page=admin&section=orders&status=pending" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>

                        <!-- Đang xử lý -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Đang xử lý</h5>
                                    <p class="card-text display-4"><?php echo $stats['processing_orders']; ?></p>
                                    <a href="?page=admin&section=orders&status=processing" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>

                        <!-- Đang giao -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Đang giao</h5>
                                    <p class="card-text display-4"><?php echo $stats['shipping_orders']; ?></p>
                                    <a href="?page=admin&section=orders&status=shipping" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>

                        <!-- Hoàn thành -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Hoàn thành</h5>
                                    <p class="card-text display-4"><?php echo $stats['completed_orders']; ?></p>
                                    <a href="?page=admin&section=orders&status=completed" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>

                        <!-- Đã hủy -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Đã hủy</h5>
                                    <p class="card-text display-4"><?php echo $stats['cancelled_orders']; ?></p>
                                    <a href="?page=admin&section=orders&status=cancelled" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
