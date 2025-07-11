<?php
require_once __DIR__ . '/../../config/Database.php';

class ReportAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getOrderStats($fromDate = null, $toDate = null) {
        try {
            $query = "
                SELECT
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    SUM(discount) as total_discount,
                    SUM(points_earned) as total_points_earned,
                    SUM(points_used) as total_points_used,
                    COUNT(DISTINCT user_id) as unique_customers
                FROM orders
                WHERE 1=1
            ";

            $params = [];
            if ($fromDate) {
                $query .= " AND created_at >= ?";
                $params[] = $fromDate;
            }
            if ($toDate) {
                $query .= " AND created_at <= ?";
                $params[] = $toDate;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getOrderStats: " . $e->getMessage());
            return null;
        }
    }

    public function getTopProducts($limit = 10) {
        try {
            $query = "
                SELECT i.id, i.name, i.price,
                       COUNT(oi.id) as order_count,
                       SUM(oi.quantity) as total_quantity
                FROM items i
                LEFT JOIN order_items oi ON i.id = oi.item_id
                GROUP BY i.id
                ORDER BY total_quantity DESC
                LIMIT ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getTopProducts: " . $e->getMessage());
            return [];
        }
    }

    public function getPointsStats() {
        try {
            $query = "
                SELECT
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN type = 'earn' THEN change_amount ELSE 0 END) as total_points_earned,
                    SUM(CASE WHEN type = 'redeem' THEN change_amount ELSE 0 END) as total_points_redeemed
                FROM points_history
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getPointsStats: " . $e->getMessage());
            return null;
        }
    }

    public function getRecentReviews($limit = 5) {
        try {
            $query = "
                SELECT c.*, i.name as food_name,
                       c.username as user_name,
                       c.star as rating,
                       c.detail as content,
                       c.date as created_at
                FROM comments c
                LEFT JOIN items i ON c.id_food = i.id
                ORDER BY c.date DESC
                LIMIT ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getRecentReviews: " . $e->getMessage());
            return [];
        }
    }
}

// Khởi tạo ReportAdmin
$reportAdmin = new ReportAdmin();

// Lấy thống kê đơn hàng
$orderStats = $reportAdmin->getOrderStats();
$topProducts = $reportAdmin->getTopProducts(5);
$pointsStats = $reportAdmin->getPointsStats();
$recentReviews = $reportAdmin->getRecentReviews(5);

// Format currency
function formatCurrency($amount) {
    return number_format($amount ?? 0, 0, ',', '.') . 'đ';
}
?>

<!-- Tiêu đề -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0">Thống kê tổng quan</h2>
</div>

<!-- Thống kê tổng quan -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-subtitle text-muted mb-1">Tổng đơn hàng</h6>
                        <h4 class="card-title mb-0"><?php echo number_format($orderStats['total_orders'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-cart text-primary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-subtitle text-muted mb-1">Doanh thu</h6>
                        <h4 class="card-title mb-0"><?php echo formatCurrency($orderStats['total_revenue'] ?? 0); ?></h4>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-currency-dollar text-success fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-subtitle text-muted mb-1">Khách hàng</h6>
                        <h4 class="card-title mb-0"><?php echo number_format($orderStats['unique_customers'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-people text-info fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-subtitle text-muted mb-1">Tổng điểm đã cấp</h6>
                        <h4 class="card-title mb-0"><?php echo number_format($orderStats['total_points_earned'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-star text-warning fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top sản phẩm bán chạy -->
<div class="row mb-4">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Top sản phẩm bán chạy</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tên món</th>
                                <th class="text-end">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="text-end"><?php echo number_format($product['total_quantity'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-end"><?php echo formatCurrency($product['price']); ?></td>
                                <td class="text-end"><?php echo formatCurrency(($product['price'] ?? 0) * ($product['total_quantity'] ?? 0)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Đánh giá gần đây</h5>
            </div>
            <div class="card-body">
                <?php foreach ($recentReviews as $review): ?>
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <i class="bi bi-person-circle text-muted fs-3"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex align-items-center mb-1">
                            <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                            <small class="text-muted ms-2">
                                <?php
                                $rating = $review['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating
                                        ? '<i class="bi bi-star-fill text-warning"></i>'
                                        : '<i class="bi bi-star text-warning"></i>';
                                }
                                ?>
                            </small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($review['food_name']); ?></p>
                        <small class="text-muted">
                            <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Thống kê điểm thưởng -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thống kê điểm thưởng</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-star-half text-warning fs-3"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Tổng giao dịch điểm</h6>
                                <h4 class="mb-0"><?php echo number_format($pointsStats['total_transactions'] ?? 0, 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-plus-circle text-success fs-3"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Điểm đã cấp</h6>
                                <h4 class="mb-0"><?php echo number_format($pointsStats['total_points_earned'] ?? 0, 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="bi bi-dash-circle text-danger fs-3"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Điểm đã đổi</h6>
                                <h4 class="mb-0"><?php echo number_format($pointsStats['total_points_redeemed'] ?? 0, 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
