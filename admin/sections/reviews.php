<?php
require_once __DIR__ . '/../../config/Database.php';

class ReviewAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getReviews($page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;

            // Đếm tổng số đánh giá
            $stmt = $this->db->query('SELECT COUNT(*) as total FROM comments');
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Lấy danh sách đánh giá với thông tin người dùng và món ăn
            $query = "
                SELECT c.*, c.username as user_name, i.name as food_name,
                       c.star as rating, c.detail as content, c.date as created_at
                FROM comments c
                LEFT JOIN items i ON c.id_food = i.id
                ORDER BY c.date DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$perPage, $offset]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'reviews' => $reviews,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            error_log("ReviewAdmin error: " . $e->getMessage());
            return [
                'reviews' => [],
                'total' => 0,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => 0,
                'error' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    public function deleteReview($id) {
        try {
            $stmt = $this->db->prepare('DELETE FROM comments WHERE id = ?');
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Không thể xóa đánh giá: ' . $e->getMessage()
            ];
        }
    }
}

// Khởi tạo ReviewAdmin
$reviewAdmin = new ReviewAdmin();

// Xử lý phân trang
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Lấy danh sách đánh giá
$result = $reviewAdmin->getReviews($currentPage, $perPage);
$reviews = $result['reviews'];
$totalPages = $result['totalPages'];

// Debug thông tin nếu không có dữ liệu
if (empty($reviews)) {
    error_log("Debug - Reviews is empty. Result: " . print_r($result, true));
}
?>

<!-- Tiêu đề -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0">Quản lý đánh giá</h2>
</div>

<!-- Hiển thị thông báo lỗi nếu có -->
<?php if (!empty($result['error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($result['error']); ?>
    </div>
<?php endif; ?>

<!-- Bảng danh sách đánh giá -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Người dùng</th>
                <th>Món ăn</th>
                <th>Nội dung</th>
                <th>Đánh giá</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?php echo htmlspecialchars($review['id']); ?></td>
                    <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($review['food_name']); ?></td>
                    <td>
                        <?php
                        $content = $review['content'];
                        echo strlen($content) > 100
                            ? htmlspecialchars(substr($content, 0, 100)) . '...'
                            : htmlspecialchars($content);
                        ?>
                    </td>
                    <td>
                        <?php
                        $rating = $review['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating
                                ? '<i class="bi bi-star-fill text-warning"></i>'
                                : '<i class="bi bi-star text-warning"></i>';
                        }
                        ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info view-review"
                                data-bs-toggle="modal"
                                data-bs-target="#reviewModal"
                                data-review='<?php echo htmlspecialchars(json_encode($review)); ?>'>
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-review"
                                data-review-id="<?php echo $review['id']; ?>">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Chưa có đánh giá nào</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Phân trang -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Phân trang">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="?page=admin&section=reviews&p=<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal xem chi tiết đánh giá -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đánh giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Người dùng:</strong>
                    <p id="modalUserName"></p>
                </div>
                <div class="mb-3">
                    <strong>Món ăn:</strong>
                    <p id="modalFoodName"></p>
                </div>
                <div class="mb-3">
                    <strong>Đánh giá:</strong>
                    <p id="modalRating"></p>
                </div>
                <div class="mb-3">
                    <strong>Nội dung:</strong>
                    <p id="modalContent"></p>
                </div>
                <div class="mb-3">
                    <strong>Thời gian:</strong>
                    <p id="modalCreatedAt"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý xem chi tiết đánh giá
    document.querySelectorAll('.view-review').forEach(button => {
        button.addEventListener('click', function() {
            const review = JSON.parse(this.dataset.review);
            document.getElementById('modalUserName').textContent = review.user_name;
            document.getElementById('modalFoodName').textContent = review.food_name;

            // Hiển thị rating bằng sao
            let ratingHtml = '';
            for (let i = 1; i <= 5; i++) {
                ratingHtml += i <= review.rating
                    ? '<i class="bi bi-star-fill text-warning"></i>'
                    : '<i class="bi bi-star text-warning"></i>';
            }
            document.getElementById('modalRating').innerHTML = ratingHtml;

            document.getElementById('modalContent').textContent = review.content;
            document.getElementById('modalCreatedAt').textContent =
                new Date(review.created_at).toLocaleString('vi-VN');
        });
    });

    // Xử lý xóa đánh giá
    document.querySelectorAll('.delete-review').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Bạn có chắc chắn muốn xóa đánh giá này?')) {
                return;
            }

            const reviewId = this.dataset.reviewId;

            try {
                const response = await fetch('/healthy/api/delete_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: reviewId })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Có lỗi xảy ra khi xóa đánh giá');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa đánh giá');
            }
        });
    });
});
</script>
