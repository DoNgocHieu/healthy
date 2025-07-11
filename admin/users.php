<?php
require_once '../config/Auth.php';
require_once '../config/Database.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get filters from query params
$filters = [
    'role' => $_GET['role'] ?? null,
    'search' => $_GET['search'] ?? null,
    'status' => $_GET['status'] ?? null
];

// Get users with filters and pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($filters['role'])) {
    $where[] = 'role = ?';
    $params[] = $filters['role'];
}

if (!empty($filters['status'])) {
    $where[] = 'is_active = ?';
    $params[] = $filters['status'];
}

if (!empty($filters['search'])) {
    $where[] = '(fullname LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $searchTerm = "%{$filters['search']}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total records for pagination
$stmt = $db->prepare('
    SELECT COUNT(*) as total
    FROM users
    ' . $whereClause
);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get users with order stats
$stmt = $db->prepare('
    SELECT u.*,
           COUNT(DISTINCT o.id) as total_orders,
           SUM(CASE WHEN o.order_status != "cancelled" THEN o.total_amount ELSE 0 END) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    ' . $whereClause . '
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
');

$params = array_merge($params, [$perPage, $offset]);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Update user status
    if ($_POST['action'] === 'update_status') {
        try {
            $stmt = $db->prepare('UPDATE users SET is_active = ? WHERE id = ?');
            $stmt->execute([$_POST['status'], $_POST['user_id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công'
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Update user role
    if ($_POST['action'] === 'update_role') {
        try {
            $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->execute([$_POST['role'], $_POST['user_id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật vai trò thành công'
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Get user details
    if ($_POST['action'] === 'get_user') {
        try {
            // Get user info
            $stmt = $db->prepare('
                SELECT u.*,
                       COUNT(DISTINCT o.id) as total_orders,
                       SUM(CASE WHEN o.order_status != "cancelled" THEN o.total_amount ELSE 0 END) as total_spent,
                       COUNT(DISTINCT r.id) as total_reviews
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id
                LEFT JOIN reviews r ON u.id = r.user_id
                WHERE u.id = ?
                GROUP BY u.id
            ');
            $stmt->execute([$_POST['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get recent orders
            $stmt = $db->prepare('
                SELECT o.*, COUNT(oi.id) as total_items
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT 5
            ');
            $stmt->execute([$_POST['user_id']]);
            $user['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get recent reviews
            $stmt = $db->prepare('
                SELECT r.*, i.name as item_name
                FROM reviews r
                JOIN items i ON r.item_id = i.id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
                LIMIT 5
            ');
            $stmt->execute([$_POST['user_id']]);
            $user['recent_reviews'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($user);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

$title = "Quản lý người dùng";
require_once 'layout.php';
?>

<div class="container-fluid py-4">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header pb-0">
            <h6>Bộ lọc</h6>
        </div>
        <div class="card-body">
            <form id="filter-form" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Vai trò</label>
                            <select name="role" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="user" <?= ($filters['role'] === 'user') ? 'selected' : '' ?>>Người dùng</option>
                                <option value="admin" <?= ($filters['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="1" <?= ($filters['status'] === '1') ? 'selected' : '' ?>>Đang hoạt động</option>
                                <option value="0" <?= ($filters['status'] === '0') ? 'selected' : '' ?>>Đã khóa</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-control-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" placeholder="Tên, email hoặc số điện thoại" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-0">Lọc</button>
                        <a href="users.php" class="btn btn-outline-secondary mb-0 ms-2">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Người dùng</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Vai trò</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Trạng thái</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Đơn hàng</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Chi tiêu</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Điểm</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Ngày tham gia</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex px-2 py-1">
                                <div>
                                    <img src="<?= $user['avatar_url'] ?? '../img/default-avatar.png' ?>" class="avatar avatar-sm me-3">
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-0 text-xs"><?= htmlspecialchars($user['fullname']) ?></h6>
                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($user['email']) ?></p>
                                    <?php if ($user['phone']): ?>
                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($user['phone']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <select class="form-control form-control-sm user-role" data-user-id="<?= $user['id'] ?>" <?= ($user['id'] === $auth->getUserId()) ? 'disabled' : '' ?>>
                                <option value="user" <?= ($user['role'] === 'user') ? 'selected' : '' ?>>Người dùng</option>
                                <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input user-status" data-user-id="<?= $user['id'] ?>"
                                       <?= $user['is_active'] ? 'checked' : '' ?>
                                       <?= ($user['id'] === $auth->getUserId()) ? 'disabled' : '' ?>>
                            </div>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= number_format($user['total_orders']) ?></span>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= number_format($user['total_spent']) ?>đ</span>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= number_format($user['points']) ?></span>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold">
                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-link text-dark mb-0 view-user" data-user-id="<?= $user['id'] ?>">
                                <i class="fas fa-eye text-dark me-2"></i>
                                Chi tiết
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total > $perPage): ?>
        <div class="card-footer d-flex justify-content-center">
            <ul class="pagination pagination-primary m-0">
                <?php
                $totalPages = ceil($total / $perPage);
                for ($i = 1; $i <= $totalPages; $i++):
                ?>
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

<!-- User Detail Modal -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Chi tiết người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="user-info"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update user status
    $('.user-status').change(function() {
        const userId = $(this).data('user-id');
        const status = $(this).prop('checked') ? 1 : 0;

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'update_status',
                user_id: userId,
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

    // Update user role
    $('.user-role').change(function() {
        const userId = $(this).data('user-id');
        const role = $(this).val();

        if (!confirm('Bạn có chắc muốn thay đổi vai trò của người dùng này?')) {
            return;
        }

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'update_role',
                user_id: userId,
                role: role
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

    // View user details
    $('.view-user').click(function() {
        const userId = $(this).data('user-id');

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: {
                action: 'get_user',
                user_id: userId
            },
            success: function(user) {
                let html = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img src="${user.avatar_url || '../img/default-avatar.png'}" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px;">
                            <h6 class="mb-0">${user.fullname}</h6>
                            <p class="text-xs text-secondary mb-0">${user.email}</p>
                            ${user.phone ? `<p class="text-xs text-secondary mb-0">${user.phone}</p>` : ''}
                            <p class="text-xs text-secondary mb-0">Tham gia: ${new Date(user.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <h6 class="text-sm">Tổng đơn hàng</h6>
                                    <p class="text-xs font-weight-bold mb-0">${user.total_orders}</p>
                                </div>
                                <div class="col-6 mb-3">
                                    <h6 class="text-sm">Tổng chi tiêu</h6>
                                    <p class="text-xs font-weight-bold mb-0">${Number(user.total_spent).toLocaleString()}đ</p>
                                </div>
                                <div class="col-6 mb-3">
                                    <h6 class="text-sm">Điểm tích lũy</h6>
                                    <p class="text-xs font-weight-bold mb-0">${Number(user.points).toLocaleString()}</p>
                                </div>
                                <div class="col-6 mb-3">
                                    <h6 class="text-sm">Số đánh giá</h6>
                                    <p class="text-xs font-weight-bold mb-0">${user.total_reviews}</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-sm mb-3">Đơn hàng gần đây</h6>
                                <div class="table-responsive">
                                    <table class="table align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">#</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Ngày</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Số món</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tổng tiền</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;

                user.recent_orders.forEach(order => {
                    html += `
                        <tr>
                            <td>
                                <span class="text-xs font-weight-bold">#${order.id}</span>
                            </td>
                            <td>
                                <span class="text-xs font-weight-bold">${new Date(order.created_at).toLocaleDateString()}</span>
                            </td>
                            <td>
                                <span class="text-xs font-weight-bold">${order.total_items}</span>
                            </td>
                            <td>
                                <span class="text-xs font-weight-bold">${Number(order.total_amount).toLocaleString()}đ</span>
                            </td>
                            <td>
                                <span class="badge badge-sm bg-gradient-${getStatusColor(order.order_status)}">${getStatusText(order.order_status)}</span>
                            </td>
                        </tr>
                    `;
                });

                html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div>
                                <h6 class="text-sm mb-3">Đánh giá gần đây</h6>
                                <div class="table-responsive">
                                    <table class="table align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Món ăn</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Đánh giá</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Ngày</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;

                user.recent_reviews.forEach(review => {
                    html += `
                        <tr>
                            <td>
                                <span class="text-xs font-weight-bold">${review.item_name}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="text-xs font-weight-bold me-2">${review.rating}</span>
                                    <div class="rating">
                                        ${getRatingStars(review.rating)}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-xs font-weight-bold">${new Date(review.created_at).toLocaleDateString()}</span>
                            </td>
                        </tr>
                    `;
                });

                html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('.user-info').html(html);
                $('#userModal').modal('show');
            }
        });
    });
});

function getStatusColor(status) {
    switch (status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'shipping':
            return 'primary';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'pending':
            return 'Chờ xử lý';
        case 'processing':
            return 'Đang xử lý';
        case 'shipping':
            return 'Đang giao';
        case 'completed':
            return 'Hoàn thành';
        case 'cancelled':
            return 'Đã hủy';
        default:
            return status;
    }
}

function getRatingStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<i class="fas fa-star ${i <= rating ? 'text-warning' : 'text-secondary'}"></i>`;
    }
    return stars;
}</script>
