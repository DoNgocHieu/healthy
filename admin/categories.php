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

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Create/Update category
    if ($_POST['action'] === 'save_category') {
        try {
            if (empty($_POST['id'])) {
                // Create new category
                $stmt = $db->prepare('INSERT INTO categories (name) VALUES (?)');
                $stmt->execute([$_POST['name']]);
            } else {
                // Update existing category
                $stmt = $db->prepare('UPDATE categories SET name = ? WHERE id = ?');
                $stmt->execute([$_POST['name'], $_POST['id']]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Lưu thành công'
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

    // Delete category
    if ($_POST['action'] === 'delete_category') {
        try {
            // Check if category has items
            $stmt = $db->prepare('SELECT COUNT(*) as count FROM items WHERE category_id = ?');
            $stmt->execute([$_POST['id']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                throw new Exception('Không thể xóa danh mục đang có món ăn');
            }

            $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
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

    // Get category details
    if ($_POST['action'] === 'get_category') {
        $stmt = $db->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$_POST['id']]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($category);
        exit;
    }
}

// Get categories with item count
$stmt = $db->prepare('
    SELECT c.*, COUNT(i.id) as item_count
    FROM categories c
    LEFT JOIN items i ON c.id = i.category_id
    GROUP BY c.id
    ORDER BY c.name
');
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "Quản lý danh mục";
require_once 'layout.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="row">
                        <div class="col-6">
                            <h6>Danh sách danh mục</h6>
                        </div>
                        <div class="col-6 text-end">
                            <button class="btn btn-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                Thêm danh mục
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tên danh mục</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Số món ăn</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex px-3 py-1">
                                            <div>
                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($category['name']) ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-sm font-weight-bold mb-0"><?= $category['item_count'] ?></p>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-link text-dark mb-0 edit-category" data-category-id="<?= $category['id'] ?>">
                                            <i class="fas fa-pencil-alt text-dark me-2"></i>
                                            Sửa
                                        </button>
                                        <button type="button" class="btn btn-link text-danger mb-0 delete-category" data-category-id="<?= $category['id'] ?>">
                                            <i class="fas fa-trash text-danger me-2"></i>
                                            Xóa
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Thêm/Sửa danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_category">
                    <input type="hidden" name="id" id="category_id">

                    <div class="form-group">
                        <label class="form-control-label">Tên danh mục</label>
                        <input type="text" class="form-control" name="name" required>
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
    // Show category modal for edit
    $('.edit-category').click(function() {
        const categoryId = $(this).data('category-id');

        $.ajax({
            url: 'categories.php',
            type: 'POST',
            data: {
                action: 'get_category',
                id: categoryId
            },
            success: function(category) {
                $('#category_id').val(category.id);
                $('input[name="name"]').val(category.name);
                $('#categoryModal').modal('show');
            }
        });
    });

    // Reset form when modal is closed
    $('#categoryModal').on('hidden.bs.modal', function() {
        $('#categoryForm')[0].reset();
        $('#category_id').val('');
    });

    // Handle form submit
    $('#categoryForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: 'categories.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#categoryModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Delete category
    $('.delete-category').click(function() {
        const categoryId = $(this).data('category-id');

        if (!confirm('Bạn có chắc muốn xóa danh mục này?')) {
            return;
        }

        $.ajax({
            url: 'categories.php',
            type: 'POST',
            data: {
                action: 'delete_category',
                id: categoryId
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
