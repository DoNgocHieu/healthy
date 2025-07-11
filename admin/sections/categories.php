<?php
require_once __DIR__ . '/../../config/Database.php';

class CategoryAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCategories($page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;

            $offset = ($page - 1) * $perPage;

            // Đếm tổng số danh mục
            $stmt = $this->db->query('SELECT COUNT(*) as total FROM categories');
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Lấy danh sách danh mục
            $stmt = $this->db->prepare("
                SELECT c.*,
                       COALESCE((SELECT COUNT(*) FROM items WHERE TT = c.TT), 0) as item_count
                FROM categories c
                ORDER BY c.TT
                LIMIT ? OFFSET ?
            ");

            $stmt->execute([$perPage, $offset]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug query
            if (empty($categories)) {
                error_log("Debug - SQL: " . $stmt->queryString);
                error_log("Debug - Parameters: perPage=" . $perPage . ", offset=" . $offset);
            }

            return [
                'categories' => $categories,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            return [
                'categories' => [],
                'total' => 0,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => 0,
                'error' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    public function getCategory($id) {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE TT = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveCategory($data) {
        try {
            $this->db->beginTransaction();

            if (empty($data['id'])) {
                // Tạo mã TT tự động
                $stmt = $this->db->query('SELECT COUNT(*) + 1 as next_tt FROM categories');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $tt = sprintf('CT%03d', $result['next_tt']);

                // Thêm mới
                $stmt = $this->db->prepare('
                    INSERT INTO categories (TT, name, img)
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([
                    $tt,
                    $data['name'],
                    $data['img'] ?? ''
                ]);
            } else {
                // Cập nhật
                if (isset($data['img'])) {
                    $stmt = $this->db->prepare('
                        UPDATE categories
                        SET name = ?,
                            img = ?
                        WHERE TT = ?
                    ');
                    $stmt->execute([
                        $data['name'],
                        $data['img'],
                        $data['id']
                    ]);
                } else {
                    $stmt = $this->db->prepare('
                        UPDATE categories
                        SET name = ?
                        WHERE TT = ?
                    ');
                    $stmt->execute([
                        $data['name'],
                        $data['id']
                    ]);
                }
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    public function deleteCategory($id) {
        try {
            $this->db->beginTransaction();

            // Kiểm tra xem có món ăn nào thuộc danh mục này không
            $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM items WHERE TT = ?');
            $stmt->execute([$id]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($count > 0) {
                throw new Exception('Không thể xóa danh mục này vì đã có ' . $count . ' món ăn thuộc danh mục');
            }

            // Xóa danh mục
            $stmt = $this->db->prepare('DELETE FROM categories WHERE TT = ?');
            $stmt->execute([$id]);

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

// Khởi tạo CategoryAdmin
$categoryAdmin = new CategoryAdmin();

// Xử lý phân trang - sử dụng p làm tham số phân trang để tránh xung đột với page routing
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Lấy danh sách danh mục
$result = $categoryAdmin->getCategories($currentPage, $perPage);
$categories = $result['categories'];
$totalPages = $result['totalPages'];

// Debug thông tin nếu không có dữ liệu
if (empty($categories)) {
    error_log("Debug - Categories is empty. Result: " . print_r($result, true));
}

// Debug thông tin
if (empty($categories)) {
    echo '<!-- Debug: Không có dữ liệu categories -->';
    if (!empty($result['error'])) {
        echo '<!-- Debug Error: ' . htmlspecialchars($result['error']) . ' -->';
    }
}
?>

<!-- Tiêu đề và nút thêm mới -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0">Quản lý danh mục</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="bi bi-plus"></i> Thêm danh mục
    </button>
</div>

<!-- Hiển thị thông báo lỗi nếu có -->
<?php if (!empty($result['error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($result['error']); ?>
    </div>
<?php endif; ?>

<!-- Bảng danh sách danh mục -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Mã TT</th>
                <th>Hình ảnh</th>
                <th>Tên danh mục</th>
                <th>Số món ăn</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php echo htmlspecialchars($category['TT'] ?? ''); ?></td>
                <td>
                    <?php if (!empty($category['img'])): ?>
                        <img src="/healthy/<?php echo htmlspecialchars($category['img']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="height: 50px;">
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo $category['item_count']; ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-info view-category" data-category-id="<?php echo htmlspecialchars($category['TT']); ?>">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-category" data-category-id="<?php echo htmlspecialchars($category['TT']); ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
            <tr>
                <td colspan="6" class="text-center">Chưa có danh mục nào</td>
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
                <a class="page-link" href="?page=admin&section=categories&p=<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal thêm/sửa danh mục -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm/Sửa danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" enctype="multipart/form-data">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh</label>
                        <input type="file" class="form-control" name="img" accept="image/*">
                        <div id="currentImage" class="mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="saveCategory">Lưu</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript xử lý -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const categoryForm = document.getElementById('categoryForm');

    // Xử lý khi click nút sửa
    document.querySelectorAll('.view-category').forEach(button => {
        button.addEventListener('click', async function() {
            const categoryId = this.dataset.categoryId;

            // Gọi API lấy thông tin danh mục
            try {
                const response = await fetch(`/healthy/api/get_category.php?id=${categoryId}`);
                const data = await response.json();

                if (data.success) {
                    const category = data.category;
                    categoryForm.querySelector('[name="id"]').value = category.TT;
                    categoryForm.querySelector('[name="name"]').value = category.name;

                    // Hiển thị hình ảnh hiện tại nếu có
                    const currentImageDiv = document.getElementById('currentImage');
                    if (category.img) {
                        currentImageDiv.innerHTML = `
                            <img src="/healthy/${category.img}" alt="${category.name}" style="max-height: 100px;" class="img-thumbnail">
                            <p class="mt-2">Hình ảnh hiện tại</p>
                        `;
                    } else {
                        currentImageDiv.innerHTML = '';
                    }

                    categoryModal.show();
                } else {
                    alert('Không thể tải thông tin danh mục');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tải thông tin danh mục');
            }
        });
    });

    // Xử lý khi click nút xóa
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                return;
            }

            const categoryId = this.dataset.categoryId;

            try {
                const response = await fetch('/healthy/api/delete_category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: categoryId })
                });
                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Không thể xóa danh mục');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa danh mục');
            }
        });
    });

    // Xử lý khi click nút thêm mới
    document.querySelector('[data-bs-target="#categoryModal"]').addEventListener('click', function() {
        categoryForm.reset();
        categoryForm.querySelector('[name="id"]').value = '';
        document.getElementById('currentImage').innerHTML = '';
    });

    // Xử lý khi submit form
    document.getElementById('saveCategory').addEventListener('click', async function() {
        if (!categoryForm.checkValidity()) {
            categoryForm.reportValidity();
            return;
        }

        const formData = new FormData();
        formData.append('id', categoryForm.querySelector('[name="id"]').value);
        formData.append('name', categoryForm.querySelector('[name="name"]').value);

        const imageFile = categoryForm.querySelector('[name="img"]').files[0];
        if (imageFile) {
            formData.append('img', imageFile);
        }

        try {
            const response = await fetch('/healthy/api/save_category.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Không thể lưu danh mục');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi lưu danh mục');
        }
    });
});</script>
