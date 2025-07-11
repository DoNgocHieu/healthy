<?php
require_once __DIR__ . '/../../config/ItemAdmin.php';

$itemAdmin = new ItemAdmin();

// Xử lý các filter từ GET parameters
$filters = [];
if (!empty($_GET['item_name'])) {
    $filters['name'] = $_GET['item_name'];
}
if (!empty($_GET['category'])) {
    $filters['category_id'] = $_GET['category'];
}
if (isset($_GET['stock']) && $_GET['stock'] !== '') {
    $filters['stock'] = $_GET['stock'];
}

// Lấy trang hiện tại
$page = isset($_GET['page_number']) ? (int)$_GET['page_number'] : 1;
$perPage = 10;

// Lấy danh sách món ăn
$result = $itemAdmin->getItems($filters, $page, $perPage);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý món ăn</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">
            <i class="bi bi-plus-lg"></i> Thêm món mới
        </button>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="section" value="items">

                <div class="col-md-4">
                    <label class="form-label">Tên món</label>
                    <input type="text" class="form-control" name="item_name" value="<?php echo $_GET['item_name'] ?? ''; ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Danh mục</label>
                    <select class="form-select" name="category">
                        <option value="">Tất cả</option>
                        <?php foreach ($itemAdmin->getCategories() as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Trạng thái kho</label>
                    <select class="form-select" name="stock">
                        <option value="">Tất cả</option>
                        <option value="in" <?php echo (isset($_GET['stock']) && $_GET['stock'] === 'in') ? 'selected' : ''; ?>>Còn hàng</option>
                        <option value="out" <?php echo (isset($_GET['stock']) && $_GET['stock'] === 'out') ? 'selected' : ''; ?>>Hết hàng</option>
                        <option value="low" <?php echo (isset($_GET['stock']) && $_GET['stock'] === 'low') ? 'selected' : ''; ?>>Sắp hết</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <a href="?page=admin&section=items" class="btn btn-secondary">Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Hình ảnh</th>
                            <th>Tên món</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['items'] as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?php echo strpos($item['image_url'], '/') === 0 ? $item['image_url'] : '../img/' . $item['image_url']; ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="img-thumbnail"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="img-thumbnail text-center" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo isset($item['category_name']) ? htmlspecialchars($item['category_name']) : 'Chưa phân loại'; ?></td>
                            <td><?php echo number_format($item['price']); ?>đ</td>
                            <td>
                                <?php if (isset($item['stock_quantity'])): ?>
                                    <span class="badge <?php echo $item['stock_quantity'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $item['stock_quantity']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-item" data-item-id="<?php echo $item['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-primary edit-item" data-item-id="<?php echo $item['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-item" data-item-id="<?php echo $item['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($result['totalPages'] > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=admin&section=items&page_number=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm/Sửa món ăn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="itemId">

                    <div class="mb-3">
                        <label class="form-label">Tên món *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Danh mục</label>
                        <?php
                        $categories = $itemAdmin->getCategories();
                        if (!empty($categories)):
                        ?>
                            <select class="form-select" name="category_id">
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="category_id" value="">
                            <div class="form-control-plaintext">Chưa có danh mục</div>
                        <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Giá *</label>
                                <input type="number" class="form-control" name="price" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Số lượng tồn</label>
                                <input type="number" class="form-control" name="stock_quantity" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hình ảnh</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <div id="currentImage" class="mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="saveItem">Lưu</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
    const itemForm = document.getElementById('itemForm');

    // Mở modal thêm món mới
    document.querySelector('[data-bs-target="#itemModal"]').addEventListener('click', function() {
        itemForm.reset();
        document.getElementById('itemId').value = '';
        document.getElementById('currentImage').innerHTML = '';

        // Re-enable all form fields and show save button
        document.querySelectorAll('#itemForm input, #itemForm select, #itemForm textarea')
            .forEach(input => input.disabled = false);
        document.getElementById('saveItem').style.display = 'block';

        itemModal.show();
    });

    // Xem chi tiết món ăn
    document.querySelectorAll('.view-item').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            fetch(`/healthy/admin/api/get_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateForm(data.item);
                        document.querySelectorAll('#itemForm input, #itemForm select, #itemForm textarea')
                            .forEach(input => input.disabled = true);
                        document.getElementById('saveItem').style.display = 'none';
                        itemModal.show();
                    }
                });
        });
    });

    // Sửa món ăn
    document.querySelectorAll('.edit-item').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            fetch(`/healthy/admin/api/get_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateForm(data.item);
                        document.querySelectorAll('#itemForm input, #itemForm select, #itemForm textarea')
                            .forEach(input => input.disabled = false);
                        document.getElementById('saveItem').style.display = 'block';
                        itemModal.show();
                    }
                });
        });
    });

    // Xóa món ăn
    document.querySelectorAll('.delete-item').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Bạn có chắc muốn xóa món ăn này?')) {
                const itemId = this.dataset.itemId;
                fetch('/healthy/admin/api/delete_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: itemId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Có lỗi xảy ra: ' + data.message);
                    }
                });
            }
        });
    });

    // Lưu món ăn
    document.getElementById('saveItem').addEventListener('click', function() {
        const formData = new FormData(itemForm);
        fetch('/healthy/admin/api/save_item.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                itemModal.hide();
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        });
    });

    function populateForm(item) {
        document.getElementById('itemId').value = item.id;
        itemForm.querySelector('[name="name"]').value = item.name;
        const categorySelect = itemForm.querySelector('[name="category_id"]');
        if (categorySelect && categorySelect.tagName === 'SELECT') {
            categorySelect.value = item.category_id || '';
        }
        itemForm.querySelector('[name="description"]').value = item.description;
        itemForm.querySelector('[name="price"]').value = item.price;
        itemForm.querySelector('[name="stock_quantity"]').value = item.stock_quantity || 0;

        if (item.image_url) {
            document.getElementById('currentImage').innerHTML = `
                <img src="${item.image_url.startsWith('/') ? item.image_url : '../img/' + item.image_url}"
                     alt="${item.name}"
                     class="img-thumbnail"
                     style="max-height: 100px">
            `;
        } else {
            document.getElementById('currentImage').innerHTML = '';
        }
    }
});
</script>
