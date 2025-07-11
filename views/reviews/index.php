<?php
require_once __DIR__ . '/../config/Auth.php';
require_once __DIR__ . '/../config/Review.php';
require_once __DIR__ . '/../config/UserProfile.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: /healthy/views/login.php');
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$review = new Review($userId);
$profile = new UserProfile();

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$reviews = $review->getReviewsByUser($page);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá của tôi - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/css/lightbox.min.css">
    <style>
        .review-card {
            transition: transform 0.2s;
        }

        .review-card:hover {
            transform: translateY(-2px);
        }

        .review-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .review-gallery {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .review-gallery img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
        }

        .rating {
            color: #ffc107;
        }

        .empty-rating {
            color: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Đánh giá của tôi</h1>

        <?php if (empty($reviews)): ?>
            <div class="text-center py-5">
                <i class="bi bi-star display-1 text-muted"></i>
                <h3 class="mt-3">Bạn chưa có đánh giá nào</h3>
                <p class="text-muted">Hãy đánh giá món ăn sau khi nhận đơn hàng</p>
                <a href="/healthy/views/orders/index.php" class="btn btn-primary">
                    Xem đơn hàng
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($reviews as $reviewItem): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card review-card">
                            <div class="card-body">
                                <div class="d-flex mb-3">
                                    <img src="/healthy/img/<?php echo htmlspecialchars($reviewItem['item_image']); ?>"
                                         alt="<?php echo htmlspecialchars($reviewItem['item_name']); ?>"
                                         class="review-item-image me-3">

                                    <div>
                                        <h5 class="card-title mb-1">
                                            <a href="/healthy/views/catalog/item.php?id=<?php echo $reviewItem['item_id']; ?>"
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($reviewItem['item_name']); ?>
                                            </a>
                                        </h5>

                                        <div class="rating mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $reviewItem['rating']): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-star-fill empty-rating"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>

                                        <div class="text-muted small">
                                            <?php echo date('d/m/Y H:i', strtotime($reviewItem['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($reviewItem['comment']): ?>
                                    <p class="card-text">
                                        <?php echo nl2br(htmlspecialchars($reviewItem['comment'])); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($reviewItem['images'])): ?>
                                    <div class="review-gallery">
                                        <?php foreach ($reviewItem['images'] as $image): ?>
                                            <a href="/healthy/uploads/reviews/<?php echo $image; ?>"
                                               data-lightbox="review-<?php echo $reviewItem['id']; ?>">
                                                <img src="/healthy/uploads/reviews/<?php echo $image; ?>"
                                                     alt="Review image">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="deleteReview(<?php echo $reviewItem['id']; ?>)">
                                        <i class="bi bi-trash"></i> Xóa
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($page > 1 || count($reviews) === 10): ?>
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

                        <?php if (count($reviews) === 10): ?>
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
    <script src="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/js/lightbox.min.js"></script>
    <script>
        function deleteReview(reviewId) {
            if (confirm('Bạn có chắc muốn xóa đánh giá này?')) {
                $.ajax({
                    url: '/healthy/api/reviews/delete.php',
                    method: 'POST',
                    data: { review_id: reviewId },
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

        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true
        });
    </script>
</body>
</html>
