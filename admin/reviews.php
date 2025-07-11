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
    'status' => $_GET['status'] ?? null,
    'rating' => $_GET['rating'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
    'search' => $_GET['search'] ?? null
];

// Get reviews with filters and pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($filters['status'])) {
    $where[] = 'r.is_verified = ?';
    $params[] = $filters['status'];
}

if (!empty($filters['rating'])) {
    $where[] = 'r.rating = ?';
    $params[] = $filters['rating'];
}

if (!empty($filters['date_from'])) {
    $where[] = 'DATE(r.created_at) >= ?';
    $params[] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $where[] = 'DATE(r.created_at) <= ?';
    $params[] = $filters['date_to'];
}

if (!empty($filters['search'])) {
    $where[] = '(i.name LIKE ? OR u.fullname LIKE ? OR r.comment LIKE ?)';
    $searchTerm = "%{$filters['search']}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total records for pagination
$stmt = $db->prepare('
    SELECT COUNT(DISTINCT r.id) as total
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN items i ON r.item_id = i.id
    ' . $whereClause
);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get reviews
$stmt = $db->prepare('
    SELECT r.*, u.fullname, u.avatar_url, i.name as item_name, i.image_url as item_image,
           GROUP_CONCAT(ri.image_url) as images
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN items i ON r.item_id = i.id
    LEFT JOIN review_images ri ON r.id = ri.review_id
    ' . $whereClause . '
    GROUP BY r.id
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
');

$params = array_merge($params, [$perPage, $offset]);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Update review status
    if ($_POST['action'] === 'update_status') {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('UPDATE reviews SET is_verified = ? WHERE id = ?');
            $stmt->execute([$_POST['status'], $_POST['review_id']]);

            // Update item rating
            $stmt = $db->prepare('
                UPDATE items i
                SET rating = (
                    SELECT AVG(r.rating)
                    FROM reviews r
                    WHERE r.item_id = i.id
                    AND r.is_verified = true
                )
                WHERE i.id = (
                    SELECT item_id
                    FROM reviews
                    WHERE id = ?
                )
            ');
            $stmt->execute([$_POST['review_id']]);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công'
            ]);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Delete review
    if ($_POST['action'] === 'delete_review') {
        try {
            $db->beginTransaction();

            // Get item_id before deleting
            $stmt = $db->prepare('SELECT item_id FROM reviews WHERE id = ?');
            $stmt->execute([$_POST['review_id']]);
            $itemId = $stmt->fetch(PDO::FETCH_ASSOC)['item_id'];

            // Delete review images
            $stmt = $db->prepare('DELETE FROM review_images WHERE review_id = ?');
            $stmt->execute([$_POST['review_id']]);

            // Delete review
            $stmt = $db->prepare('DELETE FROM reviews WHERE id = ?');
            $stmt->execute([$_POST['review_id']]);

            // Update item rating
            $stmt = $db->prepare('
                UPDATE items i
                SET rating = (
                    SELECT AVG(r.rating)
                    FROM reviews r
                    WHERE r.item_id = i.id
                    AND r.is_verified = true
                )
                WHERE i.id = ?
            ');
            $stmt->execute([$itemId]);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Xóa đánh giá thành công'
            ]);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Get review details
    if ($_POST['action'] === 'get_review') {
        $stmt = $db->prepare('
            SELECT r.*, u.fullname, u.email, u.phone, u.avatar_url,
                   i.name as item_name, i.image_url as item_image, o.id as order_id,
                   GROUP_CONCAT(ri.image_url) as images
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN items i ON r.item_id = i.id
            LEFT JOIN orders o ON r.order_id = o.id
            LEFT JOIN review_images ri ON r.id = ri.review_id
            WHERE r.id = ?
            GROUP BY r.id
        ');
        $stmt->execute([$_POST['review_id']]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($review['images']) {
            $review['images'] = explode(',', $review['images']);
        } else {
            $review['images'] = [];
        }

        echo json_encode($review);
        exit;
    }
}

$title = "Quản lý đánh giá";
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
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="1" <?= ($filters['status'] === '1') ? 'selected' : '' ?>>Đã duyệt</option>
                                <option value="0" <?= ($filters['status'] === '0') ? 'selected' : '' ?>>Chờ duyệt</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-control-label">Đánh giá</label>
                            <select name="rating" class="form-control">
                                <option value="">Tất cả</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= ($filters['rating'] == $i) ? 'selected' : '' ?>>
                                    <?= $i ?> sao
                                </option>
                                <?php endfor; ?>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" placeholder="Món ăn, người dùng hoặc nội dung" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-0">Lọc</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Người dùng</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Món ăn</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Đánh giá</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Trạng thái</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Ngày</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td>
                            <div class="d-flex px-2 py-1">
                                <div>
                                    <img src="<?= $review['avatar_url'] ?? '../img/default-avatar.png' ?>" class="avatar avatar-sm me-3">
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-0 text-xs"><?= htmlspecialchars($review['fullname']) ?></h6>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?= $review['item_image'] ?>" class="avatar avatar-sm me-2">
                                <span class="text-xs font-weight-bold"><?= htmlspecialchars($review['item_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="text-xs font-weight-bold me-2"><?= $review['rating'] ?></span>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= ($i <= $review['rating']) ? 'text-warning' : 'text-secondary' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input review-status"
                                       data-review-id="<?= $review['id'] ?>"
                                       <?= $review['is_verified'] ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold">
                                <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-link text-dark mb-0 view-review" data-review-id="<?= $review['id'] ?>">
                                <i class="fas fa-eye text-dark me-2"></i>
                                Chi tiết
                            </button>
                            <button type="button" class="btn btn-link text-danger mb-0 delete-review" data-review-id="<?= $review['id'] ?>">
                                <i class="fas fa-trash text-danger me-2"></i>
                                Xóa
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

<!-- Review Detail Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Chi tiết đánh giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="review-info"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update review status
    $('.review-status').change(function() {
        const reviewId = $(this).data('review-id');
        const status = $(this).prop('checked') ? 1 : 0;

        $.ajax({
            url: 'reviews.php',
            type: 'POST',
            data: {
                action: 'update_status',
                review_id: reviewId,
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

    // Delete review
    $('.delete-review').click(function() {
        const reviewId = $(this).data('review-id');

        if (!confirm('Bạn có chắc muốn xóa đánh giá này?')) {
            return;
        }

        $.ajax({
            url: 'reviews.php',
            type: 'POST',
            data: {
                action: 'delete_review',
                review_id: reviewId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // View review details
    $('.view-review').click(function() {
        const reviewId = $(this).data('review-id');

        $.ajax({
            url: 'reviews.php',
            type: 'POST',
            data: {
                action: 'get_review',
                review_id: reviewId
            },
            success: function(review) {
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <img src="${review.avatar_url || '../img/default-avatar.png'}" class="avatar avatar-sm me-2">
                                <div>
                                    <h6 class="mb-0 text-sm">${review.fullname}</h6>
                                    <p class="text-xs text-secondary mb-0">${review.email}</p>
                                    ${review.phone ? `<p class="text-xs text-secondary mb-0">${review.phone}</p>` : ''}
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <img src="${review.item_image}" class="avatar avatar-sm me-2">
                                <div>
                                    <h6 class="mb-0 text-sm">${review.item_name}</h6>
                                    <p class="text-xs text-secondary mb-0">Mã đơn hàng: #${review.order_id}</p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="text-sm font-weight-bold me-2">${review.rating}</span>
                                    <div class="rating">
                                        ${getRatingStars(review.rating)}
                                    </div>
                                </div>
                                <p class="text-sm mb-0">${review.comment || ''}</p>
                            </div>

                            <div class="text-xs text-secondary">
                                Đăng lúc: ${new Date(review.created_at).toLocaleString()}
                            </div>
                        </div>

                        <div class="col-md-6">
                            ${review.images.length > 0 ? `
                                <h6 class="text-sm mb-2">Hình ảnh</h6>
                                <div class="row g-2">
                                    ${review.images.map(image => `
                                        <div class="col-4">
                                            <img src="${image}" class="img-fluid rounded" style="width: 100%; height: 100px; object-fit: cover;">
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;

                $('.review-info').html(html);
                $('#reviewModal').modal('show');
            }
        });
    });
});

function getRatingStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<i class="fas fa-star ${i <= rating ? 'text-warning' : 'text-secondary'}"></i>`;
    }
    return stars;
}</script>
