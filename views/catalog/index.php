<?php
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Catalog.php';

$auth = new Auth();
$catalog = new Catalog();

$userId = $auth->isLoggedIn() ? $auth->getCurrentUser()['id'] : null;
$categories = $catalog->getAllCategories();

// Get filters from query string
$filters = [
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'sort' => $_GET['sort'] ?? '',
    'page' => max(1, intval($_GET['page'] ?? 1))
];

$result = $catalog->getItems($filters);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thực đơn - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/healthy/css/catalog.css" rel="stylesheet">
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Filters sidebar -->
            <div class="col-lg-3">
                <div class="filters-sidebar card">
                    <div class="card-body">
                        <h5 class="card-title">Lọc sản phẩm</h5>

                        <form action="" method="GET" id="filters-form">
                            <!-- Categories -->
                            <div class="mb-4">
                                <h6 class="mb-3">Danh mục</h6>
                                <div class="categories-list">
                                    <div class="form-check mb-2">
                                        <input type="radio" class="form-check-input" name="category"
                                               id="cat-all" value=""
                                               <?php echo empty($filters['category']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cat-all">Tất cả</label>
                                    </div>
                                    <?php foreach ($categories as $category): ?>
                                        <div class="form-check mb-2">
                                            <input type="radio" class="form-check-input" name="category"
                                                   id="cat-<?php echo $category['TT']; ?>"
                                                   value="<?php echo $category['TT']; ?>"
                                                   <?php echo $filters['category'] === $category['TT'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="cat-<?php echo $category['TT']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Price range -->
                            <div class="mb-4">
                                <h6 class="mb-3">Giá</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="min_price"
                                               placeholder="Từ" value="<?php echo $filters['min_price']; ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="max_price"
                                               placeholder="Đến" value="<?php echo $filters['max_price']; ?>">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Áp dụng</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products grid -->
            <div class="col-lg-9">
                <!-- Search and sort -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="search-box">
                        <form action="" method="GET" class="d-flex">
                            <input type="text" class="form-control me-2" name="search"
                                   placeholder="Tìm món ăn..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>

                    <div class="sort-box">
                        <select class="form-select" name="sort" onchange="document.getElementById('filters-form').submit()">
                            <option value="">Sắp xếp</option>
                            <option value="price_asc" <?php echo $filters['sort'] === 'price_asc' ? 'selected' : ''; ?>>
                                Giá tăng dần
                            </option>
                            <option value="price_desc" <?php echo $filters['sort'] === 'price_desc' ? 'selected' : ''; ?>>
                                Giá giảm dần
                            </option>
                            <option value="name_asc" <?php echo $filters['sort'] === 'name_asc' ? 'selected' : ''; ?>>
                                Tên A-Z
                            </option>
                            <option value="name_desc" <?php echo $filters['sort'] === 'name_desc' ? 'selected' : ''; ?>>
                                Tên Z-A
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Products grid -->
                <div class="row g-4">
                    <?php foreach ($result['items'] as $item): ?>
                        <div class="col-md-4">
                            <div class="product-card card h-100">
                                <div class="product-image">
                                    <img src="/healthy/img/<?php echo htmlspecialchars($item['image_url']); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php if ($userId): ?>
                                        <button class="btn-favorite <?php echo $item['is_favorite'] ? 'active' : ''; ?>"
                                                onclick="toggleFavorite(<?php echo $item['id']; ?>)">
                                            <i class="bi bi-heart-fill"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="/healthy/views/catalog/item.php?id=<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h5>

                                    <div class="category mb-2">
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                    </div>

                                    <div class="rating mb-2">
                                        <?php
                                        $rating = round($item['average_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo '<i class="bi bi-star' . ($i <= $rating ? '-fill' : '') . '"></i>';
                                        }
                                        echo " <small>({$item['review_count']})</small>";
                                        ?>
                                    </div>

                                    <div class="price">
                                        <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button class="btn btn-primary w-100"
                                            onclick="addToCart(<?php echo $item['id']; ?>)">
                                        <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($result['pages'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                                <li class="page-item <?php echo $i === $filters['page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php
                                        $queryParams = $filters;
                                        $queryParams['page'] = $i;
                                        echo http_build_query($queryParams);
                                    ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/healthy/js/catalog.js"></script>
</body>
</html>
