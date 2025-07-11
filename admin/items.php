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

// Handle image upload
function uploadImage($file) {
    $targetDir = "../uploads/items/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($file["name"]);
    $targetFile = $targetDir . time() . '_' . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return [
            'success' => false,
            'message' => 'File không phải là hình ảnh.'
        ];
    }

    // Check file size
    if ($file["size"] > 5000000) {
        return [
            'success' => false,
            'message' => 'Kích thước file quá lớn.'
        ];
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return [
            'success' => false,
            'message' => 'Chỉ chấp nhận file JPG, JPEG & PNG.'
        ];
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return [
            'success' => true,
            'path' => str_replace("../", "", $targetFile)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Có lỗi xảy ra khi upload file.'
        ];
    }
}

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Create/Update item
    if ($_POST['action'] === 'save_item') {
        try {
            $db->beginTransaction();

            $data = [
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'price' => $_POST['price'],
                'stock_quantity' => $_POST['stock_quantity'],
                'category_id' => $_POST['category_id']
            ];

            // Handle image upload if provided
            if (!empty($_FILES['image'])) {
                $uploadResult = uploadImage($_FILES['image']);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $data['image_url'] = $uploadResult['path'];
            }

            if (empty($_POST['id'])) {
                // Create new item
                $sql = 'INSERT INTO items (name, description, price, stock_quantity, category_id, image_url)
                        VALUES (:name, :description, :price, :stock_quantity, :category_id, :image_url)';
            } else {
                // Update existing item
                $sql = 'UPDATE items
                        SET name = :name, description = :description, price = :price,
                            stock_quantity = :stock_quantity, category_id = :category_id';

                if (isset($data['image_url'])) {
                    $sql .= ', image_url = :image_url';
                }

                $sql .= ' WHERE id = :id';
                $data['id'] = $_POST['id'];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($data);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Lưu thành công'
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

    // Delete item
    if ($_POST['action'] === 'delete_item') {
        try {
            $stmt = $db->prepare('DELETE FROM items WHERE id = ?');
            $stmt->execute([$_POST['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Xóa thành công'
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

    // Get item details
    if ($_POST['action'] === 'get_item') {
        $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$_POST['id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($item);
        exit;
    }
}

// Get filters from query params
$filters = [
    'category' => $_GET['category'] ?? null,
    'search' => $_GET['search'] ?? null
];

// Get items with filters and pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($filters['category'])) {
    $where[] = 'i.category_id = ?';
    $params[] = $filters['category'];
}

if (!empty($filters['search'])) {
    $where[] = 'i.name LIKE ?';
    $params[] = "%{$filters['search']}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total records for pagination
$stmt = $db->prepare('
    SELECT COUNT(DISTINCT i.id) as total
    FROM items i
    ' . $whereClause
);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get items
$stmt = $db->prepare('
    SELECT i.*, c.name as category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    ' . $whereClause . '
    ORDER BY i.name
    LIMIT ? OFFSET ?
');

$params = array_merge($params, [$perPage, $offset]);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$stmt = $db->prepare('SELECT * FROM categories ORDER BY name');
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "Quản lý món ăn";
require_once 'layout.php';
?>

<div class="container-fluid py-4">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header pb-0">
            <div class="row">
                <div class="col">
                    <h6>Bộ lọc</h6>
                </div>
                <div class="col text-end">
                    <button type="button" class="btn btn-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#itemModal">
                        Thêm món ăn
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form id="filter-form" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-control-label">Danh mục</label>
                            <select name="category" class="form-control">
                                <option value="">Tất cả</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= ($filters['category'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-control-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" placeholder="Tên món ăn" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-0">Lọc</button>
                        <a href="items.php" class="btn btn-outline-secondary mb-0 ms-2">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Món ăn</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Giá</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Số lượng</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Danh mục</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Đánh giá</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex px-2 py-1">
                                <div>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" class="avatar avatar-sm me-3">
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-0 text-xs"><?= htmlspecialchars($item['name']) ?></h6>
                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars(substr($item['description'], 0, 50)) ?>...</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= number_format($item['price']) ?>đ</span>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= number_format($item['stock_quantity']) ?></span>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold"><?= htmlspecialchars($item['category_name']) ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="text-xs font-weight-bold me-2"><?= number_format($item['rating'], 1) ?></span>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= ($i <= round($item['rating'])) ? 'text-warning' : 'text-secondary' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-link text-dark mb-0 edit-item" data-item-id="<?= $item['id'] ?>">
                                <i class="fas fa-pencil-alt text-dark me-2"></i>
                                Sửa
                            </button>
                            <button type="button" class="btn btn-link text-danger mb-0 delete-item" data-item-id="<?= $item['id'] ?>">
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

<!-- Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" role="dialog" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Thêm/Sửa món ăn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="itemForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_item">
                    <input type="hidden" name="id" id="item_id">

                    <div class="form-group">
                        <label class="form-control-label">Tên món ăn</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Mô tả</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Giá</label>
                                <input type="number" class="form-control" name="price" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Số lượng</label>
                                <input type="number" class="form-control" name="stock_quantity" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Danh mục</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Hình ảnh</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <div id="image_preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show item modal for edit
    $('.edit-item').click(function() {
        const itemId = $(this).data('item-id');

        $.ajax({
            url: 'items.php',
            type: 'POST',
            data: {
                action: 'get_item',
                id: itemId
            },
            success: function(item) {
                $('#item_id').val(item.id);
                $('input[name="name"]').val(item.name);
                $('textarea[name="description"]').val(item.description);
                $('input[name="price"]').val(item.price);
                $('input[name="stock_quantity"]').val(item.stock_quantity);
                $('select[name="category_id"]').val(item.category_id);

                if (item.image_url) {
                    $('#image_preview').html(`
                        <img src="${item.image_url}" class="img-fluid" style="max-height: 200px">
                    `);
                }

                $('#itemModal').modal('show');
            }
        });
    });

    // Reset form when modal is closed
    $('#itemModal').on('hidden.bs.modal', function() {
        $('#itemForm')[0].reset();
        $('#item_id').val('');
        $('#image_preview').empty();
    });

    // Preview image before upload
    $('input[name="image"]').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image_preview').html(`
                    <img src="${e.target.result}" class="img-fluid" style="max-height: 200px">
                `);
            }
            reader.readAsDataURL(file);
        }
    });

    // Handle form submit
    $('#itemForm').submit(function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: 'items.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#itemModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Delete item
    $('.delete-item').click(function() {
        const itemId = $(this).data('item-id');

        if (!confirm('Bạn có chắc muốn xóa món ăn này?')) {
            return;
        }

        $.ajax({
            url: 'items.php',
            type: 'POST',
            data: {
                action: 'delete_item',
                id: itemId
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
});
</script>
