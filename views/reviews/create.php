<?php
require_once __DIR__ . '/../config/Auth.php';
require_once __DIR__ . '/../config/Review.php';
require_once __DIR__ . '/../config/Order.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: /healthy/views/login.php');
    exit;
}

if (!isset($_GET['order_id']) || !isset($_GET['item_id'])) {
    header('Location: /healthy/views/orders/index.php');
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$orderId = intval($_GET['order_id']);
$itemId = intval($_GET['item_id']);

$order = new Order($userId);
$orderDetails = $order->getOrder($orderId);

if (!$orderDetails || $orderDetails['order_status'] !== 'completed') {
    header('Location: /healthy/views/orders/index.php');
    exit;
}

// Check if item exists in order
$itemFound = false;
foreach ($orderDetails['items'] as $item) {
    if ($item['item_id'] == $itemId) {
        $itemFound = true;
        $itemDetails = $item;
        break;
    }
}

if (!$itemFound) {
    header('Location: /healthy/views/orders/index.php');
    exit;
}

$review = new Review($userId);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá món ăn - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating > input {
            display: none;
        }

        .rating > label {
            position: relative;
            width: 1.1em;
            font-size: 2.5rem;
            color: #e9ecef;
            cursor: pointer;
        }

        .rating > label::before {
            content: "\2605";
            position: absolute;
            opacity: 0;
        }

        .rating > label:hover:before,
        .rating > label:hover ~ label:before {
            opacity: 1 !important;
            color: #ffc107;
        }

        .rating > input:checked ~ label:before {
            opacity: 1;
            color: #ffc107;
        }

        .image-preview {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .preview-container {
            position: relative;
            width: 100px;
            height: 100px;
        }

        .preview-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .remove-image {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Đánh giá món ăn</h1>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex">
                            <img src="/healthy/img/<?php echo htmlspecialchars($itemDetails['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($itemDetails['name']); ?>"
                                 class="me-3" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">

                            <div>
                                <h5 class="card-title mb-2">
                                    <?php echo htmlspecialchars($itemDetails['name']); ?>
                                </h5>

                                <div class="text-muted">
                                    Đơn hàng #<?php echo $orderId; ?><br>
                                    Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($orderDetails['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="reviewForm" class="card" method="POST" action="/healthy/api/reviews/create.php">
                    <div class="card-body">
                        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                        <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">

                        <div class="mb-4">
                            <label class="form-label">Đánh giá của bạn</label>
                            <div class="rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>"
                                           id="star<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>">☆</label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="comment" class="form-label">Nhận xét (không bắt buộc)</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4"
                                      placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Hình ảnh (không bắt buộc)</label>
                            <input type="file" class="d-none" id="imageInput" accept="image/*" multiple>
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('imageInput').click()">
                                <i class="bi bi-image"></i> Thêm hình ảnh
                            </button>
                            <div class="text-muted small mt-1">Tối đa 5 hình ảnh</div>
                            <div id="imagePreview" class="image-preview"></div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Gửi đánh giá
                            </button>
                            <a href="/healthy/views/orders/detail.php?id=<?php echo $orderId; ?>"
                               class="btn btn-outline-secondary">
                                Quay lại
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const imageFiles = new Map();
        let imageCounter = 0;

        document.getElementById('imageInput').addEventListener('change', function(e) {
            const files = e.target.files;

            if (imageFiles.size + files.length > 5) {
                alert('Bạn chỉ có thể tải lên tối đa 5 hình ảnh');
                return;
            }

            for (const file of files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    const id = 'img' + (++imageCounter);

                    reader.onload = function(e) {
                        const preview = `
                            <div class="preview-container" id="${id}">
                                <img src="${e.target.result}" alt="Preview">
                                <i class="bi bi-x-circle remove-image" onclick="removeImage('${id}')"></i>
                            </div>
                        `;
                        document.getElementById('imagePreview').insertAdjacentHTML('beforeend', preview);
                    };

                    reader.readAsDataURL(file);
                    imageFiles.set(id, file);
                }
            }

            this.value = '';
        });

        function removeImage(id) {
            document.getElementById(id).remove();
            imageFiles.delete(id);
        }

        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('order_id', this.order_id.value);
            formData.append('item_id', this.item_id.value);
            formData.append('rating', this.rating.value);

            if (this.comment.value.trim()) {
                formData.append('comment', this.comment.value.trim());
            }

            imageFiles.forEach((file, id) => {
                formData.append('images[]', file);
            });

            $.ajax({
                url: this.action,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        window.location.href = '/healthy/views/reviews/index.php';
                    } else {
                        alert(response.message || 'Có lỗi xảy ra, vui lòng thử lại');
                    }
                },
                error: function() {
                    alert('Có lỗi xảy ra, vui lòng thử lại');
                }
            });
        });
    </script>
</body>
</html>
