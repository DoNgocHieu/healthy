<?php
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Catalog.php';

$auth = new Auth();
$catalog = new Catalog();

$userId = $auth->isLoggedIn() ? $auth->getCurrentUser()['id'] : null;
$itemId = $_GET['id'] ?? 0;

$item = $catalog->getItemDetails($itemId, $userId);
if (!$item) {
    header('Location: /healthy/views/catalog/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['name']); ?> - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/healthy/css/catalog.css" rel="stylesheet">
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/healthy/views/catalog/index.php">Thực đơn</a></li>
                <li class="breadcrumb-item"><a href="/healthy/views/catalog/index.php?category=<?php echo $item['TT']; ?>">
                    <?php echo htmlspecialchars($item['category_name']); ?>
                </a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($item['name']); ?>
                </li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product image -->
            <div class="col-md-6">
                <div class="product-image-large card">
                    <img src="/healthy/img/<?php echo htmlspecialchars($item['image_url']); ?>"
                         class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php if ($userId): ?>
                        <button class="btn-favorite large <?php echo $item['is_favorite'] ? 'active' : ''; ?>"
                                onclick="toggleFavorite(<?php echo $item['id']; ?>)">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product info -->
            <div class="col-md-6">
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h1>

                    <div class="category mb-3">
                        <span class="badge bg-secondary">
                            <?php echo htmlspecialchars($item['category_name']); ?>
                        </span>
                    </div>

                    <div class="rating mb-3">
                        <?php
                        $rating = round($item['average_rating']);
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<i class="bi bi-star' . ($i <= $rating ? '-fill' : '') . '"></i>';
                        }
                        echo " <span class='rating-count'>({$item['review_count']} đánh giá)</span>";
                        ?>
                    </div>

                    <div class="price mb-4">
                        <h2><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</h2>
                    </div>

                    <div class="description mb-4">
                        <h5>Mô tả</h5>
                        <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                    </div>

                    <div class="add-to-cart">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="quantity-input">
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="updateQuantity(-1)">-</button>
                                    <input type="number" class="form-control" id="quantity" value="1" min="1">
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="updateQuantity(1)">+</button>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <button class="btn btn-primary btn-lg w-100"
                                        onclick="addToCart(<?php echo $item['id']; ?>)">
                                    <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews -->
        <div class="reviews-section mt-5">
            <h3>Đánh giá (<?php echo $item['review_count']; ?>)</h3>

            <?php if (!empty($item['reviews'])): ?>
                <div class="reviews-list">
                    <?php foreach ($item['reviews'] as $review): ?>
                        <div class="review-item card mb-3">
                            <div class="card-body">
                                <div class="d-flex">
                                    <img src="<?php echo $review['avatar_url'] ?? '/healthy/img/default-avatar.png'; ?>"
                                         class="rounded-circle me-3" width="48" height="48" alt="Avatar">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($review['username']); ?></h6>
                                        <div class="rating mb-2">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo '<i class="bi bi-star' . ($i <= $review['rating'] ? '-fill' : '') . '"></i>';
                                            }
                                            ?>
                                        </div>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>

                                        <?php if ($review['images']): ?>
                                            <div class="review-images">
                                                <?php foreach (explode(',', $review['images']) as $image): ?>
                                                    <img src="<?php echo htmlspecialchars($image); ?>"
                                                         class="img-thumbnail" alt="Review image">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($item['review_count'] > 5): ?>
                        <div class="text-center">
                            <a href="/healthy/views/catalog/reviews.php?id=<?php echo $item['id']; ?>"
                               class="btn btn-outline-primary">
                                Xem tất cả đánh giá
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Chưa có đánh giá nào.</p>
            <?php endif; ?>
        </div>

        <!-- Related products -->
        <?php if (!empty($item['related_items'])): ?>
            <div class="related-products mt-5">
                <h3>Món ăn liên quan</h3>

                <div class="row g-4">
                    <?php foreach ($item['related_items'] as $relatedItem): ?>
                        <div class="col-md-3">
                            <div class="product-card card h-100">
                                <div class="product-image">
                                    <img src="/healthy/img/<?php echo htmlspecialchars($relatedItem['image_url']); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($relatedItem['name']); ?>">
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="/healthy/views/catalog/item.php?id=<?php echo $relatedItem['id']; ?>">
                                            <?php echo htmlspecialchars($relatedItem['name']); ?>
                                        </a>
                                    </h5>

                                    <div class="rating mb-2">
                                        <?php
                                        $rating = round($relatedItem['average_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo '<i class="bi bi-star' . ($i <= $rating ? '-fill' : '') . '"></i>';
                                        }
                                        ?>
                                    </div>

                                    <div class="price">
                                        <?php echo number_format($relatedItem['price'], 0, ',', '.'); ?>đ
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/healthy/js/catalog.js"></script>
</body>
</html>
