<?php
require_once __DIR__ . '/../config/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}

$db = getDbConnection();

// Get statistics
$stats = [
    'orders' => [
        'total' => 0,
        'today' => 0,
        'pending' => 0,
        'revenue' => 0
    ],
    'users' => [
        'total' => 0,
        'new' => 0
    ],
    'items' => [
        'total' => 0,
        'out_of_stock' => 0
    ],
    'reviews' => [
        'total' => 0,
        'average' => 0
    ]
];

// Orders statistics - temporary mock data until orders table is created
$orderStats = [
    'total' => 0,
    'today' => 0,
    'pending' => 0,
    'revenue' => 0
];
$stats['orders'] = $orderStats;

// Users statistics
$stmt = $db->query('
    SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new
    FROM users
');
$userStats = $stmt->fetch_assoc();
$stats['users'] = array_merge($stats['users'], $userStats);

// Items statistics
$stmt = $db->query('
    SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock
    FROM items
');
$itemStats = $stmt->fetch_assoc();
$stats['items'] = array_merge($stats['items'], $itemStats);

// Reviews statistics - temporary mock data until reviews table is created
$reviewStats = [
    'total' => 0,
    'average' => 0
];
$stats['reviews'] = array_merge($stats['reviews'], $reviewStats);

// Get recent orders - temporary empty data until orders table is created
$recentOrders = [];

// Get top selling items - show latest items for now
$topItems = [];
$stmt = $db->query('
    SELECT * FROM items
    ORDER BY id DESC
    LIMIT 5
');
while ($row = $stmt->fetch_assoc()) {
    $topItems[] = $row;
}

// Get latest reviews - temporary empty data until reviews table is created
$latestReviews = [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Healthy Food Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <link rel="stylesheet" href="/healthy/css/admin.css">
</head>
<body>
    <?php
    $content = '
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-cart3"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="/healthy/admin/orders/index.php">
                                            Xem chi tiết
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <h3 class="mb-2">' . number_format($stats['orders']['total']) . '</h3>
                        <div class="text-muted">Tổng đơn hàng</div>
                        <div class="mt-2 text-success">
                            <i class="bi bi-arrow-up"></i>
                            ' . number_format($stats['orders']['today']) . ' đơn hôm nay
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="/healthy/admin/reports/revenue.php">
                                            Xem báo cáo
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <h3 class="mb-2">' . number_format($stats['orders']['revenue']) . 'đ</h3>
                        <div class="text-muted">Doanh thu</div>
                        <div class="mt-2">
                            ' . number_format($stats['orders']['pending']) . ' đơn chờ xử lý
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="/healthy/admin/users/index.php">
                                            Xem danh sách
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <h3 class="mb-2">' . number_format($stats['users']['total']) . '</h3>
                        <div class="text-muted">Người dùng</div>
                        <div class="mt-2 text-success">
                            <i class="bi bi-arrow-up"></i>
                            ' . number_format($stats['users']['new']) . ' người dùng mới
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="stats-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-star"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="/healthy/admin/reviews/index.php">
                                            Xem đánh giá
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <h3 class="mb-2">' . number_format($stats['reviews']['total']) . '</h3>
                        <div class="text-muted">Đánh giá</div>
                        <div class="mt-2">
                            ' . number_format($stats['reviews']['average'], 1) . ' sao trung bình
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Orders -->
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Đơn hàng gần đây</h5>
                            <a href="/healthy/admin/orders/index.php" class="btn btn-primary btn-sm">
                                Xem tất cả
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>';
                                foreach ($recentOrders as $order) {
                                    $statusClass = [
                                        'pending' => 'status-pending',
                                        'processing' => 'status-processing',
                                        'shipping' => 'status-shipping',
                                        'completed' => 'status-completed',
                                        'cancelled' => 'status-cancelled'
                                    ];
                                    $statusText = [
                                        'pending' => 'Chờ xử lý',
                                        'processing' => 'Đang xử lý',
                                        'shipping' => 'Đang giao',
                                        'completed' => 'Hoàn thành',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    $content .= '
                                    <tr>
                                        <td>
                                            <a href="/healthy/admin/orders/detail.php?id=' . $order['id'] . '">
                                                #' . $order['id'] . '
                                            </a>
                                        </td>
                                        <td>' . htmlspecialchars($order['fullname']) . '</td>
                                        <td>' . number_format($order['total_amount']) . 'đ</td>
                                        <td>
                                            <span class="status-badge ' . $statusClass[$order['order_status']] . '">
                                                ' . $statusText[$order['order_status']] . '
                                            </span>
                                        </td>
                                        <td>' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</td>
                                    </tr>';
                                }
                                $content .= '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Items -->
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Món ăn bán chạy</h5>
                            <a href="/healthy/admin/items/index.php" class="btn btn-primary btn-sm">
                                Xem tất cả
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">';
                        foreach ($topItems as $item) {
                            $content .= '
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-center">
                                    <img src="/healthy/img/' . htmlspecialchars($item['image_url']) . '"
                                         alt="' . htmlspecialchars($item['name']) . '"
                                         class="me-3" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">

                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">
                                            <a href="/healthy/admin/items/edit.php?id=' . $item['id'] . '"
                                               class="text-decoration-none">
                                                ' . htmlspecialchars($item['name']) . '
                                            </a>
                                        </h6>
                                        <div class="text-muted small">
                                            ' . number_format($item['price']) . 'đ
                                        </div>
                                    </div>

                                    <div class="ms-3 text-end">
                                        <div class="text-muted small">Còn lại</div>
                                        <strong>' . $item['quantity'] . '</strong>
                                    </div>
                                </div>
                            </div>';
                        }
                        $content .= '
                        </div>
                    </div>
                </div>

                <!-- Latest Reviews -->
                <div class="card mt-4">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Đánh giá gần đây</h5>
                            <a href="/healthy/admin/reviews/index.php" class="btn btn-primary btn-sm">
                                Xem tất cả
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">';
                        foreach ($latestReviews as $review) {
                            $content .= '
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="rating-stars">';
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $review['rating']) {
                                            $content .= '<i class="bi bi-star-fill"></i>';
                                        } else {
                                            $content .= '<i class="bi bi-star-fill empty-stars"></i>';
                                        }
                                    }
                                    $content .= '
                                    </div>
                                    <small class="text-muted">
                                        ' . date('d/m/Y', strtotime($review['created_at'])) . '
                                    </small>
                                </div>

                                <div class="mb-1">
                                    <strong>' . htmlspecialchars($review['fullname']) . '</strong>
                                    đánh giá
                                    <a href="/healthy/admin/items/edit.php?id=' . $review['item_id'] . '">
                                        ' . htmlspecialchars($review['item_name']) . '
                                    </a>
                                </div>

                                <div class="text-muted small">
                                    ' . (mb_strlen($review['comment']) > 100 ?
                                        mb_substr($review['comment'], 0, 100) . '...' :
                                        $review['comment']) . '
                                </div>
                            </div>';
                        }
                        $content .= '
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-4 mt-4">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">Doanh thu theo thời gian</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">Đơn hàng theo trạng thái</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    echo $content;
    ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
                datasets: [{
                    label: 'Doanh thu',
                    data: [650, 590, 800, 810, 560, 550, 700, 850, 900, 950, 1000, 1050],
                    borderColor: '#0d6efd',
                    tension: 0.4,
                    fill: {
                        target: 'origin',
                        above: 'rgba(13, 110, 253, 0.1)'
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + 'đ';
                            }
                        }
                    }
                }
            }
        });

        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Chờ xử lý', 'Đang xử lý', 'Đang giao', 'Hoàn thành', 'Đã hủy'],
                datasets: [{
                    data: [15, 20, 25, 30, 10],
                    backgroundColor: [
                        '#ffc107',
                        '#0d6efd',
                        '#0dcaf0',
                        '#198754',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
